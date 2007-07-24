<?php
/**
 * server/module.php - MMSPanel main module
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


  $module = array (
    'order' => 0,
    'menu' => array (
      array (
        'title' => '<strong>' . $this->_tr('Server Management') . '</strong>',
        'url'   => '',
        'sub'   => array (
          array (
            'title' => $this->_tr('Manage Services'),
            'url'   => 'server_services_manage'
          ),
          array (
            'title' => $this->_tr('Monitor Services'),
            'url'   => 'server_services_monitor'
          ),
          array (
            'title' => $this->_tr('LDAP Server log'),
            'url'   => 'server_ldap'
          ),
          array (
            'title' => $this->_tr('System log'),
            'url'   => 'server_log'
          )
        )
      )
    )
  );


?>