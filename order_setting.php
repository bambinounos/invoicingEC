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
 *    \file       htdocs/custom/approval/order_setting.php
 *    \ingroup    approval
 *    \brief      File of class to setting order info
 */

require '../../main.inc.php';
$langs->loadLangs(array('approval'));

if (!$conf->approval->enabled || !isset($_SESSION['idmenu'])) {
    header("Location: ../../index.php");
}
if (GETPOSTISSET('web')) {
    $sql = "UPDATE " . MAIN_DB_PREFIX . "order_info SET ck_purpose = '" . GETPOST('web') . "', ck_invoice_number = '" . GETPOST('invoice') . "',  ck_alias = '" . GETPOST('alias') . "', ck_taxpayer = '" . GETPOST('ck_taxpayer') . "', ck_keep = '" . GETPOST('taxcheck') . "', ck_microenterprise ='" . GETPOST('strcheck') . "', ck_agent ='" . GETPOST('agent') . "', ck_prefixmark ='" . GETPOST('ck_prefixmark') . "' WHERE rowid =" . $user->id;
    $err = $db->query($sql);
    $mesg = $langs->trans('CorrectSave');
    $style = "mesgs";
    exitHandler($mesg, $style, $event);
}
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "order_info WHERE rowid = " . $user->id;
$rows = $db->query($sql);
if ($db->num_rows($rows) > 0) {
    $row = $db->fetch_object($rows);
} else {
    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "order_info (rowid, ck_purpose, ck_invoice_number, ck_note_number, ck_alias, ck_taxpayer, ck_keep, ck_microenterprise, ck_agent,ck_prefixmark) VALUES
	(" . $user->id . ", 1, 1, 1, 'IMPORTADORA HELLBAM', NULL, NULL, NULL, NULL,'DON-,MANN-,/')";
    $db->query($sql);
    header("Refresh:0");
}

$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';
llxHeader("", $langs->trans("OrderSetting"), $help_url);
print load_fiche_titre($langs->trans("OrderSetting"), '', 'product');
print '<div class="tabBar tabBarWithBottom">';

print '<form method="post">'; //autocomplete="off"
print '<table class="border">';
// Purpose
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst($langs->trans('Purpose')) . '</span>';
print '</td><td>';
print '<select id="web" name="web"><option value="1" ' . select_Val($row->ck_purpose, 1) . ' >' . ucfirst($langs->trans('TEST')) . '</option><option value="2" ' . select_Val($row->ck_purpose, 2) . ' >' . ucfirst($langs->trans('Production')) . '</option><option value="3" ' . select_Val($row->ck_purpose, 3) . ' >' . ucfirst($langs->trans('Training')) . '</option></select>';
print '</td>';
print '</tr>';
// Invoice Number
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst($langs->trans('InvoiceNumber')) . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth9" maxlength="9" name="invoice" id="invoice" value="' . $row->ck_invoice_number . '">';
print '</td>';
print '</tr>';
// Company Alias
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst($langs->trans('CompanyAlias')) . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth300" maxlength="128" name="alias" id="alias" value="' . $row->ck_alias . '">';
print '</td>';
print '</tr>';
// ck_taxpayer taxpayer resolution no
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst($langs->trans('SpecialTaxpayerResolutionNo.')) . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth" maxlength="3" name="ck_taxpayer" id="ck_taxpayer" value="' . $row->ck_taxpayer . '">';
print '</td>';
print '</tr>';
// obliged to keep accounting
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst($langs->trans('ObligedToKeepAccounting')) . '</span>';
print '</td><td>';
print '<input type="checkbox" class="minwidth" maxlength="9" name="taxcheck" id="taxcheck" ' . select_Chk($row->ck_keep) . ">";
print '</td>';
print '</tr>';
// Microenterprise Regime Taxpayer
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst("Contribuyente Régimen Rimpe") . '</span>';
print '</td><td>';
print '<input type="checkbox" class="minwidth" maxlength="5" name="strcheck" id="strcheck" ' . select_Chk($row->ck_microenterprise) . ">";
print '</td>';
print '</tr>';
// Prefix Mark
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . $langs->trans('Prefixmark') . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth300" maxlength="128" name="ck_prefixmark" id="ck_prefixmark" value="' . $row->ck_prefixmark . '">';
print '</td>';
print '</tr>';
// Withholding Agent Resolution No.
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print '<span id="TypeName">' . ucfirst($langs->trans('WithholdingAgentResolutionNo.')) . '</span>';
print '</td><td>';
print '<input type="text" class="minwidth" maxlength="3" name="agent" id="agent" value="' . $row->ck_agent . '">';
print '</td>';
print '</tr>';
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
print ' </td><td height = "50px"> ';
print '</td>';
print '</tr>';
// Button "Save setting"
print '<tr class="tr-field-thirdparty-name"><td colspan="2" class="titlefieldcreate" align = "center">';
print '<input type="submit" class="button" value="' . ucfirst($langs->trans('Save')) . '" >';
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
        exit;
    }
}
