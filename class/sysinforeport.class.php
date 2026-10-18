<?php
/* Copyright (C) 2017       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2025  Frédéric France          <frederic.france@free.fr>
 * Copyright (C) 2026		Anthony BERTON				<anthony.berton@bb2a.fr>
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
 * \file        sysinfoclass/sysinforeport.class.php
 * \ingroup     sysinfo
 * \brief       This file is a CRUD class file for SysinfoReport (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for SysinfoReport
 */
class SysinfoReport extends CommonObject
{
	/**
	 * @var string 		ID of module.
	 */
	public $module = 'sysinfo';

	/**
	 * @var string 		ID to identify managed object.
	 */
	public $element = 'sysinforeport';

	/**
	 * @var string		Prefix to check for any trigger code of any business class to prevent bad value for trigger code.
	 * @see CommonTrigger::call_trigger()
	 */
	public $TRIGGER_PREFIX = 'SYSINFO_SYSINFOREPORT';	// Will be used to build trgiger keys 'SYSINFO_SYSINFOREPORT_MODIFY', ...

	/**
	 * @var string 		Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'sysinfo_sysinforeport';

	/**
	 * @var string 		If permission must be checked with hasRight('sysinfo', 'read') and not hasright('sysinfo', 'sysinforeport', 'read'), you can uncomment this line
	 */
	//public $element_for_permission = 'sysinfo';

	/**
	 * @var string 		String with name of icon for sysinforeport. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'sysinforeport@sysinfo' if picto is file 'img/object_sysinforeport.png'.
	 */
	public $picto = 'file-circle-question';

	/**
	 * @var int<0,1>	Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var int<0,1>|string		Does this object support multicompany module ?
	 * 							0=No test on entity, 1=Test with field entity in local table, 'field@table'=Test entity into the field@table (example 'fk_soc@societe')
	 */
	public $ismultientitymanaged = 0;


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'email', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:>:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'length' the length of field. Example: 255, '24,8'
	 *  'label' the translation key.
	 *  'langfile' the key of the language file for translation.
	 *  'alias' the alias used into some old hard coded SQL requests
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form (not create). 5=Visible on list and view form (not create/not update). 6=visible on list and update/view form (not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwritten by the Setup of Default Values if the field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'placeholder' to set the placeholder of a varchar field.
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code like the constructor of the class.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *  'description' is a description of the field. Can be used to help the MCP server.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @inheritdoc
	 * Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type" => "integer", "label" => "TechnicalID", "enabled" => "1", 'position' => 1, 'notnull' => 1, "visible" => "0", "noteditable" => "1", "index" => "1", "css" => "left", "comment" => "Id"),
		"ref" => array("type" => "varchar(128)", "label" => "Ref", "enabled" => "1", 'position' => 10, 'notnull' => 1, "visible" => "1", "index" => "1", "searchall" => "1", "showoncombobox" => "1", "validate" => "1", "comment" => "Reference"),
		"guid" => array("type" => "varchar(128)", "label" => "GUID", "enabled" => "1", 'position' => 15, 'notnull' => 1, "visible" => "1", "index" => "1", "validate" => "1", "comment" => "Global Unique Identifier"),
		"fk_soc" => array("type" => "integer:Societe:societe/class/societe.class.php:1", "label" => "ThirdParty", "picto" => "company", "enabled" => "isModEnabled('societe')", 'position' => 20, 'notnull' => 0, "visible" => "1", "index" => "1", "css" => "maxwidth500", "validate" => "1",),
		"hostname" => array("type" => "varchar(255)", "label" => "Hostname", "enabled" => "1", 'position' => 30, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"fqdn" => array("type" => "varchar(255)", "label" => "FQDN", "enabled" => "1", 'position' => 35, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"os" => array("type" => "varchar(255)", "label" => "OS", "enabled" => "1", 'position' => 40, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"os_version" => array("type" => "varchar(64)", "label" => "OSVersion", "enabled" => "1", 'position' => 45, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"os_build" => array("type" => "varchar(64)", "label" => "OSBuild", "enabled" => "1", 'position' => 50, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"architecture" => array("type" => "varchar(32)", "label" => "Architecture", "enabled" => "1", 'position' => 55, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"processor" => array("type" => "varchar(255)", "label" => "Processor", "enabled" => "1", 'position' => 60, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"cpu_logical" => array("type" => "integer", "label" => "CPULogical", "enabled" => "1", 'position' => 70, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"cpu_physical" => array("type" => "integer", "label" => "CPUPhysical", "enabled" => "1", 'position' => 75, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"cpu_percent" => array("type" => "double", "label" => "CPUPercent", "enabled" => "1", 'position' => 80, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"memory_total" => array("type" => "bigint", "label" => "MemoryTotal", "enabled" => "1", 'position' => 90, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"memory_available" => array("type" => "bigint", "label" => "MemoryAvailable", "enabled" => "1", 'position' => 95, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"memory_percent" => array("type" => "double", "label" => "MemoryPercent", "enabled" => "1", 'position' => 100, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"disk_total" => array("type" => "bigint", "label" => "DiskTotal", "enabled" => "1", 'position' => 110, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"disk_used" => array("type" => "bigint", "label" => "DiskUsed", "enabled" => "1", 'position' => 115, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"disk_free" => array("type" => "bigint", "label" => "DiskFree", "enabled" => "1", 'position' => 120, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"disk_percent" => array("type" => "double", "label" => "DiskPercent", "enabled" => "1", 'position' => 125, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"ip_address" => array("type" => "varchar(64)", "label" => "IPAddress", "enabled" => "1", 'position' => 130, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"public_ip" => array("type" => "varchar(64)", "label" => "PublicIP", "enabled" => "1", 'position' => 135, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"isp" => array("type" => "varchar(128)", "label" => "ISP", "enabled" => "1", 'position' => 140, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"mac_address" => array("type" => "varchar(32)", "label" => "MACAddress", "enabled" => "1", 'position' => 145, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"firewall_enabled" => array("type" => "boolean", "label" => "FirewallEnabled", "enabled" => "1", 'position' => 150, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"pending_updates" => array("type" => "integer", "label" => "PendingUpdates", "enabled" => "1", 'position' => 155, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"last_update_date" => array("type" => "datetime", "label" => "LastUpdateDate", "enabled" => "1", 'position' => 160, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"softwares_missing" => array("type" => "integer", "label" => "SoftwaresMissing", "enabled" => "1", 'position' => 165, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"teamviewer_installed" => array("type" => "boolean", "label" => "TeamViewerInstalled", "enabled" => "1", 'position' => 170, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"teamviewer_id" => array("type" => "varchar(32)", "label" => "TeamViewerID", "enabled" => "1", 'position' => 175, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"rustdesk_installed" => array("type" => "boolean", "label" => "RustDeskInstalled", "enabled" => "1", 'position' => 180, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"rustdesk_id" => array("type" => "varchar(32)", "label" => "RustDeskID", "enabled" => "1", 'position' => 185, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"anydesk_installed" => array("type" => "boolean", "label" => "AnyDeskInstalled", "enabled" => "1", 'position' => 190, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"anydesk_id" => array("type" => "varchar(32)", "label" => "AnyDeskID", "enabled" => "1", 'position' => 195, 'notnull' => 0, "visible" => "1", "validate" => "1",),
		"report" => array("type" => "text", "label" => "ReportText", "enabled" => "1", 'position' => 200, 'notnull' => 0, "visible" => "0", "validate" => "1",),
		"note_public" => array("type" => "html", "label" => "NotePublic", "enabled" => "1", 'position' => 210, 'notnull' => 0, "visible" => "0", "cssview" => "wordbreak", "validate" => "1",),
		"note_private" => array("type" => "html", "label" => "NotePrivate", "enabled" => "1", 'position' => 220, 'notnull' => 0, "visible" => "0", "cssview" => "wordbreak", "validate" => "1",),
		"date_creation" => array("type" => "datetime", "label" => "DateCreation", "enabled" => "1", 'position' => 500, 'notnull' => 1, "visible" => "-2",),
		"tms" => array("type" => "timestamp", "label" => "DateModification", "enabled" => "1", 'position' => 501, 'notnull' => 0, "visible" => "-2",),
		"fk_user_creat" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserAuthor", "picto" => "user", "enabled" => "1", 'position' => 510, 'notnull' => 1, "visible" => "-2", "csslist" => "tdoverflowmax150",),
		"fk_user_modif" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserModif", "picto" => "user", "enabled" => "1", 'position' => 511, 'notnull' => 0, "visible" => "-2", "csslist" => "tdoverflowmax150",),
		"last_main_doc" => array("type" => "varchar(255)", "label" => "LastMainDoc", "enabled" => "1", 'position' => 600, 'notnull' => 0, "visible" => "0",),
		"import_key" => array("type" => "varchar(14)", "label" => "ImportKey", "enabled" => "1", 'position' => 1000, 'notnull' => 0, "visible" => "-2",),
		"model_pdf" => array("type" => "varchar(255)", "label" => "ModelPDF", "enabled" => "1", 'position' => 1010, 'notnull' => 0, "visible" => "0",),
		"status" => array("type" => "integer", "label" => "Status", "enabled" => "1", 'position' => 2000, 'notnull' => 1, "visible" => "1", "index" => "1", "arrayofkeyval" => array("0" => "Brouillon", "1" => "Valid&eacute;", "9" => "Annul&eacute;"), "validate" => "1",),
	);
	public $rowid;
	public $ref;
	public $guid;
	public $fk_soc;
	public $hostname;
	public $fqdn;
	public $os;
	public $os_version;
	public $os_build;
	public $architecture;
	public $processor;
	public $cpu_logical;
	public $cpu_physical;
	public $cpu_percent;
	public $memory_total;
	public $memory_available;
	public $memory_percent;
	public $disk_total;
	public $disk_used;
	public $disk_free;
	public $disk_percent;
	public $ip_address;
	public $public_ip;
	public $isp;
	public $mac_address;
	public $firewall_enabled;
	public $pending_updates;
	public $last_update_date;
	public $softwares_missing;
	public $teamviewer_installed;
	public $teamviewer_id;
	public $trustdesk_installed;
	public $trustdesk_id;
	public $anydesk_installed;
	public $anydesk_id;
	public $report;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;

	// END MODULEBUILDER PROPERTIES

	/**
	 * Constructor
	 *
	 * @param	DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('sysinfo', 'sysinforeport', 'read')) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param	User		$user		User that creates
	 * @param	int<0,1> 	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int<-1,max>				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		$result = $this->createCommon($user, $notrigger);

		// uncomment lines below if you want to validate object after creation
		// if ($result > 0) {
		// $this->fetch($this->id); // needed to retrieve some fields (ie date_creation for masked ref)
		// $resultupdate = $this->validate($user, $notrigger);
		// if ($resultupdate < 0) { return $resultupdate; }
		// }

		return $result;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param	User 	$user		User that creates
	 * @param	int 	$fromid		Id of object to clone
	 * @return	self|int<-1,-1>		New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key);
					//var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->setErrorsFromObject($object);
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (!empty($object->socid) && ((property_exists($this, 'fk_soc') && ($this->fk_soc == $object->socid)) || (property_exists($this, 'socid') && ($this->socid == $object->socid)))) {	// @phpstan-ignore-line
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int    		$id   			Id object
	 * @param	string 		$ref  			Ref
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int<0,1>	$nolines		0=Default to load lines, 1=No lines
	 * @return	int<-1,1>					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $ref, '', $noextrafields);
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @return 	int<-1,1>					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines($noextrafields = 0)
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon('', $noextrafields);
		return $result;
	}
	//END MODULEBUILDER LINES
	/**
	 * Load list of objects in memory from the database.
	 * Using a fetchAll() with limit = 0 is a very bad practice. Instead try to forge yourself an optimized SQL request with
	 * your own loop with start and stop pagination.
	 *
	 * @param	string		$sortorder	Sort Order
	 * @param	string		$sortfield	Sort field
	 * @param	int<0,max>	$limit		Limit the number of lines returned
	 * @param	int<0,max>	$offset		Offset
	 * @param	string		$filter		Filter as an Universal Search string.
	 *                                  Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param	string		$filtermode	No longer used
	 * @return	array<int,self>|int<-1,-1>	 <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 1000, $offset = 0, string $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		if (!empty($this->ismultientitymanaged) && (int) $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} elseif (preg_match('/^\w+@\w+$/', (string) $this->ismultientitymanaged)) {
			$tmparray = explode('@', (string) $this->ismultientitymanaged);
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->db->sanitize($tmparray[1])." as pt ON t.".$this->db->sanitize($tmparray[0])." = pt.rowid";
			$sql .= " WHERE pt.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				if (!empty($record->isextrafieldmanaged)) {
					$record->fetch_optionals();
				}

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param	User		$user		User that modifies
	 * @param	int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param	User		$user		User that deletes
	 * @param	int<0,1> 	$notrigger	0=launch triggers, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *	Validate object
	 *
	 *	@param	User		$user		User making status change
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int<-1,1>				Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo', 'sysinforeport', 'write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo', 'sysinforeport_advance', 'validate')))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = (string) $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE ".$this->db->prefix().$this->table_element;
			$sql .= " SET ";
			if (!empty($this->fields['ref'])) {
				$sql .= " ref = '".$this->db->escape($num)."',";
			}
			$sql .= " status = ".self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('SYSINFOREPORT_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'sysinforeport/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'sysinforeport/".$this->db->escape($this->ref)."' and entity = ".((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filepath = 'sysinforeport/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'sysinforeport/".$this->db->escape($this->ref)."' and entity = ".((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->sysinfo->dir_output.'/sysinforeport/'.$oldref;
				$dirdest = $conf->sysinfo->dir_output.'/sysinforeport/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->sysinfo->dir_output.'/sysinforeport/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set draft status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<0,1>				Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo','sysinfo_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'SYSINFO_SYSINFOREPORT_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>				Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo','sysinfo_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'SYSINFO_SYSINFOREPORT_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User		$user			Object user that modify
	 *  @param	int<0,1>	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('sysinfo','sysinfo_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'SYSINFO_SYSINFOREPORT_REOPEN');
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param	array<string,string> 	$params 	Params to construct tooltip data
	 * @since 	v18
	 * @return	array{optimize?:string,picto?:string,ref?:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowSysinfoReport")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("SysinfoReport").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		if (property_exists($this, 'ref')) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (property_exists($this, 'label')) {
			$datas['label'] = '<br>'.$langs->trans('Label').':</b> '.$this->label;
		}

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param	int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param	string  $option                     On what the link point to ('nolink', ...)
	 *  @param	int     $notooltip                  1=Disable tooltip
	 *  @param	string  $morecss                    Add more css on link
	 *  @param	int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => (string) $this->id,
			'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$baseurl = dol_buildpath('/sysinfo/sysinforeport_card.php', 1);
		$query = ['id' => $this->id];
		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$query = array_merge($query, ['save_lastsearch_values' => 1]);
			}
		}
		$url = dolBuildUrl($baseurl, $query);

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowSysinfoReport");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink') {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (!getDolGlobalString(strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS')) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array($this->element.'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *	Return a thumb for kanban views
	 *
	 *	@param	string	    			$option		Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	?array<string,mixed>	$arraydata	Array of data
	 *  @return	string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= ' <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">'.$this->label.'</div>';
		}
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br>';
			$return .= '<span class="info-box-label amount">'.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param	int<0,6>	$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param	int<0,6>	$mode	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string				Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int			$status		Id status
	 *  @param	int<0,6>	$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string					Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (is_null($status)) {
			return '';
		}

		$paramsBadge = array('badgeParams' => array('attr' => array(
			'data-status-element' => $this->element,
			'data-status' => (int) $status
		)));


		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("sysinfo@sysinfo");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode, '', $paramsBadge);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param	int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT t.rowid, t.date_creation as datec";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= ", GREATEST(t.tms, COALESCE(te.tms, t.tms)) as datem";
		} else {
			$sql .= ", t.tms as datem";
		}
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", t.date_validation as datev";
		}
		if (!empty($this->fields['fk_user_creat'])) {
			$sql .= ", t.fk_user_creat";
		}
		if (!empty($this->fields['fk_user_modif'])) {
			$sql .= ", t.fk_user_modif";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", t.fk_user_valid";
		}
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if (!empty($this->fields['fk_user_creat'])) {
					$this->user_creation_id = $obj->fk_user_creat;
				}
				if (!empty($this->fields['fk_user_modif'])) {
					$this->user_modification_id = $obj->fk_user_modif;
				}
				if (!empty($this->fields['fk_user_valid'])) {
					$this->user_validation_id = $obj->fk_user_valid;
				}
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
				if (!empty($obj->datev)) {
					$this->date_validation = empty($obj->datev) ? '' : $this->db->jdate($obj->datev);
				}
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialize object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return	int
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		return $this->initAsSpecimenCommon();
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return	string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("sysinfo@sysinfo");

		if (!getDolGlobalString('SYSINFO_SYSINFOREPORT_ADDON')) {
			$conf->global->SYSINFO_SYSINFOREPORT_ADDON = 'mod_sysinforeport_standard';
		}

		if (getDolGlobalString('SYSINFO_SYSINFOREPORT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('SYSINFO_SYSINFOREPORT_ADDON').".php";
			$classname = getDolGlobalString('SYSINFO_SYSINFOREPORT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/sysinfo/");

				// Load file with numbering class (if found)
				$mybool = $mybool || @include_once $dir.$file;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				'@phan-var-force ModeleNumRefSysinfoReport $obj';
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	string		$modele			Force template to use ('' to not force)
	 *  @param	Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param	int<0,1>	$hidedetails    Hide details of lines
	 *  @param	int<0,1>	$hidedesc       Hide description
	 *  @param	int<0,1>	$hideref        Hide ref
	 *  @param	?array<string,string>  $moreparams     Array to provide more information
	 *  @return	int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $langs;

		$result = 0;
		$includedocgeneration = 1;

		$langs->load("sysinfo@sysinfo");

		if (!dol_strlen($modele)) {
			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} else {
				$modele = getDolGlobalString('SYSINFOREPORT_ADDON_PDF', 'standard_sysinforeport');
			}
		}

		$modelpath = "core/modules/sysinfo/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Return validation test result for a field.
	 * Need MAIN_ACTIVATE_VALIDATION_RESULT to be called.
	 *
	 * @param   array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:int<0,1>|string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>  $fields Array of properties of field to show
	 * @param	string  $fieldKey            Key of attribute
	 * @param	string  $fieldValue          value of attribute
	 * @return	bool 						Return false if fail, true on success, set $this->error for error message
	 */
	public function validateField($fields, $fieldKey, $fieldValue)
	{
		// Add your own validation rules here.
		// ...

		return parent::validateField($fields, $fieldKey, $fieldValue);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		//global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlogfile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__." start", LOG_INFO);

		//$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		dol_syslog(__METHOD__." end", LOG_INFO);

		return $error;
	}

	// Vibe class functions

	/**
	* Build the column values of this object from an agent payload.
	*
	* @param array $payload Report sent by the agent (with 'softwares' matched)
	* @return void
	*/
	public function setFromPayload($payload)
	{
		$os = isset($payload['os_details']) ? $payload['os_details'] : array();

		$this->fk_soc = isset($payload['fk_soc']) ? (int) $payload['fk_soc'] : null; // null: preserve existing assignment on update
		$this->hostname = isset($payload['hostname']) ? $payload['hostname'] : null;
		$this->fqdn = isset($payload['fqdn']) ? $payload['fqdn'] : null;
		$this->os = isset($os['ProductName']) ? $os['ProductName'] : null;
		$this->os_version = isset($payload['platform_version']) ? $payload['platform_version'] : null;
		$this->os_build = isset($os['CurrentBuild']) ? $os['CurrentBuild'] : null;
		$this->architecture = isset($payload['architecture']) ? $payload['architecture'] : null;
		$this->processor = isset($payload['processor']) ? $payload['processor'] : null;

		$cpu = isset($payload['cpu']) ? $payload['cpu'] : array();
		$this->cpu_logical = isset($cpu['count_logical']) ? (int) $cpu['count_logical'] : null;
		$this->cpu_physical = isset($cpu['count_physical']) ? (int) $cpu['count_physical'] : null;
		$this->cpu_percent = isset($cpu['percent_used']) ? (double) $cpu['percent_used'] : null;

		$mem = isset($payload['memory']) ? $payload['memory'] : array();
		$this->memory_total = isset($mem['total_bytes']) ? (int) $mem['total_bytes'] : null;
		$this->memory_available = isset($mem['available_bytes']) ? (int) $mem['available_bytes'] : null;
		$this->memory_percent = isset($mem['percent_used']) ? (double) $mem['percent_used'] : null;

		// Main (system) disk = first entry, fallback C:\
		$disk = null;
		if (isset($payload['disks']) && is_array($payload['disks'])) {
			foreach ($payload['disks'] as $d) {
				if (isset($d['mountpoint']) && stripos($d['mountpoint'], 'C:') !== false) {
					$disk = $d;
					break;
				}
			}
			if (!$disk && count($payload['disks']) > 0) {
				$disk = $payload['disks'][0];
			}
		}
		if ($disk) {
			$this->disk_total = isset($disk['total_bytes']) ? (int) $disk['total_bytes'] : null;
			$this->disk_used = isset($disk['used_bytes']) ? (int) $disk['used_bytes'] : null;
			$this->disk_free = isset($disk['free_bytes']) ? (int) $disk['free_bytes'] : null;
			$this->disk_percent = isset($disk['percent_used']) ? (double) $disk['percent_used'] : null;
		}

		// First IPv4 address found
		if (isset($payload['network_interfaces']) && is_array($payload['network_interfaces'])) {
			foreach ($payload['network_interfaces'] as $nic) {
				foreach ((isset($nic['addresses']) ? $nic['addresses'] : array()) as $addr) {
					if (isset($addr['family']) && $addr['family'] == '2' && isset($addr['address'])) {
						$this->ip_address = $addr['address'];
						break 2;
					}
				}
			}
		}
		$this->public_ip = isset($payload['public_ip']) ? $payload['public_ip'] : null;
		$this->isp = isset($payload['isp']) ? $payload['isp'] : null;
		$macs = isset($payload['mac_addresses']) ? $payload['mac_addresses'] : array();
		$this->mac_address = !empty($macs) ? $macs[0] : null;

		$fw = isset($payload['firewall']) ? $payload['firewall'] : array();
		$this->firewall_enabled = isset($fw['enabled']) ? (int) $fw['enabled'] : null;

		$upd = isset($payload['updates']) ? $payload['updates'] : array();
		$this->pending_updates = isset($upd['pending_count']) ? (int) $upd['pending_count'] : null;
		if (isset($upd['last_update']['date']) && $upd['last_update']['date']) {
			$ts = strtotime($upd['last_update']['date']);
			if ($ts) {
				$this->last_update_date = $ts;
			}
		}

		$missing = 0;
		if (isset($payload['softwares']) && is_array($payload['softwares'])) {
			foreach ($payload['softwares'] as $sw) {
				if (isset($sw['installed']) && !$sw['installed']) {
					$missing++;
				}
			}
		}
		$this->softwares_missing = $missing;

		$tv = isset($payload['teamviewer']) ? $payload['teamviewer'] : array();
		$this->teamviewer_installed = isset($tv['installed']) ? (int) $tv['installed'] : null;
		$this->teamviewer_id = isset($tv['id']) ? (string) $tv['id'] : null;

		$rd = isset($payload['rustdesk']) ? $payload['rustdesk'] : array();
		$this->rustdesk_installed = isset($rd['installed']) ? (int) $rd['installed'] : null;
		$this->rustdesk_id = isset($rd['id']) ? (string) $rd['id'] : null;

		$ad = isset($payload['anydesk']) ? $payload['anydesk'] : array();
		$this->anydesk_installed = isset($ad['installed']) ? (int) $ad['installed'] : null;
		$this->anydesk_id = isset($ad['id']) ? (string) $ad['id'] : null;
	}

	/**
	* Save a report: update the existing row for this machine (guid),
	* or create it on first report (upsert, one row per machine).
	*
	* @param User $user User that saves
	* @return int Row id (< 0 on error)
	*/
	public function save($user)
	{
		$existing = new SysinfoReport($this->db);
		$found = $existing->fetchLatest($this->guid);
		if ($found < 0) {
			return -1;
		}

		$now = dol_now();

		if ($found == 1) {
			// Preserve the existing thirdparty assignment: the agent never
			// sends fk_soc, assignment is done from Dolibarr (putMachineSoc).
			$this->fk_soc = $existing->fk_soc;

			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
			$sql .= $this->_columnSql();
			$sql .= ", date_creation = '" . $this->db->idate($now) . "'";
			$sql .= " WHERE rowid = " . (int) $existing->id;

			if ($this->db->query($sql)) {
				$this->id = $existing->id;
				return $this->id;
			}
			return -1;
		}

		$cols = array_keys(self::$columns);
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " (guid, " . implode(", ", $cols) . ", report, date_creation)";
		$sql .= " VALUES ('" . $this->db->escape($this->guid) . "', ";
		$sql .= $this->_columnSql(true) . ", ";
		$sql .= "'" . $this->db->escape($this->report) . "', ";
		$sql .= "'" . $this->db->idate($now) . "')";

		if ($this->db->query($sql)) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			return $this->id;
		}
		return -1;
	}

	/**
	* Build the SQL fragment for metric columns.
	*
	* @param  bool $valuesOnly true for INSERT VALUES (no "col =" prefix)
	* @return string
	*/
	private function _columnSql($valuesOnly = false)
	{
		$parts = array();
		foreach (self::$columns as $col => $type) {
			$val = $this->$col;
			$sqlVal = 'NULL';
			if ($val !== null && $val !== '') {
				switch ($type) {
					case 'int':
						$sqlVal = (string) (int) $val;
						break;
					case 'double':
						$sqlVal = (string) (double) $val;
						break;
					case 'date':
						$sqlVal = "'" . $this->db->idate($val) . "'";
						break;
					default:
						$sqlVal = "'" . $this->db->escape($val) . "'";
				}
			}
			$parts[] = $valuesOnly ? $sqlVal : $col . " = " . $sqlVal;
		}
		if ($valuesOnly) {
			return implode(", ", $parts);
		}
		return implode(", ", $parts);
	}

	/**
	* Fetch the latest report for a given guid.
	*
	* @param  string $guid Machine identifier
	* @return int    1 if found, 0 if not found, < 0 on error
	*/
	public function fetchLatest($guid)
	{
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE guid = '" . $this->db->escape($guid) . "'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return 0;
		}

		$this->id = $obj->rowid;
		$this->guid = $obj->guid;
		$this->fk_soc = $obj->fk_soc;
		$this->hostname = $obj->hostname;
		$this->os = $obj->os;
		$this->report = $obj->report;
		$this->date_creation = $this->db->jdate($obj->date_creation);
		return 1;
	}

	/**
	* Fetch the latest report for a given thirdparty (fk_soc).
	*
	* @param  int $fk_soc Thirdparty id
	* @return int 1 if found, 0 if not found, < 0 on error
	*/
	public function fetchLatestBySoc($fk_soc)
	{
		$sql = "SELECT rowid, guid, fk_soc, hostname, os, report, date_creation";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE fk_soc = " . (int) $fk_soc;
		$sql .= " ORDER BY date_creation DESC, rowid DESC";
		$sql .= " LIMIT 1";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return 0;
		}

		$this->id = $obj->rowid;
		$this->guid = $obj->guid;
		$this->fk_soc = $obj->fk_soc;
		$this->hostname = $obj->hostname;
		$this->os = $obj->os;
		$this->report = $obj->report;
		$this->date_creation = $this->db->jdate($obj->date_creation);
		return 1;
	}

	/**
	* Fetch all machines (reports) assigned to a thirdparty.
	*
	* @param  int $fk_soc Thirdparty id
	* @return array Array of report rows (raw objects), empty on error
	*/
	public function fetchAllBySoc($fk_soc)
	{
		$sql = "SELECT rowid, guid, fk_soc, hostname, os, report, date_creation";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE fk_soc = " . (int) $fk_soc;
		$sql .= " ORDER BY hostname, guid";

		$rows = array();
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $rows;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = $obj;
		}
		return $rows;
	}

	/**
	* List all machines (one row per machine since save() is an upsert).
	*
	* @param  string $where Optional SQL filter (already sanitized)
	* @return array Array of report rows (raw objects), empty on error
	*/
	public function listLatestPerMachine($where = '')
	{
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element . " AS t";
		if ($where) {
			$sql .= " WHERE " . $where;
		}
		$sql .= " ORDER BY t.guid";

		$rows = array();
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $rows;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = $obj;
		}
		return $rows;
	}
}


/**
 * Class for SysinfoSoftware
 * Vibe code
 */
class SysinfoSoftware extends SysinfoReport
{
	public $element = 'sysinfosoftware';
	public $table_element = 'c_sysinfo_softwares';

	public $ref;
	public $label;
	public $pattern;
	public $active;

	public $ismultientitymanaged = 0;

	public $fields = array(
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'showoncombobox' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 20, 'notnull' => 1),
		'pattern' => array('type' => 'varchar(255)', 'label' => 'SearchPattern', 'enabled' => 1, 'position' => 30),
		'active' => array('type' => 'tinyint', 'label' => 'Active', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'default' => 1),
	);
}

/**
* ISP detection and logo helper.
* Vibe code
*/
class SysinfoIspLogo extends SysinfoReport
{
	/**
	* Known ISPs: pattern matched (case-insensitive, accent-insensitive)
	* against the 'org' string => logo file in img/isp/.
	*
	* @var array
	*/
	protected static $isps = array(
		'orange'    => '/orange/i',
		'free'      => '/free\s*(sas|s\.a\.s)?|proxad|iliad/i',
		'sfr'       => '/sfr|altice/i',
		'bouygues'  => '/bouygues|bbox/i',
		'numericable' => '/numericable/i',
		'walt'      => '/walt/i',
		'virgin'    => '/virgin/i',
		'ovh'       => '/ovh|ovhcloud|kimsufi/i',
		'scaleway'  => '/scaleway/i',
		'amazon'    => '/amazon|aws/i',
		'google'    => '/google/i',
		'microsoft' => '/microsoft|azure/i',
		'hetzner'   => '/hetzner/i',
		'contabo'   => '/contabo/i',
		'adeli'     => '/adeli/i',
		'axione'    => '/axione/i',
		'covage'    => '/covage/i',
	);

	/**
	* Detect the ISP key from the raw 'org' string.
	*
	* @param  string $isp Raw ISP string (e.g. 'AS1234 Orange')
	* @return string|null ISP key matching a logo, null otherwise
	*/
	public static function detect($isp)
	{
		if (empty($isp)) {
			return null;
		}
		foreach (self::$isps as $key => $pattern) {
			if (preg_match($pattern, $isp)) {
				return $key;
			}
		}
		return null;
	}

	/**
	* Return the logo URL for the ISP, or a generic fallback.
	*
	* @param  string $isp Raw ISP string
	* @return string Relative URL of the logo image
	*/
	public static function logoUrl($isp)
	{
		$key = self::detect($isp);
		if ($key) {
			foreach (array('svg', 'png') as $ext) {
				if (file_exists(__DIR__ . '/../img/isp/' . $key . '.' . $ext)) {
					return dol_buildpath('/sysinfo/img/isp/' . $key . '.' . $ext, 1);
				}
			}
		}
		return DOL_MAIN_URL_ROOT . '/theme/common/object_company.png';
	}

	/**
	* Return a human readable ISP name (without AS number).
	*
	* @param  string $isp Raw ISP string
	* @return string
	*/
	public static function cleanName($isp)
	{
		if (empty($isp)) {
			return '';
		}
		$clean = preg_replace('/^AS\d+\s*/i', '', trim($isp));
		return $clean ? $clean : $isp;
	}
}
