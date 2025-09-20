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
 *    \file       htdocs/custom/approval/approval_xml.php
 *    \ingroup    approval
 *    \brief      File of class to create order from xml
 */
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
if (!$conf->approval->enabled || !isset($_SESSION['idmenu'])) header("Location: ../../index.php");
$langs->loadLangs(array('approval'));
$dolibarr_main_prod = 1;
set_exception_handler('exceptionHandler');
$claveacceso        = GETPOST('claveacceso', 'alpha');
$dir_temp           = $conf->fournisseur->commande->dir_temp;
is_dir($dir_temp) ? '' : mkdir($dir_temp);
$newfile            = $dir_temp . '/' . strtotime(date('c')) . '.xml';
if (empty($claveacceso)) {
    $file_OK = is_uploaded_file($_FILES['addedfile']['tmp_name']);
    if (isset($_FILES['addedfile']['tmp_name']) && trim($_FILES['addedfile']['tmp_name'])) {
        $result = dol_move_uploaded_file($_FILES['addedfile']['tmp_name'], $newfile, 1, 0, $_FILES['addedfile']['error']);
        if (!$result > 0) {
            setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            $mesg = 'No se pudo cargar el archivo';
            exitHandler($newfile, 'errors', 1);
        }
    }
} else {
    $options = array('encoding' => 'UTF-8');
    try {
        substr($claveacceso, 23, 1) == '1' ? $client = new SoapClient("https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl", $options) : $client = new SoapClient("https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl", $options);
    } catch (Exception $e) {
        $mesg = $langs->trans('WsMessage');
        $style = "warnings";
        $event = 1;
        exitHandler($mesg, $style, $event);
    }
    $xml    = array("claveAccesoComprobante" => $claveacceso);
    try {
        $response    = $client->autorizacionComprobante($xml);
    } catch (Exception $e) {
        $mesg = $langs->trans('WsMessage');
        $style = "warnings";
        $event = 1;
        exitHandler($mesg, $style, $event);
    }
    $responseXML = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante;
    if ($responseXML) {
        $xml = new DOMDocument("1.0", "UTF - 8");
        $xml->loadXML($responseXML);
        $xml->save($newfile, LIBXML_NOEMPTYTAG);
    } else {
        $mesg = "No se pueden recibir datos del servicio web del SRI. Utilice archivos XML.";
        $pre_value = "XML";
        ws_log($mesg, $pre_value);
        exitHandler($mesg, 'errors', 1);
    }
}
$tempxml = simplexml_load_file($newfile);
if ($tempxml->infoFactura) {
    $xml = simplexml_load_file($newfile);
} else {
    $xml = new DOMDocument();
    $xml->loadXml($tempxml->comprobante);
    $xml->save($newfile, LIBXML_NOEMPTYTAG);
    $xml = simplexml_load_file($newfile);
}
if (!$xml->infoFactura) {
    $mesg = "El documento XML especificado es incorrecto.";
    $pre_value = "XML";
    ws_log($mesg, $pre_value);
    exitHandler($mesg, 'errors', 1);
}
$fk_nom         = $xml->infoTributaria->razonSocial;
$fk_ruc         = $xml->infoTributaria->ruc;
$claveAcceso    = substr($xml->infoTributaria->claveAcceso, 9, 1);
$claveacces        = $xml->infoTributaria->claveAcceso;
$totalDescuento = $xml->infoFactura->totalDescuento;
$formaPago      = $xml->infoFactura->pagos->pago->formaPago;
$xml->infoFactura->moneda = 'DOLAR' ? $multicurrency_code = "USD" : $multicurrency_code = '';
$estab              = $xml->infoTributaria->estab;
$ptoEmi             = $xml->infoTributaria->ptoEmi;
$secuencial         = $xml->infoTributaria->secuencial;
$ref_supplier       = $estab . '-' . $ptoEmi . '-' . $secuencial;
$date = new DateTime(date('c'));
$date_creation      = $date->format('Y-m-d H:i:s');
$date_temp          = explode('/', $xml->infoFactura->fechaEmision);
$totalSinImpuestos  = $xml->infoFactura->totalSinImpuestos;
$importeTotal       = $xml->infoFactura->importeTotal;
$date_commande      = date('Y-m-d', strtotime($date_temp['2'] . '-' . $date_temp['1'] . '-' . $date_temp['0']));
$object             = new CommandeFournisseur($db);
$soc                = new Societe($db);
$db->begin();
$sql                = "SELECT rowid, nom FROM " . MAIN_DB_PREFIX . "societe WHERE siren='" . $fk_ruc . "'";
$results            = $db->query($sql);
$row                = $db->fetch_object($results);
if ($db->num_rows($results) == 0) {
    $mesg = $fk_ruc . '  no es un VENDEDOR registrado';
    $pre_value = "XML";
    ws_log($mesg, $pre_value);
    exitHandler($mesg, 'errors', 1);
    exit;
}
$rowid              = $row->rowid;
if ($claveAcceso != 1 && $claveAcceso != 5) {
    $mesg = 'Las facturas corrientes no son facturas convertibles.(' . $xml->infoTributaria->claveAcceso . ')';
    $pre_value = "XML";
    ws_log($mesg, $pre_value);
    exitHandler($mesg, 'errors', 1);
    exit;
}
if ($xml->infoFactura->identificacionComprador != $conf->global->MAIN_INFO_SIREN) {
    $mesg = 'No es una factura de empresa.(' . $xml->infoFactura->identificacionComprador . ')';
    $pre_value = "XML";
    ws_log($mesg, $pre_value);
    exitHandler($mesg, 'errors', 1);
    exit;
}
$sql                = "SELECT ref_supplier FROM " . MAIN_DB_PREFIX . "commande_fournisseur WHERE ref_supplier='" . $ref_supplier . "'";
$results            = $db->query($sql);
if ($db->num_rows($results) > 0) {
    $mesg = $ref_supplier . ' ya está registrado';
    $pre_value = "XML";
    ws_log($mesg, $pre_value);
    exitHandler($mesg, 'errors', 1);
    exit;
}
$ref     = getNextValue();
$sql     = "INSERT INTO " . MAIN_DB_PREFIX . "commande_fournisseur(ref,entity,ref_supplier, date_creation,
fk_soc,source,fk_user_author,fk_user_valid,fk_user_approve,model_pdf,fk_cond_reglement,fk_mode_reglement,
multicurrency_total_ht,multicurrency_total_ttc,multicurrency_total_tva,total_ht,total_ttc,
total_tva,multicurrency_code,fk_input_method,fk_incoterms, claveacceso)
VALUES ('$ref','1','$ref_supplier','$date_creation',$rowid,'0',1,null,null,'muscadet',1,'$formaPago',
$totalSinImpuestos,$importeTotal, " . (floatval($importeTotal) - floatval($totalSinImpuestos)) . ",
$totalSinImpuestos,$importeTotal, " . (floatval($importeTotal) - floatval($totalSinImpuestos)) . ",'$multicurrency_code',0,0,'$claveacces')";
$results = $db->query($sql);
$sql     = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur WHERE ref='" . $ref . "'";
$results = $db->query($sql);
if ($db->num_rows($results) == 0) {
    $mesg = 'No se pudo encontrar' . $ref;
    $pre_value = "XML";
    ws_log($mesg, $pre_value);
    exitHandler($mesg, 'errors', 1);
    exit;
}
$row     = $db->fetch_object($results);
$rowid   = $row->rowid;
$db->commit();
$i = 1;
foreach ($xml->detalles->detalle as $detalle) {
    $descuento = round(100 - $detalle->precioTotalSinImpuesto / $detalle->precioUnitario / $detalle->cantidad * 100, 2);
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product WHERE ref = '" . $detalle->codigoPrincipal . "'";
    $pro_res = $db->query($sql);
    $pro_tmp = $db->fetch_object($pro_res);
    if ($pro_tmp->rowid) {
        $pro_id = $pro_tmp->rowid;
        $tmps = $db->query("SELECT reel FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_product=" . $pro_tmp->rowid);
        $tms = $db->fetch_object($tmps);
        if (!$tms->rowid) {
            $db->query("INSERT INTO " . MAIN_DB_PREFIX . "product_stock (reel, fk_product, fk_entrepot) VALUES (0, " . $pro_tmp->rowid . ", 1 )");
            $tmps = $db->query("SELECT reel FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_product=" . $pro_tmp->rowid);
            $tms = $db->fetch_object($tmps);
        }
        $tm = $tms->reel + $detalle->cantidad;
        // $db->query("UPDATE " . MAIN_DB_PREFIX . "product_stock SET reel=" . $tm . " WHERE fk_product=" . $pro_tmp->rowid);
    } else {
        $rows = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "order_info WHERE rowid = " . $user->id);
        $row = $db->fetch_object($rows);
        $rows = explode(",", $row->ck_prefixmark);
        foreach ($rows as $row) {
            $real_refs = explode($row, $detalle->codigoPrincipal);
            foreach ($real_refs as $real_ref) {
                $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product WHERE ref = '" . $real_ref . "'";
                $pro_res = $db->query($sql);
                $pro_tmp = $db->fetch_object($pro_res);
                if ($pro_tmp->rowid) {
                    $pro_id = $pro_tmp->rowid;
                    $tmps = $db->query("SELECT reel FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_product=" . $pro_tmp->rowid);
                    $tms = $db->fetch_object($tmps);
                    $tm = $tms->reel + $detalle->cantidad;
                    // $db->query("UPDATE " . MAIN_DB_PREFIX . "product_stock SET reel=" . $tm . " WHERE fk_product=" . $pro_tmp->rowid);
                    goto for_exit;
                }
            }
        }
        $pro_id = 'null';
    }
    for_exit:
    $deta = $detalle->impuestos->impuesto->baseImponible;
    $detb = $detalle->impuestos->impuesto->valor;

    $sum = floatval($deta) + floatval($detb);
    $sql       = "INSERT INTO " . MAIN_DB_PREFIX . "commande_fournisseurdet(fk_commande, fk_product, ref, description,vat_src_code, tva_tx, qty,remise_percent,subprice,total_ht,total_tva,total_ttc,multicurrency_code,multicurrency_subprice,multicurrency_total_ht,multicurrency_total_tva,multicurrency_total_ttc,rang,localtax1_type,localtax2_type) VALUES
	(" . $rowid . "," . $pro_id . ",'" . $detalle->codigoPrincipal . "','" . $detalle->descripcion . "', 'TAX_IVA" . intval($detalle->impuestos->impuesto->tarifa) . "'," . $detalle->impuestos->impuesto->tarifa . "," . $detalle->cantidad . "," . $descuento . "," . $detalle->precioUnitario . "," . $detalle->precioTotalSinImpuesto . "," . $detalle->impuestos->impuesto->valor . "," . $sum . ",'" . $multicurrency_code . "'," . $detalle->precioUnitario . "," . $detalle->precioTotalSinImpuesto . "," . $detalle->impuestos->impuesto->valor . "," . $sum . "," . $i . ", 0, 0)";
    $results   = $db->query($sql);
    $i++;
}
$object->fetch($rowid);
$langs->load("approval");
$object->fetch_projet();
$db->commit();
$db->close();
$response = $xml->infoTributaria->claveAcceso;
$pre_value = "XML";
ws_log($response, $pre_value);
unlink($newfile);
$mesg = $claveacceso . "Convertido con éxito " . $ref; //substr($claveacceso, 23, 1);
exitHandler($mesg, 'mesgs', 0);
header("Location: ../../fourn/commande/card.php?id=" . $rowid . "");
exit;
function getNextValue()
{
    global $db, $conf;
    $pos = 8;
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur ORDER BY rowid DESC LIMIT 1";
    $resql = $db->query($sql);
    $re = $db->fetch_object($resql);
    $a = $re->rowid + 1; //;
    return '(PROV' . $a . ')';
}
function exitHandler($mesg, $style, $event)
{
    $_SESSION['dol_events'][$style][] = $mesg;
    print_r($mesg);
    if ($event) {
        header("Location: ../../fourn/commande/card.php?action=create");
        exit;
    }
}
function p($a)
{
    print_r($a);
    print_r("\n");
}
function exceptionHandler($exception)
{
    $mesg = "El documento XML especificado es incorrecto.";
    exitHandler($mesg, 'errors', 1);
}
function ws_log($log_msg, $msg)
{
    global $user;
    $log_filename = "log";
    if (!file_exists($log_filename)) {
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename . '/' . date('Y-m') . '.log';
    $str = date('d/m/Y H:m:s') . ' ' . $msg . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . $user->login . ' ' . str_replace(array("stdClass Object", "\r\n", "\n", "\r", "[", "]", "  "), '', print_r($log_msg, true));
    file_put_contents($log_file_data, $str . "\n", FILE_APPEND);
}
