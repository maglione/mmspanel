<?php
/**
 * samba/groups.php - Samba group manager interface
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


require_once 'samba.php';

class SambaGroups extends Samba
{
  var $Form;

  function SambaGroups()
  {
    $this->Samba();
  }

  function _PrintGroupLink($params, $args)
  {
    extract($params);
    
    return ('<a href="?action=update&cn=' . htmlentities($record['cn']) . '">' . $record[$args['field']] . '</a>');
  }
  
  function _PrintGridMembers($params)
  {
    extract($params);
    return(nl2br($record['memberUid']));
  }

  function GroupFormCreate()
  {
    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/Renderer/Tableless.php';

    /** Create a new instance of QuickForm */
    $this->Form = new HTML_QuickForm('form_groups', 'post', null, null, array('class' => 'forms'));

    /** Define 'default' parameters of form */
    $this->Form->setRequiredNote($this->_tr('* obrigatory field'));
    $this->Form->setJsWarnings($this->_tr('Error:'), $this->_tr('please verify form.'));

    /** Cache action */
    $action_cache = & HTML_QuickForm::createElement('hidden', 'action');
    $action_cache->setValue($this->GetVar('action', 'view'));

    /** Define field elements */
    $group = & HTML_QuickForm::createElement('text', 'group', $this->_tr('Group'), array('size' => 40, 'maxlength' => 40));
    $description = & HTML_QuickForm::createElement('text', 'description', $this->_tr('Description'), array('size' => 40, 'maxlength' => 60));
    $users = $this->QFUsersMultiselect('users', $this->_tr('Users:'));
    
    /** Add all elements in the form */
    $this->Form->addElement($action_cache);
    $this->Form->addElement($group);
    $this->Form->addElement($description);
    $this->Form->addElement($users);
    $this->Form->addElement('submit', 'Insert', $this->_tr('Insert'));
  }

  function GroupFormFilters()
  {
    /** Apply some filters */
    $this->Form->applyFilter('__ALL__', 'trim');
  }

  function GroupFormRules($action)
  {
    /** Groupname validation */
    $this->Form->addRule('group', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Group'), 'maxlength', 40, 'client');
    $this->Form->addRule('group', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Group'), array('MaglioneFramework', 'ValidateName'), null, 'client');
    $this->Form->addRule('group', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Group'), 'required', null, 'client');
   
    /** Description validation */
    $this->Form->addRule('description', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Description'), 'maxlength', 60, 'client');
    $this->Form->addRule('description', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Description'),'callback', array('MaglioneFramework', 'ValidateName'), 'server');
  }
  
  function GroupFormSetValues($group_cn)
  {
    $group_array = $this->GroupInfoGet($group_cn);

    $group = & $this->Form->getElement('group');
    $description = & $this->Form->getElement('description');
    $users = & $this->Form->getElement('users');
  
    $group->setValue($group_array['cn']);
    $group->Freeze();

    $description->setValue($group_array['description']);
    $users->setValue($this->GetUsersArray($group_array['cn'], true));
  }
    
  function GroupFormDisplay()
  {
    /** Cria instancia do renderizador Tableless e desenha o form */
    $renderer = & new HTML_QuickForm_Renderer_Tableless();
    $this->Form->accept($renderer);
    $this->Template->outputBody .= $renderer->toHtml();
  }

  function GroupsGridDisplay()
  {
    /** Get all samba users from LDAP */
    $users = array();
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('cn','description', 'memberUid')
              );
    if ($this->Filter) {
      $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, '(&(objectclass=sambaGroupMapping)( | (cn=*' . $this->Filter . '*) (description=*' . $this->Filter . '*) ))', $options);
    } else {
      $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, '(objectclass=sambaGroupMapping)', $options);
    }

    $groups = array();
    while ($entry = $search->shiftEntry()) {
      $group = array();

      $group['cn'] = $entry->getValue('cn', 'single');
      $group['description'] = ($entry->exists('description')) ? $entry->getValue('description', 'single') : "";
      $group['memberUid'] = ($entry->exists('memberUid')) ? $entry->getValue('memberUid', 'all') : "";
      if (is_array($group['memberUid'])) {
        natcasesort($group['memberUid']);
        $group['memberUid'] = implode("\n", $group['memberUid']);
      }

      $groups[] = $group;
    }
    asort($groups);

    $this->Debug("GroupsGridDisplay(): groups", $groups);

    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $users array */
    $datagrid->bind($groups);

    /** Set columns */
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Group'), 'cn', 'cn', null, null, array('SambaGroups', '_PrintGroupLink'), array('field' => 'cn')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Description'), 'description', 'description', null, null, array('SambaGroups', '_PrintGroupLink'), array('field' => 'description')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Members'), 'memberUid', 'memberUid', null, null, array('SambaGroups', '_PrintGridMembers')));
    $datagrid->addColumn(new Structures_DataGrid_Column('', null, null, null, null, array('MaglioneFramework', 'print_checkbox'), array('field_key' => 'cn', 'exception_values' => array('Domain Admins', 'Domain Users'))));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }

  function GroupsDelete()
  {
    $selection = $this->GetVar('selection', null);

    $this->Debug("GroupsDelete(): selection", $selection);

    /** Delete all selected entries from LDAP */
    foreach ($selection as $group_cn) {
      $this->GroupDel($group_cn);
    }
  }
  
} # End of SambaUsers Class

function process_group($values)
{
  global $Framework;

  if (is_array($values['users'])) {
    for ($i = 0; $i < count($values['users']); $i++) {
      $values['users'][$i] = escapeshellarg($values['users'][$i]);
    }
    $members = implode(',', $values['users']);
  } else {
    $members = '';
  }

  if ($values['action'] == 'insert') {
    $Framework->GroupAdd($values['group'], $values['description'], $members);
  }
  elseif ($values['action'] == 'update') {
  $Framework->GroupMod($values['group'], $values['description'], $members);
  }

}

/** Create a new instance of MaglioneFramework */
$Framework = new SambaGroups();

/** Sets the page title */
$Framework->Title($Framework->_tr('Groups'), '/samba/groups.png');

/** Sets default action to view */
$action = $Framework->GetVar('action', 'view');

/** If $action = delete, then delete the selected users */
if ($action == 'delete') {
  $Framework->GroupsDelete();
  $action = 'view';
}

if ($action == 'insert' || $action == 'update') {
  $Framework->GroupFormCreate();
  $Framework->GroupFormFilters();
  $Framework->GroupFormRules($action);

  if ($action == 'update') {
    $Framework->GroupFormSetValues($Framework->GetVar('cn', null));
  }

  /** Check form validation */
  if ($Framework->Form->validate()) {
    $Framework->Form->process('process_group');
    $action = 'view';
  } else {
    $Framework->Toolbar(true, false, false, false);

    /** Draw Form */
    $Framework->GroupFormDisplay();
  }
}

/** If $action = view then display all users on LDAP */
if ($action == 'view') {
  $Framework->Toolbar(true, true, true, true);
  $Framework->GroupsGridDisplay();
}

/** Draw the page */
$Framework->Draw();
?>