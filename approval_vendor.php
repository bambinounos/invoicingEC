<?php
/* Copyright (C) 2021-2024 Maxim Maximovich Isaev <isayev95117@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/custom/approval/approval_vendor.php
 *    \ingroup    approval
 *    \brief      File of class to send vendor invoices info
 */
require '../../main.inc.php';
require 'xmlseclibs/XMLSecurityKey.php';
require 'xmlseclibs/XMLSecurityDSig.php';
require 'xmlseclibs/XMLSecEnc.php';
require 'xmlseclibs/Utils/XPath.php';
require 'xmlseclibs/pdf_xml.modules.php';
require DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

error_reporting(E_ALL);
ini_set('display_errors', '1');

class PDF extends FPDF
{
    public function Rect_one($one_value, $profit, $logo)
    {
        $this->SetAutoPageBreak(1, 1);
        $this->Image($logo, 7, 13, 101, 31);
        $this->SetFont('Arial', 'B', 10);
        $this->RoundedRect(7, 48, 101, 82, 3, '1234');
        $y = 35;
        $this->SetXY(11, $y);
        $this->Cell(20, 45, utf8_decode($one_value['0'])); //'Razón Social:'
        $this->SetXY(11, $y + 10);
        $this->Cell(20, 45, utf8_decode($one_value['3'])); //'Dir. Matriz:'
        $this->SetXY(11, $y + 20);
        $this->Cell(20, 45, 'Dir. Matriz:');
        $this->SetXY(11, $y + 30);
        $this->Cell(20, 45, 'Dir. Sucursal:');
        $this->SetXY(11, $y + 40);
        $this->Cell(20, 45, 'OBLIGADO A LLEVAR CONTABILIDAD:');
        $this->SetXY(11, $y + 50);
        $text = utf8_decode('CONTRIBUYENTE RÉGIMEN RIMPE');
        if ($one_value['23']) {
            $this->Cell(20, 45, $text);
        }
        $x = 113;
        $this->SetFont('Arial', 'B', 14);
        $this->RoundedRect(110, 12, 92, 118, 3, '1234');
        $this->SetXY($x + 1, 35);
        $this->Cell(10, 10, utf8_decode($profit) . ":");
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY($x + 1, 15);
        $this->Cell(10, 10, utf8_decode('COMPROBANTE DE RETENCIÓN'));
        $this->SetXY($x + 1, 25);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(10, 10, 'No.');
        $this->SetXY($x + 1, 45);
        $text = utf8_decode('NÚMERO DE AUTORIZACIÓN:');
        $this->Cell(10, 10, $text);
        $this->SetXY($x + 1, 65);
        $text = utf8_decode('FECHA Y HORA DE AUTORIZACIÓN:');
        $this->Cell(10, 10, $text);
        $this->SetXY($x + 1, 80);
        $this->Cell(10, 10, 'AMBIENTE:');
        $this->SetXY($x + 1, 90);
        $text = utf8_decode('EMISIÓN:');
        $this->Cell(10, 10, $text);
        $this->SetXY($x + 1, 100);
        $this->Cell(10, 10, 'CLAVE DE ACCESO');
        $this->Image(DOL_DATA_ROOT . "/fournisseur/temp/" . $one_value['7'] . '.png', $x - 2, 110, 90, 16);
        $x = 11;
        $y = 117;
        $this->Rect(7, 133, 195, 19);
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY($x, $y);
        $text = utf8_decode('Razón Social / Nombres y Apellidos:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x, $y + 7);
        $text = utf8_decode('Fecha Emisión:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x + 130, $y);
        $text = utf8_decode('Identificación:');
        $this->Cell(20, 45, $text);
        $this->SetFont('Arial', '', 8);
        $this->SetXY($x + 65, $y + 21);
        $this->MultiCell(65, 3, $one_value['16'], 0, 'J', '', 0);
        $this->SetXY($x + 32, $y + 7);
        $this->Cell(20, 45, utf8_decode($one_value['18']));
        $this->SetXY($x + 160, $y);
        $this->Cell(20, 45, $one_value['17']);
        $x = 28;
        $y = 54;
        $this->SetFont('Arial', '', 9);
        $this->SetXY($x + 6, $y + 20);
        $this->Cell(20, 7, $one_value['1']);
        $this->SetXY($x + 10, $y + 30);
        $this->Cell(50, 7, $one_value['2']);
        $this->SetXY($x + 53, $y + 40);
        $this->Cell(20, 7, $one_value['4']);
        $x = 115;
        $this->SetFont('Arial', '', 14);
        $this->SetXY($x + strlen($profit) * 4, 35);
        $this->Cell(10, 10, $one_value['5']);
        $this->SetFont('Arial', '', 10);
        $this->SetXY($x + 8, 25);
        $this->Cell(10, 10, $one_value['6']);
        $this->SetXY($x + 1, 54);
        $this->MultiCell(84, 5, $one_value['7']);
        $this->SetXY($x + 30, 72);
        $this->Cell(53, 10, $one_value['8'], 0, 0, "R");
        $this->SetXY($x + 24, 80);
        $this->Cell(10, 10, utf8_decode($one_value['9']));
        $this->SetXY($x + 20, 90);
        $this->Cell(10, 10, $one_value['10']);
    }
    // Simple table
    public function BasicTable($header, $header1, $data, $addInfo, $addlavel)
    {
        $doc_type = array(
            '01' => 'FACTURA', '03' => 'LIQUIDACIÓN DE C., D. & S.', //LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS
            '04' => 'NOTA DE CRÉDITO', '05' => 'NOTA DE DÉBITO', '06' => 'GUÍA DE REMISIÓN', '07' => 'COMPROBANTE DE RETENCIÓN',
        );
        $this->SetFont('Arial', 'B', 7);
        $this->SetXY(7, 155);
        $x = 22;
        foreach ($header as $col) {
            if ($col == utf8_decode('Comprobante')) {
                $this->Cell($x + 15, 9, '', 1, '', 'C');
            } elseif ($col == utf8_decode('Número')) {
                $this->Cell($x + 4, 9, '', 1, '', 'C');
            } else {
                $this->Cell($x, 9, '', 1, '', 'C');
            }
        }
        $this->SetXY(7, 152);
        foreach ($header as $col) {
            if ($col == utf8_decode('Comprobante')) {
                $this->Cell($x + 15, 12, $col, 0, '', 'C');
            } elseif ($col == utf8_decode('Número')) {
                $this->Cell($x + 4, 12, $col, 0, '', 'C');
            } else {
                $this->Cell($x, 12, $col, 0, '', 'C');
            }
        }
        $this->SetXY(7, 155);
        foreach ($header1 as $col) {
            if ($col == '-') {
                $this->Cell($x + 10, 12, '', 0, '', 'C');
            } else {
                $this->Cell($x, 12, $col, 0, '', 'C');
            }
        }
        $re = 7;
        $i = 0;
        $this->SetFont('Arial', '', 6);
        foreach ($data as $item) {
            $y = 164 + $re * $i;
            $this->SetXY(7, $y);
            if ($item['0']) {
                $this->Cell($x + 15, $re, utf8_decode($doc_type[$item['j']]), 1, '', 'C');
            }
            if ($item['1']) {
                $this->Cell($x + 4, $re, $item['g'], 1, '', 'C');
            }
            $this->Cell($x, $re, $item['h'], 1, '', "C");
            $this->Cell($x, $re, $item['i'], 1, '', "C");
            $this->Cell($x, $re, $item['d'], 1, '', "C");
            $this->Cell($x, $re, $item['b'] == '1' ? 'RENTA' : 'IVA', 1, '', "C");
            $this->Cell($x, $re, $item['e'], 1, '', "C");
            $this->Cell($x, $re, $item['f'], 1, '', "C");
            $this->Ln();
            $i++;
        }
        $temp = $this->GetY();
        if ($this->GetY() >= 235) {
            $this->AddPage();
            $x = 0;
            $y = 0;
        }
        $y = $y - 2;
        $this->Ln();
        $this->SetXY(10, $y + 15);
        $this->SetFont('Arial', '', 12);
        $this->Rect(7, $y + 13, 195, 33, '', 1);
        $this->Cell(22, 6, utf8_decode('Información Adicional'), 0, '', "L");
        $this->SetFont('Arial', '', 7);
        $yy = $y + 22;
        for ($i = 0; $i <= count($addlavel); $i++) {
            if (!empty($addInfo[$i])) {
                $this->SetXY(10, $yy);
                $this->MultiCell(185, 3, utf8_decode($addlavel[$i]) . ' : ' . utf8_decode($addInfo[$i]), 0, "L", "", 0);
                $yy = $yy + 4;
            }
        }
    }
}
class modulo
{
    public function getMod11Dv($num)
    {
        $digits = str_replace(array('.', ','), array('' . ''), strrev($num));
        if (!ctype_digit($digits)) {
            return false;
        }
        $sum = 0;
        $factor = 2;
        for ($i = 0; $i < strlen($digits); $i++) {
            $sum += substr($digits, $i, 1) * $factor;
            $factor == 7 ? $factor = 2 : $factor++;
        }
        $dv = 11 - ($sum % 11);
        if ($dv == 10) {
            return 1;
        }
        if ($dv == 11) {
            return 0;
        }
        return $dv;
    }
}
if (!$conf->approval->enabled || !isset($_SESSION['idmenu'])) {
    header("Location: ../../index.php");
}
$langs->loadLangs(array('approval'));
$index = GETPOST('index', 'int') ? GETPOST('index', 'int') : 0;
if ($index <= 0 || !isset($index)) exit;
$object = new FactureFournisseur($db);
$object->fetch($index);
$path = DOL_DATA_ROOT . '/fournisseur/facture/' . get_exdir($object->id, 2, 0, 0, $object, $object->element) . $object->ref . '/';
$db->begin();
$warehouse_row = $db->query("SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot WHERE " . MAIN_DB_PREFIX . "entrepot.ref = '" . str_pad($object->warehouse, 3, '0', STR_PAD_LEFT) . "'");
$warehouse = $db->fetch_object($warehouse_row);
$warehouse_no = $warehouse->ref;
$warehouse_ad = $warehouse->address;

$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "user_info where rowid=" . $user->id;
$sql = $db->query($sql);
$user_info = $db->fetch_object($sql);
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "vendor WHERE id=" . $index;
$result = $db->query($sql);
if ($object->id > 0) {
    $object->fetch_thirdparty();
}

if ($db->num_rows($result) > 0) {
    if (($object->ws_approval_two == 'AUTORIZADO' || $object->ws_approval_fou == 'AUTORIZADO') && ($user_info->fk_purpose == 1 || $user_info->fk_purpose == 2)) {
        $mesg = 'La factura ya ha sido emitida.(' . $object->ws_time . ') => ' . $object->claveacceso;
        $style = 'errors';
        exitHandler($mesg, $style, 1);
        exit;
    }
    $i = '001';
    $j = 0;
    $impuestos = array();
    if (is_null($object->invoice_number)) {
        $mesg = 'No se puede especificar un número de factura.';
        $style = 'errors';
        exitHandler($mesg, $style, 1);        
        exit;
    }
    $invoice_number = str_pad($object->invoice_number, 9, '0', STR_PAD_LEFT);
    $sellerid = str_pad($object->seller, 3, '0', STR_PAD_LEFT);
    $j = 0;
    while ($row = $db->fetch_array($result)) {
        $impuesto = array(
            "codigo" => $row['b'], // Product / Service reference
            "codigoRetencion" => $row['a'], // table 17
            "baseImponible" => $row['d'], // Tax percent
            "porcentajeRetener" => $row['e'], // Product / Service amount excl taxes
            "valorRetenido" => $row['f'], // Product / Service tax
            "codDocSustento" => $row['j'], // Product / Service tax type
            "numDocSustento" => str_replace(array("-"), '', $row['g']), // Product / Service tax
            "fechaEmisionDocSustento" => date('d/m/Y', strtotime($row['h'])), // Product / Service tax
        );
        $j++;
        $impuestos['impuesto_' . $j] = $impuesto;
        $detail[] = $row;
    }
    $claveacceso = $object->claveacceso;
    if (strlen($claveacceso) != 49) {
        $mesg = $langs->trans('SEN_2') . " " . strlen($claveacceso) . "(" . $claveacceso . ")";
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    $profid = array('4' => 'R.U.C.', '5' => 'CEDULA', '6' => 'PASSPORTE', '7' => 'VENTA A CONSUMIDOR FINAL', '8' => 'IDENTIFICACION DELEXTERIOR');
    if ($object->identification_type == "4") {
        $ruc = 'ruc';
    } else {
        $ruc = $profid[$object->identification_type];
    }
    $new_obj = array("infoTributaria" => array(
        "ambiente" => $user_info->fk_purpose, // need to add combo box 1 means testing and 2 production
        "tipoEmision" => '1', // need to add. It alwayes will be 1
        "razonSocial" => $conf->global->MAIN_INFO_SOCIETE_NOM, // Company Name
        "nombreComercial" => $user_info->fk_alias, // Company tradename(alias)
        strtolower($ruc) => $conf->global->MAIN_INFO_SIREN, // Company Prof ID strtolower($ruc)
        "claveAcceso" => $object->claveacceso, // need to add and it is unique, I said you how to make it
        "codDoc" => '07', // need to add, check table 3, It means document type, for example 01 invoice
        "estab" => str_pad($object->warehouse, 3, '0', STR_PAD_LEFT), // Warehouse ID, It could be 001 up to 999
        "ptoEmi" => str_pad($object->seller, 3, '0', STR_PAD_LEFT), // * seller or user id, It could be 001 up to 999
        "secuencial" => $invoice_number, // Invoice number
        "dirMatriz" => $conf->global->MAIN_INFO_SOCIETE_ADDRESS, // Company address, main address
        "contribuyenteRimpe" => $user_info->fk_microenterprise == 'on' ? "CONTRIBUYENTE RÉGIMEN RIMPE" : null, // Check list, we need to add others as well
        "agenteRetencion" => $user_info->fk_agent == "" ? null : $user_info->fk_agent,
    ), "infoCompRetencion" => array(
        "fechaEmision" => date('d/m/Y', strtotime($object->date_done)), // Invoice date
        "dirEstablecimiento" => $warehouse_ad, // * Warehouse address or branch office It need do add
        // "contribuyenteEspecial" => $user_info->fk_taxpayer == "" ? NULL : $user_info->fk_taxpayer,
        "obligadoContabilidad" => $user_info->fk_keep == 'on' ? "SI" : null, // Check list
        "tipoIdentificacionSujetoRetenido" => "0" . $object->identification_type, "razonSocialSujetoRetenido" => $object->thirdparty->name, // Third - party name
        "identificacionSujetoRetenido" => $object->thirdparty->idprof1, // Third - party Prof ID
        "periodoFiscal" => date('m/Y', $object->date), // Third - party address
    ), "impuestos" => $impuestos);
    $xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><comprobanteRetencion id="comprobante" version="1.0.0"></comprobanteRetencion>');
    // function call to convert array to xml
    array_to_xml($new_obj, $xml_data);
    //saving generated xml file;
    $xml = $xml_data->addChild('infoAdicional');
    if (!empty($object->thirdparty->address)) {
        $xmlAdd = $xml->addChild('campoAdicional', $object->thirdparty->address);
        $xmlAdd->addAttribute('nombre', ('Dirección'));
    }
    if (!empty($object->thirdparty->phone)) {
        $xmlAdd = $xml->addChild('campoAdicional', $object->thirdparty->phone);
        $xmlAdd->addAttribute('nombre', ('Teléfono'));
    }
    if (!empty($object->thirdparty->email)) {
        $xmlAdd = $xml->addChild('campoAdicional', $object->thirdparty->email);
        $xmlAdd->addAttribute('nombre', ('Email'));
    }
    if (!empty($object->f_name1)) {
        $xmlAdd = $xml->addChild('campoAdicional', $object->f_note1);
        $xmlAdd->addAttribute('nombre', $object->f_name1);
    }
    if (!empty($object->f_name2)) {
        $xmlAdd = $xml->addChild('campoAdicional', $object->f_note2);
        $xmlAdd->addAttribute('nombre', $object->f_name2);
    }
    $signatureID = "Signature" . randomId();
    $signatureinfoID = "Signature-SignedInfo" . randomId();
    $referenceID = "SignedPropertiesID" . randomId();
    $comprobanteID = "Reference-ID-" . randomId();
    $signaturevalueID = "SignatureValue" . randomId();
    $keyinfoID = "Certificate" . randomId();
    $certificateURI = "#" . $keyinfoID;
    $SignedPropertiesID = $signatureID . "-" . "SignedProperties" . randomId();
    $referenceURI = "#" . $SignedPropertiesID;
    $DataObjectFormatID = "#" . $comprobanteID;
    $etsi = "http://uri.etsi.org/01903/v1.3.2#";
    $doc = new DOMDocument("1.0", "UTF-8");
    $doc->loadXML($xml_data->saveXML());
    $comprobante = $doc->C14N();
    $objDSig = new XMLSecurityDSig();
    $objDSig->sigNode->setAttribute('xmlns:etsi', $etsi);
    $objDSig->sigNode->setAttribute('Id', $signatureID);
    $objDSig->setCanonicalMethod(XMLSecurityDSig::C14N);
    $objDSig->addReference($doc, XMLSecurityDSig::SHA1, null, null, ['Id' => $referenceID, 'Type' => "http://uri.etsi.org/01903#SignedProperties", "URI" => $referenceURI], $signatureinfoID);
    $objDSig->addReference($doc, XMLSecurityDSig::SHA1, null, null, ["URI" => $certificateURI]);
    $objDSig->addReference($doc, XMLSecurityDSig::SHA1, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'), [], ["Id" => $comprobanteID, "URI" => "#comprobante"], []);
    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
    $objKey->passphrase = 'Areyouhere4me_1';
    $objKey->loadKey('Key.pem', true);
    $objDSig->sign($objKey);
    $objDSig->add509Cert(file_get_contents('Cert.pem'), true, false, null, $keyinfoID, $signatureID, $SignedPropertiesID, $DataObjectFormatID);
    $objDSig->appendSignature($doc->documentElement);
    $current = DOL_DATA_ROOT . '/facture/temp/' . $index . '_temp.xml';
    $doc->save($current);
    $sXML = simplexml_load_file($current, null, LIBXML_NOBLANKS);
    $domDocument = dom_import_simplexml($sXML)->ownerDocument;
    $domDocument->formatOutput = false;
    $domDocument->save($current, LIBXML_NOEMPTYTAG);
    $dom = new DOMDocument("1.0", "UTF-8");
    $dom->load($current);
    $xpath = new DOMXpath($dom);
    $ns = array("ds" => 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"', "esti" => 'xmlns:esti="http://uri.etsi.org/01903/v1.3.2#"');
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    $Certificate = $xpath->evaluate('/comprobanteRetencion/ds:Signature/ds:KeyInfo')->item(0)->C14N();
    $xpath->registerNamespace('etsi', 'http://uri.etsi.org/01903/v1.3.2#');
    $SignedProperties = $xpath->evaluate('/comprobanteRetencion/ds:Signature/ds:Object/etsi:QualifyingProperties/etsi:SignedProperties')->item(0)->C14N();
    $nodes = $xpath->evaluate('/comprobanteRetencion/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue');
    $nodes->item(0)->nodeValue = getDigest($SignedProperties);
    $nodes->item(1)->nodeValue = getDigest($Certificate);
    $nodes->item(2)->nodeValue = getDigest($comprobante);
    $SignatureNode = $xpath->evaluate('/comprobanteRetencion/ds:Signature/ds:SignedInfo')->item(0)->C14N();
    openssl_sign($SignatureNode, $SignatureValue, file_get_contents('Key.pem'));
    $SignatureValue = base64_encode($SignatureValue);
    $nodes = $xpath->evaluate('/comprobanteRetencion/ds:Signature/ds:SignatureValue');
    $nodes->item(0)->nodeValue = " " . trim(chunk_split($SignatureValue, 76, "\n")) . " ";
    $dom->save($current, LIBXML_NOEMPTYTAG);
    switch ($user_info->fk_purpose) {
        case 1:
            $pre_value = 'PRUEBAS';
            break;
        case 2:
            $pre_value = 'PRODUCCIÓN';
            break;
        case 3:
            $pre_value = 'EXPERIMENTACIÓN';
            break;
        default:
    }
    if ($user_info->fk_purpose < 3) {
        $options = array('encoding' => 'UTF-8');
        try {
            $user_info->fk_purpose == 1 ? $client = new SoapClient("https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl", $options) : $client = new SoapClient("https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl", $options);
        } catch (Exception $e) {
            $mesg = $langs->trans('WsMessage');
            $style = "warnings";
            $event = 1;
            exitHandler($mesg, $style, $event);
        }
        $user_info->fk_purpose == 1 ? $ws_approval = $object->ws_approval_one : $ws_approval = $object->ws_approval_thr;
        $xml_t = file_get_contents($current);
        $xml = array("xml" => $xml_t);
        try {
            $response = $client->validarComprobante($xml);
        } catch (Exception $e) {
            $mesg = $langs->trans('WsMessage');
            $style = "warnings";
            $event = 1;
            exitHandler($mesg, $style, $event);
        }
        $db->query("UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET modify = 'disabled' WHERE rowid=" . (int) $index);
    	$db->commit();
        if ($object->ws_approval_one != 'RECIBIDA') {
            $objDSig->ws_log($response, $pre_value);
            $response_tmp = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes->mensaje;
            if ($response->RespuestaRecepcionComprobante->estado == 'RECIBIDA') {
                $user_info->fk_purpose == 1 ? $db->query("UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET ws_approval_one ='RECIBIDA' WHERE rowid=" . (int) $index) : $db->query("UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET ws_approval_thr ='RECIBIDA' WHERE rowid=" . (int) $index);
            } else {
                if (isset($response_tmp->informacionAdicional)) {
                    $res = $response_tmp->informacionAdicional;
                    exitHandler($res, 'errors', 0);
                } else {
                    if ($response_tmp->identificador) {
                    $res = $response_tmp->mensaje;
                    exitHandler($res, 'errors', 0);
                    }
                }
            }
            sleep(1);
        } else {
            $mesg = $response_tmp->mensaje . ' (' . $claveacceso . ')';
            $style = 'warnings';
            exitHandler($mesg, $style, 0);
        }
        try {
            $user_info->fk_purpose == 1 ? $client = new SoapClient("https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl", $options) : $client = new SoapClient("https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl", $options);
        } catch (Exception $e) {
            $mesg = $langs->trans('WsMessage');
            $style = "warnings";
            $event = 1;
            exitHandler($mesg, $style, $event);
        }
        $xml = array("claveAccesoComprobante" => $claveacceso);
        try {
            $response = $client->autorizacionComprobante($xml);
        } catch (Exception $e) {
            $mesg = $langs->trans('WsMessage');
            $style = "warnings";
            $event = 1;
            exitHandler($mesg, $style, $event);
        }
        $objDSig->ws_log($response, $pre_value);
        $response_tmp = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion;
        $date = new DateTime($response_tmp->fechaAutorizacion);
        $process = $date->format('Y-m-d H:i:s');
        if ($response_tmp->estado == 'AUTORIZADO') {
            $responseXML = $response_tmp->comprobante;
            $dom = new DOMDocument("1.0", "UTF - 8");
            $dom->loadXML($responseXML);
            $dom->save($path . '/' . $claveacceso . '.xml', LIBXML_NOEMPTYTAG);
            $user_info->fk_purpose == 1 ? $db->query("UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET ws_approval_two ='AUTORIZADO', ws_time = '" . $process . "', modify = 'disabled', claveacceso = '" . $claveacceso . "' WHERE rowid=" . (int) $index) : $db->query("UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET ws_approval_fou ='AUTORIZADO', ws_time_end = '" . $process . "', modify = 'disabled',  claveacceso_end = '" . $claveacceso . "' WHERE rowid=" . (int) $index);
        } else {
            $res = $response_tmp->mensajes->mensaje->mensaje . '(' . $response_tmp->mensajes->mensaje->informacionAdicional . ')';
            if ($response_tmp->mensajes->mensaje->mensaje) {
                exitHandler($res, 'errors', 1);
            }
            exit;
        }
        $db->commit();
    }
    if ($user_info->fk_purpose == 3) {
        $training = $path . '/' . $claveacceso . '.xml';
        $dom->save($training, LIBXML_NOEMPTYTAG);
        $objDSig->ws_log($dom->saveXML(), $pre_value);
        $process = date('c');
    }
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->SetTitle($claveacceso);
    $pdf->SetCreator("Dolibarr");
    $pdf->SetAuthor("Wilson M.");
    $pdf->SetSubject("Electronic Billing");
    $pdf->barcode(DOL_DATA_ROOT . '/fournisseur/temp/', $claveacceso);
    $one_value['0'] = $conf->global->MAIN_INFO_SOCIETE_NOM;
    $one_value['1'] = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
    $one_value['2'] = $warehouse_ad;
    $one_value['3'] = $user_info->fk_alias;
    $one_value['4'] = $user_info->fk_keep == 'on' ? "SI" : "NO";
    $one_value['5'] = $conf->global->MAIN_INFO_SIREN;
    $one_value['6'] = $warehouse_no . '-' . $sellerid . '-' . $invoice_number;
    $one_value['7'] = $claveacceso;
    $one_value['8'] = $process; //date('d / m / Y h:m:s', strtotime($process));
    $one_value['9'] = $pre_value;
    $one_value['10'] = 'NORMAL';
    // Column headings
    $t = 0.00;
    $header = array('Comprobante', utf8_decode('Número'), 'Fecha', 'Ejercicio', 'Base', 'IMPUESTO', 'Porcentaje', 'Valor');
    $header1 = array('-', '-', utf8_decode('Emisión'), 'Fiscal', 'Imponible', '', utf8_decode('Retención'), 'Retenido');
    $one_value['16'] = $object->thirdparty->name;
    $one_value['17'] = $object->thirdparty->idprof1;
    $one_value['18'] = date('d/m/Y', strtotime($object->date_done)); // WS certify time
    $one_value['23'] = $user_info->fk_microenterprise == 'on' ? "CONTRIBUYENTE RÉGIMEN RIMPE" : null; // WS certify time
    $addlavel = array(str_pad('Dirección', 10, ' ', STR_PAD_RIGHT), str_pad('Email', 10, ' ', STR_PAD_RIGHT), str_pad('Teléfono', 10, ' ', STR_PAD_RIGHT), str_pad($object->f_name1, 10, ' ', STR_PAD_RIGHT), str_pad($object->f_name2, 10, ' ', STR_PAD_RIGHT));
    $addInfo = array($object->thirdparty->address, $object->thirdparty->email, $object->thirdparty->phone, $object->f_note1, $object->f_note2);
    $conf->global->MAIN_INFO_SOCIETE_LOGO ? $logo = DOL_DATA_ROOT . '/mycompany/logos/' . $conf->global->MAIN_INFO_SOCIETE_LOGO : $logo = 'img/logo.png';
    $pdf->AddPage();
    $pdf->Rect_one($one_value, $profid[$object->identification_type], $logo);
    $pdf->BasicTable($header, $header1, $detail, $addInfo, $addlavel);
    $file = $path . '/' . $claveacceso . '.pdf';
    $pdf->Output($file, 'F');
    $dol_syslog = "SUCCESS " . $pre_value . ", " . $object->ref . ", " . (int) $index . ", " . $claveacceso . "," . $process;
    dol_syslog($dol_syslog, LOG_NOTICE);
    file_remove(DOL_DATA_ROOT . "/fournisseur/temp/" . $claveacceso . '.png');
    file_remove($path . '/' . $index . '_temp.xml');
    file_remove($current);
    $db->commit();
    $db->close();
    $res = $claveacceso . ' fue creado con éxito';
    exitHandler($res, 'mesgs', 1);
} else {
    $db->commit();
    $db->close();
    $res = $langs->trans('SEN_1');
    exitHandler($res, 'warnings', 1);
}
function getSignature($payload, $privateKey, $pretty = true)
{
    openssl_sign($payload, $signature, $privateKey);
    return getDigest($signature);
}
function file_remove($file)
{
    if (file_exists($file)) {
        unlink($file);
    }
}
function getNamespaces()
{
    $xmlns = array();
    $xmlns[] = 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"';
    $xmlns[] = 'xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#"';
    return $xmlns;
}
// function defination to convert array to xml
function array_to_xml($data, &$xml_data)
{
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (strpos($key, '_') > 0) {
                $fixValue = substr($key, 0, strpos($key, '_'));
            } else {
                $fixValue = $key;
            }
            $subnode = $xml_data->addChild($fixValue);
            array_to_xml($value, $subnode);
        } elseif (!is_null($value)) {
            // if ($value != 'Noitem' || $value == 0) $xml_data->addChild($key, htmlspecialchars($value));
            $xml_data->addChild($key, htmlspecialchars($value));
        }
    }
}
function getDigest($input)
{
    return base64_encode(sha1($input, true));
}
function randomId()
{
    return rand(100000, 999999);
}
function checkInt($val)
{
    switch ($val) {
        case 0:
            return 0;
            break;
        case 12:
            return 2;
            break;
        case 14:
            return 3;
            break;
        default:
            return 2;
    }
    return;
}
function exceptionHandler($exception)
{
    if ($exception->faultcode = 'WSDL') {
        $msg = "Error del servidor WSDL";
    } else {
        switch ($exception->getMessage()) {
            case "Class 'SoapClient' not found":
                $msg = 'No se puede comunicar con el servidor WS.';
                break;
            default:
                $msg = $exception->getMessage();
        }
    }
    print_r($msg);
    exit;
}
function zero($value)
{
    return $value ? $value : 0;
}
function exitHandler($mesg, $style, $event)
{
    $_SESSION['dol_events'][$style][] = $mesg;
    if ($event) {
        exit;
    }
}
