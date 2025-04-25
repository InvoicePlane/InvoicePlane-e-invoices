<?php
defined('BASEPATH') || exit('No direct script access allowed');
/*
 * ZUGFeRD v2.3.2 (EN 16931) : https://ecosio.com/en/peppol-and-xml-document-validator/
 */
 $xml_setting = [
    'full-name'   => 'ZUGFeRD v2.3 - confort', // Adjust like : 'ZUGFeRD v2.3 - EN 16931' (if you need)
    'countrycode' => 'DE',
    'embedXML'    => true,
    'XMLname'     => 'factur-x.xml', // The name of file embedded in PDF (xrechnung.xml or zugferd-invoice.xml ...)
    'generator'   => 'Facturxv10', // Use the libraries/XMLtemplates/Facturxv10Xml.php
];
