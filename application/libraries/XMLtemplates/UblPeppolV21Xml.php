<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * UBL / Peppol BIS Billing 3.0 (UBL 2.1) generator for InvoicePlane
 * NOTE: tailored for BE (0208 KBO as default endpoint scheme when present).
 * Fixes:
 *  - PHP 8.2 dynamic properties (declare all props)
 *  - TaxTotal now aggregates all lines (no $items[0] index)
 *  - EndpointID not lost in supplier party
 *  - EN16931 / Peppol profile IDs set on root
 */
class UblPeppolV21Xml
{
    /** @var object|null */
    public $invoice = null;

    /** @var DOMDocument|null */
    public $doc = null;

    /** @var DOMElement|null */
    public $root = null;

    /** constructor flags / data **/
    protected $useBelgiumPreset = true;

    /** data injected via $params **/
    protected $items = [];
    protected $filename = '';
    protected $currencyCode = 'EUR';

    /** endpoints (electronic addresses) **/
    protected $supplierEndpointId = null;
    protected $supplierEndpointScheme = null;
    protected $customerEndpointId = null;
    protected $customerEndpointScheme = null;

    /** misc **/
    protected $paymentMeansCode = '58'; // SEPA credit transfer (as default)
    protected $defaultUnitCode = 'C62';

    public function __construct($params)
    {
        $CI = &get_instance();

        $this->invoice  = $params['invoice'] ?? null;
        $this->items    = isset($params['items']) ? (is_array($params['items']) ? $params['items'] : (array)$params['items']) : [];
        $this->filename = $params['filename'] ?? ($this->invoice->invoice_number ?? 'invoice');

        $this->currencyCode = !empty($params['currencyCode'])
            ? $params['currencyCode']
            : (function_exists('get_setting') ? get_setting('currency_code') : 'EUR');

        // Endpoints (optional but recommended)
        $this->supplierEndpointId     = $params['supplier_endpoint_id']     ?? null;
        $this->supplierEndpointScheme = $params['supplier_endpoint_scheme'] ?? null;
        $this->customerEndpointId     = $params['customer_endpoint_id']     ?? null;
        $this->customerEndpointScheme = $params['customer_endpoint_scheme'] ?? null;

        // Payment means & default unit
        $this->paymentMeansCode = $params['payment_means_code'] ?? $this->paymentMeansCode;
        $this->defaultUnitCode  = $params['default_unit_code']  ?? $this->defaultUnitCode;

        // Belgium preset (default ON)
        $this->useBelgiumPreset = array_key_exists('belgium_preset', $params) ? (bool)$params['belgium_preset'] : true;

        if ($this->useBelgiumPreset && $this->invoice) {
            // Supplier: if no scheme but we do have a BE company id (e.g. KBO), use 0208
            if (!$this->supplierEndpointScheme && !empty($this->invoice->user_company_id)) {
                $this->supplierEndpointId     = $this->supplierEndpointId ?? $this->stripNonDigits($this->invoice->user_company_id);
                $this->supplierEndpointScheme = '0208'; // BE KBO/BCE
            }
            // Customer: if BE VAT available and no endpoint set, use 9925 (VAT)
            if (!$this->customerEndpointId && !empty($this->invoice->client_vat_id)) {
                $vat = $this->normalizeVat($this->invoice->client_vat_id);
                if ($this->isBeVat($vat)) {
                    $this->customerEndpointId     = $this->stripVatPrefix($vat); // BE0123456789 -> 0123456789
                    $this->customerEndpointScheme = '9925'; // BE VAT
                }
            }
        }
    }

    public function xml(): void
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput = true;

        $this->root = $this->xmlRoot();

        // Parties
        $this->root->appendChild($this->xmlAccountingSupplierParty());
        $this->root->appendChild($this->xmlAccountingCustomerParty());

        // Payment
        $this->root->appendChild($this->xmlPaymentMeans());
        if (!empty($this->invoice->invoice_terms)) {
            $this->root->appendChild($this->xmlPaymentTerms());
        }

        // Tax total (aggregate all lines; no items[0] indexing)
        $this->root->appendChild($this->xmlTaxTotal());

        // Totals
        $this->root->appendChild($this->xmlLegalMonetaryTotal());

        // Lines
        foreach ($this->items as $index => $item) {
            $this->root->appendChild($this->xmlInvoiceLine($index + 1, $item));
        }

        $this->doc->appendChild($this->root);
        $this->doc->save(UPLOADS_TEMP_FOLDER . $this->filename . '.xml');
    }

    protected function formattedFloat($amount, $nb_decimals = 2)
    {
        return number_format((float)$amount, $nb_decimals, '.', '');
    }

    /** ================= ROOT ================= */
    protected function xmlRoot()
    {
        $node = $this->doc->createElement('Invoice');
        $node->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $node->setAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $node->setAttribute('xmlns',     'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');

        // Peppol BIS Billing 3.0 / EN16931 identifiers (Belgium expects this)
        // Ref: EN16931 & Peppol BIS 3.0 profile
        $node->appendChild($this->doc->createElement('cbc:UBLVersionID', '2.1'));
        $node->appendChild($this->doc->createElement('cbc:CustomizationID', 'urn:cen.eu:en16931:2017'));
        $node->appendChild($this->doc->createElement('cbc:ProfileID', 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0'));

        $id = (string)$this->filename;
        $issueDate = !empty($this->invoice->invoice_date_created)
            ? date('Y-m-d', strtotime($this->invoice->invoice_date_created))
            : date('Y-m-d');

        $node->appendChild($this->doc->createElement('cbc:ID', $id));
        $node->appendChild($this->doc->createElement('cbc:IssueDate', $issueDate));
        $node->appendChild($this->doc->createElement('cbc:InvoiceTypeCode', '380'));
        $node->appendChild($this->doc->createElement('cbc:DocumentCurrencyCode', (string)$this->currencyCode));

        return $node;
    }

    /** ================= Parties ================= */
    protected function xmlAccountingSupplierParty()
    {
        $node = $this->doc->createElement('cac:AccountingSupplierParty');
        $node->appendChild($this->xmlSuppParty());
        return $node;
    }

    protected function xmlSuppParty()
    {
        // Keep a single Party node (do not overwrite after adding EndpointID)
        $node = $this->doc->createElement('cac:Party');

        // EndpointID (Peppol electronic address)
        if ($this->supplierEndpointId && $this->supplierEndpointScheme) {
            $ep = $this->doc->createElement('cbc:EndpointID', (string)$this->supplierEndpointId);
            $ep->setAttribute('schemeID', (string)$this->supplierEndpointScheme); // e.g. 0208
            $node->appendChild($ep);
        }

        $node->appendChild($this->xmlSuppPartyIdentification());
        $node->appendChild($this->xmlSuppPartyName());
        $node->appendChild($this->xmlSuppPostalAddress());
        if ($contact = $this->xmlSuppContact()) {
            $node->appendChild($contact);
        }
        return $node;
    }

    protected function xmlSuppPartyIdentification()
    {
        $node = $this->doc->createElement('cac:PartyIdentification');
        $idVal = (string)($this->invoice->user_vat_id ?? '');
        $nodeID = $this->doc->createElement('cbc:ID', $idVal);
        $nodeID->setAttribute('schemeAgencyID', (string)($this->invoice->user_country ?? ''));
        $nodeID->setAttribute('schemeAgencyName', 'Example');
        $node->appendChild($nodeID);
        return $node;
    }

    protected function xmlSuppPartyName()
    {
        $node = $this->doc->createElement('cac:PartyName');
        $nodeName = $this->doc->createElement('cbc:Name', (string)($this->invoice->user_company ?? $this->invoice->user_name ?? ''));
        $node->appendChild($nodeName);
        return $node;
    }

    protected function xmlSuppPostalAddress()
    {
        $node = $this->doc->createElement('cac:PostalAddress');
        $node->appendChild($this->doc->createElement('cbc:StreetName', (string)($this->invoice->user_address_1 ?? '')));
        $node->appendChild($this->doc->createElement('cbc:CityName', (string)($this->invoice->user_city ?? '')));
        $node->appendChild($this->doc->createElement('cbc:PostalZone', (string)($this->invoice->user_zip ?? '')));
        $nodeCountry = $this->doc->createElement('cac:Country');
        $nodeCountry->appendChild($this->doc->createElement('cbc:IdentificationCode', (string)($this->invoice->user_country ?? 'BE')));
        $node->appendChild($nodeCountry);
        return $node;
    }

    protected function xmlSuppContact()
    {
        $contactName  = $this->invoice->user_invoicing_contact ?? '';
        $contactPhone = $this->invoice->user_phone ?? '';
        $contactFax   = $this->invoice->user_fax ?? '';
        $contactEmail = $this->invoice->user_email ?? '';

        if ($contactName . $contactPhone . $contactFax . $contactEmail) {
            $node = $this->doc->createElement('cac:Contact');
            if ($contactName)  { $node->appendChild($this->doc->createElement('cbc:Name', (string)$contactName)); }
            if ($contactPhone) { $node->appendChild($this->doc->createElement('cbc:Telephone', (string)$contactPhone)); }
            if ($contactFax)   { $node->appendChild($this->doc->createElement('cbc:Telefax', (string)$contactFax)); }
            if ($contactEmail) { $node->appendChild($this->doc->createElement('cbc:ElectronicMail', (string)$contactEmail)); }
            return $node;
        }
        return null;
    }

    protected function xmlAccountingCustomerParty()
    {
        $node = $this->doc->createElement('cac:AccountingCustomerParty');
        $node->appendChild($this->xmlCustParty());
        return $node;
    }

    protected function xmlCustParty()
    {
        $node = $this->doc->createElement('cac:Party');

        // Customer EndpointID if provided (useful for Peppol addressing)
        if ($this->customerEndpointId && $this->customerEndpointScheme) {
            $ep = $this->doc->createElement('cbc:EndpointID', (string)$this->customerEndpointId);
            $ep->setAttribute('schemeID', (string)$this->customerEndpointScheme);
            $node->appendChild($ep);
        }

        $node->appendChild($this->xmlCustPartyIdentification());
        $node->appendChild($this->xmlCustPartyName());
        $node->appendChild($this->xmlCustPostalAddress());
        return $node;
    }

    protected function xmlCustPartyIdentification()
    {
        $node = $this->doc->createElement('cac:PartyIdentification');
        $idVal = (string)($this->invoice->client_vat_id ?? '');
        $nodeID = $this->doc->createElement('cbc:ID', $idVal);
        $nodeID->setAttribute('schemeAgencyID', (string)($this->invoice->client_country ?? ''));
        $nodeID->setAttribute('schemeAgencyName', 'Example');
        $node->appendChild($nodeID);
        return $node;
    }

    protected function xmlCustPartyName()
    {
        $node = $this->doc->createElement('cac:PartyName');
        $nodeName = $this->doc->createElement('cbc:Name', (string)($this->invoice->client_name ?? ''));
        $node->appendChild($nodeName);
        return $node;
    }

    protected function xmlCustPostalAddress()
    {
        $node = $this->doc->createElement('cac:PostalAddress');
        $node->appendChild($this->doc->createElement('cbc:StreetName', (string)($this->invoice->client_address_1 ?? '')));
        $node->appendChild($this->doc->createElement('cbc:CityName', (string)($this->invoice->client_city ?? '')));
        $node->appendChild($this->doc->createElement('cbc:PostalZone', (string)($this->invoice->client_zip ?? '')));
        $nodeCountry = $this->doc->createElement('cac:Country');
        $nodeCountry->appendChild($this->doc->createElement('cbc:IdentificationCode', (string)($this->invoice->client_country ?? 'BE')));
        $node->appendChild($nodeCountry);
        return $node;
    }

    /** ================= Payment ================= */
    protected function xmlPaymentMeans()
    {
        $PaymentDueDate   = $this->invoice->invoice_date_due ?? null;
        $InstructionNote  = 'Invoice: #' . (string)$this->filename;

        $node   = $this->doc->createElement('cac:PaymentMeans');
        $nodePMC = $this->doc->createElement('cbc:PaymentMeansCode', (string)$this->paymentMeansCode);
        $nodePMC->setAttribute('listID', 'UN/ECE 4461');
        $nodePMC->setAttribute('listName', 'Payment Means');
        $node->appendChild($nodePMC);

        if ($PaymentDueDate) {
            $node->appendChild($this->doc->createElement('cbc:PaymentDueDate', (string)$PaymentDueDate));
        }
        if ($InstructionNote) {
            $node->appendChild($this->doc->createElement('cbc:InstructionNote', (string)$InstructionNote));
        }

        $node->appendChild($this->xmlPFAccount());
        return $node;
    }

    protected function xmlPFAccount()
    {
        $BankRekIBAN = (string)($this->invoice->user_iban ?? '');
        $BankRekBIC  = (string)($this->invoice->user_bic ?? '');

        $node  = $this->doc->createElement('cac:PayeeFinancialAccount');
        $nodeID = $this->doc->createElement('cbc:ID', $BankRekIBAN);
        $nodeID->setAttribute('schemeName', 'IBAN');
        $node->appendChild($nodeID);

        if ($BankRekBIC) {
            $nodeFIBranch     = $this->doc->createElement('cac:FinancialInstitutionBranch');
            $nodeFInstitution = $this->doc->createElement('cac:FinancialInstitution');
            $nodeFIBranch->appendChild($nodeFInstitution);
            $nodeFInstitutionID = $this->doc->createElement('cbc:ID', $BankRekBIC);
            $nodeFInstitutionID->setAttribute('schemeName', 'BIC');
            $nodeFInstitution->appendChild($nodeFInstitutionID);
            $node->appendChild($nodeFIBranch);
        }
        return $node;
    }

    protected function xmlPaymentTerms()
    {
        $node = $this->doc->createElement('cac:PaymentTerms');
        if (!empty($this->invoice->invoice_date_due)) {
            $date = date_create($this->invoice->invoice_date_due);
            $PaymentTerms = 'Pay before: ' . date_format($date, 'd/m/Y');
            $nodePayTerms = $this->doc->createElement('cbc:Note', (string)$PaymentTerms);
            $node->appendChild($nodePayTerms);
        }
        return $node;
    }

    /** ================= Tax totals ================= */
    protected function xmlTaxTotal()
    {
        $node = $this->doc->createElement('cac:TaxTotal');

        // Use invoice total tax as header tax amount
        $totalTax = (float)($this->invoice->invoice_item_tax_total ?? 0.0);
        $node->appendChild($this->currencyElement('cbc:TaxAmount', $totalTax));

        // Add TaxSubtotal per line (simple approach); could be grouped by rate if desired
        foreach ((array)$this->items as $it) {
            $percent  = (float)($it->item_tax_rate_percent ?? 0);
            $subtotal = (float)($it->item_subtotal ?? 0);
            $node->appendChild($this->xmlTaxSubtotal($percent, $subtotal));
        }
        return $node;
    }

    protected function xmlTaxSubtotal($percent, $subtotal)
    {
        $taxamount = (float)$subtotal * (float)$percent / 100.0;
        $node = $this->doc->createElement('cac:TaxSubtotal');
        $node->appendChild($this->currencyElement('cbc:TaxableAmount', $subtotal));
        $node->appendChild($this->currencyElement('cbc:TaxAmount', $taxamount));
        $node->appendChild($this->doc->createElement('cbc:Percent', $this->formattedFloat($percent, 2)));

        // Minimal VAT code (EN16931 expects a TaxCategory/TaxScheme, omitted in your original)
        $taxCategory = $this->doc->createElement('cac:TaxCategory');
        $taxScheme   = $this->doc->createElement('cac:TaxScheme');
        $taxScheme->appendChild($this->doc->createElement('cbc:ID', 'VAT'));
        $taxCategory->appendChild($taxScheme);
        $node->appendChild($taxCategory);

        return $node;
    }

    /** ================= Totals ================= */
    protected function xmlLegalMonetaryTotal()
    {
        $invoice_total       = (float)($this->invoice->invoice_total ?? 0);
        $invoice_item_tax    = (float)($this->invoice->invoice_item_tax_total ?? 0);
        $invoice_subtotal    = (float)($this->invoice->invoice_item_subtotal ?? ($invoice_total - $invoice_item_tax));
        $taxExclusiveAmount  = $invoice_total - $invoice_item_tax;
        $payable             = (float)($this->invoice->invoice_balance ?? $invoice_total);

        $node = $this->doc->createElement('cac:LegalMonetaryTotal');
        $node->appendChild($this->currencyElement('cbc:LineExtensionAmount', $invoice_subtotal));
        $node->appendChild($this->currencyElement('cbc:TaxExclusiveAmount', $taxExclusiveAmount));
        $node->appendChild($this->currencyElement('cbc:TaxInclusiveAmount', $invoice_total));
        $node->appendChild($this->currencyElement('cbc:PayableAmount', $payable));
        return $node;
    }

    /** ================= Lines ================= */
    protected function xmlInvoiceLine($lineNumber, $item)
    {
        $qty      = (float)($item->item_quantity ?? 0);
        $subtotal = (float)($item->item_subtotal ?? 0);
        $price    = (float)($item->item_price ?? 0);
        $taxTot   = (float)($item->item_tax_total ?? 0);
        $percent  = (float)($item->item_tax_rate_percent ?? 0);
        $name     = (string)($item->item_name ?? '');

        $node = $this->doc->createElement('cac:InvoiceLine');
        $node->appendChild($this->doc->createElement('cbc:ID', (string)$lineNumber));

        $q = $this->doc->createElement('cbc:InvoicedQuantity', $this->formattedFloat($qty, 2));
        $q->setAttribute('unitCode', $this->defaultUnitCode); // Peppol expects unitCode
        $node->appendChild($q);

        $node->appendChild($this->currencyElement('cbc:LineExtensionAmount', $subtotal));

        $nodeTaxTotal = $this->doc->createElement('cac:TaxTotal');
        $nodeTaxTotal->appendChild($this->currencyElement('cbc:TaxAmount', $taxTot));
        $nodeTaxTotal->appendChild($this->xmlTaxSubtotal($percent, $subtotal));
        $node->appendChild($nodeTaxTotal);

        $nodeItem = $this->doc->createElement('cac:Item');
        $nodeItem->appendChild($this->doc->createElement('cbc:Name', $name));
        $node->appendChild($nodeItem);

        $nodePrice = $this->doc->createElement('cac:Price');
        $nodePrice->appendChild($this->currencyElement('cbc:PriceAmount', $price));
        $node->appendChild($nodePrice);

        return $node;
    }

    /** ================= Helpers ================= */
    protected function currencyElement($name, $amount, $nb_decimals = 2)
    {
        $el = $this->doc->createElement($name, $this->formattedFloat($amount, $nb_decimals));
        $el->setAttribute('currencyID', (string)$this->currencyCode);
        return $el;
    }

    protected function normalizeVat($vat)
    {
        return strtoupper(preg_replace('/\s+/', '', (string)$vat));
    }

    protected function isBeVat($vat)
    {
        if (strpos($vat, 'BE') === 0) return true;
        if (!empty($this->invoice->client_country) && strtoupper($this->invoice->client_country) === 'BE') {
            return true;
        }
        return false;
    }

    protected function stripVatPrefix($vat)
    {
        return preg_replace('/^BE/i', '', (string)$vat);
    }

    protected function stripNonDigits($s)
    {
        return preg_replace('/\D+/', '', (string)$s);
    }
}
