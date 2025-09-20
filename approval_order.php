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
 *    \file       htdocs/custom/approval/approval_order.php
 *    \ingroup    approval
 *    \brief      File of class to send order invoices info
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../../main.inc.php';
require 'xmlseclibs/XMLSecurityKey.php';
require 'xmlseclibs/XMLSecurityDSig.php';
require 'xmlseclibs/XMLSecEnc.php';
require 'xmlseclibs/Utils/XPath.php';
require 'xmlseclibs/pdf_xml.modules.php';
require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class PDF extends FPDF
{
    public function Rect_one($one_value, $bottom_name, $bottom_value, $detalles, $profit, $addlavel, $addInfo, $logo)
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
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY($x + 1, 15);
        $this->Cell(10, 10, utf8_decode('G U I A   D E   R E M I S I Ó N'));
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
        $this->Image(DOL_DATA_ROOT . '/commande/temp/' . $one_value['7'] . '.png', $x - 2, 110, 90, 16);
        $x = 28;
        $y = 54;
        $this->SetFont('Arial', '', 7);
        $this->SetXY($x + 6, $y + 20);
        $this->Cell(20, 7, $one_value['1']);
        $this->SetXY($x + 10, $y + 32);
        $this->MultiCell(65, 3, utf8_decode($one_value['2']), 0, 'L', '', 0);
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
        $x = 11;
        $y = 117;
        $this->Rect(7, 133, 195, 26);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($x, $y);
        $text = utf8_decode('Identificación (Transportista):');
        $this->Cell(20, 45, $text);
        $this->SetXY($x, $y + 7);
        $text = utf8_decode('Razón Social / Nombres:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x, $y + 14);
        $text = utf8_decode('Punto de Partida:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x + 130, $y);
        $text = utf8_decode('Placa:'); //
        $this->Cell(20, 45, $text);
        $this->SetXY($x + 130, $y + 7);
        $text = utf8_decode('Fecha inicio Transporte:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x + 130, $y + 14);
        $text = utf8_decode('Fecha fin Transporte:'); //
        $this->Cell(20, 45, $text);
        $this->SetFont('Arial', '', 8);
        $this->SetXY($x + 46, $y + 21);
        $this->MultiCell(65, 3, $one_value['16'], 0, 'J', '', 0);
        $this->SetXY($x + 40, $y + 28);
        $this->MultiCell(85, 3, $one_value['17'], 0, 'J', '', 0);
        $this->SetXY($x + 29, $y + 14);
        $this->Cell(20, 45, utf8_decode($one_value['18']));
        $this->SetXY($x + 141, $y);
        $this->Cell(20, 45, $one_value['19']);
        $this->SetXY($x + 169, $y + 7);
        $this->Cell(20, 45, $one_value['20']);
        $this->SetXY($x + 164, $y + 14);
        $this->Cell(20, 45, $one_value['21']);
        $x = 11;
        $y = 145;
        $this->Rect(7, 162, 195, 125);
        for ($i = 0; $i < 9; $i++) {
            $this->SetFont('Arial', 'B', 9);
            $this->SetXY($x, $y + $i * 6);
            $text = utf8_decode($bottom_name[$i]);
            $this->Cell(20, 45, $text);
            $this->SetFont('Arial', '', 8);
            $this->SetXY($x + 52, $y + $i * 6);
            $this->Cell(20, 45, utf8_decode($bottom_value[$i]));
        }
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY(130, $y);
        $this->Cell(20, 45, utf8_decode('Fecha de Emisión:'));
        $this->SetFont('Arial', '', 9);
        $this->SetXY(160, $y);
        $this->Cell(20, 45, $one_value['22']);
        $this->SetFont('Arial', '', 7);
        $this->SetXY(20, 223);
        $header = array('Código Principal', 'Código Auxiliar', 'Descripción', 'Cantidad');
        foreach ($header as $col) {
            if ($col == 'Descripción') {
                $this->Cell($x + 80, 7, utf8_decode($col), 1, '', 'C');
            } else {
                $this->Cell($x + 15, 7, utf8_decode($col), 1, '', 'C');
            }
        }
        $i = 1;
        $y = 223;
        foreach ($detalles as $detalle) {
            $yy = $y + 7 * $i;
            $this->SetXY(20, $yy);
            foreach ($detalle as $col) {
                if (strlen($detalle['descripcion']) > 150) $py = 14;
                else $py = 7;
                if ($detalle['descripcion'] == $col) {
                    $tx = $this->GetX();
                    $ty = $this->GetY();
                    $this->SetFont('Arial', '', 5);
                    $this->Cell($x + 80, $py, '', 1, '', 'C');
                    $this->SetXY($x + 61, $this->GetY() + 1);
                    $this->MultiCell($x + 80, 3, str_replace(array("\r\n", "\n", "\r", "  ", "<br>", "<br />"), ' ', utf8_decode($col)), 0, 'C', '', 0);
                    $this->SetXY($tx + 91, $ty);
                } else {
                    $this->Cell($x + 15, $py, utf8_decode($col), 1, '', 'C');
                }
            }
            if (strlen($detalle['descripcion']) > 150) $y = $y + 7;
            $i++;
            if ($this->GetY() > 270) {
                $this->AddPage();
                $x = 11;
                $y = 3;
                $i = 1;
            }
        }
        if ($this->GetY() >= 190 || $y == 3) {
            if ($y != 3) $this->AddPage();
            $yy = 0;
        }
        $y = $yy;
        $this->Rect(7, $y + 10, 195, 50, '', 1);
        $this->SetXY(10, $y + 15);
        $this->SetFont('Arial', '', 12);
        $this->Cell(22, 6, utf8_decode('Información Adicional'), 0, '', "L");
        $yy = $y + 16;
        for ($i = 0; $i <= 4; $i++) {
            if (!empty($addInfo[$i])) {
                $yy = $yy + 7;
                $this->SetXY(10, $yy);
                $this->SetFont('Arial', 'B', 9);
                $this->MultiCell(122, 4, utf8_decode($addlavel[$i]) . ' : ', 0, "L", "", 0);
                $add = 15 + strlen(utf8_decode($addlavel[$i])) * 2.5;
                $this->SetFont('Arial', '', 9);
                $this->SetXY($add, $yy);
                $this->MultiCell(182, 4, utf8_decode($addInfo[$i]), 0, "L", "", 0);
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
$index = $_GET['index'];
$old_index = $_GET['in'];
$eid = $_GET['eid'];
$object = new Expedition($db);
$object->fetch($eid);
$db->begin();
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "order_info where rowid=" . $user->id;
$sql = $db->query($sql);
$order_info = $db->fetch_object($sql);
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "expedition LEFT JOIN " . MAIN_DB_PREFIX . "societe ON " . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "expedition.fk_soc WHERE " . MAIN_DB_PREFIX . "expedition.rowid = " . $eid;
$result = $db->query($sql);
$payment_sql = "SELECT id, code, libelle FROM " . MAIN_DB_PREFIX . "c_paiement ORDER BY id ASC ";
$payment_result = $db->query($payment_sql);
while ($row = $db->fetch_array($payment_result)) {
    $payment_name[$row['id']] = $row['libelle'];
    $payment_method[$row['id']] = $row['code'];
}
if ($db->num_rows($result) > 0) {
    $obj = $db->fetch_object($result);
    if (($obj->type != 0)) {
        $mesg = $langs->trans('SEN_3');
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    $i = '001';
    $j = 0;
    $totalConImpuestos = array();
    $detalles = array();
    $tip = "0.00";
    if (is_null($obj->invoice_number)) {
        $invoice_number = str_pad($order_info->ck_invoice_number, 9, '0', STR_PAD_LEFT); //$row['Auto_increment']
    } else {
        $invoice_number = str_pad($obj->invoice_number, 9, '0', STR_PAD_LEFT);
    }
    if (strlen($invoice_number) < 9) {
        header("Location: ../../custom/approval/order_setting.php");
        exit;
    }
    if (isset($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER))
        $sql = "SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid IN (SELECT fk_entrepot FROM " . MAIN_DB_PREFIX . "stock_mouvement where fk_origin IN (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element where fk_target = '" . $old_index . "') ORDER BY rowid DESC)";
    else
        $sql = "SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid IN ( SELECT fk_entrepot FROM " . MAIN_DB_PREFIX . "expeditiondet WHERE fk_expedition = '" . $eid . "' AND fk_entrepot >'0' )";
    $sql_ship = $db->query($sql);
    $numrows = $db->num_rows($sql_ship);
    if ($numrows != 0)
        $warehouse = $db->fetch_object($sql_ship);
    else {
        $sql = "SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot ORDER by ref ASC LIMIT 1";
        $sql_ship = $db->query($sql);
        $numrows = $db->num_rows($sql_ship);
        $warehouse = $db->fetch_object($sql_ship);
    }

    $warehouse_no = $warehouse->ref;
    $warehouse_ad = $warehouse->address;
    // print_r($warehouse);exit;
    if ($old_index) {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "facture Where rowid=" . $old_index;
        $results = $db->query($sql);
        $result = $db->fetch_object($results);
        $datef = $result->datef;
        $old_invoice = str_pad($result->warehouse, 3, '0', STR_PAD_LEFT) . "-" . str_pad($result->seller, 3, '0', STR_PAD_LEFT) . "-" . str_pad($result->invoice_number, 9, '0', STR_PAD_LEFT);
        $old_claveacceso_end = $result->claveacceso_end ? $result->claveacceso_end : ($result->claveacceso ? $result->claveacceso : null);
        if (is_null($result->invoice_number) || is_null($result->warehouse) || is_null($result->seller)) {
            $mesg = 'Factura no emitida.(' . $result->ref . ')';
            $style = 'errors';
            exitHandler($mesg, $style, 1);
        }
    } else {
        $mesg = 'Factura no emitida.';
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    foreach ($object->lines as $row) {
        $detalle = array(
            "codigoInterno" => $row->ref, // product / service reference $row['ref']
            "codigoAuxiliar" => $row->barcode,
            "descripcion" => $row->ref ? str_replace(array("\r\n", "\n", "\r"), ' ', $row->ref . '-' . $row->libelle) : str_replace(array("\r\n", "\n", "\r"), ' ', $row->description), // product / service reference
            "cantidad" => $row->qty_shipped, // QTY
        );
        $j++;
        $detalles['detalle_' . $j] = $detalle;
    }
    $Impuesto = "SELECT DISTINCT vat_src_code FROM " . MAIN_DB_PREFIX . "commandedet ORDER BY " . MAIN_DB_PREFIX . "commandedet.vat_src_code ASC";
    $Impuesto_results = $db->query($Impuesto);
    $j = 0;
    $dig = new modulo();
    $sellerid = str_pad($user->job, 3, '0', STR_PAD_LEFT);
    $profid = $conf->global->MAIN_INFO_SIREN;
    $companyaddress = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
    $companyname = $conf->global->MAIN_INFO_SOCIETE_NOM;
    if ($sellerid <= 0) {
        $mesg = $langs->trans('SEN_4');
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    if (!$profid || strlen($profid) < 13) {
        $mesg = 'Customer R.U.C error occure(' . $profid . ')';
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    $date = date('d-m-y h:i:s');
    $claveacceso = date('dmY', strtotime($obj->start_date)) . '06' . $profid . $order_info->ck_purpose . $warehouse_no . $sellerid . $invoice_number . '12345678' . '1';
    $claveacceso = $claveacceso . $dig->getMod11Dv(strval($claveacceso));
    if (strlen($claveacceso) != 49) {
        $mesg = $langs->trans('SEN_2') . " " . strlen($claveacceso) . "(" . $claveacceso . ")";
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    if (!$obj->carrier) {
        $mesg = "Seleccione un transportista.";
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "societe WHERE rowid=" . $obj->carrier;
    $results = $db->query($sql);
    $carrier = $db->fetch_object($results);
    if ($obj->identification_c_type == 4 && strlen($carrier->siren) < 13) {
        $mesg = 'Carrier R.U.C error occure(' . $carrier->siren . ')';
        $style = 'errors';
        exitHandler($mesg, $style, 1);
    }
    $ruc = array(
        '4' => 'R.U.C.',
        '5' => 'CEDULA',
        '6' => 'PASSPORTE',
        '7' => 'VENTAACONSUMIDORFINAL',
        '8' => 'IDENTIFICACIONDELEXTERIOR',
    );
    $new_obj = array(
        "infoTributaria" => array(
            "ambiente" => $order_info->ck_purpose, // need to add combo box 1 means testing and 2 production
            "tipoEmision" => '1', // need to add. It alwayes will be 1
            "razonSocial" => $companyname, // Company Name
            "nombreComercial" => $order_info->ck_alias, // Company tradename(alias)
            "ruc" => $profid, // Company Prof ID strtolower($ruc)
            "claveAcceso" => $claveacceso, // need to add and it is unique, I said you how to make it
            "codDoc" => '06', // need to add, check table 3, It means document type, for example 01 invoice
            "estab" => $warehouse_no, // Warehouse ID, It could be 001 up to 999
            "ptoEmi" => $sellerid, // * seller or user id, It could be 001 up to 999
            "secuencial" => $invoice_number, // Invoice number
            "dirMatriz" => $companyaddress, // Company address, main address
            "contribuyenteRimpe" => $order_info->ck_microenterprise == 'on' ? "CONTRIBUYENTE RÉGIMEN RIMPE" : null, // Check list, we need to add others as well
            "agenteRetencion" => $order_info->ck_agent == "" ? null : $order_info->ck_agent,
        ),
        "infoGuiaRemision" => array(
            "dirEstablecimiento" => $warehouse_ad, //companyaddress,
            "dirPartida" => $warehouse_ad, //$warehouse_ad,
            'razonSocialTransportista' => $carrier->nom,
            'tipoIdentificacionTransportista' => "0" . $obj->identification_c_type,
            'rucTransportista' => $carrier->siren == "" ? 9999999999999 : $carrier->siren,
            "contribuyenteEspecial" => $order_info->ck_taxpayer == "" ? null : $order_info->ck_taxpayer,
            "obligadoContabilidad" => $order_info->ck_keep == 'on' ? "SI" : null, // Check list
            "fechaIniTransporte" => is_null($obj->start_date) ? null : date('d/m/Y', strtotime($obj->start_date)),
            "fechaFinTransporte" => $obj->end_date ? date('d/m/Y', strtotime($obj->end_date)) : null,
            "placa" => $obj->c_name1 ? $obj->c_name1 : null, // it means credit 3 months, payment conditions
        ),
        "destinatarios" => array(
            "destinatario" => array(
                "identificacionDestinatario" => $obj->siren, // Third - party Prof ID
                'razonSocialDestinatario' => $obj->nom,
                'dirDestinatario' => $obj->address,
                'motivoTraslado' => $obj->c_name2 ? $obj->c_name2 : null,
                "contribuyenteEspecial" => $order_info->ck_taxpayer == "" ? null : $order_info->ck_taxpayer,
                'docAduaneroUnico' => $obj->c_name3,
                "codEstabDestino" => $obj->c_name4 ? $obj->c_name4 : null,
                "ruta" => $obj->c_name5 ? $obj->c_name5 : null,
                "codDocSustento" => '01', // TABLA 3
                "numDocSustento" => $old_invoice,
                "numAutDocSustento" => $old_claveacceso_end,
                "fechaEmisionDocSustento" => date('d/m/Y', strtotime($datef)),
                "detalles" => $detalles,
            ),
        ),
    );
    $xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><guiaRemision id="comprobante" version="1.0.0"></guiaRemision>');
    array_to_xml($new_obj, $xml_data);
    $xml = $xml_data->addChild('infoAdicional');
    if (!empty($obj->address)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->address);
        $xmlAdd->addAttribute('nombre', ('Dirección'));
    }
    if (!empty($obj->email)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->email);
        $xmlAdd->addAttribute('nombre', ('Email'));
    }
    if (!empty($obj->phone)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->phone);
        $xmlAdd->addAttribute('nombre', ('Teléfono'));
    }
    if (!empty($obj->c_note6)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->c_name6);
        $xmlAdd->addAttribute('nombre', ($obj->c_note6));
    }
    if (!empty($obj->c_note7)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->c_name7);
        $xmlAdd->addAttribute('nombre', ($obj->c_note7));
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
    $objDSig->addReference(
        $doc,
        XMLSecurityDSig::SHA1,
        null,
        null,
        ['Id' => $referenceID, 'Type' => "http://uri.etsi.org/01903#SignedProperties", "URI" => $referenceURI],
        $signatureinfoID
    );
    $objDSig->addReference(
        $doc,
        XMLSecurityDSig::SHA1,
        null,
        null,
        ["URI" => $certificateURI]
    );
    $objDSig->addReference(
        $doc,
        XMLSecurityDSig::SHA1,
        array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
        [],
        ["Id" => $comprobanteID, "URI" => "#comprobante"],
        []
    );
    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
    $objKey->passphrase = 'Areyouhere4me_1';
    $objKey->loadKey('Key.pem', true);
    $objDSig->sign($objKey);
    $objDSig->add509Cert(file_get_contents('Cert.pem'), true, false, null, $keyinfoID, $signatureID, $SignedPropertiesID, $DataObjectFormatID);
    $objDSig->appendSignature($doc->documentElement);
    $path = DOL_DATA_ROOT . '/expedition/sending/' . get_exdir($object->id, 2, 0, 0, $object, $object->element) . '/';
    $current = DOL_DATA_ROOT . '/expedition/sending/temp/' . $obj->ref . '_temp.xml';
    $doc->save($current);
    $sXML = simplexml_load_file($current, null, LIBXML_NOBLANKS);
    $domDocument = dom_import_simplexml($sXML)->ownerDocument;
    $domDocument->formatOutput = false;
    $domDocument->save($current, LIBXML_NOEMPTYTAG);
    $dom = new DOMDocument("1.0", "UTF-8");
    $dom->load($current);
    $xpath = new DOMXpath($dom);
    $ns = array(
        "ds" => 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',
        "esti" => 'xmlns:esti="http://uri.etsi.org/01903/v1.3.2#"',
    );
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    $Certificate = $xpath->evaluate('/guiaRemision/ds:Signature/ds:KeyInfo')->item(0)->C14N();
    $xpath->registerNamespace('etsi', 'http://uri.etsi.org/01903/v1.3.2#');
    $SignedProperties = $xpath->evaluate('/guiaRemision/ds:Signature/ds:Object/etsi:QualifyingProperties/etsi:SignedProperties')->item(0)->C14N();
    $nodes = $xpath->evaluate('/guiaRemision/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue');
    $nodes->item(0)->nodeValue = getDigest($SignedProperties);
    $nodes->item(1)->nodeValue = getDigest($Certificate);
    $nodes->item(2)->nodeValue = getDigest($comprobante);
    $SignatureNode = $xpath->evaluate('/guiaRemision/ds:Signature/ds:SignedInfo')->item(0)->C14N();
    openssl_sign($SignatureNode, $SignatureValue, file_get_contents('Key.pem'));
    $SignatureValue = base64_encode($SignatureValue);
    $nodes = $xpath->evaluate('/guiaRemision/ds:Signature/ds:SignatureValue');
    $nodes->item(0)->nodeValue = " " . trim(chunk_split($SignatureValue, 76, "\n")) . " ";
    $dom->save($current, LIBXML_NOEMPTYTAG);
    switch ($order_info->ck_purpose) {
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
    if ($order_info->ck_purpose < 3) {
        $options = array('encoding' => 'UTF-8');
        try {
            $order_info->fk_purpose == 1 ? $client = new SoapClient("https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl", $options) : $client = new SoapClient("https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl", $options);
        } catch (Exception $e) {
            $mesg = $langs->trans('WsMessage');
            $style = "warnings";
            $event = 1;
            exitHandler($mesg, $style, $event);
        }
        $order_info->ck_purpose == 1 ? $ws_approval = $obj->ws_approval_one : $ws_approval = $obj->ws_approval_thr;
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
        $objDSig->ws_log($response, $pre_value);
        print_r($response);
        $response_tmp = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes->mensaje;
        if ($ws_approval != 'RECIBIDA') {
            if ($response->RespuestaRecepcionComprobante->estado == 'RECIBIDA') {
                $order_info->ck_purpose == 1 ? $db->query("UPDATE " . MAIN_DB_PREFIX . "expedition SET ws_approval_one ='RECIBIDA' WHERE rowid=" . ((int) $eid)) : $db->query("UPDATE " . MAIN_DB_PREFIX . "expedition SET ws_approval_thr ='RECIBIDA' WHERE rowid=" . (int) $eid);
            } else {
                $response_tmp->identificador ? $res = $response_tmp->mensaje . '(' . $response_tmp->informacionAdicional . ')' : '';
                exitHandler($res, 'warnings', 0);
            }
            sleep(1);
        } else {
            $mesg = $response_tmp->mensaje . ' (' . $obj->claveacceso . ')';
            $style = 'warnings';
            exitHandler($mesg, $style, 0);
        }
        try {
            $order_info->fk_purpose == 1 ? $client = new SoapClient("https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl", $options) : $client = new SoapClient("https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl", $options);
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
        print_r($response);
        $date = new DateTime($response_tmp->fechaAutorizacion);
        $process = $date->format('Y-m-d H:i:s');
        if ($response_tmp->estado == 'AUTORIZADO') {
            $responseXML = $response_tmp->comprobante;
            $dom = new DOMDocument("1.0", "UTF - 8");
            $dom->loadXML($responseXML);
            $dom->save($path . $claveacceso . '.xml', LIBXML_NOEMPTYTAG);
            $order_info->ck_purpose == 1 ? $db->query("UPDATE " . MAIN_DB_PREFIX . "expedition SET ws_approval_two ='AUTORIZADO', ws_time = '" . $process . "',  claveacceso = '" . $claveacceso . "' WHERE rowid=" . (int) $eid) : $db->query("UPDATE " . MAIN_DB_PREFIX . "expedition SET ws_approval_fou ='AUTORIZADO', ws_time_end = '" . $process . "' ,  claveacceso_end = '" . $claveacceso . "' WHERE rowid=" . (int) $eid);
        } elseif ($response_tmp->estado == 'NO AUTORIZADO') {
            $res = $response_tmp->mensajes->mensaje->mensaje . '(' . $response_tmp->mensajes->mensaje->informacionAdicional . ')';
            exitHandler($res, 'warnings', 1);
            exit;
        } else {
            if ($response_tmp->mensajes->mensaje->identificador) $res = $response_tmp->mensajes->mensaje->mensaje . '(' . $response_tmp->mensajes->mensaje->informacionAdicional . ')';
            exitHandler($res, 'warnings', 0);
            exit;
        }
        $db->commit();
    }
    if ($order_info->ck_purpose == 3) {
        $training = $path . $claveacceso . '.xml';
        $dom->save($training, LIBXML_NOEMPTYTAG);
        $objDSig->ws_log($dom->saveXML(), $pre_value);
        $process = date('c');
    }
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->SetTitle($claveacceso);
    $pdf->SetCreator("Dolibarr");
    $pdf->SetAuthor("Wilson M.");
    $pdf->SetSubject("WSDL Invoice");
    $pdf->barcode(DOL_DATA_ROOT . "/commande/temp/", $claveacceso);
    $one_value['0'] = $companyname;
    $one_value['1'] = $companyaddress;
    $one_value['2'] = $warehouse_ad;
    $one_value['3'] = $order_info->ck_alias;
    $one_value['4'] = $order_info->ck_keep == 'on' ? "SI" : "NO";
    $one_value['5'] = $profid;
    $one_value['6'] = $warehouse_no . '-' . $sellerid . '-' . $invoice_number;
    $one_value['7'] = $claveacceso;
    $one_value['8'] = $process; //date('d / m / Y h:m:s', strtotime($process));
    $one_value['9'] = $pre_value;
    $one_value['10'] = 'NORMAL';
    $one_value['16'] = $carrier->siren;
    $one_value['17'] = $carrier->nom;
    $one_value['18'] = $warehouse_ad;
    $one_value['19'] = $obj->c_name1;
    $one_value['20'] = $obj->start_date ? date('d/m/Y', strtotime($obj->start_date)) : null; // WS certify time
    $one_value['21'] = $obj->end_date ? date('d/m/Y', strtotime($obj->end_date)) : null; // WS certify time
    $one_value['22'] = date('d/m/Y', strtotime($datef)); // WS certify time
    $one_value['23'] = $order_info->ck_microenterprise == 'on' ? "CONTRIBUYENTE RÉGIMEN RIMPE" : null; // WS certify time
    $old_invoice ? $old_invoice = 'FACTURA:    ' . $old_invoice : $old_invoice = null;
    $bottom_name = array('Comprobante de Venta:', 'Número de Autorización:', 'Motivo Traslado:', 'Destino(Punto de llegada):', 'Identificación (Destinatario):', 'Razón Social/Nombres Apellidos:', 'Documento Aduanero:', 'Código Establecimiento Destino:', 'Ruta:');
    $bottom_value = array($old_invoice, $old_claveacceso_end, $obj->c_name2, $obj->address, $obj->siren, $obj->nom, $obj->c_name3, $obj->c_name4, $obj->c_name5);
    $t = 0.00;
    $header = array('Cod.', 'Cod.', 'Cant.', utf8_decode('Descripción'), 'Precio', 'Descuento', 'Precio');
    $header1 = array('Principal', 'Auxiliar', ' ', utf8_decode('Descripción'), 'Unitario', '(%)', 'Total');
    $addlavel = array('Dirección', 'Email', 'Teléfono', $obj->c_note6, $obj->c_note7); //, $obj->c_note1, $obj->c_note2, $obj->c_note3, $obj->c_note4, $obj->c_note5
    $addInfo = array($obj->address, $obj->email, $obj->phone, $obj->c_name6, $obj->c_name7); //, $obj->c_name1, $obj->c_name2, $obj->c_name3, $obj->c_name4, $obj->c_name5
    $formago = number_format((float) $obj->total_ttc, 2, '.', '');
    $conf->global->MAIN_INFO_SOCIETE_LOGO ? $logo = DOL_DATA_ROOT . '/mycompany/logos/' . $conf->global->MAIN_INFO_SOCIETE_LOGO : $logo = 'img/logo.png';
    $pdf->AddPage();
    $pdf->Rect_one($one_value, $bottom_name, $bottom_value, $detalles, 'R.U.C.', $addlavel, $addInfo, $logo);
    $file = $path . $claveacceso . '.pdf';
    $pdf->Output($file, 'F');
    $dol_syslog = "SUCCESS " . $pre_value . ", " . $obj->ref . ", " . (int) $eid . ", " . $claveacceso . "," . $process;
    dol_syslog($dol_syslog, LOG_NOTICE);
    $invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "expedition SET invoice_number=" . ($invoice_number) . ", warehouse=" . ($warehouse_no) . ", seller=" . ($sellerid) . "   WHERE rowid=" . ((int) $eid);
    $db->query($invoice_number_sql);
    $i = $invoice_number + 1;
    if ($invoice_number == $order_info->ck_invoice_number) { //&& ($order_info->ck_purpose != 3)
        $invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "order_info SET ck_invoice_number=" . ($i) . " WHERE rowid=" . ((int) $user->id);
        $db->query($invoice_number_sql);
    }
    file_remove(DOL_DATA_ROOT . "/expedition/temp/" . $claveacceso . '.png');
    file_remove($path . $index . '_temp.xml');
    file_remove($current);
    $db->commit();
    $db->close();
    $res = $claveacceso . ' fue creado con éxito';
    exitHandler($res, 'mesgs', 0);
} else {
    $db->commit();
    $db->close();
    $res = $langs->trans('SEN_1');
    exitHandler($res, 'errors', 0);
}
function file_remove($file)
{
    if (file_exists($file)) {
        unlink($file);
    }
}
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
        } else {
            if (isset($value)) {
                if ($key != 'codigoAuxiliar') {
                    $xml_data->addChild($key, htmlspecialchars($value));
                }
            }
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
