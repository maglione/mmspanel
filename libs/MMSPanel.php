<?php
/**
 * MMSPanel.php - MMSPanel
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

require_once 'Maglione.php';

/**
 * Main Panel class, extends MaglioneFramework
 */
class NetPanel extends MaglioneFramework {
  var $Ldap;
  var $SambaDomainName;
  var $SambaSID;

  /**
   * Initialize NetPanel, setting menu and Auth Type. 
   * To change auth type, all you need is change Auth['Type'] and Auth['Options']
   * according PEAR Auth manual
   */
  function NetPanel()
  {
    $this->MaglioneFramework();

    /** LDAP configuration */
    require_once 'Net/LDAP.php';
    $ldap_config = array (
      'host'   => LDAP_HOST,
      'port'   => LDAP_PORT,
      'binddn' => LDAP_BIND_USER,
      'bindpw' => LDAP_BIND_PASS,
      'basedn' => LDAP_ROOT_DN
    );
    $this->Ldap = Net_LDAP::connect($ldap_config);

    $options = array(
                   'scope' => 'one',
                   'attributes' => array('sambaDomainName','sambaSID')
              );
    $search = $this->Ldap->search(LDAP_ROOT_DN, '(objectclass=SambaDomain)', $options);

    $result = $search->entries();

    $this->SambaDomainName = trim($result[0]->getValue('sambadomainname','single'));
    $this->SambaSID = trim($result[0]->getValue('sambasid','single'));

    unset($this->MenuArray);
    $this->MenuArray = $this->_ReadModulesMenu();

    $this->Auth['Type'] = 'LDAP';
    $this->Auth['Options'] = array(
      # 'url' => 'ldap://' . LDAP_HOST . ':' . LDAP_PORT . '/',
      'host' => LDAP_HOST,
      'port' => LDAP_PORT,
      'version' => 3,
      'binddn' => LDAP_BIND_USER,
      'bindpw' => LDAP_BIND_PASS,
      'basedn' => LDAP_ROOT_DN,
      'userscope' => 'one',
      'userdn' => LDAP_USERS_OU,
      'groupdn' => LDAP_GROUPS_OU,
      'groupfilter' => '(objectClass=posixGroup)',
      'memberattr' => 'memberUid',
      'memberisdn' => false,
      'group' => 'Domain Admins',
      'cryptType' => 'sha1',
      'debug' => true
    );

  }

###############################################################################
#                           MODULES MENU FUNCTIONS                            #
###############################################################################

  /**
   * Fix menu URLs from form module_file to /modules/module/file.php
   */
  function _FixMenuUrl(&$item, $key)
  {
    if ($key == 'url') {
      if (ereg('([[:alnum:]]+)_(.+)', $item, $registers))
      {
        $item = SYS_URL_SERVER . '/modules/' . $registers[1] . '/' . $registers[2] . '.php';
      }
    }
  }

  /**
   * Walk on module.php files for all modules and build a menu
   */
  function _ReadModulesMenu()
  {
    require_once 'PHP/Compat/Function/array_walk_recursive.php';

    $menu_tmp = array();
    if ($handle = opendir(REAL_PATH . '/modules')) {
      while (false !== ($dir = readdir($handle))) {
        if ( (is_dir(REAL_PATH . "/modules/$dir")) && ($dir != '.') && ($dir != '..') ) {
          if (file_exists(REAL_PATH . "/modules/$dir/module.php")) {
            include(REAL_PATH . "/modules/$dir/module.php");
            $menu_tmp[$module['order']] = $module['menu'];
          }
        }
      }
      closedir($handle);
    }

    ksort($menu_tmp);
    reset($menu_tmp);
    $menu = array ( 0 => array(
                    'title' => '<strong>' . $this->_tr('Home') . '</strong>',
                    'url'   => '/' )
                  );
    foreach($menu_tmp as $menu_order => $menu_entry) {
      $menu = array_merge_recursive($menu, $menu_entry);
    }
    array_walk_recursive($menu, array('NetPanel', '_FixMenuUrl'));

    return($menu);
  }

###############################################################################
#                         ESPECIFIC PANEL FUNCTIONS                           #
###############################################################################

  function Draw()
  {
    $this->Template->outputDomain = $this->SambaDomainName;

    parent::Draw();
  }

  function ExecuteExternalCommand($command, $output = null)
  {
    exec($command, $output, $return);

    $this->Debug("ExecuteExternalCommand($command): Return: [$return]", $output);

    return($return);
  }

}

?>
