<?php
  require_once 'config.php';

  @ini_set('include_path', REAL_PATH . PATH_SEPARATOR . REAL_PATH . '/libs' . PATH_SEPARATOR . REAL_PATH . '/libs/pear' . PATH_SEPARATOR . REAL_PATH . '/libs/php-gettext');

  require_once 'Maglione_NetPanel.php';

  $NetPanel = new NetPanel();
  $NetPanel->Draw();
?>
