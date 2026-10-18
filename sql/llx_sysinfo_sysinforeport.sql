-- Copyright (C) 2026		Anthony BERTON				<anthony.berton@bb2a.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_sysinfo_sysinforeport(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	ref varchar(128) NOT NULL,
	guid varchar(128) NOT NULL UNIQUE,
	fk_soc integer DEFAULT NULL,

	-- Identity / OS
	hostname varchar(255) DEFAULT NULL,
	fqdn varchar(255) DEFAULT NULL,
	os varchar(255) DEFAULT NULL,
	os_version varchar(64) DEFAULT NULL,
	os_build varchar(64) DEFAULT NULL,
	architecture varchar(32) DEFAULT NULL,
	processor varchar(255) DEFAULT NULL,

	-- CPU
	cpu_logical integer DEFAULT NULL,
	cpu_physical integer DEFAULT NULL,
	cpu_percent double DEFAULT NULL,

	-- Memory
	memory_total bigint DEFAULT NULL,
	memory_available bigint DEFAULT NULL,
	memory_percent double DEFAULT NULL,

	-- Main disk (system partition)
	disk_total bigint DEFAULT NULL,
	disk_used bigint DEFAULT NULL,
	disk_free bigint DEFAULT NULL,
	disk_percent double DEFAULT NULL,

	-- Network
	ip_address varchar(64) DEFAULT NULL,
	public_ip varchar(64) DEFAULT NULL,
	isp varchar(128) DEFAULT NULL,
	mac_address varchar(32) DEFAULT NULL,

	-- Firewall
	firewall_enabled tinyint DEFAULT NULL,

	-- Windows Update
	pending_updates integer DEFAULT NULL,
	last_update_date datetime DEFAULT NULL,

	-- Software check (dictionary match summary)
	softwares_missing integer DEFAULT NULL,

	-- Remote access
	teamviewer_installed tinyint DEFAULT NULL,
	teamviewer_id varchar(32) DEFAULT NULL,
	rustdesk_installed tinyint DEFAULT NULL,
	rustdesk_id varchar(32) DEFAULT NULL,
	anydesk_installed tinyint DEFAULT NULL,
	anydesk_id varchar(32) DEFAULT NULL,

	-- Raw JSON payload and timestamps
	report text DEFAULT NULL,

	note_public text DEFAULT NULL,
	note_private text DEFAULT NULL,
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer DEFAULT NULL,
	last_main_doc varchar(255) DEFAULT NULL,
	import_key varchar(14) DEFAULT NULL,
	model_pdf varchar(255) DEFAULT NULL,
	status integer NOT NULL

	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
