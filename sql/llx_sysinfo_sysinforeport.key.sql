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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_rowid (rowid);
ALTER TABLE llx_sysinfo_sysinforeport ADD UNIQUE INDEX uk_sysinfo_sysinforeport_ref (ref);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_fk_soc (fk_soc);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_fk_soc (fk_soc);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_hostname (hostname);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_public_ip (public_ip);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_isp (isp);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_pending_updates (pending_updates);
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_firewall (firewall_enabled)
ALTER TABLE llx_sysinfo_sysinforeport ADD INDEX idx_sysinfo_sysinforeport_status (status);

-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_sysinfo_sysinforeport ADD UNIQUE INDEX uk_sysinfo_sysinforeport_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_sysinfoSysInfoReport ADD CONSTRAINT llx_SYSINFO_SYSINFOREPORT_FK_FIELD FOREIGN KEY (fk_field) REFERENCES llx_SYSINFO_MYOTHEROBJECT(rowid);
