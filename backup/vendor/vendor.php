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
 *      \file       htdocs/fourn/facture/vendor.php
 *      \ingroup    facture
 *      \brief      Fiche de vendor sur une facture fournisseur
 */
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

$langs->loadLangs(array("bills", "companies", "approval"));

// GETPOST
$id = GETPOST('facid', 'int');
$type = (GETPOST('type', 'int') ? GETPOST('type', 'int') : 1);
$identification_type = GETPOST('identification_type', 'int');
$date_done = dol_mktime(12, 0, 0, GETPOST('domonth', 'int'), GETPOST('doday', 'int'), GETPOST('doyear', 'int'));
$claveacceso = GETPOST('claveacceso', 'alpha');
$nro = GETPOST('nro', 'alpha');
$tax = GETPOST('tax', 'alpha');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$comprovante = GETPOST('comprovante', 'alpha');
$rowid = GETPOST('rowid', 'int') ? GETPOST('rowid', 'int') : null;
$imporable = GETPOST('imporable', 'alpha');
$retain = GETPOST('retain', 'alpha');
$tax_text = GETPOST('tax_text', 'alpha');
$retent = GETPOST('retent', 'alpha');
$f_name1 = GETPOST('f_name1', 'alpha');
$f_name2 = GETPOST('f_name2', 'alpha');
$f_note1 = GETPOST('f_note1', 'alpha');
$f_note2 = GETPOST('f_note2', 'alpha');
$tax_type = (GETPOST('tax_type', 'alpha') ? GETPOST('tax_type', 'alpha') : '01');
$file = GETPOST('file', 'alpha');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$object = new FactureFournisseur($db);

if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret < 0) dol_print_error($db, $object->error);
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) dol_print_error($db, $object->error);
}

$permissionnote = $user->rights->fournisseur->facture->creer;	// Used by the include of actions_setnotes.inc.php

/*
 * Actions
 */
include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php';	// Must be include, not includ_once
$usercancreate = $user->rights->commande->creer;
$db->begin();

$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "vendor where id=" . $object->id;
$rows = $db->query($sql);
if ($type == 1)
	$imporable = $object->multicurrency_total_ht;
else
	$imporable = $object->multicurrency_total_tva;
if (!$identification_type && $object->identification_type) $identification_type = $object->identification_type;
if ($action == 'setlabel' && $user->rights->fournisseur->facture->creer) {
	$object->label = $_POST['label'];
	$result = $object->update($user);
	if ($result < 0) dol_print_error($db);
} elseif ($action == 'add') {
	$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "vendor ORDER BY rowid DESC LIMIT 1";
	$rows = $db->query($sql);
	$numrows = $db->fetch_object($rows);
	$day = date('Y-m-d', ($object->date));
	$tax_text = explode(' - ', $tax_text);
	$sql = "INSERT INTO " . MAIN_DB_PREFIX . "vendor(rowid, a,b,c,d,e,f,g,h,i,j,id) VALUES ($numrows->rowid+1,'" . $tax_text['0'] . "','$type','" . $tax_text['1'] . "','$imporable','$retent','$retain','$comprovante','$day','" . date('m', $object->date) . "','$tax_type',$object->id)";
	$result = $db->query($sql);
	// print_r($sql);
	if ($result < 0) {
		dol_print_error($db, $object->error);
		print_r($object->error);
	}
	if ($identification_type) $sql = "identification_type=" . $identification_type;
	$sql .= ", f_name1='" . $f_name1 . "', f_note1='" . $f_note1 . "'";
	$sql .= ", f_name2='" . $f_name2 . "', f_note2='" . $f_note2 . "'";
	$sql = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET " . $sql . " WHERE rowid=" . ((int) $object->id);
	$result = $db->query($sql);
	if ($result < 0) {
		dol_print_error($db, $object->error);
		print_r($object->error);
	}
	$db->commit();
	header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
} elseif ($action == 'remove') {
	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "vendor WHERE rowid=" . $rowid;
	$rows = $db->query($sql);
	$db->commit();
	header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
} elseif ($action == 'remove_file' && $user->rights->fournisseur->facture->creer) {
	$file = $conf->fournisseur->facture->dir_output . '/' . $file;
	if (file_exists($file)) {
		if (!unlink($file)) // For triggers
		{
			$object->error = 'ErrorFailToDeleteFile';
			$error++;
		}
	}
	header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
} elseif ($confirm == "yes") {
	$sql = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET modify = '' WHERE rowid=" . $object->id;
	$result = $db->query($sql);
	if ($result < 0) {
		dol_print_error($db, $object->error);
		print_r($object->error);
	}
	header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
}

$fileparams = dol_most_recent_file($filedir, preg_quote($ref, '/') . '([^\-])+');
$file = $fileparams['fullname'];
if ((!$file || !is_readable($file)) && method_exists($object, 'generateDocument')) {
	$result = $object->generateDocument('canelle', $langs);
	if ($result < 0) {
		dol_print_error($db, $object->error, $object->errors);
		exit();
	}
}

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('SupplierInvoice') . " - " . $langs->trans("WSDL");
$helpurl = "EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores";
llxHeader('', $title, $helpurl);

if ($action == "modify") {
	$text = "Are you sure you want to change the value?";
	print $form->formconfirm($_SERVER["PHP_SELF"] . "?facid=" . $object->id, $langs->trans("Modify"), $text, "", '', '', 1);
}


if ($object->id > 0) {

	$object->fetch_thirdparty();

	$sql            = "SELECT * FROM " . MAIN_DB_PREFIX . "user_info where rowid=" . $user->id;
	$sql            = $db->query($sql);
	$user_info      = $db->fetch_object($sql);
	if (is_null($object->invoice_number) || $object->invoice_number <= 0) {
		$invoice_number = str_pad($user_info->fk_vendor_number, 9, '0', STR_PAD_LEFT); //$row['Auto_increment']
	} else
		$invoice_number = str_pad($object->invoice_number, 9, '0', STR_PAD_LEFT); //$row['Auto_increment']
// 	$sql = "SELECT fk_default_warehouse FROM " . MAIN_DB_PREFIX . "facturedet LEFT JOIN " . MAIN_DB_PREFIX . "product ON " . MAIN_DB_PREFIX . "product.rowid = " . MAIN_DB_PREFIX . "facturedet.fk_product WHERE " . MAIN_DB_PREFIX . "facturedet.fk_facture = '" . $object->id . "' ORDER BY " . MAIN_DB_PREFIX . "facturedet.vat_src_code ASC";
    $result = $db->query("SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot WHERE " . MAIN_DB_PREFIX . "entrepot.ref = '" . str_pad($object->warehouse, 3, '0', STR_PAD_LEFT) . "'");	
	$warehouse    = $db->fetch_object($result);

	if (is_null($warehouse->ref)) {
		$sql = $db->query("SELECT ref, address FROM " . MAIN_DB_PREFIX . "entrepot ORDER BY ref LIMIT 1");
		$warehouse = $db->fetch_object($sql);
	}
		
	$warehouse_no = $warehouse->ref;
	$warehouse_ad = $warehouse->address;
	if (empty($warehouse_no)) {
		$mesg = $langs->trans('Warehousno');
		$style = 'warnings';
		exitHandler($mesg, $style, 1);
	}
	$dig      = new modulo();
	$sellerid = str_pad($user->job, 3, '0', STR_PAD_LEFT);
	if ($sellerid <= 0) {
		$mesg = $langs->trans('SEN_4');
		$style = 'warnings';
		exitHandler($mesg, $style, 0);
	}
	if (!$conf->global->MAIN_INFO_SIREN || strlen($conf->global->MAIN_INFO_SIREN) < 13) {
		$mesg = $langs->trans('SEN_5');
		$style = 'warnings';
		exitHandler($mesg, $style, 1);
	}
	if ((!$object->date_done && $date_done) || ($object->date_done != $date_done && $date_done)) {
		$claveacceso = date('dmY', $date_done) . '07' . $conf->global->MAIN_INFO_SIREN . $user_info->fk_purpose . $warehouse_no . $sellerid . $invoice_number . '12345678' . '1';
		$claveacceso = $claveacceso . $dig->getMod11Dv(strval($claveacceso));
	} else {
		$claveacceso = $object->claveacceso;
		$date_done = $object->date_done;
	}
	if (strlen($claveacceso) != 49) {
		$mesg = $langs->trans('SEN_2') . " " . strlen($claveacceso) . "(" . $claveacceso . ")";
		$style = 'errors';
		exitHandler($mesg, $style, 1);
	}
	if ((strlen($claveacceso) == 49) && ($object->claveacceso != $claveacceso)) {
		$sql = ", f_name1='" . $f_name1 . "', f_note1='" . $f_note1 . "'";
		$sql .= ", f_name2='" . $f_name2 . "', f_note2='" . $f_note2 . "'";
		$invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn SET invoice_number=" . ($invoice_number) . ", warehouse=" . ($warehouse_no) . ", seller=" . ($sellerid) . ", claveacceso='" . ($claveacceso) . "', date_done='" . date('Y-m-d', $date_done) . "', identification_type='" . $identification_type . "'" . $sql . " WHERE rowid=" . ((int) $object->id);
		$db->query($invoice_number_sql);
		$i = $invoice_number + 1;
		if ($invoice_number == $user_info->fk_vendor_number) { //&& ($user_info->fk_purpose != 3)
			$invoice_number_sql = "UPDATE " . MAIN_DB_PREFIX . "user_info SET fk_vendor_number=" . ((int)$i) . " WHERE rowid=" . ((int) $user->id);
			$db->query($invoice_number_sql);
		}
		$object = new FactureFournisseur($db);
		$object->fetch($id, $ref);
		$object->fetch_thirdparty();
	}
	$db->commit();
	$alreadypaid = $object->getSommePaiement();

	$head = facturefourn_prepare_head($object);
	$titre = $langs->trans('SupplierInvoice', 'approval');
	dol_fiche_head($head, 'wsdl', $titre, -1, 'bill');

	$sql = "SELECT modify, claveacceso, claveacceso_end FROM " . MAIN_DB_PREFIX . "facture_fourn WHERE rowid=" . $object->id;
	$res = $db->query($sql);
	$res = $db->fetch_object($res);
	$modify = $res->modify;
	$claveacceso_end = strlen($res->claveacceso_end);
	$claveacceso = strlen($res->claveacceso);
	// Supplier invoice card
	$linkback = '<a href="' . DOL_URL_ROOT . '/fourn/facture/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="' . DOL_URL_ROOT . '/fourn/facture/list.php?socid=' . $object->thirdparty->id . '&search_company=' . urlencode($object->thirdparty->name) . '">' . $langs->trans("OtherBills") . '</a>)';
	// Project

	$morehtmlref .= '</div>';

	$object->totalpaye = $alreadypaid;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<input type="hidden" name="token" value="' . newToken() . '">';

	print '<table class="border tableforfield">'; //border centpercent tableforfield
	print '<form method="POST" id="vendor" name="vendor">';
	print '<input type="hidden" name="action" id="action" value="' . $action . '">';
	// Fecha De Emisión:
	print '<tr><td class="titlefield" style="width: 15%;">' . $langs->trans('Fecha De Emisión') . '</td><td>';
	if ($modify != "disabled")
		print $form->selectDate($date_done == $object->date_done ? $object->date_done : $date_done, 'do', '', '', 0, $_SERVER['PHP_SELF']);

	print '</td></tr>';

	// NRO 
	print '<tr><td class="titlefield">' . $langs->trans('NRO') . '</td><td>';
	print '<input type="text" name="nro" id="nro" style="width:367px;background: fixed; border-bottom: none;" value="' . $warehouse_no . '-' . $sellerid . '-' . $invoice_number . '" readonly>';
	print '</td></tr>';

	// Clave De Acceso
	print '<tr><td class="titlefield">' . $langs->trans('Clave De Acceso') . '</td><td>';
	print '<input type="text" name="claveacceso" id="claveacceso" style="width:367px;background: fixed;border-bottom: none;" value="' . ($object->claveacceso ? $object->claveacceso : $claveacceso) . '" readonly>';
	print '</td></tr>';

	// identification_type
	print '<tr><td class="titlefield">' . $langs->trans('IdentificationType') . '</td><td>';
	print '<select name="identification_type" id="identification_type" ' . $modify . '>
	<option value="4" ' . ($identification_type == 4 ? "selected" : "") . '>RUC</option>
	<option value="5" ' . ($identification_type == 5 ? "selected" : "") . '>CEDULA</option>
	<option value="6" ' . ($identification_type == 6 ? "selected" : "") . '>PASAPORTE</option>
	<option value="7" ' . ($identification_type == 7 ? "selected" : "") . '>VENTA A CONSUMIDOR FINAL*</option>
	<option value="8" ' . ($identification_type == 8 ? "selected" : "") . '>IDENTIFICACION DELEXTERIOR*</option></select>';
	print '</td></tr>';

	// Phone
	print '<tr><td class="titlefield">' . $langs->trans('Phone') . '</td><td>';
	print '<input type="text" name="phone" id="phone" style="width:367px;background: fixed;border-bottom: none;" value="' . $object->thirdparty->phone . '" readonly>';
	print '</td></tr>';

	// Email
	print '<tr><td class="titlefield">' . $langs->trans('Email') . '</td><td>';
	print '<input type="text" name="email" id="email" style="width:367px;background: fixed;border-bottom: none;" value="' . $object->thirdparty->email . '" readonly>';
	print '</td></tr>';

	// Address
	print '<tr><td class="titlefield">' . $langs->trans('Address') . '</td><td>';
	print '<input type="text" name="address" id="address" style="width:100%;background: fixed;border-bottom: none;" value="' . $object->thirdparty->address . '" readonly>';
	print '</td></tr>';

	// Name, Value
	print '<tr class="liste_titre nodrag nodrop"><td class="titlefield"></td><td>';
	print '<input type="text" style="background: fixed;border-bottom: none;" value="' . $langs->trans('Name') . '" readonly>&nbsp;&nbsp;&nbsp; : &nbsp;&nbsp;&nbsp;';
	print '<input type="text" style="background: fixed;border-bottom: none;" value="' . $langs->trans('Value') . '" readonly>';
	print '</td></tr>';

	// Custom Note 1
	print '<tr><td class="titlefield">' . $langs->trans('CustomNote') . ' 1</td><td>';
	print '<input type="text" name="f_name1" id="f_name1" value="' . ($object->f_name1 ? $object->f_name1 : $f_name1) . '" >&nbsp;&nbsp;&nbsp; : &nbsp;&nbsp;&nbsp;';
	print '<input type="text" name="f_note1" id="f_note1" value="' . ($object->f_note1 ? $object->f_note1 : $f_note1) . '" >';
	print '</td></tr>';

	// Custom Note 2
	print '<tr><td class="titlefield">' . $langs->trans('CustomNote') . ' 2</td><td>';
	print '<input type="text" name="f_name2" id="f_name2" value="' . ($object->f_name2 ? $object->f_name2 : $f_name2) . '" >&nbsp;&nbsp;&nbsp; : &nbsp;&nbsp;&nbsp;';
	print '<input type="text" name="f_note2" id="f_note2" value="' . ($object->f_note2 ? $object->f_note2 : $f_note2) . '" >';
	print '</td></tr>';

	// Tipo De Documento
	print '<tr"><td class="titlefield">' . $langs->trans('Tipo De Documento') . '</td><td>';
	print '<select name="tax_type"  class="js-type-matcher" id="tax_type" ' . $modify . '>
	<option value="01" ' . ($tax_type == "01" ? "selected" : "") . '>FACTURA</option>
	<option value="03" ' . ($tax_type == "03" ? "selected" : "") . '>LIQUIDACIÓN DE COMPRA DE BIENES Y PRESTACIÓN DE SERVICIOS</option>
	<option value="04" ' . ($tax_type == "04" ? "selected" : "") . '>NOTA DE CRÉDITO</option>
	<option value="05" ' . ($tax_type == "05" ? "selected" : "") . '>NOTA DE DÉBITO</option>
	<option value="06" ' . ($tax_type == "06" ? "selected" : "") . '>GUÍA DE REMISIÓN</option>
	<option value="07" ' . ($tax_type == "07" ? "selected" : "") . '>COMPROBANTE DE RETENCIÓN</option>';
	print '</td></tr>';

	// NRO Comprovante
	print '<tr><td class="titlefield">' . $langs->trans('NRO. Comprovante') . '</td><td>';
	print '<input type="text" name="comprovante" id="comprovante" style="width:167px;background: fixed;border-bottom: none;" value="' . $object->ref_supplier . '" readonly>';
	print '</td></tr>';

	// Invoice date:
	print '<tr><td class="titlefield">' . $langs->trans('Invoicedate') . '</td><td>';
	print '<input type="text" name="claveacceso" id="claveacceso" style="width:367px;background: fixed;border-bottom: none;" value="' . (date('m/d/Y', $object->date)) . '" readonly>';
	print '</td></tr>';

	// Fiscal period
	print '<tr><td class="titlefield">' . $langs->trans('fiscalperiod') . '</td><td>Mes : ';
	print '<input type="text" name="fiscal_d" id="fiscal_d" style="width:20px; background: fixed;" maxlength="2" value="' . (date('m', $object->date)) . '" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Año : ';
	print '<input type="text" name="fiscal_y" id="fiscal_y" style="width:40px; background: fixed;" maxlength="4" value="' . (date('Y', $object->date)) . '" >';
	print '</td></tr>';

	// IMPESTO
	print '<tr><td class="titlefield">' . $langs->trans('Impuesto') . '</td><td>';
	print '<select name="type" id="type" class="flat valignmiddle"  onchange="submit()" ' . $modify . '>
	<option value="1" ' . ($type == "1" ? "selected" : "") . '>Retencion Impuesto</option>
	<option value="2" ' . ($type == "2" ? "selected" : "") . '>I.V.A</option>';
	print '</td></tr>';

	// Table
	print '<tr><td class="titlefield"></td><td>';

	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "income WHERE type='" . $type . "'";
	$rows = $db->query($sql);
	print '<select name="tax" id="tax" class="js-tax-matcher" style="width:500px"  ' . $modify . '>';
	while ($row = $db->fetch_array($rows)) {
		print '<option value="' . $row['value'] . '" ' . ($tax == $row['value'] ? "selected" : "") . '>' . str_pad(str_replace(array("\r\n", "\n", "\r", "<br>", "<br />"), '', $row['code']), 4, ' ', STR_PAD_LEFT) . ' - ' . $row['detail'] . '</option>';
		// print '<input type="hidden" id="code" value="' . $row['code'] . '">';
	}
	print '</td></tr>';

	// Base Imporable
	print '<tr><td class="titlefield">' . $langs->trans('Base Imporable') . '</td><td>';
	print '<input type="text" name="imporable" id="imporable" style="width:100px;background: fixed;border-bottom: none;" value=' . number_format((float)$imporable, 2, '.', '') . ' readonly>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	if ($modify != "disabled") print '<input class="button" type="button" onclick="javascript:show(' . $object->id . ')" value="Add" >';
	print '</td></tr>';

	// % De Retencion
	print '<tr><td class="titlefield">' . $langs->trans('% De retent') . '</td><td>';
	print '<input type="hidden" id="tax_text" name="tax_text" >';
	print '<input type="text" id="sub_retent" name="sub_retent" disabled value ="' . ($sub_retent ? $sub_retent : '') . '" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;>';
	print '<input type="text" id="retent"  style="width:37px;background: fixed;display:none;" name="retent" value ="' . ($retent ? $retent : '') . '">';
	print '</td></tr>';

	// * Valor Retenido
	print '<tr><td class="titlefield">' . $langs->trans('* Valor Retenido') . '</td><td>';
	print '<input type="text" id="retain" name="retain" value="' . $retain . '">';
	print '<input type="hidden" id="rowid" name="rowid" >';
	print '</td></tr>';
	print '</form>';


	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "vendor where id=" . $id;
	$rows = $db->query($sql);
	$num = $db->num_rows($sql);
	if ($num > 0) {
		$i = 1;
		// Table
		print '<tr><td class="titlefield" colspan="8">';
		print '<table class="border tableforfield" id="showtable" name="showtable">';
		print '<tr class="liste_titre" style="text-align: center;">';
		print '<td style="width: 2%">No</td>';
		print '<td style="width: 2%">Cod.Reten.</td>';
		print '<td style="width: 2%">Código</td>';
		print '<td>Descripción</td>';
		print '<td style="width: 5%">Base Imponible</td>';
		print '<td style="width: 2%">Per.</td>'; //Porcentaje
		print '<td style="width: 5%;text-align:right">Total</td>';
		print '<td style="width: 10%;">Documento</td>';
		print '<td>Fecha</td>';
		print '<td style="width: 2%">Tipo</td>';
		if ($modify != "disabled") print '<td style="width: 2%">Acción</td>';
		print '</tr>';

		while ($row = $db->fetch_array($rows)) {
			print '<tr style="text-align: center;">';
			print '<td>' . $i . '</td>';
			print '<td>' . $row['a'] . '</td>';
			print '<td>' . $row['b'] . '</td>';
			print '<td>' . $row['c'] . '</td>';
			print '<td align="right">' . number_format((float)$row['d'], 2, '.', '') . '</td>';
			print '<td align="right">' . number_format((float)$row['e'], 2, '.', '') . '</td>';
			print '<td align="right">' . number_format((float)$row['f'], 2, '.', '') . '</td>';
			print '<td>' . $row['g'] . '</td>';
			print '<td>' . date('d/m/Y', strtotime($row['h'])) . '</td>';
			print '<td>' . $row['j'] . '</td>';
			if ($modify != "disabled") print "<td><button onclick='remove(" . $row['rowid'] . ")'>delete</button></td></tr>";
			$i++;
		}
		print '</table></td></tr>';
	}
	print '</table>';
	$formfile = new FormFile($db);
	$ref = dol_sanitizeFileName($object->ref);
	$subdir = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier') . $ref;
	$filedir = $conf->fournisseur->facture->dir_output . '/' . $subdir;
	$urlsource = $_SERVER['PHP_SELF'] . '?facid=' . $object->id;
	$genallowed = $user->rights->fournisseur->facture->lire;
	$delallowed = $user->rights->fournisseur->facture->creer;
	$modelpdf = (!empty($object->modelpdf) ? $object->modelpdf : (empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF) ? '' : $conf->global->INVOICE_SUPPLIER_ADDON_PDF));

	print $formfile->showdocuments('facture_fournisseur', $subdir, $filedir, $urlsource, $genallowed, $delallowed, $modelpdf, 1, 0, 0, 40, 0, '', '', '');

	print "<div class='fichecenter'>";
	print "<table><tr><td><img src='" . DOL_URL_ROOT . "/custom/approval/img/load.gif' id='spinner' class = 'imgModal' style='display:none;'></td></tr>";
	print "<tr align='center'>";
	if ($db->num_rows($rows) > 0)
		print "<td><input class='button' type='button' onclick='javascript:showHint(`$object->id`)' value = 'Enviar'></td>";
	if ($claveacceso_end == 49 || $claveacceso == 49)
		if ($modify == "disabled")
			print "<td><input class='button' type='button' onclick='javascript:showModify(`$object->id`)' value = ' " . $langs->trans("Modify") . "'></td>";
	print "</tr>";
	dol_fiche_end();
}

// End of page
llxFooter();
$db->close();

class modulo
{
	public function getMod11Dv($num)
	{
		$digits = str_replace(array('.', ','), array('' . ''), strrev($num));
		if (!ctype_digit($digits)) return false;
		$sum    = 0;
		$factor = 2;
		for ($i = 0; $i < strlen($digits); $i++) {
			$sum += substr($digits, $i, 1) * $factor;
			$factor == 7 ? $factor = 2 : $factor++;
		}

		$dv = 11 - ($sum % 11);
		if ($dv == 10) return 1;
		if ($dv == 11) return 0;
		return $dv;
	}
}
function exitHandler($mesg, $style, $event)
{
	$_SESSION['dol_events'][$style][] = $mesg;
	// print_r($mesg);
	// if ($event) exit;
}


?>

<script type="text/javascript">
	var imporable, tax, retent;
	$('#sub_retent').val($('#tax').val());

	if (Number($('#tax').val()) % 1 == 0) {
		$('#retent').val(Number($('#tax').val()).toFixed(2));
		imporable = Number($('#imporable').val());
		retent = Number($('#retent').val());
		$('#retain').val(Number(imporable * retent / 100).toFixed(2));
	} else {
		$('#retent').val('0.00');
		$('#retent').css('display', 'inline');

	}
	$('#tax').on('change', function(e) {
		$('#sub_retent').val($('#tax').val());
		if (Number($('#tax').val()) % 1 == 0) {
			$('#retent').css('display', 'None');
			imporable = Number($('#imporable').val()).toFixed(2);
			tax = Number($('#tax').val()).toFixed(2);
			if (tax <= 0) tax = 100;
			$('#retent').val(Number($('#tax').val()).toFixed(2));
			$('#retain').val(Number(imporable * tax / 100).toFixed(2));
		} else {
			$('#retent').css('display', 'inline');
			$('#retent').val('0.00');
		}
	});
	$('#retent').on('keyup change', function(e) {

		$('#retent').css('display', 'inline');
		imporable = Number($('#imporable').val());
		retent = Number($('#retent').val());
		$('#retain').val(Number(imporable * retent / 100).toFixed(2));

	});

	$(document).ready(function(e) {
		console.log(e);
	});


	$('.inline-block').on('change', function() {
		document.getElementById('vendor').submit();
	});

	$('#imporable').on('keyup change', function() {
		Number(imporable = $('#imporable').val()).toFixed(2);
		Number(retent = $('#retent').val()).toFixed(2);
		if (retent <= 0) retent = 100;
		retent = $('#retain').val(Number(imporable * retent / 100).toFixed(2));
	});

	function remove(id) {
		document.getElementById('action').value = 'remove';
		document.getElementById('rowid').value = id;
		document.getElementById('vendor').submit();
	}

	function show(id) {
		if (document.getElementById('imporable').value) {
			var tax_text = $('#tax option:selected').text();
			var strArray = tax_text.split(" - ");
			document.getElementById('tax_text').value = tax_text;
			document.getElementById('action').value = 'add';
			document.getElementById('vendor').submit();
		}
	}

	function showHint(id) {
		// if (confirm("¿Son correctos los datos introducidos?")) {
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				window.location.href = "vendor.php?facid=" + id;
			}
		}
		xmlhttp.open("GET", "../../custom/approval/approval_vendor.php?index=" + id, true);
		document.getElementById('spinner').style.display = 'block';
		document.getElementById('mainbody').style.opacity = '.5';
		document.getElementById('mainbody').style.background = 'white';
		document.getElementById('mainbody').style.pointerEvents = 'none';
		xmlhttp.send();
	}

	function showModify(id) {
		window.location.href = "vendor.php?facid=" + id + "&action=modify";
	}

	$(document).ready(function() {
		$('.js-tax-matcher').select2();
		$('.js-type-matcher').select2();
	});
</script>
<style>
	.imgModal {
		overflow-block: none;
		display: none;
		position: fixed;
		width: 200px;
		height: 200px;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}
</style>