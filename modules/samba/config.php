<?php
define('SMBLDAP_GROUPADD', '/usr/sbin/smbldap-groupadd');
define('SMBLDAP_GROUPMOD', '/usr/sbin/smbldap-groupmod');
define('SMBLDAP_GROUPDEL', '/usr/sbin/smbldap-groupdel');

define('SMBLDAP_PASSWD', '/usr/sbin/smbldap-passwd');
define('SMBLDAP_USERADD', '/usr/sbin/smbldap-useradd');
define('SMBLDAP_USERMOD', '/usr/sbin/smbldap-usermod');
define('SMBLDAP_USERDEL', '/usr/sbin/smbldap-userdel');

#define('SMBSTATUS', '/usr/bin/smbstatus');
define('SMBSTATUS', 'cat /home/vhosts/maglione.com.br/subdomains/desenvolvimento/httpdocs/tmp/smbstatus.txt');

define('SAMBA_HOMES', '/home/samba/usuarios');
define('SHARES_CONFIG_FILE', '/home/vhosts/maglione.com.br/subdomains/desenvolvimento/httpdocs/tmp/shares.conf');

define('PING_CMD', '/bin/ping');
?>