<?php
  /** Debug */
  define ('DEBUG', 1);
  define ('DEBUG_DB', 0);
  define ('DEBUG_FLEXY', 0);

  /** Lingua */
  define ('DEFAULT_LANGUAGE', 'pt_BR');

  /** Dados para conexão com LDAP */
  define ('LDAP_HOST', 'localhost');
  define ('LDAP_PORT', '389');
  define ('LDAP_ROOT_DN', 'dc=maglione, dc=com, dc=br');
  define ('LDAP_BIND_USER', 'cn=admin, dc=maglione, dc=com, dc=br');
  define ('LDAP_BIND_PASS', 'maglione');
  define ('LDAP_USERS_OU', 'ou=Users');
  define ('LDAP_GROUPS_OU', 'ou=Groups');
  define ('LDAP_COMPUTERS_OU', 'ou=Computers');

  /** Diretorio do programa */
  define ('REAL_PATH', '/usr/share/mmspanel');
  
  /** */
  define ('WEBROOT_INDEX', 'index.php');
  define ('SYS_URL_SERVER', 'http://' . $_SERVER['HTTP_HOST'] . '/mmspanel');
  define ('SYS_URL_INDEX', SYS_URL_SERVER .'/'. WEBROOT_INDEX);

  /** Skin ou Layout padrao */
  define ('SYS_THEME_DIR', 'default');
  define ('FULL_THEME_DIR', SYS_URL_SERVER . '/themes/' . SYS_THEME_DIR);
  
  /** Numero de linhas para os DataGrids */
  define ('DG_NUM_ROWS', 30);

  /** Localização do comando sudo */
  define('SUDO', '/usr/bin/sudo');

  /** Localização do comando echo */
  define('ECHO_CMD', '/bin/echo');

?>
