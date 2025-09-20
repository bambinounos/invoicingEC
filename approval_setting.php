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
 *    \file       htdocs/custom/approval/approval_setting.php
 *    \ingroup    approval
 *    \brief      File of class to setting invoices info
 */


require '../../main.inc.php';

if (!$conf->approval->enabled || !isset($_SESSION['idmenu'])) {
    header("Location: ../../index.php");
}

// Load translation files required by the page
$langs->loadLangs(array('approval'));

// set_exception_handler('exceptionHandler');
if (GETPOSTISSET('web')) {
    $sql = "UPDATE " . MAIN_DB_PREFIX . "user_info SET fk_purpose = " . GETPOST('web') . ", fk_invoice_number = " . GETPOST('invoice') . ", fk_vendor_number = " . GETPOST('vendor') . ", fk_note_number = " . GETPOST('note') . ", fk_debit_number = " . GETPOST('debit') . ", fk_alias = '" . GETPOST('alias') . "', fk_taxpayer = '" . GETPOST('fk_taxpayer') . "', fk_keep = '" . GETPOST('taxcheck') . "', fk_microenterprise ='" . GETPOST('strcheck') . "', fk_agent ='" . GETPOST('agent') . "' WHERE rowid =" . $user->id;
    $err = $db->query($sql);
    $mesg = $langs->trans('CorrectSave');
    $style = "mesgs";
    exitHandler($mesg, $style, $event);
    // header("Location: ../../compta/index.php?mainmenu=billing&leftmenu=");
}

$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "user_info WHERE rowid = " . $user->id;
$rows = $db->query($sql);
if ($db->num_rows($rows) > 0) {
    $row = $db->fetch_object($rows);
} else {
    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "user_info (rowid, fk_purpose, fk_vendor_number, fk_invoice_number, fk_note_number, fk_debit_number, fk_alias, fk_taxpayer, fk_keep, fk_microenterprise, fk_agent) VALUES
	(" . $user->id . ", 1, 1, 1, 1, 1, 'IMPORTADORA HELLBAM', NULL, NULL, NULL, NULL)";
    $db->query($sql);
    $db->commit();
    header("refresh:0");
}
$help_url = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader("", $langs->trans("ApprovalSetting"), $help_url);
print load_fiche_titre($langs->trans("ApprovalSetting"), '', 'bill');
print '<div class="tabBar tabBarWithBottom">';
print '<form method="post">';
print '<table class="border centpercent" >';
// Purpose
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('Purpose') . '</span>';
print '</td><td>';
print '<select id="web" name="web"><option value="1" ' . select_Val($row->fk_purpose, 1) . ' >' . $langs->trans('TEST') . '</option><option value="2" ' . select_Val($row->fk_purpose, 2) . ' >' . $langs->trans('Production') . '</option><option value="3" ' . select_Val($row->fk_purpose, 3) . ' >' . $langs->trans('Training') . '</option></select>';
print '</td>';
print '</tr>';

// Invoice Number
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('InvoiceNumber') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth9" maxlength="9" name="invoice" id="invoice" value="' . $row->fk_invoice_number . '">';
print '</td>';
print '</tr>';

// Note Number
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('NoteNumber') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth9" maxlength="9" name="note" id="note" value="' . $row->fk_note_number . '">';
print '</td>';
print '</tr>';

// Debit Number
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('DebitNumber') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth9" maxlength="9" name="debit" id="debit" value="' . $row->fk_debit_number . '">';
print '</td>';
print '</tr>';

// Comprobante Number
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('VendorNumber') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth9" maxlength="9" name="vendor" id="vendor" value="' . $row->fk_vendor_number . '">';
print '</td>';
print '</tr>';

// Company Alias
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('CompanyAlias') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth300" maxlength="128" name="alias" id="alias" value="' . $row->fk_alias . '">';
print '</td>';
print '</tr>';

// fk_taxpayer taxpayer resolution no
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('SpecialTaxpayerResolutionNo.') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth" maxlength="8" name="fk_taxpayer" id="fk_taxpayer" value="' . $row->fk_taxpayer . '">';
print '</td>';
print '</tr>';

// obliged to keep accounting
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('ObligedToKeepAccounting') . '</span>';
print '</td><td>';
print '<input type="checkbox" class="minwidth" maxlength="9" name="taxcheck" id="taxcheck" ' . select_Chk($row->fk_keep) . ">";
print '</td>';
print '</tr>';

// Microenterprise Regime Taxpayer
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . "Contribuyente Régimen Rimpe" . '</span>';
print '</td><td>';
print '<input type="checkbox" class="minwidth" maxlength="5" name="strcheck" id="strcheck" ' . select_Chk($row->fk_microenterprise) . ">";
print '</td>';
print '</tr>';

// Withholding Agent Resolution No.
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('WithholdingAgentResolutionNo.') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth" maxlength="8" name="agent" id="agent" value="' . $row->fk_agent . '">';
print '</td>';
print '</tr>';

print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print ' </td><td height = "50px"> ';
print '</td>';
print '</tr>';

// Button "Save setting"
print '<tr class="tr-field-thirdparty-name"><td colspan="2" class="titlefieldcreate" align = "center">';
print '<input type="submit" class="button" value="' . $langs->trans('Save') . '" >';
print '</td>';
print '</tr>';

print '</table>';
print '</div></div>';

// End of page
llxFooter();
$db->close();
function select_Val($json, $const)
{
    if ($json == strval($const)) {
        return 'selected="selected"';
    } else {
        return '';
    }
}

function select_Chk($json)
{
    if ($json == "on") {
        return 'checked';
    } else {
        return '';
    }
}

function exitHandler($mesg, $style, $event)
{
    $_SESSION['dol_events'][$style][] = $mesg;
    if ($event) {
        header("Location:approval_setting.php");
    }
}