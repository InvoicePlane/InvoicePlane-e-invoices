<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Helper metadata voor InvoicePlane - Peppol BIS Billing 3.0 (UBL 2.1)
 *
 * - 'full-name'  : Label zoals zichtbaar in de template drop-down
 * - 'countrycode': Landcode voor weergave (hier BE)
 * - 'embedXML'   : Peppol gebruikt losse UBL XML -> FALSE
 * - 'XMLname'    : Bestandsnaam voor de te genereren UBL-factuur
 * - 'generator'  : Basisnaam van de generatorklasse (zonder 'Xml' en zonder '.php')
 *                  => verwijst naar class UblPeppolV21Xml in UblPeppolV21Xml.php
 */

$xml_setting = [
    'full-name'   => 'Peppol BIS Billing 3.0 (UBL 2.1)',
    'countrycode' => 'BE',
    'embedXML'    => false,
    'XMLname'     => 'Peppol-BIS3-Invoice.xml',
    'generator'   => 'UblPeppolV21',
];