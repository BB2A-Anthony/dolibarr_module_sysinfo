-- Rate limiting for the enrollment endpoint (per IP attempts).
CREATE TABLE llx_sysinfo_ratelimit (
    rowid        integer AUTO_INCREMENT PRIMARY KEY,
    ip           varchar(64) NOT NULL,
    date_attempt datetime NOT NULL,
    INDEX idx_sysinfo_ratelimit_ip (ip, date_attempt)
) ENGINE=innodb;
