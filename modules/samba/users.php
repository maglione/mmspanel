<?php
/**
 * samba/users.php - Samba user manager interface
 * 
 * @author Daniel Maglione <daniel@maglione.com.br>
 * @version 1.0
 * @package netpanel-samba
 * @project MAGLIONE NetPanel
 * @copyright Maglione Informatica 2007
 */

require_once 'samba.php';

class SambaUsers extends Samba
{
  var $Form;

  function SambaUsers()
  {
    $this->Samba();
  }

  function _PrintUserLink($params, $args)
  {
    extract($params);
  
    return ('<a href="?action=update&uid=' . htmlentities($record['uid']) . '">' . $record[$args['field']] . '</a>');
  }

  /**
  * Return user flags legend table
  */
  function UserFlagsLegend()
  {
    $legend = new HTML_Table(array('style' => 'background-color: #eeeeee;'));
    $legend->setAutoGrow(true);
    $legend->setAutoFill("&nbsp;");
    $legend->addRow(array('* ' . $this->_tr('Samba Flags')), array('colspan' => '2'), 'th', false);
    $legend->addRow(array('<b>N</b>', $this->_tr('No password required')));
    $legend->addRow(array('<b>D</b>', $this->_tr('Account disabled')));
    $legend->addRow(array('<b>H</b>', $this->_tr('Home directory required')));
    $legend->addRow(array('<b>T</b>', $this->_tr('Temporary duplicate of other account')));
    $legend->addRow(array('<b>U</b>', $this->_tr('Regular user account')));
    $legend->addRow(array('<b>M</b>', $this->_tr('MNS logon user account')));
    $legend->addRow(array('<b>W</b>', $this->_tr('Workstation Trust Account')));
    $legend->addRow(array('<b>S</b>', $this->_tr('Server Trust Account')));
    $legend->addRow(array('<b>L</b>', $this->_tr('Automatic Locking')));
    $legend->addRow(array('<b>X</b>', $this->_tr('Password does not expire')));
    $legend->addRow(array('<b>I</b>', $this->_tr('Domain Trust Account')));

    $this->Template->outputTools .= $legend->toHtml();
  }

  # function samba_users_showinfo()
  function UserInfoShow($uid)
  {
    $this->UserFlagsLegend();
    if ($uid) {
      $user = $this->UserInfoGet($uid);
  
      $table = new HTML_Table();
      $table->setAutoGrow(true);
      $table->setAutoFill("&nbsp;");
  
      $table->addRow(array($this->_tr('Username'), $user['uid']));
      $table->addRow(array($this->_tr('Full Name'), $user['displayName']));
      $table->addRow(array($this->_tr('E-Mail'), '<a href="mailto:' . $user['mail'] . '">' . $user['mail'] . '</a>'));
      $table->addRow(array($this->_tr('Home Directory'), $user['homeDirectory']));
      if ($user['sambaPwdCanChange']) {
        $table->addRow(array($this->_tr('User can change password?'), $this->_tr('Yes')));
      } else {
        $table->addRow(array($this->_tr('User can change password?'), $this->_tr('No')));
      }
  
      $table->addRow(array($this->_tr('DN'), $user['dn']));
      $table->addRow(array($this->_tr('UID'), $user['uidNumber']));
      $table->addRow(array($this->_tr('SID'), $user['sambaSID']));
      $table->addRow(array($this->_tr('Samba Flags') . ' *', $user['sambaAcctFlags']));
      $table->addRow(array($this->_tr('Last login'), $this->FormatTimestamp($user['sambaLogonTime'])));
      $table->addRow(array($this->_tr('Last logout'), $this->FormatTimestamp($user['sambaLogoffTime'])));
      $table->addRow(array($this->_tr('Last password change'), $this->FormatTimestamp( $user['sambaPwdLastSet'])));
      $table->addRow(array($this->_tr('Next required password change'), $this->FormatTimestamp( $user['sambaPwdMustChange'])));
  
      $table->setColAttributes(1, array('style' => 'font-weight: bold;'));

      $this->Template->outputBody .= $table->toHtml();
    }
  }

  function UserFormCreate()
  {
    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/Renderer/Tableless.php';

    /** Create a new instance of QuickForm */
    $this->Form = new HTML_QuickForm('form_users', 'post', null, null, array('class' => 'forms'));

    /** Define 'default' parameters of form */
    $this->Form->setRequiredNote($this->_tr('* obrigatory field'));
    $this->Form->setJsWarnings($this->_tr('Error:'), $this->_tr('please verify form.'));

    /** Cache action */
    $action_cache = & HTML_QuickForm::createElement('hidden', 'action');
    $action_cache->setValue($this->GetVar('action', 'view'));

    /** Define field elements */
    $username = & HTML_QuickForm::createElement('text', 'username', $this->_tr('User'), array('size' => 15, 'maxlength' => 25));
    $fullname = & HTML_QuickForm::createElement('text', 'fullname', $this->_tr('Full Name'), array('size' => 40, 'maxlength' => 60));
    $email = & HTML_QuickForm::createElement('text', 'email', $this->_tr('E-Mail'), array('size' => 40, 'maxlength' => 60));
    $primary_group = & HTML_QuickForm::createElement('select', 'primary_group', $this->_tr('Primary group'), $this->GetGroupsArray());
    $groups = $this->QFGroupsMultiselect('groups', $this->_tr('Groups:'), $uid);
    $password = & HTML_QuickForm::createElement('password', 'password', $this->_tr('Password'), array('size' => 15, 'maxlength' => 10));
    $password_repeat = & HTML_QuickForm::createElement('password', 'password_repeat', $this->_tr('Password (repeat)'), array('size' => 15, 'maxlength' => 10));
    $sambaPwdMST = & HTML_QuickForm::createElement('checkbox', 'sambaPwdMST', null, $this->_tr('User Must Change Password'), null, 'MST');
    $sambaPwdCNT = & HTML_QuickForm::createElement('checkbox', 'sambaPwdCNT', null, $this->_tr('User Cannot Change Password'), null, 'CNT');

    $sambaAcctFlags[] = & HTML_QuickForm::createElement('checkbox', 'sambaFlagX', null, $this->_tr('Password Never Expires'));
    $sambaAcctFlags[] = & HTML_QuickForm::createElement('checkbox', 'sambaFlagD', null, $this->_tr('Account Disabled'));
    $sambaAcctFlags[] = & HTML_QuickForm::createElement('checkbox', 'sambaFlagL', null, $this->_tr('Account Locked'));
    
    /** Add all elements in the form */
    $this->Form->addElement($action_cache);
    $this->Form->addElement($username);
    $this->Form->addElement($password);
    $this->Form->addElement($password_repeat);
    $this->Form->addElement($fullname);
    $this->Form->addElement($email);
    $this->Form->addElement($primary_group);
    $this->Form->addElement($groups);
    $this->Form->addElement($sambaPwdMST);
    $this->Form->addElement($sambaPwdCNT);
    $this->Form->addGroup($sambaAcctFlags, 'sambaAcctFlags');
    $this->Form->addElement('submit', 'Insert', $this->_tr('Insert'));
  }

  function UserFormFilters()
  {
    /** Apply some filters */
    $this->Form->applyFilter('__ALL__', 'trim');
    $this->Form->applyFilter('username', 'strtolower');
    $this->Form->applyFilter('password', 'strtolower');
    $this->Form->applyFilter('email', 'strtolower');
  }

  function UserFormRules($action)
  {
    /** Username validation */
    $this->Form->addRule('username', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('User'), 'maxlength', 25, 'client');
    $this->Form->addRule('username', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('User'),'alphanumeric', null, 'client');
    $this->Form->addRule('username', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('User'), 'required', null, 'client');
   
    /** Password validation */
    if ($action == 'insert') {
      $this->Form->addRule('password', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Password'), 'required', null, 'client');
    }
    $this->Form->addRule('password', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Password'),'alphanumeric', null, 'client');
    $this->Form->addRule('password', $this->_tr('Password must have between 3 and 10 characters'), 'rangelength', array(3, 10), 'client');

    if ($action == 'insert') {
      $this->Form->addRule('password_repeat', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Password (repeat)'), 'required', null, 'client');
    }
    $this->Form->addRule('password_repeat', $this->_tr('Password must have between 3 and 10 characters'), 'rangelength', array(3, 10), 'client');

    $this->Form->addRule(array('password', 'password_repeat'), $this->_tr('The passwords do not match'), 'compare', null, 'client');

    /** Fullname validation */
    $this->Form->addRule('fullname', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Full Name'), 'maxlength', 60, 'client');
    $this->Form->addRule('fullname', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Full Name'),'callback', array('MaglioneFramework', 'ValidateName'), 'server');
    $this->Form->addRule('fullname', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Full Name'), 'required', null, 'client');

    /** Email validation */
    $this->Form->addRule('email', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('E-Mail'), 'maxlength', 60, 'client');
    $this->Form->addRule('email', $this->_tr('Not a valid e-mail'), 'email', false, 'client');

    /** Primary group validation */
    $this->Form->addRule('primary_group', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Primary group'), 'required', null, 'client');


    /** SambaPwdMST and SambaPwdCNT validation */
    $this->Form->addRule(array('sambaPwdMST', 'sambaPwdCNT'), $this->_tr('Only one of this fields can be selected at the same time:') . ' ' . $this->_tr('User Must Change Password') . ', ' . $this->_tr('User Cannot Change Password'), 'compare', 'neq', 'client');    
  }

  function UserFormSetValues($uid)
  {
    $user = $this->UserInfoGet($uid);

    $username = & $this->Form->getElement('username');
    $fullname = & $this->Form->getElement('fullname');
    $email = & $this->Form->getElement('email');
    $primary_group = & $this->Form->getElement('primary_group');
    $sambaPwdCNT = & $this->Form->getElement('sambaPwdCNT');
    $sambaAcctFlags = & $this->Form->getElement('sambaAcctFlags'); /** Group */
    $sambaAcctFlags = & $sambaAcctFlags->getElements();
    $groups = & $this->Form->getElement('groups');

    $username->setValue($user['uid']);
    $username->Freeze();

    $fullname->setValue($user['displayName']);

    $email->setValue($user['mail']);

    $primary_group->setValue($user['gidNumber']);

    if (ereg('X', $user['sambaAcctFlags'])) {
      $sambaAcctFlags[0]->setValue(1);
    }

    if (ereg('D', $user['sambaAcctFlags'])) {
      $sambaAcctFlags[1]->setValue(1);
    }

    if (ereg('L', $user['sambaAcctFlags'])) {
      $sambaAcctFlags[2]->setValue(1);
    }

    $groups->setValue($this->GetSupGroupsArray($uid, true));
  }

  function UserFormSetDefaultValues()
  {
    $primary_group = & $this->Form->getElement('primary_group');

    $primary_group->setValue(513);
  }

  function UserFormDisplay()
  {
    /** Cria instancia do renderizador Tableless e desenha o form */
    $renderer = & new HTML_QuickForm_Renderer_Tableless();
    $this->Form->accept($renderer);
    $this->Template->outputBody .= $renderer->toHtml();
  }

  function UsersGridDisplay()
  {
    /** Get all samba users from LDAP */
    $users = array();
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('uid','displayName', 'mail')
              );
    if ($this->Filter) {
      $search = $this->Ldap->search(LDAP_USERS_OU . ', ' . LDAP_ROOT_DN, '(&(objectclass=sambaSamAccount)( | (uid=*' . $this->Filter . '*) (displayName=*' . $this->Filter . '*) (mail=*' . $this->Filter . '*) ))', $options);
    } else {
      $search = $this->Ldap->search(LDAP_USERS_OU . ', ' . LDAP_ROOT_DN, '(objectclass=sambaSamAccount)', $options);
    }

    $users = array();
    while ($entry = $search->shiftEntry()) {
      $user = array();

      $user['uid'] = $entry->getValue('uid', 'single');
      $user['displayName'] = ($entry->exists('displayName')) ? $entry->getValue('displayName', 'single') : "";
      $user['mail'] = ($entry->exists('mail')) ? $entry->getValue('mail', 'single') : "";

      $users[] = $user;
    }
    asort($users);

    $this->Debug("UsersGridDisplay(): users", $users);

    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $users array */
    $datagrid->bind($users);

    /** Set columns */
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Username'), 'uid', 'uid', null, null, array('SambaUsers', '_PrintUserLink'), array('field' => 'uid')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Full Name'), 'displayName', 'displayName', null, null, array('SambaUsers', '_PrintUserLink'), array('field' => 'displayName')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('E-Mail'), 'mail', 'mail'));
    $datagrid->addColumn(new Structures_DataGrid_Column('', null, null, null, null, array('MaglioneFramework', 'print_checkbox'), array('field_key' => 'uid', 'exception_values' => array('root'))));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }

  function UsersDelete()
  {
    $selection = $this->GetVar('selection', null);

    $this->Debug("UsersDelete(): selection", $selection);

    /** Delete all selected entries from LDAP */
    foreach ($selection as $uid) {
      $this->UserDel($uid);
    }
  }
  
} # End of SambaUsers Class

function process_user($values)
{
  global $Framework;

  $flags = array('U');
  if ($values['sambaAcctFlags']['sambaFlagX']) {
    $flags[] = 'X';
  }
  if ($values['sambaAcctFlags']['sambaFlagD']) {
    $flags[] = 'D';
  }
  if ($values['sambaAcctFlags']['sambaFlagL']) {
    $flags[] = 'L';
  }

  $groups_tmp = $Framework->GetGroupsArray();
  if (is_array($values['groups'])) {
    /** Garante que o grupo primário fará parte dos grupos secundários */
    if (! in_array($groups_tmp[$values['primary_group']], $values['groups'])) {
      $values['groups'][] = $groups_tmp[$values['primary_group']];
    }
  } else {
    $values['groups'] = array($groups_tmp[$values['primary_group']]);
  }

  /** Escapa todos os grupos */
  for ($i = 0; $i < count($values['groups']); $i++) {
    $values['groups'][$i] = escapeshellarg($values['groups'][$i]);
  }
  $groups = implode(',', $values['groups']);

  if ($values['action'] == 'insert') {

    $Framework->UserAdd($values['username'], $values['fullname'], $values['password'], $values['primary_group'], $groups, $values['email'], $flags, $values['sambaPwdCNT'], $values['sambaPwdMST']);
  }
  elseif ($values['action'] == 'update') {
    $Framework->UserMod($values['username'], $values['fullname'], $values['primary_group'], $groups, $values['email'], $flags, $values['sambaPwdCNT'], $values['sambaPwdMST']);

    if ($values['password']) {
      $Framework->SetPasswd($values['username'], $values['password']);
    }
  }

}

/** Create a new instance of MaglioneFramework */
$Framework = new SambaUsers();

/** Sets the page title */
$Framework->Title($Framework->_tr('User Accounts'), '/samba/users.png');

/** Sets default action to view */
$action = $Framework->GetVar('action', 'view');

/** If $action = delete, then delete the selected users */
if ($action == 'delete') {
  $Framework->UsersDelete();
  $action = 'view';
}

if ($action == 'insert' || $action == 'update') {
  $Framework->UserFormCreate();
  $Framework->UserFormFilters();
  $Framework->UserFormRules($action);

  if ($action == 'update') {
    $Framework->UserFormSetValues($Framework->GetVar('uid', null));
  } else {
    $Framework->UserFormSetDefaultValues();
  }

  /** Check form validation */
  if ($Framework->Form->validate()) {
    $Framework->Form->process('process_user');
    $action = 'view';
  } else {
    $Framework->Toolbar(true, false, false, false);

    /** If $action = update, then show user info... */
    if ($action == 'update') {
      $Framework->UserInfoShow($Framework->GetVar('uid', null));
    }

    /** Draw Form */
    $Framework->UserFormDisplay();
  }
}

/** If $action = view then display all users on LDAP */
if ($action == 'view') {
  $Framework->Toolbar(true, true, true, true);
  $Framework->UsersGridDisplay();
}

/** Draw the page */
$Framework->Draw();
?>