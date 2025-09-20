<?php
/* Copyright (C) 2024      AI-generated         <gemini@google.com>
 *
 * This file is an interface to launch triggers for module Approval.
 */

require_once DOL_DOCUMENT_ROOT.'/core/interfaces/DolibarrTriggers.class.php';

/**
 * Class to manage triggers for Approval module
 */
class Interface_99_modApproval_Approval extends DolibarrTriggers
{
	public $name = "ApprovalTrigger";
	public $description = "Triggers for Approval module";
	public $version = '1.0';

	public function __construct()
	{
		// constructor
	}

	public function run_trigger($action, $object, $user, $langs, $conf)
	{
		global $db;
		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by user ".$user->id." for object id ".$object->id, LOG_DEBUG);

		$context_map = array(
			'ORDER_SUPPLIER_CREATE' => 'supplierordercard', 'ORDER_SUPPLIER_MODIFY' => 'supplierordercard',
			'INVOICE_SUPPLIER_CREATE' => 'supplierinvoicecard', 'INVOICE_SUPPLIER_MODIFY' => 'supplierinvoicecard',
			'ORDER_CREATE' => 'ordercard', 'ORDER_MODIFY' => 'ordercard',
			'INVOICE_CREATE' => 'invoicecard', 'INVOICE_MODIFY' => 'invoicecard',
			'SHIPMENT_CREATE' => 'expeditioncard', 'SHIPMENT_MODIFY' => 'expeditioncard'
		);

		if (isset($context_map[$action]) && !empty($object->id)) {
			$context = $context_map[$action];
			$updates = array();
			$table_name = '';

			if ($context == 'supplierordercard') {
				$table_name = 'commande_fournisseur';
				if (isset($_POST['claveacceso'])) {
					$updates[] = "claveacceso = '".$db->escape(GETPOST('claveacceso', 'alpha'))."'";
				}
			} else {
				$prefixes = array('ordercard' => 'c_', 'supplierinvoicecard' => 'f_', 'invoicecard' => 'c_', 'expeditioncard' => 'c_');
				$tables = array('ordercard' => 'commande', 'supplierinvoicecard' => 'facture_fourn', 'invoicecard' => 'facture', 'expeditioncard' => 'expedition');
				$var_prefix = $prefixes[$context];
				$table_name = $tables[$context];

				for ($i = 1; $i < 8; $i++) {
					$note_field = $var_prefix . 'note' . $i;
					$name_field = $var_prefix . 'name' . $i;
					if (isset($_POST[$note_field])) {
						$updates[] = $note_field . " = '" . $db->escape(GETPOST($note_field, 'alpha')) . "'";
					}
					if (isset($_POST[$name_field])) {
						$updates[] = $name_field . " = '" . $db->escape(GETPOST($name_field, 'alpha')) . "'";
					}
				}
			}

			if (!empty($updates) && !empty($table_name)) {
				$sql = "UPDATE " . MAIN_DB_PREFIX . $table_name . " SET " . implode(', ', $updates) . " WHERE rowid = " . $object->id;
				dol_syslog("Approval Trigger SQL: ".$sql, LOG_DEBUG);
				if (!$db->query($sql)) {
					dol_syslog("Error updating custom fields for ".$table_name." ".$object->id.": ".$db->lasterror(), LOG_ERR);
				}
			}
		}

		return 0;
	}
}
