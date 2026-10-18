<?php
/* Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2026		Anthony BERTON				<a.berton@gest-mag.com>
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

use Luracast\Restler\RestException;

dol_include_once('/sysinfo/class/sysinforeport.class.php');



/**
 * \file    sysinfo/class/api_sysinfo.class.php
 * \ingroup sysinfo
 * \brief   File for API management of sysinforeport.
 */

/**
 * API class for sysinfo sysinforeport
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SysinfoApi extends DolibarrApi
{
	/**
	 * @var SysinfoReport {@type SysinfoReport}
	 */
	/*
	 * @var mixed TODO: set type
	 */
	public $sysinforeport;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->sysinforeport = new SysinfoReport($this->db);
	}


	/* BEGIN MODULEBUILDER API SYSINFOREPORT */
	/**
	 * Get properties of a sysinforeport object
	 *
	 * Return an array with sysinforeport information
	 *
	 * @param	int		$id				ID of sysinforeport
	 * @return  Object					Object with cleaned properties
	 * @phan-return	SysinfoReport			Object with cleaned properties
	 * @phpstan-return	SysinfoReport			Object with cleaned properties
	 *
	 * @phan-return  SysinfoReport
	 *
	 * @url	GET sysinforeports/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('sysinfo', 'sysinforeport', 'read')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('sysinforeport', $id, 'sysinfo_sysinforeport')) {
			throw new RestException(403, 'Access to instance id='.$id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->sysinforeport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'SysinfoReport not found');
		}

		return $this->_cleanObjectDatas($this->sysinforeport);
	}


	/**
	 * List sysinforeports
	 *
	 * Get a list of sysinforeports
	 *
	 * @param 	string		   $sortfield			Sort field
	 * @param 	string		   $sortorder			Sort order
	 * @param 	int			   $limit				Limit for list
	 * @param 	int			   $page				Page number
	 * @param 	string         $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:>:'20160101')"
	 * @param 	string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of SysinfoReport objects
	 * @phan-return array<int,SysinfoReport>
	 * @phpstan-return array<int,SysinfoReport>
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 *
	 * @url	GET /sysinforeports/
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		global $hookmanager;

		$obj_ret = array();
		$tmpobject = new SysinfoReport($this->db);

		if (!DolibarrApiAccess::$user->hasRight('sysinfo', 'sysinforeport', 'read')) {
			throw new RestException(403);
		}

		$socid = DolibarrApiAccess::$user->socid ?: 0;

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}
		if (!isModEnabled('societe')) {
			$search_sale = 0; // If module thirdparty not enabled, sale representative is something that does not exists
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".$this->db->prefix().$tmpobject->table_element." AS t";
		if (!empty($tmpobject->isextrafieldmanaged) && (int) $tmpobject->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$tmpobject->table_element."_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		}
		if (!empty($tmpobject->ismultientitymanaged) && (int) $tmpobject->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($tmpobject->element).")";
		} elseif (preg_match('/^\w+@\w+$/', (string) $tmpobject->ismultientitymanaged)) {
			$tmparray = explode('@', (string) $tmpobject->ismultientitymanaged);
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->db->sanitize($tmparray[1])." as pt ON t.".$this->db->sanitize($tmparray[0])." = pt.rowid";
			$sql .= " WHERE pt.entity IN (".getEntity($tmpobject->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".$this->db->prefix()."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".$this->db->prefix()."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Add where from hooks and sqlfilters
		$parameters = array('sqlfilters' => $sqlfilters, 'apiroute' => 'sysinforeport', 'apimethod' => __METHOD__);
		$action = 'list';
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $tmpobject, $action); // Note that $action and $object may have been modified by hook
		if ($reshook > 0) {
			$sql = $hookmanager->resPrint;
		} elseif ($reshook == 0) {
			$sql .= $hookmanager->resPrint;
		}

		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new SysinfoReport($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving sysinforeport list: '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create sysinforeport object
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return int  				ID of sysinforeport
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 500 System error
	 *
	 * @url	POST sysinforeports/
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('sysinfo', 'sysinforeport', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$result = $this->_validateSysinfoReport($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller @phan-suppress-next-line PhanTypeInvalidDimOffset
				$this->sysinforeport->context['caller'] = sanitizeVal((string) $request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				$this->sysinforeport->fetch_optionals();	// To force the load of the extrafields definition by fetch_name_optionals_label()

				foreach ($value as $index => $val) {
					$this->sysinforeport->array_options[$index] = $this->_checkValExtrafieldsForAPI($index, $val, $this->sysinforeport);
				}
				continue;
			}

			$this->sysinforeport->$field = $this->_checkValForAPI((string) $field, $value, $this->sysinforeport);
		}

		// Clean data
		// $this->sysinforeport->abc = sanitizeVal($this->sysinforeport->abc, 'alphanohtml');

		if ($this->sysinforeport->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating SysinfoReport", array_merge(array($this->sysinforeport->error), $this->sysinforeport->errors));
		}
		return $this->sysinforeport->id;
	}

	/**
	 * Update sysinforeport
	 *
	 * @param 	int   		$id             Id of sysinforeport to update
	 * @param 	array 		$request_data   Data
	 * @phan-param ?array<string,mixed>	$request_data
	 * @phpstan-param ?array<string,mixed>	$request_data
	 * @return 	Object						Object after update
	 * @phan-return SysinfoReport
	 * @phpstan-return SysinfoReport
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 *
	 * @url	PUT sysinforeports/{id}
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('sysinfo', 'sysinforeport', 'write')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('sysinforeport', $id, 'sysinfo_sysinforeport')) {
			throw new RestException(403, 'Access to instance id='.$this->sysinforeport->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->sysinforeport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'SysinfoReport not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->sysinforeport->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->sysinforeport->array_options[$index] = $this->_checkValExtrafieldsForAPI($index, $val, $this->sysinforeport);
				}
				continue;
			}

			$this->sysinforeport->$field = $this->_checkValForAPI((string) $field, $value, $this->sysinforeport);
		}

		// Clean data
		// $this->sysinforeport->abc = sanitizeVal($this->sysinforeport->abc, 'alphanohtml');

		if ($this->sysinforeport->update(DolibarrApiAccess::$user, 0) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->sysinforeport->error);
		}
	}

	/**
	 * Delete sysinforeport
	 *
	 * @param   int     $id   SysinfoReport ID
	 * @return  array
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 409 Nothing to do
	 * @throws RestException 500 System error
	 *
	 * @url	DELETE sysinforeports/{id}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('sysinfo', 'sysinforeport', 'delete')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('sysinforeport', $id, 'sysinfo_sysinforeport')) {
			throw new RestException(403, 'Access to instance id='.$this->sysinforeport->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->sysinforeport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'SysinfoReport not found');
		}

		if ($this->sysinforeport->delete(DolibarrApiAccess::$user) == 0) {
			throw new RestException(409, 'Error when deleting SysinfoReport : '.$this->sysinforeport->error);
		} elseif ($this->sysinforeport->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting SysinfoReport : '.$this->sysinforeport->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'SysinfoReport deleted'
			)
		);
	}


	/**
	 * Validate fields before creating or updating object
	 *
	 * @param	array		$data   Array of data to validate
	 * @phan-param		?array<string,null|int|float|string> $data
	 * @phpstan-param	?array<string,null|int|float|string> $data
	 * @return	array
	 * @phan-return		array<string,null|int|float|string>|array{}
	 * @phpstan-return	array<string,null|int|float|string>|array{}
	 *
	 * @throws	RestException
	 */
	private function _validateSysinfoReport($data)
	{
		if (!is_array($data)) {
			$data = array();
		}
		$sysinforeport = array();
		foreach ($this->sysinforeport->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$sysinforeport[$field] = $data[$field];
		}
		return $sysinforeport;
	}

	/* END MODULEBUILDER API SYSINFOREPORT */


	/* BEGIN MODULEBUILDER API MYOBJECT */
	/* END MODULEBUILDER API MYOBJECT */



	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensitive object data fields
	 * @phpstan-template T
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 *
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);
		//BEGIN MODULEBUILDER LINES
		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}
		//END MODULEBUILDER LINES
		return $object;
	}

	// Viber API methods

	/**
	* Receive system info from the Windows agent and store it.
	*
	* @param  array $payload JSON body sent by the agent
	* @return array
	* @throws RestException 400 Missing guid
	*/
	public function postMachine($payload)
	{
		global $user;

		$this->_checkHttps();

		// The agent posts reports: require the module write permission
		// (granted to the technical 'sysinfoagent' user at module activation).
		if (empty($user) || !$user->hasRight('sysinfo', 'write')) {
			throw new RestException(
				403,
				'Permission sysinfo write requise pour envoyer un rapport'
			);
		}

		if (empty($payload['guid'])) {
			throw new RestException(400, 'Champ guid manquant');
		}

		// F5: refuse oversized reports (a real report is ~50 KB).
		if (strlen(json_encode($payload)) > 1048576) {
			throw new RestException(413, 'Rapport trop volumineux.');
		}

		$payload['softwares'] = $this->_matchSoftwares(
			isset($payload['installed_softwares']) ? $payload['installed_softwares'] : array()
		);

		$report = new SysinfoReport($this->db);
		$report->guid = $payload['guid'];
		$report->setFromPayload($payload);
		$report->report = json_encode($payload);
		$id = $report->save($user);

		if ($id < 0) {
			throw new RestException(500, 'Erreur lors de l\'enregistrement du rapport');
		}

		dol_syslog(
			'sysinfo: rapport enregistre (rowid=' . $id . ') pour la machine guid='
			. $payload['guid'],
			LOG_INFO
		);

		return array(
			'success' => true,
			'report_id' => $id,
			'guid' => $payload['guid'],
			'softwares' => $payload['softwares'],
		);
	}

	/**
	* Assign a machine (guid) to a thirdparty. Called from Dolibarr,
	* not from the agent: the agent never sends fk_soc.
	*
	* @param  string $guid   Machine identifier
	* @param  int    $fk_soc Thirdparty id
	* @param  int    $force  Force update
	* @return array
	* @throws RestException 404 Unknown machine/thirdparty
	*/
	public function putMachineSoc($guid, $fk_soc, $force = 0)
	{
		$this->_checkHttps();

		if (empty($guid) || empty($fk_soc)) {
			throw new RestException(400, 'guid et fk_soc requis');
		}

		$report = new SysinfoReport($this->db);
		if ($report->fetchLatest($guid) != 1) {
			throw new RestException(404, 'Machine guid=' . $guid . ' introuvable');
		}

		// M4 fix: refuse to silently reassign a machine that already belongs
		// to a thirdparty, unless force is explicitly requested.
		if ($report->fk_soc && empty($force)) {
			throw new RestException(
				409,
				'Machine deja rattachee au tiers #' . $report->fk_soc
				. ' : utilisez force=1 pour reassigner'
			);
		}

		$thirdparty = new Societe($this->db);
		if ($thirdparty->fetch((int) $fk_soc) <= 0) {
			throw new RestException(404, 'fk_soc=' . $fk_soc . ' : tiers introuvable');
		}

		$sql = "UPDATE " . MAIN_DB_PREFIX . "sysinfo_reports";
		$sql .= " SET fk_soc = " . (int) $fk_soc;
		$sql .= " WHERE rowid = " . (int) $report->id;
		if (!$this->db->query($sql)) {
			throw new RestException(500, 'Erreur lors de l\'affectation');
		}

		return array(
			'success' => true,
			'guid' => $guid,
			'thirdparty_id' => (int) $fk_soc,
		);
	}

	/**
	* Match installed softwares against the dictionary (llx_sysinfo_softwares).
	*
	* @param  array $installed List of installed software names sent by the agent
	* @return array ref => array(label, installed, match)
	*/
	private function _matchSoftwares($installed)
	{
		$out = array();

		$sql = "SELECT ref, label, pattern FROM " . MAIN_DB_PREFIX . "sysinfo_softwares";
		$sql .= " WHERE active = 1";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $out;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$candidates = array($obj->ref, $obj->label);
			if (!empty($obj->pattern)) {
				$candidates[] = $obj->pattern;
			}
			$found = null;
			foreach ($installed as $name) {
				foreach ($candidates as $candidate) {
					if ($candidate !== '' && stripos($name, $candidate) !== false) {
						$found = $name;
						break 2;
					}
				}
			}
			$out[$obj->ref] = array(
				'label' => $obj->label,
				'installed' => ($found !== null),
				'match' => $found,
			);
		}
		return $out;
	}

	/**
	* Get the latest report of each known machine.
	*
	* Optional SQL filter on metric columns, e.g.:
	*   GET /sysinfo/machine?sqlfilters=(t.firewall_enabled:=:0)
	*   GET /sysinfo/machine?sqlfilters=(t.pending_updates:>:0)
	*
	* @param  string $sqlfilters Filter syntax (Dolibarr standard)
	* @return array
	* @throws RestException 400 Invalid filter
	*/
	public function indexMachine($sqlfilters = '')
	{
		$this->_checkHttps();

		$where = '';
		if ($sqlfilters) {
			try {
				$where = $this->_sanitizeSqlFilter($sqlfilters);
			} catch (Exception $e) {
				throw new RestException(400, 'Filtre invalide: ' . $e->getMessage());
			}
		}

		$report = new SysinfoReport($this->db);
		$rows = $report->listLatestPerMachine($where);
		$out = array();
		foreach ($rows as $obj) {
			$out[] = $this->_formatRow($obj);
		}
		return $out;
	}

	/**
	* Get the latest report of one machine by its guid.
	*
	* @param  string $guid Machine identifier
	* @return array
	* @throws RestException 404 Unknown machine
	*/
	public function getMachine($guid)
	{
		$this->_checkHttps();

		$report = new SysinfoReport($this->db);
		$result = $report->fetchLatest($guid);
		if ($result < 0) {
			throw new RestException(500, 'Erreur base de donnees');
		}
		if ($result == 0) {
			throw new RestException(404, 'Aucun rapport pour guid=' . $guid);
		}

		$row = (object) array(
			'rowid' => $report->id,
			'guid' => $report->guid,
			'fk_soc' => $report->fk_soc,
			'hostname' => $report->hostname,
			'os' => $report->os,
			'report' => $report->report,
			'date_creation' => $report->date_creation,
		);
		return $this->_formatRow($row);
	}

	/**
	* Get agent versions and update urls (module descriptor, overridable
	* via the SYSINFO_AGENT_* constants).
	*
	* GET /api/index.php/sysinfo/config
	*
	* @return array
	*/
	public function getConfig()
	{
		$module = new modSysinfo($this->db);
		$out = array();
		foreach (array('Windows', 'Linux', 'Android') as $os) {
			$versionKey = 'agent' . $os . 'Version';
			$urlKey = 'agent' . $os . 'UpdateUrl';
			$out[strtolower($os)] = array(
				'version' => getDolGlobalString('SYSINFO_AGENT_' . dol_strtoupper($os) . '_VERSION') ?: $module->$versionKey,
				'update_url' => getDolGlobalString('SYSINFO_AGENT_' . dol_strtoupper($os) . '_UPDATE_URL') ?: $module->$urlKey,
			);
		}
		return $out;
	}

	/**
	* Refuse non-HTTPS requests unless explicitly disabled by the admin
	* via the SYSINFO_ALLOW_HTTP constant (module settings page).
	*
	* @return void
	* @throws RestException 426 Upgrade Required
	*/
	private function _checkHttps()
	{
		if (getDolGlobalString('SYSINFO_ALLOW_HTTP')) {
			return;
		}
		if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
			throw new RestException(
				426,
				'HTTPS requis : activez HTTPS sur le serveur, ou definissez SYSINFO_ALLOW_HTTP=1 pour desactiver ce controle (non recommande).'
			);
		}
	}

	/**
	* Format a report row for API output, with each metric as a field.
	*
	* @param  object $obj Report object
	* @return array  Formatted row
	*/
	private function _formatRow($obj)
	{
		$row = array(
			'guid' => $obj->guid,
			'thirdparty_id' => $obj->fk_soc,
			'hostname' => $obj->hostname,
			'os' => $obj->os,
			'date_creation' => $obj->date_creation,
			'report' => json_decode($obj->report, true),
		);
		foreach (SysinfoReport::$columns as $col => $type) {
			if ($col == 'fk_soc') {
				continue;
			}
			if (isset($obj->$col)) {
				$row[$col] = $obj->$col;
			}
		}
		return $row;
	}
}
