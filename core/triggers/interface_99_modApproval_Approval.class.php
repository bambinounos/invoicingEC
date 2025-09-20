<?php
/* Copyright (C) 2024      Final Version by AI         <gemini@google.com>
 *
 * This trigger file synchronizes data from standard ExtraFields
 * into the custom table columns expected by the Approval module's logic.
 */

require_once DOL_DOCUMENT_ROOT.'/core/interfaces/DolibarrTriggers.class.php';

class Interface_99_modApproval_Approval extends DolibarrTriggers
{
	public $name = "ApprovalTrigger";
	public $description = "Triggers for Approval module (manages ExtraFields sync)";
	public $version = '1.0';

	public function run_trigger($action, $object, $user, $langs, $conf)
	{
		global $db;
		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by user ".$user->id." for object id ".$object->id, LOG_DEBUG);

		$actions_map = array(
			'ORDER_SUPPLIER_CREATE'   => 'commande_fournisseur',
			'ORDER_SUPPLIER_MODIFY'   => 'commande_fournisseur',
			'INVOICE_SUPPLIER_CREATE' => 'facture_fourn',
			'INVOICE_SUPPLIER_MODIFY' => 'facture_fourn',
			'ORDER_CREATE'            => 'commande',
			'ORDER_MODIFY'            => 'commande',
			'INVOICE_CREATE'          => 'facture',
			'INVOICE_MODIFY'          => 'facture',
			'SHIPMENT_CREATE'         => 'expedition',
			'SHIPMENT_MODIFY'         => 'expedition'
		);

		if (isset($actions_map[$action]) && !empty($object->id)) {
			$table_name = $actions_map[$action];
			$updates = array();

			// Fetch all extrafields data from POST
			$extrafields_data = (isset($_POST['options']) && is_array($_POST['options'])) ? $_POST['options'] : array();
			
			// Map extrafield names to table column names
			if ($table_name == 'commande_fournisseur') {
				if(isset($extrafields_data['claveacceso'])) $updates[] = "claveacceso = '".$db->escape($extrafields_data['claveacceso'])."'";
			} else {
				$prefixes = array('commande' => 'c_', 'facture_fourn' => 'f_', 'facture' => 'c_', 'expedition' => 'c_');
				$var_prefix = $prefixes[$table_name];

				if(isset($extrafields_data['claveacceso'])) $updates[] = "claveacceso = '".$db->escape($extrafields_data['claveacceso'])."'";

				for ($i = 1; $i <= 7; $i++) {
					if (isset($extrafields_data['note'.$i])) {
						$updates[] = $var_prefix."note".$i." = '".$db->escape($extrafields_data['note'.$i])."'";
					}
					if (isset($extrafields_data['name'.$i])) {
						$updates[] = $var_prefix."name".$i." = '".$db->escape($extrafields_data['name'.$i])."'";
					}
				}
			}

			if (!empty($updates)) {
				$sql = "UPDATE " . MAIN_DB_PREFIX . $table_name . " SET " . implode(', ', $updates) . " WHERE rowid = " . $object->id;
				dol_syslog("Approval Trigger SQL (Sync ExtraFields): ".$sql, LOG_DEBUG);
				if (!$db->query($sql)) {
					dol_syslog("Error syncing ExtraFields for ".$table_name." ".$object->id.": ".$db->lasterror(), LOG_ERR);
				}
			}
		}
		return 0;
	}
}
