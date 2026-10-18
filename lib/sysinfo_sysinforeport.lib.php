<?php
/* Copyright (C) 2026		Anthony BERTON				<anthony.berton@bb2a.fr>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    sysinfolib/sysinfo_sysinforeport.lib.php
 * \ingroup sysinfo
 * \brief   Library files with common functions for SysinfoReport
 */

/**
 * Prepare array of tabs for SysinfoReport
 *
 * @param	SysinfoReport	$object					SysinfoReport
 * @return 	array<array{string,string,string}>	Array of tabs
 */
function sysinforeportPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("sysinfo@sysinfo");

	// BEGIN MODULEBUILDER TABFLAG CONTACT
	$showtabofpagecontact = 0;
	// END MODULEBUILDER TABFLAG CONTACT
	// BEGIN MODULEBUILDER TABFLAG NOTE
	$showtabofpagenote = 0;
	// END MODULEBUILDER TABFLAG NOTE
	// BEGIN MODULEBUILDER TABFLAG DOCUMENT
	$showtabofpagedocument = 0;
	// END MODULEBUILDER TABFLAG DOCUMENT
	// BEGIN MODULEBUILDER TABFLAG AGENDA
	$showtabofpageagenda = 0;
	// END MODULEBUILDER TABFLAG AGENDA

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(dol_buildpath("/sysinfo/sysinforeport_card.php", 1), ['id' => $object->id]);
	$head[$h][1] = $langs->trans("SysinfoReport");
	$head[$h][2] = 'card';
	$h++;

	// BEGIN MODULEBUILDER TAB CONTACT
	if ($showtabofpagecontact) {
		$head[$h][0] = dolBuildUrl(dol_buildpath("/sysinfo/sysinforeport_contact.php", 1), ['id' => $object->id]);
		$head[$h][1] = $langs->trans("Contacts");
		$head[$h][2] = 'contact';
		$h++;
	}
	// END MODULEBUILDER TAB CONTACT

	// BEGIN MODULEBUILDER TAB NOTE
	if ($showtabofpagenote) {
		if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
			$nbNote = 0;
			if (!empty($object->note_private)) {
				$nbNote++;
			}
			if (!empty($object->note_public)) {
				$nbNote++;
			}
			$head[$h][0] = dolBuildUrl(dol_buildpath('/sysinfo/sysinforeport_note.php', 1), ['id' => $object->id]);
			$head[$h][1] = $langs->trans('Notes');
			if ($nbNote > 0) {
				$head[$h][1] .= (!getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
			}
			$head[$h][2] = 'note';
			$h++;
		}
	}
	// END MODULEBUILDER TAB NOTE

	// BEGIN MODULEBUILDER TAB DOCUMENT
	if ($showtabofpagedocument) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->sysinfo->dir_output."/sysinforeport/".dol_sanitizeFileName($object->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = dolBuildUrl(dol_buildpath("/sysinfo/sysinforeport_document.php", 1), ['id' => $object->id]);
		$head[$h][1] = $langs->trans('Documents');
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;
	}
	// END MODULEBUILDER TAB DOCUMENT

	// BEGIN MODULEBUILDER TAB AGENDA
	if ($showtabofpageagenda) {
		$head[$h][0] = dolBuildUrl(dol_buildpath("/sysinfo/sysinforeport_agenda.php", 1), ['id' => $object->id]);
		$head[$h][1] = $langs->trans("Events");
		$head[$h][2] = 'agenda';
		$h++;
	}
	// END MODULEBUILDER TAB AGENDA

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@sysinfo:/sysinfo/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@sysinfo:/sysinfo/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'sysinforeport@sysinfo');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'sysinforeport@sysinfo', 'remove');

	return $head;
}
