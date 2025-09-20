<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2024  Maxim Maksimovich Isaev <isayev95117@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modApproval extends DolibarrModules
{
	public function __construct($db)
	{
		parent::__construct($db);
		$this->numero = 170000;
		$this->rights_class = 'approval';
		$this->family = "financial";
		$this->module_position = 50;
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Module for electronic invoicing compliance in Ecuador, adapted for PostgreSQL.";
		$this->editor_name = 'Maxim Maksimovich Isaev';
		$this->editor_url = 'https://www.dolibarr.org';
		$this->version = '17.0.7'; // Definitive final version
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'approval.png';
		$this->module_parts = array(
			'triggers' => 1,
			'hooks' => array(
				'invoicecard',
				'ordercard',
				'supplierinvoicecard',
				'supplierordercard',
				'expeditioncard',
			)
		);
		$this->config_page_url = array("setup.php@approval");
		$this->depends = array('modSociete');
		$this->langfiles = array("approval");
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = 170001; $this->rights[$r][1] = 'Read approval';    $this->rights[$r][2] = 'r'; $this->rights[$r][3] = 1; $this->rights[$r][4] = 'read';   $r++;
		$this->rights[$r][0] = 170002; $this->rights[$r][1] = 'Create/update approval'; $this->rights[$r][2] = 'w'; $this->rights[$r][3] = 0; $this->rights[$r][4] = 'write';  $r++;
		$this->rights[$r][0] = 170003; $this->rights[$r][1] = 'Delete approval';  $this->rights[$r][2] = 'd'; $this->rights[$r][3] = 0; $this->rights[$r][4] = 'delete'; $r++;
	}

	public function init($options = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);
		$this->remove($options); // Clean slate first
		$this->db->begin();
		try {
			$this->_create_tables_and_columns();
			$this->_insert_initial_data();
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_print_error($this->db, $this->error);
			$this->db->rollback();
			return -1;
		}
		if (parent::init($options) < 0) {
			$this->db->rollback(); return -1;
		}
		$this->db->commit();
		return 1;
	}

	public function remove($options = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);
		$this->db->begin();
		try {
			$this->_cleanup_extrafields(); // IMPORTANT: Clean orphaned extrafields first
			$this->_drop_tables_and_columns();
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_print_error($this->db, $this->error);
			$this->db->rollback();
			return -1;
		}
		if (parent::remove($options) < 0) {
			$this->db->rollback(); return -1;
		}
		$this->db->commit();
		return 1;
	}

	public function invoicecard($p, &$o, &$a, $h){ return $this->_print_texte($p, $o, $a, $h); }
	public function ordercard($p, &$o, &$a, $h){ return $this->_print_texte($p, $o, $a, $h); }
	public function supplierinvoicecard($p, &$o, &$a, $h){ return $this->_print_texte($p, $o, $a, $h); }
	public function supplierordercard($p, &$o, &$a, $h){ return $this->_print_texte($p, $o, $a, $h); }
	public function expeditioncard($p, &$o, &$a, $h){ return $this->_print_texte($p, $o, $a, $h); }

	private function _print_texte($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
		$langs->load("approval");
		$is_edit_mode = ($action == 'create' || strpos($action, 'edit') !== false);

		print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("Approval").'</td></tr>';

		if ($hookmanager->context == 'supplierordercard') {
			$value = isset($object->claveacceso) ? $object->claveacceso : '';
			if ($is_edit_mode) {
				print '<tr class="oddeven"><td>' . $langs->trans('ClaveDeAcceso') . '</td><td colspan="3"><input class="flat" name="claveacceso" value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"></td></tr>';
			} else {
				print '<tr class="oddeven"><td>' . $langs->trans('ClaveDeAcceso') . '</td><td colspan="3">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td></tr>';
			}
		} else {
			$prefixes = array('ordercard' => 'c_', 'supplierinvoicecard' => 'f_', 'invoicecard' => 'c_', 'expeditioncard' => 'c_');
			$var_prefix = isset($prefixes[$hookmanager->context]) ? $prefixes[$hookmanager->context] : '';
			for ($i = 1; $i < 8; $i++) {
				$note_field = $var_prefix . 'note' . $i; $name_field = $var_prefix . 'name' . $i;
				$note_value = isset($object->{$note_field}) ? $object->{$note_field} : ''; $name_value = isset($object->{$name_field}) ? $object->{$name_field} : '';
				if ($is_edit_mode) {
					print '<tr class="oddeven"><td>' . $langs->trans('Note' . $i) . '</td><td><input class="flat" name="' . $note_field . '" value="' . htmlspecialchars($note_value, ENT_QUOTES, 'UTF-8') . '"></td>';
					print '<td>' . $langs->trans('Name' . $i) . '</td><td><input class="flat" name="' . $name_field . '" value="' . htmlspecialchars($name_value, ENT_QUOTES, 'UTF-8') . '"></td></tr>';
				} else {
					print '<tr class="oddeven"><td>' . $langs->trans('Note' . $i) . '</td><td>' . htmlspecialchars($note_value, ENT_QUOTES, 'UTF-8') . '</td>';
					print '<td>' . $langs->trans('Name' . $i) . '</td><td>' . htmlspecialchars($name_value, ENT_QUOTES, 'UTF-8') . '</td></tr>';
				}
			}
		}
		return 0;
	}

	private function _create_tables_and_columns()
	{
		$this->db->ddl->createTable(MAIN_DB_PREFIX.'order_info', ['rowid'=>'integer auto_increment PRIMARY KEY','ck_purpose'=>'integer NOT NULL DEFAULT 1','ck_invoice_number'=>'integer','ck_note_number'=>'integer','ck_alias'=>'text','ck_taxpayer'=>'text','ck_keep'=>'text','ck_microenterprise'=>'text','ck_agent'=>'text','ck_prefixmark'=>'text']);
		$this->db->ddl->createTable(MAIN_DB_PREFIX.'user_info', ['rowid'=>'integer auto_increment PRIMARY KEY','fk_purpose'=>'integer NOT NULL DEFAULT 1','fk_invoice_number'=>'integer','fk_note_number'=>'integer','fk_vendor_number'=>'integer','fk_debit_number'=>'integer','fk_alias'=>'text','fk_taxpayer'=>'text','fk_keep'=>'text','fk_microenterprise'=>'text','fk_agent'=>'text']);
		$this->db->ddl->createTable(MAIN_DB_PREFIX.'vendor', ['rowid'=>'integer auto_increment PRIMARY KEY','a'=>'text NOT NULL','b'=>'text NOT NULL','c'=>'text NOT NULL','d'=>'double(24,2) DEFAULT 0','e'=>'double(24,2) DEFAULT 0','f'=>'double(24,2) DEFAULT 0','g'=>'text NOT NULL','h'=>'date','i'=>'text NOT NULL','j'=>'text NOT NULL','id'=>'integer']);
		$this->db->ddl->createTable(MAIN_DB_PREFIX.'income', ['rowid'=>'integer auto_increment PRIMARY KEY','detail'=>'text NOT NULL','value'=>'text NOT NULL','form'=>'text NOT NULL','code'=>'text NOT NULL','type'=>'text NOT NULL']);
		$this->db->ddl->addColumn(MAIN_DB_PREFIX.'commande_fournisseur', 'claveacceso', 'text');
		$cols_common = ['c_note1'=>'text','c_name1'=>'text','c_note2'=>'text','c_name2'=>'text','c_note3'=>'text','c_name3'=>'text','c_note4'=>'text','c_name4'=>'text','c_note5'=>'text','c_name5'=>'text','c_note6'=>'text','c_name6'=>'text','c_note7'=>'text','c_name7'=>'text','identification_type'=>'integer DEFAULT 4','identification_c_type'=>'integer DEFAULT 4','tip'=>'integer','invoice_number'=>'integer','warehouse'=>'integer','seller'=>'integer','reason_type'=>'integer DEFAULT 3','ws_approval_one'=>'text','ws_approval_two'=>'text','ws_time'=>'datetime','claveacceso'=>'text','ws_approval_thr'=>'text','ws_approval_fou'=>'text','ws_time_end'=>'datetime','claveacceso_end'=>'text','start_date'=>'date','end_date'=>'date','carrier'=>'integer'];
		foreach ($cols_common as $col => $def) { $this->db->ddl->addColumn(MAIN_DB_PREFIX.'commande', $col, $def); $this->db->ddl->addColumn(MAIN_DB_PREFIX.'expedition', $col, $def); }
		$cols_facture = array_diff_key($cols_common, ['c_note7'=>0, 'c_name7'=>0, 'identification_c_type'=>0,'start_date'=>0,'end_date'=>0,'carrier'=>0]);
		foreach ($cols_facture as $col => $def) { $this->db->ddl->addColumn(MAIN_DB_PREFIX.'facture', $col, $def); }
		$cols_fourn = ['f_note1'=>'text','f_name1'=>'text','f_note2'=>'text','f_name2'=>'text','f_note3'=>'text','f_name3'=>'text','f_note4'=>'text','f_name4'=>'text','f_note5'=>'text','f_name5'=>'text','f_note6'=>'text','f_name6'=>'text','f_note7'=>'text','f_name7'=>'text','identification_type'=>'integer DEFAULT 4','tip'=>'integer','invoice_number'=>'integer','warehouse'=>'integer','seller'=>'integer','reason_type'=>'integer DEFAULT 3','ws_approval_one'=>'text','ws_approval_two'=>'text','ws_time'=>'datetime','claveacceso'=>'text','ws_approval_thr'=>'text','ws_approval_fou'=>'text','ws_time_end'=>'datetime','claveacceso_end'=>'text','date_done'=>'date','date_create'=>'date','modify'=>'text'];
		foreach ($cols_fourn as $col => $def) { $this->db->ddl->addColumn(MAIN_DB_PREFIX.'facture_fourn', $col, $def); }
	}

	private function _insert_initial_data()
	{
		$this->db->query("INSERT INTO ".MAIN_DB_PREFIX."order_info (rowid, ck_purpose, ck_invoice_number, ck_note_number, ck_alias, ck_prefixmark) VALUES (1, 1, 1, 1, 'Alias name', 'DON-,MANN-,/')");
		$this->db->query("INSERT INTO ".MAIN_DB_PREFIX."user_info (rowid, fk_purpose, fk_vendor_number, fk_invoice_number, fk_note_number, fk_debit_number, fk_alias) VALUES (1, 1, 1, 1, 1, 1, 'Alias name')");
		$incomeData = array(
			array(1,'Honorarios profesionales y demás pagos por servicios relacionados con el título profesional','10','303','303','1'), array(2,'Servicios profesionales prestados por sociedades residentes','3','3030','303A','1'),
			array(3,'Servicios predomina el intelecto no relacionados con el título profesional','10','304','304','1'), array(4,'Comisiones y demás pagos por servicios predomina intelecto no relacionados con el título profesional','10','304','304A','1'),
			array(5,'Pagos a notarios y registradores de la propiedad y mercantil por sus actividades ejercidas como tales','10','304','304B','1'), array(6,'Pagos a deportistas, entrenadores, árbitros, miembros del cuerpo técnico por sus actividades ejercidas como tales','8','304','304C','1'),
			array(7,'Pagos a artistas por sus actividades ejercidas como tales','8','304','304D','1'), array(8,'Honorarios y demás pagos por servicios de docencia','10','304','304E','1'),
			array(9,'Servicios predomina la mano de obra','2','307','307','1'), array(10,'Utilización o aprovechamiento de la imagen o renombre (personas naturales, sociedades," influencers")','10','308','308','1'),
			array(11,'Servicios prestados por medios de comunicación y agencias de publicidad','2.75','309','309','1'), array(12,'Servicio de transporte privado de pasajeros o transporte público o privado de carga','1','310','310','1'),
			array(13,'Pagos a través de liquidación de compra (nivel cultural o rusticidad)','2','311','311','1'), array(14,'Transferencia de bienes muebles de naturaleza corporal','1.75','312','312','1'),
			array(15,'COMPRAS AL PRODUCTOR: de bienes de origen bioacuático, forestal y los descritos  el art.27.1 de LRTI','1','3120','312A','1'), array(16,'COMPRAS AL COMERCIALIZADOR: de bienes de origen bioacuático, forestal y los descritos  el art.27.1 de LRTI','1.75','3121','312C','1'),
			array(17,'Regalías por concepto de franquicias de acuerdo al Código INGENIOS (COESCCI) - pago a personas naturales','10','314','314A','1'), array(18,'Cánones, derechos de autor,  marcas, patentes y similares de acuerdo  al Código INGENIOS (COESCCI) – pago a personas naturales','10','314','314B','1'),
			array(19,'Regalías por concepto de franquicias de acuerdo al Código INGENIOS (COESCCI) - pago a sociades','10','314','314C','1'), array(20,'Cánones, derechos de autor,  marcas, patentes y similares de acuerdo  al Código INGENIOS (COESCCI)','10','314','314D','1'),
			array(21,'Cuotas de arrendamiento mercantil (prestado por sociedades), inclusive la de opción de compra','2','319','319','1'), array(22,'Arrendamiento bienes inmuebles','10','320','320','1'),
			array(23,'Seguros y reaseguros (primas y cesiones)','1','322','322','1'), array(24,'Rendimientos financieros pagados a naturales y sociedades  (No a IFIs)','2','323','323','1'),
			array(25,'Rendimientos financieros: depósitos Cta. Corriente','2','323','323A','1'), array(26,'Rendimientos financieros:  depósitos Cta. Ahorros Sociedades','2','323','323B1','1'),
			array(27,'Rendimientos financieros: depósito a plazo fijo  gravados','2','323','323E','1'), array(28,'Rendimientos financieros: depósito a plazo fijo exentos','0','332','323E2','1'),
			array(29,'Rendimientos financieros: operaciones de reporto - repos','2','323','323F','1'), array(30,'Inversiones (captaciones) rendimientos distintos de aquellos pagados a IFIs','2','323','323G','1'),
			array(31,'Rendimientos financieros: obligaciones','2','323','323H','1'), array(32,'Rendimientos financieros: bonos convertible en acciones','2','323','323I','1'),
			array(33,'Rendimientos financieros: Inversiones en títulos valores en renta fija gravados','2','323','323M','1'), array(34,'Rendimientos financieros: Inversiones en títulos valores en renta fija exentos','0','332','323N','1'),
			array(35,'Intereses y demás rendimientos financieros pagados a bancos y otras entidades sometidas al control de la Superintendencia de Bancos y de la Economía Popular y Solidaria','0','332','323O','1'), array(36,'Intereses pagados por entidades del sector público a favor de sujetos pasivos','2','323','323P','1'),
			array(37,'Otros intereses y rendimientos financieros gravados','2','323','323Q','1'), array(38,'Otros intereses y rendimientos financieros exentos','0','332','323R','1'),
			array(39,'Pagos y créditos en cuenta efectuados por el BCE y los depósitos centralizados de valores, en calidad de intermediarios, a instituciones del sistema financiero por cuenta de otras personas naturales y sociedades','2','323','323S','1'), array(40,'Rendimientos financieros originados en la deuda pública ecuatoriana','0','332','323T','1'),
			array(41,'Rendimientos financieros originados en títulos valores de obligaciones de 360 días o más para el financiamiento de proyectos públicos en asociación público-privada','0','332','323U','1'), array(42,'Intereses y comisiones en operaciones de crédito entre instituciones del sistema financiero y entidades economía popular y solidaria.','1','324','324A','1'),
			array(43,'Inversiones entre instituciones del sistema financiero y entidades economía popular y solidaria','1','324','324B','1'), array(44,'Pagos y créditos en cuenta efectuados por el BCE y los depósitos centralizados de valores, en calidad de intermediarios, a institutions del sistema financiero por cuenta de otras instituciones del sistema financiero','1','324','324C','1'),
			array(45,'Anticipo dividendos','22 ó 25','325','325','1'), array(46,'Préstamos accionistas, beneficiarios o partícipes residentes o establecidos en el Ecuador','22 ó 25','325','325A','1'),
			array(47,'Dividendos distribuidos que correspondan al impuesto a la renta único establecido en el art. 27 de la LRTI','Hasta 25 y conforme la Resolución NAC-DGERCGC20-000000013','326','326','1'), array(48,'Dividendos distribuidos a personas naturales residentes','Hasta 25 y conforme la Resolución NAC-DGERCGC20-000000013','327','327','1'),
			array(49,'Dividendos distribuidos a sociedades residentes','0','328','328','1'), array(50,'Dividendos distribuidos a fideicomisos residentes','0','329','329','1'),
			array(51,'Dividendos en acciones (capitalización de utilidades)','0','331','331','1'), array(52,'Otras compras de bienes y servicios no sujetas a retención (incluye régimen RIMPE - Negocios Populares para este caso aplica con cualquier forma de pago inclusive los pagos que deban realizar las tarjetas de crédito/débito)','0','332','332','1'),
			array(53,'Compra de bienes inmuebles','0','332','332B','1'), array(54,'Transporte público de pasajeros','0','332','332C','1'),
			array(55,'Pagos en el país por transporte de pasajeros o transporte internacional de carga, a compañías nacionales o extranjeras de aviación o marítimas','0','332','332D','1'), array(56,'Valores entregados por las cooperativas de transporte a sus socios','0','332','332E','1'),
			array(57,'Compraventa de divisas distintas al dólar de los Estados Unidos de América','0','332','332F','1'), array(58,'Pagos con tarjeta de crédito','0','332','332G','1'),
			array(59,'Pago al exterior tarjeta de crédito reportada por la Emisora de tarjeta de crédito, solo RECAP','0','332','332H','1'), array(60,'Pago a través de convenio de debito (Clientes IFI`s)','0','332','332I','1'),
			array(61,'Ganancia en la enajenación de derechos representativos de capital u otros derechos que permitan la exploración, explotación, concesión o similares de sociedades, que se coticen en bolsa de valores del Ecuador','10','333','333','1'), array(62,'Contraprestación producida por la enajenación de derechos representativos de capital u otros derechos que permitan la exploración, explotación, concesión o similares de sociedades, no cotizados en bolsa de valores del Ecuador','1','334','334','1'),
			array(63,'Loterías, rifas, pronosticos deportivos, apuestas y similares','15','335','335','1'), array(64,'Venta de combustibles a comercializadoras','2/mil','336','336','1'),
			array(65,'Venta de combustibles a distribuidores','3/mil','337','337','1'), array(66,'Producción y venta local de banano produzido o no por el mismo sujeto pasivo','1 - 2','3380','338','1'),
			array(67,'Impuesto único a la exportación de banano','3','3400','340','1'), array(68,'Otras retenciones aplicables el 1% (incluye régimen RIMPE - Emprendedores, para este caso aplica con cualquier forma de pago inclusive los pagos que deban realizar las tarjetas de crédito/débito)','1','343','343','1'),
			array(69,'Energía eléctrica','1','343','343A','1'), array(70,'Actividades de construcción de obra material inmueble, urbanización, lotización o actividades similares','1.75%','346','343B','1'),
			array(71,'Recepción de botellas plásticas no retornables de PET','2','343','343C','1'), array(72,'Otras retenciones aplicables el 2,75%','2.75','3440','3440','1'),
			array(73,'Pago local tarjeta de crédito /débito reportada por la Emisora de tarjeta de crédito / entidades del sistema financiero','2','344','344A','1'), array(74,'Adquisición de sustancias minerales dentro del territorio nacional','2','344','344B','1'),
			array(75,'Otras retenciones aplicables el 8%','8','345','345','1'), array(76,'Otras retenciones aplicables a otros porcentajes','varios porcentajes','346','346','1'),
			array(77,'Otras ganancias de capital distintas de enajenación de derechos representativos de capital','varios porcentajes','346','346A','1'), array(78,'Donaciones en dinero -Impuesto a la donaciones','Según art 36 LRTI literal d)','346','346B','1'),
			array(79,'Retención a cargo del propio sujeto pasivo por la exportación de concentrados y/o elementos metálicos','0 ó 10','346','346C','1'), array(80,'Retención a cargo del propio sujeto pasivo por la comercialización de productos forestales','0 ó 10','346','346D','1'),
			array(81,'Impuesto único a ingresos provenientes de actividades agropecuarias en etapa de producción / comercialización local o exportación','1','348','348','1'), array(82,'Impuesto a la renta único sobre los ingresos percibidos por los operadores de pronósticos deportivos (vigente desde 01/07/2024)','15','3480','3480','1'),
			array(83,'Autorretenciones Sociedades Grandes Contribuyentes','varios porcentajes','3481','3481','1'), array(84,'Comisiones  a sociedades, nacionales o extranjeras residentes y establecimientos permanentes domiciliados en el país','3','3140','3482','1'),
			array(85,'Otras autorretenciones (inciso 1 y 2 Art.92.1 RLRTI)','1,50 ó 1,75','350','350','1'), array(86,'Pago a no residentes - Rentas Inmobiliarias','25 ó 37','411,422,432','500','1'),
			array(87,'Pago a no residentes - Beneficios/Servicios  Empresariales','25 ó 37','411,422,432','501','1'), array(88,'Pago a no residentes - Servicios técnicos, administrativos o de consultoría y regalías','25 ó 37','410,421,431','501A','1'),
			array(89,'Pago a no residentes- Navegación Marítima y/o aérea','0 ó 25 ó 37','411,422,432','503','1'), array(90,'Pago a no residentes- Dividendos distribuidos a personas naturales (domicilados o no en paraiso fiscal) o a sociedades sin beneficiario efectivo persona natural residente en Ecuador','25','4050, 4160, 4260','504','1'),
			array(91,'Dividendos a sociedades con beneficiario efectivo persona natural residente en el Ecuador','Hasta 25 y conforme la Resolución NAC-DGERCGC20-000000013','4060, 4170','504A','1'), array(92,'Dividendos a no residentes incumpliendo el deber de informar la composición societaria','37','4070, 4180, 4280','504B','1'),
			array(93,'Dividendos a residentes o establecidos en paraísos fiscales o regímenes de menor imposición (con beneficiario Persona Natural residente en Ecuador)','Hasta 25 y conforme la Resolución NAC-DGERCGC20-000000013','4270','504C','1'), array(94,'Pago a no residentes - Dividendos a fideicomisos domiciladas en paraísos fiscales o regímenes de menor imposición (con beneficiario efectivo persona natural residente en el Ecuador)','Hasta 25 y conforme la Resolución NAC-DGERCGC20-000000013','4270','504D','1'),
			array(95,'Pago a no residentes - Anticipo dividendos (no domiciliada en paraísos fiscales o regímenes de menor imposición)','22 ó 25','404,415','504E','1'), array(96,'Pago a no residentes - Anticipo dividendos (domiciliadas en paraísos fiscales o regímenes de menor imposición)','22, 25 ó 28','425','504F','1'),
			array(97,'Pago a no residentes - Préstamos accionistas, beneficiarios o partìcipes (no domiciladas en paraísos fiscales o regímenes de menor imposición)','22 ó 25','404,415','504G','1'), array(98,'Pago a no residentes - Préstamos accionistas, beneficiarios o partìcipes (domiciladas en paraísos fiscales o regímenes de menor imposición)','22, 25 ó 28','425','504H','1'),
			array(99,'Pago a no residentes - Préstamos no comerciales a partes relacionadas  (no domiciladas en paraísos fiscales o regímenes de menor imposición)','22 ó 25','404,415','504I','1'), array(100,'Pago a no residentes - Préstamos no comerciales a partes relacionadas  (domiciladas en paraísos fiscales o regímenes de menor imposición)','22, 25 ó 28','425','504J','1'),
			array(101,'Pago a no residentes - Rendimientos financieros','25 ó 37','411,422,432','505','1'), array(102,'Pago a no residentes – Intereses de créditos de Instituciones Financieras del exterior','0 ó 25','403,414,424','505A','1'),
			array(103,'Pago a no residentes – Intereses de créditos de gobierno a gobierno','0 ó 25','403,414,424','505B','1'), array(104,'Pago a no residentes – Intereses de créditos de organismos multilaterales','0 ó 25','403,414,424','505C','1'),
			array(105,'Pago a no residentes - Intereses por financiamiento de proveedores externos','25','402,413,424','505D','1'), array(106,'Pago a no residentes - Intereses de otros créditos externos','25','411,422,432','505E','1'),
			array(107,'Pago a no residentes - Otros Intereses y Rendimientos Financieros','25 ó 37','411,422,432','505F','1'), array(108,'Pago a no residentes- Cánones, derechos de autor,  marcas, patentes y similares','25 ó 37','411,422,432','509','1'),
			array(109,'PPago a no residentes - Regalías por concepto de franquicias','25 ó 37','411,422,432','509A','1'), array(110,'Pago a no residentes - Otras ganancias de capital distintas de enajenación de derechos representativos de capital','5, 25, 37','411,422,432','510','1'),
			array(111,'Pago a no residentes - Servicios profesionales independientes','25 ó 37','411,422,432','511','1'), array(112,'Pago a no residentes - Servicios profesionales dependientes','25 ó 37','411,422,432','512','1'),
			array(113,'Pago a no residentes- Artistas','25 ó 37','411,422,432','513','1'), array(114,'Pago a no residentes - Deportistas','25 ó 37','411,422,432','513A','1'),
			array(115,'Pago a no residentes - Participación de consejeros','25 ó 37','411,422,432','514','1'), array(116,'Pago a no residentes - Entretenimiento Público','25 ó 37','411,422,432','515','1'),
			array(117,'Pago a no residentes - Pensiones','25 ó 37','411,422,432','516','1'), array(118,'Pago a no residentes- Reembolso de Gastos','25 ó 37','411,422,432','517','1'),
			array(119,'Pago a no residentes- Funciones Públicas','25 ó 37','411,422,432','518','1'), array(120,'Pago a no residentes - Estudiantes','25 ó 37','411,422,432','519','1'),
			array(121,'Pago a no residentes - Pago a proveedores de servicios hoteleros y turísticos en el exterior','25 ó 37','411,422,432','520A','1'), array(122,'Pago a no residentes - Arrendamientos mercantil internacional','0, 25, 37','411,422,432','520B','1'),
			array(123,'Pago a no residentes - Comisiones por exportaciones y por promoción de turismo receptivo','0, 25, 37','411,422,432','520D','1'), array(124,'Pago a no residentes - Por las empresas de transporte marítimo o aéreo y por empresas pesqueras de alta mar, por su actividad.','0','411,422,432','520E','1'),
			array(125,'Pago a no residentes - Por las agencias internacionales de prensa','0, 25, 37','411,422,432','520F','1'), array(126,'Pago a no residentes - Contratos de fletamento de naves para empresas de transporte aéreo o marítimo internacional','0, 25, 37','411,422,432','520G','1'),
			array(127,'Pago a no residentes - Enajenación de derechos representativos de capital u otros derechos que permitan la exploración, explotación, concesión o similares de sociedades','1, 10','408,419,429','521','1'), array(128,'Pago a no residentes - Seguros y reaseguros (primas y cesiones)','0, 25, 37','409,420,430','523A','1'),
			array(129,'Pago a no residentes- Donaciones en dinero -Impuesto a las donaciones','Según art 36 LRTI literal d)','411,422,432','525','1'), array(130,'30.00 %','30','0','1','2'),
			array(131,'70.00 %','70','0','2','2'), array(132,'100.00 %','100','0','3','2'), array(133,'100% VALOR RETENIDO DISTRIBUIDOR','100','0','5','2'),
			array(134,'100% VALOR VOCEADORES VARIOS','100','0','6','2'), array(135,'0.00 %','0','0','7','2'), array(136,'0 % NO PROCEDE RETENCION','0','0','8','2'),
			array(137,'10.00 %','10','0','9','2'), array(138,'20.00 %','20','0','10','2')
		);

		foreach ($incomeData as $row) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."income (rowid, detail, value, form, code, type) VALUES (";
			$sql .= $row[0].", '".$this->db->escape($row[1])."', '".$this->db->escape($row[2])."', '".$this->db->escape($row[3])."', '".$this->db->escape($row[4])."', '".$this->db->escape($row[5])."')";
			$this->db->query($sql);
		}
	}

	private function _drop_tables_and_columns()
	{
		$this->db->ddl->dropTable(MAIN_DB_PREFIX.'order_info'); $this->db->ddl->dropTable(MAIN_DB_PREFIX.'user_info'); $this->db->ddl->dropTable(MAIN_DB_PREFIX.'vendor'); $this->db->ddl->dropTable(MAIN_DB_PREFIX.'income');
		$this->db->ddl->dropColumn(MAIN_DB_PREFIX.'commande_fournisseur', 'claveacceso');
		$all_columns_to_drop = [
			'commande' => ['c_note1','c_name1','c_note2','c_name2','c_note3','c_name3','c_note4','c_name4','c_note5','c_name5','c_note6','c_name6','c_note7','c_name7','identification_type','identification_c_type','tip','invoice_number','warehouse','seller','reason_type','ws_approval_one','ws_approval_two','ws_time','claveacceso','ws_approval_thr','ws_approval_fou','ws_time_end','claveacceso_end','start_date','end_date','carrier'],
			'expedition' => ['c_note1','c_name1','c_note2','c_name2','c_note3','c_name3','c_note4','c_name4','c_note5','c_name5','c_note6','c_name6','c_note7','c_name7','identification_type','identification_c_type','tip','invoice_number','warehouse','seller','reason_type','ws_approval_one','ws_approval_two','ws_time','claveacceso','ws_approval_thr','ws_approval_fou','ws_time_end','claveacceso_end','start_date','end_date','carrier'],
			'facture' => ['c_note1','c_name1','c_note2','c_name2','c_note3','c_name3','c_note4','c_name4','c_note5','c_name5','identification_type','tip','invoice_number','warehouse','seller','reason_type','ws_approval_one','ws_approval_two','ws_time','claveacceso','ws_approval_thr','ws_approval_fou','ws_time_end','claveacceso_end'],
			'facture_fourn' => ['f_note1','f_name1','f_note2','f_name2','f_note3','f_name3','f_note4','f_name4','f_note5','f_name5','f_note6','f_name6','f_note7','f_name7','identification_type','tip','invoice_number','warehouse','seller','reason_type','ws_approval_one','ws_approval_two','ws_time','claveacceso','ws_approval_thr','ws_approval_fou','ws_time_end','claveacceso_end','date_done','date_create','modify']
		];
		foreach ($all_columns_to_drop as $table => $cols) {
			foreach ($cols as $col) { $this->db->ddl->dropColumn(MAIN_DB_PREFIX.$table, $col); }
		}
	}
    
    private function _cleanup_extrafields()
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        $extrafields_to_delete = [
            'facture' => ['approval_c_note1', 'approval_c_name1', 'approval_identification_type', 'approval_tip', 'approval_invoice_number', 'approval_warehouse', 'approval_seller', 'approval_reason_type', 'approval_ws_approval_one', 'approval_ws_approval_two', 'approval_claveacceso'],
            'commande' => ['approval_c_note1', 'approval_c_name1', 'approval_identification_type', 'approval_tip', 'approval_invoice_number', 'approval_warehouse', 'approval_seller', 'approval_reason_type', 'approval_claveacceso'],
            'facture_fourn' => ['approval_claveacceso'],
            'commande_fournisseur' => ['approval_claveacceso']
        ];

        foreach ($extrafields_to_delete as $elementtype => $fieldlist) {
            foreach ($fieldlist as $fieldname) {
                // Delete from llx_extrafields
                $sql_delete_extrafield = "DELETE FROM ".MAIN_DB_PREFIX."extrafields WHERE name = '".$this->db->escape($fieldname)."' AND elementtype = '".$this->db->escape($elementtype)."'";
                $this->db->query($sql_delete_extrafield);

                // Drop column from llx_*_extrafields table
                $this->db->ddl->dropColumn(MAIN_DB_PREFIX.$elementtype."_extrafields", $fieldname);
            }
        }
    }
}
