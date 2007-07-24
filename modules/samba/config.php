<?php
/**
 * samba/config.php - Samba module configuration file
 *
 * LICENSE: This source file is subject to version 2.0 of GNU GENERAL PUBLIC LICENSE (GPL).
 *          See the enclosed file COPYRIGHT for license information. If you did not receive 
 *          this file, see http://www.gnu.org/licenses/gpl-2.0.txt.
 *
 * @package MMSPanel
 * @author Daniel Maglione <daniel@maglione.com.br>
 * @version 1.0
 * @copyright 2007 - Daniel Maglione <daniel@maglione.com.br>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt
 * @project   MMSPanel
 */


define('SMBLDAP_GROUPADD', '/usr/sbin/smbldap-groupadd');
define('SMBLDAP_GROUPMOD', '/usr/sbin/smbldap-groupmod');
define('SMBLDAP_GROUPDEL', '/usr/sbin/smbldap-groupdel');

define('SMBLDAP_PASSWD', '/usr/sbin/smbldap-passwd');
define('SMBLDAP_USERADD', '/usr/sbin/smbldap-useradd');
define('SMBLDAP_USERMOD', '/usr/sbin/smbldap-usermod');
define('SMBLDAP_USERDEL', '/usr/sbin/smbldap-userdel');

#define('SMBSTATUS', '/usr/bin/smbstatus');
define('SMBSTATUS', 'cat  ' . REAL_PATH . '/tmp/smbstatus.txt');

define('SAMBA_HOMES', '/home/samba/usuarios');
define('SHARES_CONFIG_FILE', '/home/vhosts/maglione.com.br/subdomains/desenvolvimento/httpdocs/tmp/shares.conf');

define('PING_CMD', '/bin/ping');
?>