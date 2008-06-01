<?php
  require_once 'config.php';

  #@ini_set('include_path', REAL_PATH . PATH_SEPARATOR . REAL_PATH . '/libs' . PATH_SEPARATOR . REAL_PATH . '/libs/pear' . PATH_SEPARATOR . REAL_PATH . '/libs/php-gettext');
  @ini_set('include_path', REAL_PATH . PATH_SEPARATOR . REAL_PATH . '/libs' . PATH_SEPARATOR . '/usr/share/php');

  require_once 'MMSPanel.php';

  $NetPanel = new NetPanel();
  $NetPanel->Draw();
?>
