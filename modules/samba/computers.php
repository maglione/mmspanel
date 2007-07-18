<?php
/**
 * samba/groups.php - Samba computer manager interface
 * 
 * @author Daniel Maglione <daniel@maglione.com.br>
 * @version 1.0
 * @package netpanel-samba
 * @project MAGLIONE NetPanel
 * @copyright Maglione Informatica 2007
 */

require_once 'samba.php';

class SambaComputers extends Samba
{
  var $Form;

  function SambaComputers()
  {
    $this->Samba();
  }

  function _PrintComputerLink($params, $args)
  {
    extract($params);
    
    return ('<a href="?action=update&uid=' . htmlentities($record['uid']) . '">' . $record[$args['field']] . '</a>');
  }
  
  function _PrintGridMembers($params)
  {
    extract($params);
    return(nl2br($record['memberUid']));
  }

  function ComputerFormCreate()
  {
    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/Renderer/Tableless.php';

    /** Create a new instance of QuickForm */
    $this->Form = new HTML_QuickForm('form_computers', 'post', null, null, array('class' => 'forms'));

    /** Define 'default' parameters of form */
    $this->Form->setRequiredNote($this->_tr('* obrigatory field'));
    $this->Form->setJsWarnings($this->_tr('Error:'), $this->_tr('please verify form.'));

    /** Cache action */
    $action_cache = & HTML_QuickForm::createElement('hidden', 'action');
    $action_cache->setValue($this->GetVar('action', 'view'));

    /** Define field elements */
    $computer = & HTML_QuickForm::createElement('text', 'computer', $this->_tr('Computer'), array('size' => 40, 'maxlength' => 40));
    $description = & HTML_QuickForm::createElement('text', 'description', $this->_tr('Description'), array('size' => 40, 'maxlength' => 60));

    /** Add all elements in the form */
    $this->Form->addElement($action_cache);
    $this->Form->addElement($computer);
    $this->Form->addElement($description);
    $this->Form->addElement('submit', 'Insert', $this->_tr('Insert'));
  }

  function ComputerFormFilters()
  {
    /** Apply some filters */
    $this->Form->applyFilter('__ALL__', 'trim');
  }

  function ComputerFormRules($action)
  {
    /** Groupname validation */
    $this->Form->addRule('computer', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Computer'), 'maxlength', 40, 'client');
    $this->Form->addRule('computer', $this->_tr('Field must contains only numbers and letters:') . ' ' .  $this->_tr('Computer'), array('MaglioneFramework', 'ValidateName'), null, 'client');
    $this->Form->addRule('computer', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Computer'), 'required', null, 'client');
   
    /** Description validation */
    $this->Form->addRule('description', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Description'), 'maxlength', 60, 'client');
    $this->Form->addRule('description', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Description'),'callback', array('MaglioneFramework', 'ValidateName'), 'server');
  }
  
  function ComputerFormSetValues($computer_uid)
  {
    $computer_array = $this->ComputerInfoGet($computer_uid);

    $computer = & $this->Form->getElement('computer');
    $description = & $this->Form->getElement('description');

    $computer->setValue($computer_array['uid']);
    $computer->Freeze();

    $description->setValue($computer_array['description']);
  }

  function ComputerFormDisplay()
  {
    /** Cria instancia do renderizador Tableless e desenha o form */
    $renderer = & new HTML_QuickForm_Renderer_Tableless();
    $this->Form->accept($renderer);
    $this->Template->outputBody .= $renderer->toHtml();
  }

  function ComputersGridDisplay()
  {
    /** Get all samba users from LDAP */
    $users = array();
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('uid','description')
              );
    if ($this->Filter) {
      $search = $this->Ldap->search(LDAP_COMPUTERS_OU . ', ' . LDAP_ROOT_DN, '(&(objectclass=posixAccount)( | (uid=*' . $this->Filter . '*) (description=*' . $this->Filter . '*) ))', $options);
    } else {
      $search = $this->Ldap->search(LDAP_COMPUTERS_OU . ', ' . LDAP_ROOT_DN, '(objectclass=posixAccount)', $options);
    }

    $computers = array();
    while ($entry = $search->shiftEntry()) {
      $computer = array();

      $computer['uid'] = $entry->getValue('uid', 'single');
      $computer['description'] = ($entry->exists('description')) ? $entry->getValue('description', 'single') : "";

      $computers[] = $computer;
    }
    asort($computers);

    $this->Debug("ComputersGridDisplay(): computers", $computers);

    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $computers array */
    $datagrid->bind($computers);

    /** Set columns */
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Computer'), 'uid', 'uid', null, null, array('SambaComputers', '_PrintComputerLink'), array('field' => 'uid')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Description'), 'description', 'description', null, null, array('SambaComputers', '_PrintComputerLink'), array('field' => 'description')));
    $datagrid->addColumn(new Structures_DataGrid_Column('', null, null, null, null, array('MaglioneFramework', 'print_checkbox'), array('field_key' => 'uid', )));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }

  function ComputersDelete()
  {
    $selection = $this->GetVar('selection', null);

    $this->Debug("ComputersDelete(): selection", $selection);

    /** Delete all selected entries from LDAP */
    foreach ($selection as $computer_uid) {
      /** Computer accounts are special "user account" */
      $this->UserDel($computer_uid);
    }
  }
  
} # End of SambaComputers Class

function process_computer($values)
{
  global $Framework;

  if ($values['action'] == 'insert') {
    $Framework->ComputerAdd($values['computer'], $values['description']);
  }
  elseif ($values['action'] == 'update') {
  $Framework->ComputerMod($values['computer'], $values['description']);
  }

}

/** Create a new instance of MaglioneFramework */
$Framework = new SambaComputers();

/** Sets the page title */
$Framework->Title($Framework->_tr('Computers'), '/samba/computers.png');

/** Sets default action to view */
$action = $Framework->GetVar('action', 'view');

/** If $action = delete, then delete the selected users */
if ($action == 'delete') {
  $Framework->ComputersDelete();
  $action = 'view';
}

if ($action == 'insert' || $action == 'update') {
  $Framework->ComputerFormCreate();
  $Framework->ComputerFormFilters();
  $Framework->ComputerFormRules($action);

  if ($action == 'update') {
    $Framework->ComputerFormSetValues($Framework->GetVar('uid', null));
  }

  /** Check form validation */
  if ($Framework->Form->validate()) {
    $Framework->Form->process('process_computer');
    $action = 'view';
  } else {
    $Framework->Toolbar(true, false, false, false);

    /** Draw Form */
    $Framework->ComputerFormDisplay();
  }
}

/** If $action = view then display all users on LDAP */
if ($action == 'view') {
  $Framework->Toolbar(true, true, true, true);
  $Framework->ComputersGridDisplay();
}

/** Draw the page */
$Framework->Draw();
?>