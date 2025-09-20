<?php
/* Copyright (C) 2024      AI-generated         <gemini@google.com>
 *
 * This file is an interface to launch triggers for module Approval.
 * It is not a license violation to include this file into a module
 * even if the module is not GPL.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/interfaces/DolibarrTriggers.class.php';

/**
 * Class to manage triggers for Approval module
 */
class Interface_99_modApproval_Approval extends DolibarrTriggers
{
	/**
	 * Trigger name
	 *
	 * @var string
	 */
	public $name = "ApprovalTrigger";
	/**
	 * Trigger description
	 *
	 * @var string
	 */
	public $description = "Triggers for Approval module";
	/**
	 * Trigger version
	 *
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		// You can add logic here if needed for initialization
	}

	/**
	 * This function is called by Dolibarr triggers.
	 * It executes after an object has been created or modified, ensuring we have an ID to work with.
	 *
	 * @param  string   $action      Event code
	 * @param  CommonObject  $object      Object on which event was done
	 * @param  User     $user        Object of user who done action
	 * @param  Translate   $langs       Object for translation
	 * @param  conf     $conf        Object with Dolibarr config
	 * @return int                   0 if OK, <0 if KO
	 */
	public function run_trigger($action, $object, $user, $langs, $conf)
	{
		global $db;
		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by user ".$user->id.".", LOG_DEBUG);

		$context_map = array(
			'ORDER_SUPPLIER_CREATE'   => 'supplierordercard',
			'ORDER_SUPPLIER_MODIFY'   => 'supplierordercard',
			'INVOICE_SUPPLIER_CREATE' => 'supplierinvoicecard',
			'INVOICE_SUPPLIER_MODIFY' => 'supplierinvoicecard',
			'ORDER_CREATE'            => 'ordercard',
			'ORDER_MODIFY'            => 'ordercard',
			'INVOICE_CREATE'          => 'invoicecard',
			'INVOICE_MODIFY'          => 'invoicecard',
			'SHIPMENT_CREATE'         => 'expeditioncard',
			'SHIPMENT_MODIFY'         => 'expeditioncard'
		);

		if (isset($context_map[$action]) && !empty($object->id)) {
			$context = $context_map[$action];

			if ($context == 'supplierordercard') {
				if (isset($_POST['claveacceso'])) {
					$sql = "UPDATE " . MAIN_DB_PREFIX . "commande_fournisseur SET claveacceso = '".$db->escape(GETPOST('claveacceso', 'alpha'))."' WHERE rowid = " . $object->id;
					if (!$db->query($sql)) {
						dol_syslog("Error updating claveacceso for supplier order ".$object->id.": ".$db->lasterror(), LOG_ERR);
					}
				}
			} else {
				$var_prefix = '';
				$table_name = '';
				switch ($context) {
					case 'ordercard':           $var_prefix = 'c_'; $table_name = 'commande'; break;
					case 'supplierinvoicecard': $var_prefix = 'f_'; $table_name = 'facture_fourn'; break;
					case 'invoicecard':         $var_prefix = 'c_'; $table_name = 'facture'; break;
					case 'expeditioncard':      $var_prefix = 'c_'; $table_name = 'expedition'; break;
				}

				if ($table_name) {
					$updates = array();
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
					if (!empty($updates)) {
						$sql = "UPDATE " . MAIN_DB_PREFIX . $table_name . " SET " . implode(', ', $updates) . " WHERE rowid = " . $object->id;
						if (!$db->query($sql)) {
							dol_syslog("Error updating custom fields for ".$table_name." ".$object->id.": ".$db->lasterror(), LOG_ERR);
						}
					}
				}
			}
		}

		return 0;
	}
}
