<?php
/**
 * samba/module.php - Samba module description
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
  'order' => 1,
  'menu' => array (
    array (
      'title' => '<strong>' . $this->_tr('File Server') . '</strong>',
      'url'   => '',
      'sub'   => array (
        array (
          'title' => $this->_tr('Account Policy'),
          'url'   => 'samba_policy'
        ),
        array (
          'title' => $this->_tr('Manage user accounts'),
          'url'   => 'samba_users'
        ),
        array (
          'title' => $this->_tr('Manage group accounts'),
          'url'   => 'samba_groups'
        ),
        array (
          'title' => $this->_tr('Manage computer accounts'),
          'url'   => 'samba_computers'
        ),
        array (
          'title' => $this->_tr('Manage shares'),
          'url'   => 'samba_shares'
        ),
        array (
          'title' => $this->_tr('Show network status'),
          'url'   => 'samba_status'
        ),
        array (
          'title' => $this->_tr('Samba Server log'),
          'url'   => 'samba_log'
        ),
        array (
          'title' => $this->_tr('NetBIOS log'),
          'url'   => 'samba_log_netbios'
        )
      )
    )
  )
);

?>