<?php
defined('BASEPATH') || exit('No direct script access allowed');
/*
 * CIUS-PT UBL Invoice 2.1.1 :
 * https://ecosio.com/en/peppol-and-xml-document-validator/
 * https://svc.feap.gov.pt/Doc.Client/public/CIUSvalidation/PT?language=pt
 * [explain](https://helpcenter.phcgo.net/PT/sug/ptxview.aspx?stamp=dg5eeb7efg4f6e6b16758g)
 * * [BR-CL-25]-Endpoint identifier scheme identifier MUST belong to the CEF EAS code list* : 0002 0007 0009 0037 0060 0088 0096 0097 0106 0130 0135 0142 0147 0151 0170 0183 0184 0188 0190 0191 0192 0193 0194 0195 0196 0198 0199 0200 0201 0202 0203 0204 0205 0208 0209 0210 0211 0212 0213 0215 0216 9901 9906 9907 9910 9913 9914 9915 9918 9919 9920 9922 9923 9924 9925 9926 9927 9928 9929 9930 9931 9932 9933 9934 9935 9936 9937 9938 9939 9940 9941 9942 9943 9944 9945 9946 9947 9948 9949 9950 9951 9952 9953 9955 9957 AN AQ AS AU EM
 * * CEF (Connecting Europe Facility) EAS (Electronic Address Scheme) code for EndpointID > schemeID : https://ec.europa.eu/digital-building-blocks/sites/display/DIGITAL/Code+lists
 * 9946 PEPPOL EAS CODE For Portugal VAT number
 * Note:
 *  Need in user 'Bank' field to prefix the value with the following '#REFERENCE@ATMPAYMENT#' or '#REFERENCE@DUCPAYMENT#' and suffice with '#'.
 *  like : #REFERENCE@ATMPAYMENT#!#123456789#
 *  OR empty to be valid
 */
$xml_setting = [
    'full-name'   => 'CIUS-PT eSPap-UBL 2.1.1 - ' . trans('vat_id'), // Adjust like : 'CIUS-PT eSPap-UBL 2.1.1 - EAS 9946' (if you need)
    'countrycode' => 'PT',
    'embedXML'    => false,
    'XMLname'     => '', // Must be empty when not embedded in PDF
    'generator'   => 'Ublv24', // Use the libraries/XMLtemplates/Ublv24Xml.php
    // Options in Ublv24 generator
    'options'     => [
        // [DT-CIUS-PT-022]-The BT-24 only allows the following value:
        'CustomizationID'     => 'urn:cen.eu:en16931:2017#compliant#urn:feap.gov.pt:CIUS-PT:2.1.1',
        'ProfileID'           => 'urn:www:espap:pt:profiles:profile1:ver1.0',
        'BuyerReference'      => true,
        'Delivery'            => true,
        // (exists(@schemeID) and not(matches(@schemeID,'^(.1 20)$')))
        // [DT-CIUS-PT-047.2]-The BT-49 identification scheme identifier does not meet the defined format: alphanumeric with size between 1 and 20.
        // /ubl:Invoice[1]/cac:AccountingCustomerParty[1]/cac:Party[1]/cbc:EndpointID[1] schemeID="`client_eas_code`"
        'client_eas_code'     => '9946', // *EAS code for EndpointID > schemeID : Adjust with what you need
        // /ubl:Invoice[1]/cac:AccountingSupplierParty[1]/cac:Party[1]/cbc:EndpointID[1] schemeID="`user_eas_code`"
        'user_eas_code'       => '9946', // *EAS code for EndpointID > schemeID : Adjust with what you need
        // Adjust with what you need (vat_id or tax_code) : Note same for user & client
        'EndpointID'          => 'vat_id',
        'PartyIdentification' => false, // or '' or 0 or null
        'PartyLegalEntity'    => ['CompanyID' => 'vat_id', 'SchemeID' => false],
    ],
];
