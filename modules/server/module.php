<?php
  $module = array (
    'order' => 0,
    'menu' => array (
      array (
        'title' => '<strong>' . $this->_tr('Server Management') . '</strong>',
        'url'   => 'server',
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