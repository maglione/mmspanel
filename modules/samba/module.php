<?php
$module = array (
  'order' => 1,
  'menu' => array (
    array (
      'title' => '<strong>' . $this->_tr('File Server') . '</strong>',
      'url'   => 'samba',
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