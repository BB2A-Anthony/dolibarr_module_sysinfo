<?php
/**
* Machines list with thirdparty reassignment (fk_soc) from Dolibarr UI.
*
* Changing the assignment never blocks agent reports: the agent never sends
* fk_soc and the upsert preserves the server-side assignment. This page is
* for cases like equipment being resold to another thirdparty.
*/
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;  // @phan-suppress-current-line DolibarrForbiddenFunctionPlugin
$j = strlen($tmp2) - 1;  // @phan-suppress-current-line DolibarrForbiddenFunctionPlugin
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once '../lib/sysinfo.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once __DIR__ . '/class/sysinfo_report.class.php';

global $conf, $db, $user, $langs;

if (empty($user) || !$user->hasRight('sysinfo', 'read')) {
	accessforbidden('Permission manquante : lire les informations systeme', 0, 1);
}

$id = GETPOST('machine_id', 'int');
$newSoc = GETPOST('fk_soc_new', 'int');
$confirm = GETPOST('confirm_reassign', 'int');
$cancel = GETPOST('cancel', 'alpha');
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// --- Reassignment form processing -------------------------------------------

if ($action == 'reassign' && $cancel) {
	header('Location: ' . ($_SERVER['PHP_SELF']));
	exit;
}

if ($action == 'reassign' && $id > 0 && $confirm && checkUserAccessRight($user)) {
	// CSRF token check.
	if (GETPOST('token', 'alpha') != newToken()) {
		accessforbidden('Jeton de securite invalide (CSRF)', 0, 1);
	}

	$report = new SysinfoReport($db);
	$machineRow = fetchReportById($db, $id);
	if (!$machineRow) {
		setEventMessage('Machine introuvable', 'errors');
	} else {
		$oldSocId = (int) $machineRow->fk_soc;
		$newSocId = $newSoc;

		// Unassign (fk_soc_new = -1 means "remove assignment").
		$sqlSet = $newSocId == -1 ? 'NULL' : (int) $newSocId;

		// Check thirdparty exists if assigning.
		if ($newSocId > 0) {
			$tmpsoc = new Societe($db);
			if ($tmpsoc->fetch($newSocId) <= 0) {
				setEventMessage('Tiers #' . $newSocId . ' introuvable', 'errors');
				$error = 1;
			}
		}

		if (empty($error)) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . "sysinfo_reports";
			$sql .= " SET fk_soc = " . $sqlSet;
			$sql .= " WHERE rowid = " . (int) $id;
			if ($db->query($sql)) {
				$message = 'Machine ' . dol_escape_htmltag($machineRow->hostname ? $machineRow->hostname : $machineRow->guid);
				$message .= $newSocId == -1 ? ' désaffectée' : ' réaffectée au tiers #' . $newSocId;
				setEventMessage($message);
				dol_syslog(
					'sysinfo: machine rowid=' . $id . ' reassignee de '
					. ($oldSocId ?: 'NULL') . ' vers ' . ($newSocId == -1 ? 'NULL' : $newSocId)
					. ' par utilisateur #' . $user->id,
					LOG_INFO
				);
			} else {
				setEventMessage('Erreur base de donnees : ' . $db->lasterror(), 'errors');
			}
		}
	}
}

function fetchReportById($db, $rowid)
{
	$sql = "SELECT rowid, guid, fk_soc, hostname, os, date_creation";
	$sql .= " FROM " . MAIN_DB_PREFIX . "sysinfo_reports";
	$sql .= " WHERE rowid = " . (int) $rowid;
	$resql = $db->query($sql);
	if (!$resql) {
		return null;
	}
	return $db->fetch_object($resql);
}

function checkUserAccessRight($user)
{
	global $db;
	if (empty($user) || !$user->hasRight('sysinfo', 'write')) {
		accessforbidden('Permission manquante : ecrire les informations systeme', 0, 1);
	}
	return true;
}

llxHeader('', 'Sysinfo - Machines');

print load_fiche_titre('Machines surveillées par l\'agent Windows', '', 'globe');

// --- Reassignment confirmation form -----------------------------------------

if ($action == 'reassign' && $id > 0 && !$confirm) {
	$machineRow = fetchReportById($db, $id);
	if ($machineRow) {
		$form = new Form($db);
		print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="reassign">';
		print '<input type="hidden" name="machine_id" value="' . $id . '">';
		print '<input type="hidden" name="confirm_reassign" value="1">';
		print '<input type="hidden" name="backtopage" value="' . dol_escape_htmltag($backtopage) . '">';

		print '<p>Réaffecter la machine <strong>' . dol_escape_htmltag($machineRow->hostname ? $machineRow->hostname : $machineRow->guid) . '</strong> :</p>';
		print $form->select_thirdparty_list(GETPOST('fk_soc_new', 'int'), 'fk_soc_new', '', 1, 0, 0, array(), 0, 0, array('blank' => 1, 'notooltip' => 1), 0, '', 'Tiers de destination');

		print '<p class="opacitymedium">La machine continuera à remonter ses rapports sans interruption : seul le tiers destinataire change. Utilisez la liste « — Aucun — » pour désaffecter.</p>';

		print '<input type="submit" class="button" value="Confirmer la réaffectation">';
		print ' &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="Annuler">';
		print '</form>';
		llxFooter();
		$db->close();
		exit;
	}
}

// --- Machines list -----------------------------------------------------------

$report = new SysinfoReport($db);
$machines = $report->listLatestPerMachine();

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Machine</th><th>Tiers</th><th>OS</th><th>Dernier rapport</th><th>Action</th>';
print '</tr>';

foreach ($machines as $machine) {
	print '<tr class="oddeven">';
	$label = $machine->hostname ? $machine->hostname : $machine->guid;
	print '<td>' . dol_escape_htmltag($label) . '</td>';

	if ($machine->fk_soc) {
		$tmpsoc = new Societe($db);
		if ($tmpsoc->fetch($machine->fk_soc) > 0) {
			print '<td>' . $tmpsoc->getNomUrl(1) . '</td>';
		} else {
			print '<td>#' . (int) $machine->fk_soc . '</td>';
		}
	} else {
		print '<td class="opacitymedium">Non affecté</td>';
	}

	print '<td>' . dol_escape_htmltag($machine->os) . '</td>';
	print '<td>' . dol_print_date($db->jdate($machine->date_creation), 'dayhour') . '</td>';
	print '<td><a class="button button-small" href="' . $_SERVER['PHP_SELF']
		. '?action=reassign&machine_id=' . (int) $machine->rowid . '">'
		. ($user->hasRight('sysinfo', 'write') ? 'Réaffecter' : '—') . '</a></td>';
	print '</tr>';
}

print '</table>';

llxFooter();
$db->close();
