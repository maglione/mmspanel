<?php
/**
 * Maglione.php - Maglione Framework
 *
 * LICENSE: This source file is subject to version 2.0 of GNU GENERAL PUBLIC LICENSE (GPL).
 *          See the enclosed file COPYRIGHT for license information. If you did not receive 
 *          this file, see http://www.gnu.org/licenses/gpl-2.0.txt.
 *
 * @package MaglioneFramework
 * @author Daniel Maglione <daniel@maglione.com.br>
 * @version 1.0
 * @copyright 2007 - Daniel Maglione <daniel@maglione.com.br>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt
 * @project   MMSPanel
 */

/**
* Requires HTML/Table.
*/
require_once 'HTML/Table.php';

/**
* Requires Structures/DataGrid.
*/
require_once 'Structures/DataGrid.php';

/** Configuracao do Gerenciamento de Sessoes */
require_once 'HTTP/Session.php';
$Session = new HTTP_Session();
HTTP_Session::useCookies(true);
HTTP_Session::start('MaglioneFramework');
HTTP_Session::setExpire(time() + 60 * 60);
HTTP_Session::setIdle(time()+ 10 * 60);
if (HTTP_Session::isExpired()) {
  HTTP_Session::destroy();
}
if (HTTP_Session::isIdle()) {
  HTTP_Session::destroy();
}
HTTP_Session::updateIdle();

# Reset errors
$_SESSION['Errors'] = array();

/**
* The class MaglioneFramework provides an consistent PEAR environment
* that easy the development of web applications
*
* @author       Daniel Maglione <daniel@maglione.com.br>
* 
* Usage Example:
* <code>
*  $Framework = new MaglioneFramework();
* </code>
*/
class MaglioneFramework {
  var $Locale;
  var $Flexy;
  var $Template;
  var $Translation;
  var $Session;
  var $Auth;
  var $MenuArray;
  var $Filter;
  var $Errors;

  /**
  * Constructor
  *
  * @access   public
  */
  function MaglioneFramework()
  { 
    global $Session;

    /**
    * Requires PEAR.
    */
    require_once 'PEAR.php';
    PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array('MaglioneFramework', 'PEARErrorHandler'));

    if ( (! $Session->get('language')) && ($_POST['language']) ) {
      $Session->set('language', $_POST['language']);
    }

    /**
    * Requires I18Nv2.
    */
    require_once 'I18Nv2/Locale.php';
    if ($Session->get('language')) {
      $this->Locale = new I18Nv2_Locale($Session->get('language'));
    } else {
      $this->Locale = new I18Nv2_Locale(DEFAULT_LANGUAGE);
    }
    # $this->Locale->setDateTimeFormat(I18Nv2_DATETIME_SHORT);

    /**
    * Requires Translation2.
    */
    require_once 'Translation2.php';
    $options = array (
      'prefetch'          => false,
      'langs_avail_file'  => REAL_PATH .'/locale/langs.ini',
      'domains_path_file' => REAL_PATH .'/locale/domains.ini',
      'default_domain'    => 'messages',
      'file_type'         => 'po',
      'default_lang'      => DEFAULT_LANGUAGE
    );
    $this->Translation = Translation2::factory('gettext', $options);
    if ($Session->get('language')) {
      $this->Translation->setLang($Session->get('language'));
    } else {
      $this->Translation->setLang(DEFAULT_LANGUAGE);
    }
    $this->Translation =& $this->Translation->getDecorator('DefaultText');

    /**
    * Requires HTML/Template/Flexy.
    */
    require_once 'HTML/Template/Flexy.php';
    $options = &PEAR::getStaticProperty('HTML_Template_Flexy','options');
    $options = array(
      'templateDir'   => REAL_PATH .'/template',
      'compileDir'    => REAL_PATH .'/tmp/compile_dir',
      'forceCompile'  => 0,
      'debug'         => DEBUG_FLEXY,
      'locale'        => $this->Translation->getLang(),
      'compiler'      => 'Standard',
      'Translation2'  => $this->Translation,
    );
    $this->Flexy = new HTML_Template_Flexy($options);

    /** Cria a classe array que contera as strings enviadas ao Flexy */
    $this->Template = new StdClass;
    $this->Template->outputHead = '';
    $this->Template->body = '';
    $this->Template->outputJShead = '';
    $this->Template->outputJSbody = '';
    $this->Template->outputDebug = '';
    $this->Template->outputError = '';
    $this->Template->outputUser = '';
    $this->Template->outputBody = '';
    $this->Template->outputMenu = '';
    $this->Template->outputTopMenu = '';
    $this->Template->outputDate = '';
    $this->Template->outputDomain = '';
    $this->Template->outputTools = '';
    $this->Template->sys_theme_dir = FULL_THEME_DIR;
    $this->Template->sys_url_server = SYS_URL_SERVER;
    $this->Template->sys_url_index = SYS_URL_INDEX;
  }

  /**
  * PEAR Error Handler
  * Handle PEAR errors in a friendly way
  *
  * @static
  * @access   public
  * @param    PEAR_Error     $obj     PEAR Error Object
  * @param    string         $action  Action, null or die to exit script
  */
  function PEARErrorHandler (&$obj, $action = '')
  {
    $msg = $obj->getMessage();
    $code = $obj->getCode();
    $info = $obj->getUserInfo();

    if (DEBUG && $code != TRANSLATION2_ERROR_CANNOT_FIND_FILE) {
      $Error['msg'] = $msg;
      $Error['code'] = $code;
      $Error['info'] = htmlspecialchars($info);

      $_SESSION['Errors'][] = $Error;
    }
    if ($action == 'die') die();
  }

  /**
  * Debug
  * Show debug messages using template
  *
  * @access   public
  * @param    string     $description     Description to show on Debug area
  * @param    mixed      $var             Variable to display, can be an array
  */
  function Debug ($description, $var = null)
  {
    if (DEBUG) {
      if (is_array($var)) {
        $var = var_export($var, true);
      }

      $this->Template->outputDebug .= $description;
      if ($var) {
        $this->Template->outputDebug .=  ": <pre>$var</pre>";
      }
      $this->Template->outputDebug .= "\n";
    }
  }

  /**
  * _tr
  * Translate a string using Translation2
  *
  * @access   public
  * @param    string     $msgid  Message ID to translate
  * @return   string     Message translated
  */
  function _tr($msgid)
  {
    return ($this->Translation->get($msgid));
  }

  /**
  * SetLangByBrowser
  * Set Translation2 language using users browser preference 
  * (if language does not exists, remains language already set)
  *
  * @access   public
  * @param    string     $msgid  Message ID to translate
  * @return   string     Message translated
  */
  function SetLangByBrowser()
  {
    $browser_language = array();
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $language) {
      $language = explode(';', $language);
      $browser_language[] = $language[0];
    }

    $languages_available = $this->Translation->getLangs('ids');
    foreach ($browser_language as $language) {
      if (in_array($language, $languages_available)) {
        $this->Translation->setLang($language);
        return(true);
      }
    }
    return(false);
  }

  /**
  * GetVar
  * Get $_REQUEST http variable.(GET or POST). It can return a default
  * value if variable is empty
  *
  * @static
  * @access   public
  * @param    string     $var        Name of requested variable
  * @param    string     $default    Default value to return if requested var is empty
  * @return   string     requested variable or default value if it's empty
  */
  function GetVar ($var, $default = '') {
    $return = $_REQUEST[$var];
    if (! $return) {
      $return = $default;
    }
    return ($return);
  }

  /**
  * ValidateName
  * Extend QuickForm validation, using PEAR/Validate to check if an name
  * is valid (this primary use is for brasilian names that can be accented
  * but we don't want accents in LDAP)
  * Rules: VALIDATE_ALPHA . VALIDATE_SPACE . VALIDATE_NUM
  *
  * @static
  * @access   public
  * @param    string     $name  Name to validate
  * @return   mixed      True (name valid) False (name not valid)
  */
  function ValidateName($name)
  {
    require_once 'Validate.php';

    if (! $name) {
      return(true);
    }

    if (Validate::string($name, array('format' => VALIDATE_ALPHA . VALIDATE_SPACE . VALIDATE_NUM))) {
      return(true);
    } else {
      return(false);
    }
  }

  /**
  * FormatTimestamp
  * Format timestamp with I18Nv2
  *
  * @static
  * @access   public
  * @param    timestamp     $datetime  Date / time to format
  * @return   mixed         Timestamp formatted or '-' if $datetime is null
  */
  function FormatTimestamp($datetime)
  {
    if ($datetime) {
      return($this->Locale->formatDateTime($datetime));
    } else {
      return('-');
    }
  }

###############################################################################
#                                LOGIN FORM                                   #
###############################################################################


  /**
  * LoginForm
  * Create a login form using QuickForm for PEAR/Auth
  *
  * @access   public
  */
  function LoginForm($username = null, $status = null, &$auth)
  {
    $this->SetLangByBrowser();

    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/Renderer/Tableless.php';

    $form = new HTML_QuickForm('form_senha', 'post');
    
    // Define parametros 'default' do formulario
    $form->setRequiredNote($this->_tr('* obrigatory field'));
    $form->setJsWarnings($this->_tr('Error:'), $this->_tr('please verify form.'));
    
    $username = & HTML_QuickForm::createElement('text', 'username', $this->_tr('User'), array('size' => 15, 'maxlength' => 25));
    $password = & HTML_QuickForm::createElement('password', 'password', $this->_tr('Password'), array('size' => 15, 'maxlength' => 10));
    foreach ($this->Translation->getLangs('array') as $id => $lang) {
      $languages[$id] = $lang['name'];
    }
    $language = & HTML_QuickForm::createElement('select', 'language', $this->_tr('Language'), $languages);
    $language->setValue($this->Translation->lang['id']);

    $form->addElement($username);
    $form->addElement($password);
    $form->addElement($language);
    $form->addElement('submit', 'doLogin', 'Login');
    
    // Define os filtros
    $form->applyFilter('username', 'trim');
    $form->applyFilter('username', 'strtolower');
    $form->applyFilter('password', 'trim');
    $form->applyFilter('password', 'strtolower');
    
    // Define regras de validacao (regras de Action sao apenas protecao no caso de POSTS 'manuais'
    $form->addRule('username', $this->_tr('Field exceeded maximum length:') . $this->_tr(' User'), 'maxlength', 25, 'client');
    $form->addRule('username', $this->_tr('Obrigatory field:') . $this->_tr(' User'), 'required', null, 'client');
    $form->addRule('password', $this->_tr('Field exceeded maximum length:') . $this->_tr(' Password'), 'maxlength', 10, 'client');
    $form->addRule('password', $this->_tr('Obrigatory field:') . $this->_tr(' Password'), 'required', null, 'client');

    // Cria instancia do renderizador Tableless e desenha o form
    $renderer = & new HTML_QuickForm_Renderer_Tableless();
    $form->accept($renderer);

    if ($status < 0) {
      echo ($this->_tr('<p class="loginerror">Invalid Login</p>'));
    }

    if ( ! ereg("auth.html$", $this->Flexy->currentTemplate)) {
      $this->Template->outputBody = $renderer->toHtml();
      $this->Flexy->compile('auth.html');
      $this->Flexy->outputObject($this->Template);
    }
  }

###############################################################################
#                                MENU METHODS                                 #
###############################################################################

  /**
  * Menu
  * Display left and top menu using PEAR/HTML/Menu
  *
  * @access   public
  */
  function Menu()
  {
    /** Criacao do Menu */
    require_once 'HTML/Menu.php';
    require_once 'HTML/Menu/DirectRenderer.php';
    require_once 'HTML/Menu/DirectTreeRenderer.php';

    $menu =& new HTML_Menu($this->MenuArray, 'sitemap');
    $renderer =& new HTML_Menu_DirectTreeRenderer();
    $renderer->setLevelTemplate('<ul id="nav">', '</ul>');
    $menu->render($renderer);
    $this->Template->outputMenu = $renderer->toHtml();

    $menu_top =& new HTML_Menu($this->MenuArray, 'urhere');
    $renderer_top =& new HTML_Menu_DirectRenderer();
    $renderer_top->setMenuTemplate ('<table border="0">', '</table>');
    $menu_top->render($renderer_top);
    $this->Template->outputTopMenu = $renderer_top->toHtml();
  }

###############################################################################
#                                GRID METHODS                                 #
###############################################################################

  /**
  * Datagrid
  * Format and render a datagrid using Structures/Datagrid
  *
  * @access   public
  * @param    object     $datagrid  Structure_DataGrid object
  * @return   string     HTML code to draw table
  */
  function Datagrid(&$datagrid, $options = array())
  {
    $tableAttribs = array (
      'width' => '100%',
      'class' => 'log'
    );
/*
    $headerAttribs = array(
      'class' => 'datagrid_header'
    );
    $evenRowAttribs = array(
      'class' => 'datagrid_even'
    );
    $oddRowAttribs = array(
      'class' => 'datagrid_odd'
    );

    $rendererOptions = array(
      'sortIconASC' => '<img src="'.$sys_theme_dir.'/images/asc.gif'.'" alt="ASC" border="0" />',
      'sortIconDESC' => '<img src="'.$sys_theme_dir.'/images/desc.gif'.'" alt="DESC" border="0" />',
    );
*/
    // Instancia um objeto HTML_Table que sera utilizado na renderizacao
    $table = new HTML_Table($tableAttribs);
    $tableHeader =& $table->getHeader();
    $tableBody =& $table->getBody();

    $datagrid->fill($table, $options);

    // Atributos do cabecalho da tabela
    $tableHeader->setRowAttributes(0, $headerAttribs);

    // Atributos das linhas da tabela
    $tableBody->altRowAttributes(0, $evenRowAttribs, $oddRowAttribs, true);
    
    // Mostra a tabela
    return ($table->toHtml());
  }

  /**
  * print_checkbox
  * Prints a checkbox for each grid line (Can be replaced by Structures_DataGrid_Renderer_CheckableHTMLTable soon)
  *
  * @access   public
  * @param    array     $params  Params sent by Structures_DataGrid
  * @param    array     $args    Array of ['exception_values'] and ['field_key']
  *                           .  field_key is the name of the primary key of table
  *                              exception_values is values of primary key where checkbox will NOT be draw
  * @return   string    HTML code to draw checkbox
  */
  function print_checkbox($params, $args)
  {
    extract($params);

    if ($args['exception_values']) {
      if (! in_array($record[$args['field_key']], $args['exception_values']) ) {
        return('<input type="checkbox" name="selection[]" value="' . $record[$args['field_key']] . '">');
      }
    } else {
      return('<input type="checkbox" name="selection[]" value="' . $record[$args['field_key']] . '">');
    }
  }

###############################################################################
#                              DISPLAY METHODS                                #
###############################################################################

  /**
  * Title
  * Sets page title
  *
  * @access   public
  * @param    string     $title   Title of the page
  * @param    string     $icon    Location of icon (if you want an icon)
  */
  function Title($title, $icon = null)
  {
    if ($icon) {
      $icon = '<img src="' . FULL_THEME_DIR . '/images/' . $icon . '" style="margin-right:0.2em;">';
    }
    $this->Template->outputBody = '<h4>' . $icon . $title . '</h4>';
  }

  /**
  * Toolbar
  * Display page toolbar
  *
  * @access   public
  * @param    bool     $filter   Show filter in toolbar (to get filter values use $this->GetVar('Filter)
  * @param    bool     $add      Show add record button
  * @param    bool     $delete   Show delete records button
  * @param    bool     $update   Show update record button
  */
  function Toolbar($filter = true, $add = true, $delete = true, $update = true) 
  {
    require_once 'Validate.php';
  
    if ($this && ($filter || $add || $delete || $update)) {
      $this->Template->outputBody .= '<div>';
    } else {
      return(false);
    }
  
    if ($filter) {
      if (Validate::string($this->GetVar('filter'), array('format' => VALIDATE_ALPHA . VALIDATE_SPACE . VALIDATE_NUM))) {
        $this->Filter =  $this->GetVar('filter');
      }
  
      $this->Template->outputBody .= '<form name="head_form" method="post">';
      $this->Template->outputBody .= '<input type="text" name="filter" value="' . $this->Filter . '">&nbsp;';
  
      $this->Template->outputBody .= '<img src="' . FULL_THEME_DIR . '/images/viewmag.png">&nbsp;';
      $this->Template->outputBody .= '<a href="#" onclick="document.head_form.submit();">' . $this->_tr('Search') . '</a>&nbsp;';
  
      $this->Template->outputBody .= '<img src="' . FULL_THEME_DIR . '/images/clear_left.png">&nbsp;';
      $this->Template->outputBody .= '<a href="?filter=">' . $this->_tr('View all') . '</a>';
  
      $this->Template->outputBody .= '</form>';
    }
  
    if ($add) {
      $this->Template->outputBody .= '<img src="' . FULL_THEME_DIR . '/images/identity.png" style="margin-left:1.5em;margin-right:0.2em;">';
      $this->Template->outputBody .= '<a href="?action=insert">' . $this->_tr('Add') . '</a>';
    }
  
    if ($delete) {
      $this->Template->outputBody .= '<img src="' . FULL_THEME_DIR . '/images/no.png" style="margin-left:1.5em;margin-right:0.2em;">';
      $this->Template->outputBody .= '<a href="#" onclick="confirmSubmit(\'delete\', \'' . $this->_tr('Are you sure you want to delete (This operation can not be undone)?') . '\');">' . $this->_tr('Remove') . '</a>';
  
      $this->Template->outputJShead .= "
      function confirmSubmit(cmd, msg) {
        document.listForm.action.value = cmd;
        var result;
        result = confirm(msg);
        if (result == true) {
          document.listForm.submit();
        }
      }
      ";
    }

    if ($filter || $add || $delete || $update) {
      $this->Template->outputBody .= '</div><br>';
    }
  
    return(true);
  }

  /**
  * CheckAuth
  * Checks authentication
  *
  * @access   public
  * @return   bool     true if user is authenticated or false if not authenticated
  */
  function CheckAuth()
  {
    global $Session;

    require_once 'Auth.php';
    $this->Auth['Object'] = new Auth($this->Auth['Type'], $this->Auth['Options'], array($this, 'LoginForm'));

    $this->Auth['Object']->start();

    if ($_GET['action'] == "logout" && $this->Auth['Object']->checkAuth()) {
      $this->Auth['Object']->logout();
      $Session->destroy();
      $this->Auth['Object']->start();
    }

    if ($this->Auth['Object']->checkAuth()) {
      return(true);
    } else {
      return(false);
    }
  }

  /**
  * Draw
  * Draw the page using HTML_Template_Flexy engine, based on $this->Template object
  *
  * @access   public
  */
  function Draw()
  {
    global $Session;

    if ( (DEBUG) && (@count($_SESSION['Errors']) > 0) ) {
      $this->Template->outputErrors  =  "<pre>";
      foreach ($_SESSION['Errors'] as $error) {
        $this->Template->outputErrors .=  'Error (' . $error['code'] . ')<strong>: ' . $error['msg'] . "</strong>\n";
        if ($error['info']) {
          $this->Template->outputErrors .= 'Info: ' . $error['info'] . "\n";
        }
        $this->Template->outputErrors .= "\n";
      }
      $this->Template->outputErrors .=  "</pre>";
    }

    if ($this->CheckAuth()) {
      if (! $this->Template->body) {
        $this->Template->body = '<body>';
      }

      if ($this->Template->outputJShead) {
        $this->Template->outputJShead =
          '<script type="text/javascript">
            //<![CDATA['.
            $this->Template->outputJShead.
            '//]]>
          </script>';
      }
      if ($this->Template->outputJSbody) {
        $this->Template->outputJSbody =
          '<script type="text/javascript">
            //<![CDATA['.
            $this->Template->outputJSbody.
            '//]]>
          </script>';
      }

      $this->Template->outputUser = $this->_tr('Logged in as') . ' ' . $this->Auth['Object']->getUsername() . ' | <a href="/?action=logout">' . $this->_tr('Logout') . '</a>';
      $this->Menu();
      $this->Template->outputDate = $this->Locale->formatDate(mktime());
      $this->Flexy->compile('body.html');
      $this->Flexy->outputObject($this->Template);
    }
  }
}

?>
