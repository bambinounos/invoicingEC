<?php
/* Copyright (C) 2021-2024 Maxim Maximovich Isayev <isayev95117@gmail.com>
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
 *    \file       htdocs/custom/approval/approval_debit.php
 *    \ingroup    approval
 *    \brief      File of class to send debit invoices info
 */

require '../../main.inc.php';
require 'xmlseclibs/XMLSecurityKey.php';
require 'xmlseclibs/XMLSecurityDSig.php';
require 'xmlseclibs/XMLSecEnc.php';
require 'xmlseclibs/Utils/XPath.php';
require 'xmlseclibs/pdf_xml.modules.php';

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

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
        $this->Cell(20, 45, utf8_decode('OBLIGADO A LLEVAR CONTABILIDAD:'));
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
        $this->Cell(10, 10, utf8_decode('N O T A  D E  D É B I T O'));
        $this->SetXY($x + 1, 25);
        $this->SetFont('Arial', 'B', 10);
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
        $this->Image(DOL_DATA_ROOT . '/facture/temp/' . $one_value['7'] . '.png', $x - 2, 110, 90, 16);
        $x = 11;
        $y = 115;
        $this->Rect(7, 133, 195, 26);
        $this->SetFont('Arial', 'B', 9);
        $this->SetXY($x, $y);
        $text = utf8_decode('Razón Social / Nombres y Apellidos:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x, $y + 5);
        $text = utf8_decode('Fecha De Emisión:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x + 141, $y);
        $text = utf8_decode('Identificación:');
        $this->Cell(20, 45, $text);
        $this->Line($x, $y + 31, $x + 186, $y + 31);
        $this->SetXY($x, $y + 13);
        $text = utf8_decode('Comprobante que se modifica:');
        $this->Cell(20, 45, $text);
        $this->SetXY($x, $y + 18);
        $text = utf8_decode('Fecha Emisión (Comprobante a modificar):');
        $this->Cell(20, 45, $text);
        $this->SetFont('Arial', '', 8);
        $this->SetXY($x + 58, $y + 21);
        $this->MultiCell(65, 3, $one_value['16'], 0, 'J', '', 0);
        $this->SetXY($x + 35, $y + 5);
        $this->Cell(20, 45, $one_value['17']);
        $this->SetXY($x + 165, $y);
        $this->Cell(20, 45, $one_value['19']);
        $this->SetXY($x + 50, $y + 13);
        $this->Cell(20, 45, strlen($one_value['18']) == 17 ? 'FACTURA: ' . $one_value['18'] : 'Ref : ' . $one_value['18']);
        $this->SetXY($x + 70, $y + 18);
        $text = utf8_decode($one_value['20']);
        $this->Cell(20, 45, $text);
        $x = 28;
        $y = 54;
        $this->SetFont('Arial', '', 7);
        $this->SetXY($x + 6, $y + 20);
        $this->Cell(20, 7, $one_value['1']);
        $this->SetXY($x + 10, $y + 32);
        $this->MultiCell(65, 3, utf8_decode($one_value['2']), 0, 'L', '', 0);
        $this->SetXY($x + 60, $y + 40);
        $this->Cell(20, 7, $one_value['4']);
        $this->SetXY($x + 50, $y + 50);
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
        $this->Cell(10, 10, utf8_decode($one_value['10']));
    }
    public function BasicTable($header, $data, $t_data, $payment_method, $formago, $addInfo, $addlavel, $libelle)
    {
        $this->SetFont('Arial', '', 11);
        $this->SetXY(7, 162);
        $x = 22;
        $this->Cell($x + 110, 9, $header['0'], 1, '', 'C');
        $this->Cell($x + 41, 9, $header['1'], 1, '', 'C');
        $re = 8;
        $i = 0;
        $this->Ln();
        $this->SetFont('Arial', '', 9);
        $y = 171 + $re * $i;
        $this->SetXY(7, $y);
        $this->Cell($x + 110, $re, utf8_decode($data['0']), 1, '', "C");
        $this->Cell($x + 41, $re, abs($data['1']), 1, '', "C");
        $this->Ln();
        if ($this->GetY() >= 235) {
            $this->AddPage();
            $x = 0;
            $y = 0;
        }
        $this->Ln();
        $this->SetXY(10, $y + 13);
        $this->SetFont('Arial', '', 11);
        $this->Rect(7, $y + 10, 122, 30, '', 1);
        $this->Cell(22, 4, utf8_decode('Información Adicional'), 0, '', "L");
        $this->SetFont('Arial', '', 8);
        $yy = $y + 14;
        for ($i = 0; $i <= 5; $i++) {
            if (!empty($addInfo[$i])) {
                $yy = $yy + 4;
                $this->SetXY(10, $yy);
                $this->MultiCell(122, 4, utf8_decode($addlavel[$i]) . ' : ' . utf8_decode($addInfo[$i]), 0, "L", "", 0);
            }
        }
        $this->SetFont('Arial', '', 10);
        $this->SetXY(7, $yy + 17);
        $this->Cell(80, 6, 'Forma de Pago', 1, '', "C");
        $this->Cell(42, 6, 'Valor', 1, '', "C");
        $this->SetFont('Arial', '', 7);
        $this->SetXY(7, $yy + 23);
        $this->Cell(80, 6, $payment_method, 1, '', "C");
        $this->Cell(42, 6, abs($formago), 1, '', "C");
        $x = 132;
        $y = $y + 10;
        $i = 0;
        $this->SetFont('Arial', '', 7);
        foreach ($t_data as $key => $value) {
            $t = $y + 4 * $i;
            $this->SetXY($x, $t);
            $this->Cell(50, 4, $key, 1, '', "L");
            $this->Cell(20, 4, $value, 1, '', "R");
           $i++;
        }
    }
}
class modulo
{
    public function getMod11Dv($num)
    {
        $digits = str_replace(array('.', ','), array('' . ''), strrev($num));
        if (!ctype_digit($digits))
            return false;
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
$index = $_GET['index'];
if ($index <= 0 || !isset($index)) exit;
$langs->loadLangs(array('approval'));
$db->begin();
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "user_info where rowid=" . $user->id;
$sql = $db->query($sql);
$user_info = $db->fetch_object($sql);
$sql = "SELECT *, (SELECT value FROM " . MAIN_DB_PREFIX . "const WHERE name='MAIN_INFO_SIREN') as profid, (SELECT value FROM " . MAIN_DB_PREFIX . "const WHERE name='MAIN_INFO_SOCIETE_ADDRESS') as companyaddress, (SELECT value FROM " . MAIN_DB_PREFIX . "const WHERE name='MAIN_INFO_SOCIETE_NOM') as companyname FROM " . MAIN_DB_PREFIX . "facture LEFT JOIN " . MAIN_DB_PREFIX . "societe  ON (" . MAIN_DB_PREFIX . "societe.rowid = " . MAIN_DB_PREFIX . "facture.fk_soc ) LEFT JOIN " . MAIN_DB_PREFIX . "c_payment_term ON " . MAIN_DB_PREFIX . "c_payment_term.rowid = " . MAIN_DB_PREFIX . "facture.fk_cond_reglement WHERE " . MAIN_DB_PREFIX . "facture.rowid = " . $index;
$result = $db->query($sql);
$payment_sql = "SELECT id, code, libelle FROM " . MAIN_DB_PREFIX . "c_paiement Where active = 1 ORDER BY id ASC ";
$payment_result = $db->query($payment_sql);
$numrows = $db->num_rows($payment_result);
if ($numrows == 0) {
    $mesg = $langs->trans('Paymentmethod');
    $style = 'warnings';
    exitHandler($mesg, $style, 1);
}
while ($row = $db->fetch_array($payment_result)) {
    $payment_name[$row['id']] = $row['libelle'];
    $payment_method[$row['id']] = $row['code'];
}
$reason = array('0' => 'DEVOLUCIÓN DE MERCADERÍA', '1' => 'EMISIÓN DE FACTURA POR ERROR', '2' => 'DESCUENTO', '3' => 'CORRECCIÓN DE DATOS, VALORES O INFORMACIÓN', '4' => 'GASTOS POR ENVIO');
if ($db->num_rows($result) > 0) {
    $obj = $db->fetch_object($result);
    if (($obj->type != 6)) {
        $mesg = $langs->trans('SEN_3');
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
    }
    if ($obj->fk_mode_reglement <= 0) {
        $mesg = $langs->trans('Paymentmethod');
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
    }
    $i = '001';
    $j = 0;
    $totalConImpuestos = array();
    $detalles = array();
    $tip = "0.00";
    $temp = "SELECT * FROM " . MAIN_DB_PREFIX . "facture WHERE rowid = " . $obj->fk_facture_source;
    $temp = $db->query($temp);
    $temp = $db->fetch_object($temp);
    if (is_null($obj->invoice_number)) {
        $invoice_number = str_pad($user_info->fk_debit_number, 9, '0', STR_PAD_LEFT); //$row['Auto_increment']
    } elseif (is_null($user_info->fk_debit_number)) {
        $mesg = $langs->trans('SEN_6');
        $style = 'warnings';
        exitHandler($mesg, $style, 0);
        header("Location: ../../custom/approval/approval_setting.php");
    } else {
        $invoice_number = str_pad($obj->invoice_number, 9, '0', STR_PAD_LEFT);
    }
    $sql1 = "SELECT " . MAIN_DB_PREFIX . "facturedet.*, " . MAIN_DB_PREFIX . "product.ref FROM " . MAIN_DB_PREFIX . "facturedet LEFT JOIN " . MAIN_DB_PREFIX . "product ON " . MAIN_DB_PREFIX . "product.rowid = " . MAIN_DB_PREFIX . "facturedet.fk_product WHERE " . MAIN_DB_PREFIX . "facturedet.fk_facture = '" . $index . "' ORDER BY " . MAIN_DB_PREFIX . "facturedet.vat_src_code ASC";
    $result1 = $db->query($sql1);
    $warehouse_no = $warehouse_ad = $discount = null;
    while ($row = $db->fetch_array($result1)) {
        if (!isset($conf->global->STOCK_CALCULATE_ON_SHIPMENT))
            $sql_ship = $db->query("SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid IN (SELECT fk_entrepot FROM " . MAIN_DB_PREFIX . "stock_mouvement where fk_origin IN (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element where fk_target = '" . $index . "') ORDER BY rowid DESC)");
        else
            $sql_ship = $db->query("SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot WHERE rowid IN (SELECT fk_default_warehouse FROM " . MAIN_DB_PREFIX . "product where rowid IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "facturedet where fk_facture = '" . $index . "') ORDER BY rowid ASC)");
        $warehouse = $db->fetch_object($sql_ship);
        if (empty($warehouse)) {
            $sql_ship = $db->query("SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot ORDER by ref ASC LIMIT 1");
            $warehouse = $db->fetch_object($sql_ship);
        }
        $warehouse_no = $warehouse->ref;
        $warehouse_ad = $warehouse->address;
        switch ($row['vat_src_code']) {
            case 'TAX_IVA0':
                $TAX_IVA['TAX_IVA0']['0'] = 2;
                $TAX_IVA['TAX_IVA0']['1'] = 0;
                $TAX_IVA['TAX_IVA0']['2'] = 0.00;
                $TAX_IVA['TAX_IVA0']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA0']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 0;
                $vat_src_pro = 0;
                break;
            case 'TAX_IVA5':
                $TAX_IVA['TAX_IVA5']['0'] = 2;
                $TAX_IVA['TAX_IVA5']['1'] = 5;
                $TAX_IVA['TAX_IVA5']['2'] = 5.00;
                $TAX_IVA['TAX_IVA5']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA5']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 5;
                $vat_src_pro = 5;
                break;
            case 'TAX_IVA12':
                $TAX_IVA['TAX_IVA12']['0'] = 2;
                $TAX_IVA['TAX_IVA12']['1'] = 2;
                $TAX_IVA['TAX_IVA12']['2'] = 12.00;
                $TAX_IVA['TAX_IVA12']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA12']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 2;
                $vat_src_pro = 12;
                break;
            case 'TAX_IVA13':
                $TAX_IVA['TAX_IVA13']['0'] = 2;
                $TAX_IVA['TAX_IVA13']['1'] = 10;
                $TAX_IVA['TAX_IVA13']['2'] = 13.00;
                $TAX_IVA['TAX_IVA13']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA13']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 10;
                $vat_src_pro = 13;
                break;
            case 'TAX_IVA14':
                $TAX_IVA['TAX_IVA14']['0'] = 2;
                $TAX_IVA['TAX_IVA14']['1'] = 3;
                $TAX_IVA['TAX_IVA14']['2'] = 14.00;
                $TAX_IVA['TAX_IVA14']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA14']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 3;
                $vat_src_pro = 14;
                break;
            case 'TAX_IVA15':
                $TAX_IVA['TAX_IVA15']['0'] = 2;
                $TAX_IVA['TAX_IVA15']['1'] = 4;
                $TAX_IVA['TAX_IVA15']['2'] = 15.00;
                $TAX_IVA['TAX_IVA15']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA15']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 4;
                $vat_src_pro = 15;
                break;
            case 'TAX_IVANO':
                $TAX_IVA['TAX_IVANO']['0'] = 2;
                $TAX_IVA['TAX_IVANO']['1'] = 6;
                $TAX_IVA['TAX_IVANO']['2'] = 0.00;
                $TAX_IVA['TAX_IVANO']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVANO']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 6;
                $vat_src_pro = 0;
                break;
            case 'TAX_IVAE':
                $TAX_IVA['TAX_IVAE']['0'] = 2;
                $TAX_IVA['TAX_IVAE']['1'] = 7;
                $TAX_IVA['TAX_IVAE']['2'] = 0.00;
                $TAX_IVA['TAX_IVAE']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVAE']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 7;
                $vat_src_pro = 0;
                break;
            case 'TAX_DIF':
                $TAX_IVA['TAX_DIF']['0'] = 2;
                $TAX_IVA['TAX_DIF']['1'] = 8;
                $TAX_IVA['TAX_DIF']['2'] = 8.00;
                $TAX_IVA['TAX_DIF']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_DIF']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 8;
                $vat_src_pro = 8;
                break;
            default:
                $TAX_IVA['TAX_IVA0']['0'] = 2;
                $TAX_IVA['TAX_IVA0']['1'] = 0;
                $TAX_IVA['TAX_IVA0']['2'] = 0.00;
                $TAX_IVA['TAX_IVA0']['3'] += zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', ''));
                $TAX_IVA['TAX_IVA0']['4'] += zero(number_format((float) $row['multicurrency_total_tva'], 2, '.', ''));
                $vat_src_code = 0;
                $vat_src_pro = 0;
        }
        if (!is_null($row['label'])) {
            $description = substr(strip_tags($row['label']), 0, 300);
        } else
            $description = substr(strip_tags($row['description']), 0, 300);
        if(!strlen($description))
        $description = substr($row['description'], 0, 300);
        $discount += price2num($row['multicurrency_subprice'] * $row['qty'] - $row['multicurrency_total_ht']);
        $detalle = array(
            "codigoInterno" => $row['ref'], // product / service reference $row['ref']
            "codigoAuxiliar" => $row['barcode'] ? $row['barcode'] : null, // product name
            "descripcion" => str_replace(array("/[\x00-\x1F\x7F-\xFF]/", "\r\n", "\n", "\r"), ' ', $description), // product / service reference
            "cantidad" => $row['qty'], // QTY
            "precioUnitario" => number_format(abs((float) $row['subprice']), 2, '.', ''), // product price
            "descuento" => number_format((float) $row['remise_percent'], 2, '.', ''), // * discount (value) not %
            "precioTotalSinImpuesto" => zero(number_format((float) $row['multicurrency_total_ht'], 2, '.', '')), // total amount for product
            "impuestos" => array("impuesto" => array(
                "codigo" => 2, // excl. taxes tabla 17
                "codigoPorcentaje" => $vat_src_code, // taxes code, table 16
                "tarifa" => $vat_src_pro, // % taxes
                "baseImponible" => number_format(abs((float) $row['total_ht']), 2, '.', ''), // total amount for product exc. taxes
                "valor" => number_format(abs((float) $row['total_tva']), 2, '.', ''), // tax value 152.67 * 12 %
            )),
        );
        $j++;
        $detalles['detalle_' . $j] = $detalle;
    }
    $Impuesto = "SELECT DISTINCT vat_src_code FROM " . MAIN_DB_PREFIX . "facturedet ORDER BY " . MAIN_DB_PREFIX . "facturedet.vat_src_code ASC";
    $Impuesto_results = $db->query($Impuesto);
    $j = 0;
    while ($row = $db->fetch_array($Impuesto_results)) {
        if ($row['vat_src_code']) {
            if (isset($TAX_IVA[$row['vat_src_code']]['3'])) {
                $totalConImpuesto = array(
                    "codigo" => 2, // Product / Service reference
                    "codigoPorcentaje" => zero($TAX_IVA[$row['vat_src_code']]['1']), // table 17
                    "tarifa" => zero($TAX_IVA[$row['vat_src_code']]['2']), // Product / Service amount excl taxes
                    "baseImponible" => zero(number_format((float) $TAX_IVA[$row['vat_src_code']]['3'], 2, '.', '')), // Tax percent
                    "valor" => zero(number_format((float) $TAX_IVA[$row['vat_src_code']]['4'], 2, '.', '')), // Product / Service tax
                );
                $j++;
                $impuestos['impuesto_' . $j] = $totalConImpuesto;
            }
        }
    }
    $dig = new modulo();
    $sellerid = str_pad($user->job, 3, '0', STR_PAD_LEFT);
    if ($sellerid <= 0) {
        $mesg = $langs->trans('SEN_4');
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
    }
    if (!$obj->profid || strlen($obj->profid) < 13) {
        $mesg = $langs->trans('SEN_5');
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
    }
    if (is_null($user_info->fk_purpose)) {
        $mesg = 'Valor de PRUEBA/PRODUCCIÓN no configurado';
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
    }
    $claveacceso = date('dmY', strtotime($obj->datef)) . '05' . $obj->profid . $user_info->fk_purpose . $warehouse_no . $sellerid . $invoice_number . '12345678' . '1';
    $claveacceso = $claveacceso . $dig->getMod11Dv(strval($claveacceso));
    if (strlen($claveacceso) != 49) {
        $mesg = $langs->trans('SEN_2') . " " . strlen($claveacceso) . "(" . $claveacceso . ")";
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
    }
    $profid = array('4' => 'R.U.C.', '5' => 'CEDULA', '6' => 'PASSPORTE', '7' => 'VENTA A CONSUMIDOR FINAL', '8' => 'IDENTIFICACION DELEXTERIOR');
    if ($temp->invoice_number > 0) {
        $old_number = $temp->invoice_number;
    } else {
        $mesg = "Factura no emitida";
        $style = 'warnings';
        exitHandler($mesg, $style, 1);
        exit;
    }
    $old_number = ($temp->warehouse ? str_pad($temp->warehouse, 3, '0', STR_PAD_LEFT) : '000') . '-' . ($temp->seller ? str_pad($temp->seller, 3, '0', STR_PAD_LEFT) : '000') . '-' . str_pad($old_number, 9, '0', STR_PAD_LEFT); //$row['Auto_increment']
    $new_obj = array("infoTributaria" => array(
        "ambiente" => $user_info->fk_purpose, // need to add combo box 1 means testing and 2 production
        "tipoEmision" => '1', // need to add. It alwayes will be 1
        "razonSocial" => $obj->companyname, // Company Name
        "nombreComercial" => $user_info->fk_alias, // Company tradename(alias)
        "ruc" => $obj->profid, // Company Prof ID strtolower($ruc)
        "claveAcceso" => $claveacceso, // need to add and it is unique, I said you how to make it
        "codDoc" => '05', // need to add, check table 3, It means document type, for example 05 NOTA DE DÉBITO
        "estab" => $warehouse_no, // Warehouse ID, It could be 001 up to 999
        "ptoEmi" => $sellerid, // * seller or user id, It could be 001 up to 999
        "secuencial" => $invoice_number, // Invoice number
        "dirMatriz" => $obj->companyaddress, // Company address, main address
        "contribuyenteRimpe" => $user_info->fk_microenterprise == 'on' ? "CONTRIBUYENTE RÉGIMEN RIMPE" : null, // Check list, we need to add others as well
        "agenteRetencion" => $user_info->fk_agent == "" ? null : $user_info->fk_agent,
    ), "infoNotaDebito" => array(
        "fechaEmision" => date('d/m/Y', strtotime($obj->datef)), // Invoice date
        "dirEstablecimiento" => $warehouse_ad, // * Warehouse address or branch office It need do add
        "contribuyenteEspecial" => $user_info->fk_taxpayer == "" ? null : $user_info->fk_taxpayer, "tipoIdentificacionComprador" => "0" . $obj->identification_type, // Table 6
        "razonSocialComprador" => $obj->nom, // Third - party name
        "identificacionComprador" => $obj->siren, // Third - party Prof ID
        "obligadoContabilidad" => $user_info->fk_keep == 'on' ? "SI" : null, // Check list
        "codDocModificado" => '01', // check table 3,
        "numDocModificado" => $old_number, "fechaEmisionDocSustento" => date('d/m/Y', strtotime($temp->datef)), // date_valid
        "totalSinImpuestos" => abs(number_format((float) $obj->multicurrency_total_ht, 2, '.', '')), // Amount (excl taxes)
        "impuestos" => $impuestos, "valorTotal" => number_format(abs((float) $obj->multicurrency_total_ttc), 2, '.', ''), // Amount (excl taxes)
        "pagos" => array(
            "pago" => array(
                "formaPago" => $payment_method[$obj->fk_mode_reglement], // * payment code see table 24
                "total" => number_format((float) $obj->multicurrency_total_ttc, 2, '.', ''),
            ),
        ),
    ), "motivos" => array("motivo" => array(
        "razon" => $reason[$obj->reason_type], // * payment code see table 24
        "valor" => number_format((float) $obj->multicurrency_total_ht, 2, '.', ''), // invoice amount inc. taxes
    )));
    $xml_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><notaDebito id="comprobante" version="1.0.0"></notaDebito>');
    array_to_xml($new_obj, $xml_data);
    $xml = $xml_data->addChild('infoAdicional');
    if (!empty($obj->address)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->address);
        $xmlAdd->addAttribute('nombre', ('Dirección'));
    }
    if (!empty($obj->phone)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->phone);
        $xmlAdd->addAttribute('nombre', ('Dirección'));
    }    
    if (!empty($obj->email)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->email);
        $xmlAdd->addAttribute('nombre', ('Email'));
    }
    if (!empty($obj->c_note1)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->c_name1);
        $xmlAdd->addAttribute('nombre', ($obj->c_note1));
    }
    if (!empty($obj->c_note2)) {
        $xmlAdd = $xml->addChild('campoAdicional', $obj->c_name2);
        $xmlAdd->addAttribute('nombre', ($obj->c_note2));
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
    $Certificate = $xpath->evaluate('/notaDebito/ds:Signature/ds:KeyInfo')->item(0)->C14N();
    $xpath->registerNamespace('etsi', 'http://uri.etsi.org/01903/v1.3.2#');
    $SignedProperties = $xpath->evaluate('/notaDebito/ds:Signature/ds:Object/etsi:QualifyingProperties/etsi:SignedProperties')->item(0)->C14N();
    $nodes = $xpath->evaluate('/notaDebito/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue');
    $nodes->item(0)->nodeValue = getDigest($SignedProperties);
    $nodes->item(1)->nodeValue = getDigest($Certificate);
    $nodes->item(2)->nodeValue = getDigest($comprobante);
    $SignatureNode = $xpath->evaluate('/notaDebito/ds:Signature/ds:SignedInfo')->item(0)->C14N();
    openssl_sign($SignatureNode, $SignatureValue, file_get_contents('Key.pem'));
    $SignatureValue = base64_encode($SignatureValue);
    $nodes = $xpath->evaluate('/notaDebito/ds:Signature/ds:SignatureValue');
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
        $user_info->fk_purpose == 1 ? $ws_approval = $obj->ws_approval_one : $ws_approval = $obj->ws_approval_thr;
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
        $response_tmp = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes->mensaje;
        if ($ws_approval != 'RECIBIDA') {
            if ($response->RespuestaRecepcionComprobante->estado == 'RECIBIDA') {
                $user_info->fk_purpose == 1 ? $db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET ws_approval_one ='RECIBIDA' WHERE rowid=" . (int) $index) : $db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET ws_approval_thr ='RECIBIDA' WHERE rowid=" . (int) $index);
            } else {
                $response_tmp->identificador ? $res = $response_tmp->mensaje . '(' . $claveacceso . ')' : '';
                if ($response_tmp->identificador == '45') {
                    $invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "user_info SET fk_debit_number=" . ((int) $invoice_number + 1) . " WHERE rowid=" . ((int) $user->id);
                    $db->query($invoice_number_sql);
                    $db->commit();
                    $res = "Por favor inténtalo de nuevo (Error en el número de factura).-" . $invoice_number;
                    exitHandler($res, 'warnings', 1);
                }
                exitHandler($res, 'warnings', 0);
            }
            sleep(1);
        } else {
            $mesg = $response_tmp->mensaje . '-' . $claveacceso;
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
            $dom->save(DOL_DATA_ROOT . '/facture/' . $obj->ref . '/' . $claveacceso . '.xml', LIBXML_NOEMPTYTAG);
            $user_info->fk_purpose == 1 ? $db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET ws_approval_two ='AUTORIZADO', ws_time = '" . $process . "',  claveacceso = '" . $claveacceso . "' WHERE rowid=" . (int) $index) : $db->query("UPDATE " . MAIN_DB_PREFIX . "facture SET ws_approval_fou ='AUTORIZADO', ws_time_end = '" . $process . "' ,  claveacceso_end = '" . $claveacceso . "' WHERE rowid=" . (int) $index);
        } else {
            $res = $response_tmp->mensajes->mensaje->mensaje . '(' . $response_tmp->mensajes->mensaje->informacionAdicional . ')';
            if ($response_tmp->identificador == '45') {
                $invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "user_info SET fk_debit_number=" . ((int) $invoice_number + 1) . " WHERE rowid=" . ((int) $user->id);
                $db->query($invoice_number_sql);
                $db->commit();
                $res = "Por favor inténtalo de nuevo (Error en el número de factura)-" . $invoice_number;
                exitHandler($res, 'warnings', 1);
            }
            if ($response_tmp->mensajes->mensaje->mensaje) exitHandler($res, 'warnings', 1);
            exit;
        }
        $db->commit();
    }
    if ($user_info->fk_purpose == 3) {
        $training = DOL_DATA_ROOT . '/facture/' . $obj->ref . '/' . $claveacceso . '.xml';
        $dom->save($training, LIBXML_NOEMPTYTAG);
        $objDSig->ws_log($dom->saveXML(), $pre_value);
        $process = date('c');
    }
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->SetTitle($claveacceso);
    $pdf->SetCreator("Dolibarr");
    $pdf->SetAuthor("Wilson M.");
    $pdf->SetSubject("Electronic Billing");
    $pdf->barcode(DOL_DATA_ROOT . "/facture/temp/", $claveacceso);
    $one_value['0'] = $obj->companyname;
    $one_value['1'] = $obj->companyaddress;
    $one_value['2'] = $warehouse_ad;
    $one_value['3'] = $user_info->fk_alias;
    $one_value['4'] = $user_info->fk_keep == 'on' ? "SI" : null; // Check list
    $one_value['5'] = $obj->profid;
    $one_value['6'] = $warehouse_no . '-' . $sellerid . '-' . $invoice_number;
    $one_value['7'] = $claveacceso;
    $one_value['8'] = $process; //date('d / m / Y h:m:s', strtotime($process));
    $one_value['9'] = $pre_value;
    $one_value['10'] = 'NORMAL';
    $t = 0.00;
    $header = array(utf8_decode('RAZÓN DE LA MODIFICACIÓN'), utf8_decode('VALOR DE LA MODIFICACIÓN'));
    $t_data = array('SUBTOTAL 0%' => number_format($TAX_IVA['TAX_IVA0']['3'], 2, '.', ''), 'SUBTOTAL 5%' => number_format((float) $TAX_IVA['TAX_IVA5']['3'], 2, '.', ''), 'SUBTOTAL 12%' => number_format((float) $TAX_IVA['TAX_IVA12']['3'], 2, '.', ''), 'SUBTOTAL 13%' => number_format((float) $TAX_IVA['TAX_IVA13']['3'], 2, '.', ''), 'SUBTOTAL 14%' => number_format((float) $TAX_IVA['TAX_IVA14']['3'], 2, '.', ''), 'SUBTOTAL 15%' => number_format((float) $TAX_IVA['TAX_IVA15']['3'], 2, '.', ''), 'SUBTOTAL NO OBJETO IVA' => number_format($TAX_IVA['TAX_IVANO']['3'], 2, '.', ''), 'SUBTOTAL EXENTO IVA' => number_format($TAX_IVA['TAX_IVAE']['3'], 2, '.', ''), 'SUBTOTAL IVA DIFERENCIADO' => number_format((float) $TAX_IVA['TAX_DIF']['3'], 2, '.', ''), 'SUBTOTAL SIN IMPUESTOS' => number_format((float) $obj->multicurrency_total_ht, 2, '.', ''), 'TOTAL DESCUENTO' => number_format((float) $discount, 2, '.', ''), 'ICE' => number_format($t, 2, '.', ''), 'IVA 5%' => number_format((float) $TAX_IVA['TAX_IVA5']['3']*.05, 2, '.', ''), 'IVA 12%' => number_format((float) $TAX_IVA['TAX_IVA12']['3']*.12, 2, '.', ''), 'IVA 13%' => number_format((float) $TAX_IVA['TAX_IVA13']['3']*.13, 2, '.', ''), 'IVA 14%' => number_format((float) $TAX_IVA['TAX_IVA14']['3']*.14, 2, '.', ''), 'IVA 15%' => number_format((float) $TAX_IVA['TAX_IVA15']['3']*.15, 2, '.', ''), 'TOTAL DEVOLUCION IVA' => 0.00, 'IRBPNR' => number_format($t, 2, '.', ''), 'PROPINA' => number_format($tip, 2, '.', ''), 'VALOR TOTAL' => number_format((float) $obj->multicurrency_total_ttc, 2, '.', ''));
    $one_value['16'] = $obj->nom;
    $one_value['17'] = date('d/m/Y', strtotime($obj->datef)); // WS certify time
    $one_value['18'] = $old_number;
    $one_value['19'] = $obj->siren;
    $one_value['20'] = date('d/m/Y', strtotime($temp->datef)); // WS invoice certify time;
    $one_value['21'] = $reason[$obj->reason_type]; // reason_type;
    $one_value['23'] = $user_info->fk_microenterprise == 'on' ? "CONTRIBUYENTE RÉGIMEN RIMPE" : null; // WS certify time
    $addlavel = array(str_pad('Dirección', 10, ' ', STR_PAD_RIGHT), str_pad('Email', 10, ' ', STR_PAD_RIGHT), str_pad('Phone', 10, ' ', STR_PAD_RIGHT), $obj->c_note1, $obj->c_note2);
    $addInfo = array($obj->address, $obj->email, $obj->phone, $obj->c_name1, $obj->c_name2);
    $formago = number_format((float) $obj->total_ttc, 2, '.', '');
    $data = array($reason[$obj->reason_type], number_format((float) $obj->multicurrency_total_ht, 2, '.', ''));
    $conf->global->MAIN_INFO_SOCIETE_LOGO ? $logo = DOL_DATA_ROOT . '/mycompany/logos/' . $conf->global->MAIN_INFO_SOCIETE_LOGO : $logo = 'img/logo.png';
    $pdf->AddPage();
    $pdf->Rect_one($one_value, 'R.U.C.', $logo);
    $pdf->BasicTable($header, $data, $t_data, utf8_decode($payment_name[$obj->fk_mode_reglement]), $formago, $addInfo, $addlavel, $obj->libelle);
    $file = DOL_DATA_ROOT . '/facture/' . $obj->ref . '/' . $claveacceso . '.pdf';
    $pdf->Output($file, 'F');
    $dol_syslog = "SUCCESS " . $pre_value . ", " . $obj->ref . ", " . (int) $index . ", " . $claveacceso . "," . $process;
    dol_syslog($dol_syslog, LOG_NOTICE);
    $invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "facture SET invoice_number=" . ($invoice_number) . ", warehouse=" . ($warehouse_no) . ", seller=" . ($sellerid) . "   WHERE rowid=" . ((int) $index);
    $db->query($invoice_number_sql);
    $db->commit();
    $i = $invoice_number + 1;
    if ($invoice_number == $user_info->fk_debit_number) { //&& ($user_info->fk_purpose != 3)
        $invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "user_info SET fk_debit_number=" . ((int) $i) . " WHERE rowid=" . ((int) $user->id);
        $db->query($invoice_number_sql);
    }
    $db->commit();
    file_remove(DOL_DATA_ROOT . "/facture/temp/" . $claveacceso . '.png');
    file_remove(DOL_DATA_ROOT . '/facture/temp/' . $index . '_temp.xml');
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
exit;
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
        } elseif (!is_null($value)) {
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
function zero($value)
{
    return $value ? $value : 0;
}
function exitHandler($mesg, $style, $event)
{
    $_SESSION['dol_events'][$style][] = $mesg;
    print_r($mesg);
    if ($event) exit;
}
