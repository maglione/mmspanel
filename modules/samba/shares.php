<?php
/**
 * samba/groups.php - Samba shares manager interface
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

class SambaShares extends Samba
{
  function SambaShares()
  {
    $this->Samba();
  }

  function _FilterArray($share) {
    global $Framework;

    if ( eregi($Framework->Filter, strtolower($share['name'])) ) {
      return(true);
    } else {
      return(false);
    }
  }

  function GetShareByName($share_name)
  {
    require_once 'Config.php';
    require_once 'Config/Container.php';

    $conf = new Config;
    $root =& $conf->parseConfig(SHARES_CONFIG_FILE, 'IniCommented');

    for ($i = 0; $i < $root->countChildren(); $i++)
    {
      $item =& $root->getChild($i);
      if ($item->getName() == $share_name) {
        $item_arr = $item->toArray();
  
        $share['name'] = $item->getName();
        $share['comment'] = $item_arr[$share['name']]['comment'];
        $share['browseable'] = $item_arr[$share['name']]['browseable'];
        $share['available'] = $item_arr[$share['name']]['available'];
        $share['write list'] = $item_arr[$share['name']]['write list'];
        $share['read list'] = $item_arr[$share['name']]['read list'];

        return($share);
      }

      $this->Debug("GetShareByName($share_name)", $share);
    }
    return(false);
  }

  function _EscapeGroups($groups)
  {
    if (is_array($groups)) {
      for ($i = 0; $i < count($groups); $i++) {
        $groups[$i] = '@' . escapeshellarg($groups[$i]);
      }
      return($groups);
    } else {
      return(false);
    }
  }

  function InsertModifyShare($values)
  {
    require_once 'Config.php';
    require_once 'Config/Container.php';

    $conf = new Config;
    $root =& $conf->parseConfig(SHARES_CONFIG_FILE, 'IniCommented');

    /** If action is insert, check if the share does not exists yet */
    if ($values['action'] == 'insert') {
      for ($i = 0; $i < $root->countChildren(); $i++)
      {
        $item =& $root->getChild($i);
        if ( $values['share'] == strtolower($item->getName()) ) {
          return(false);
        }
      }
    }

    $fields =& $root->createSection($values['share']);

    $fields->createDirective('comment', $values['comment']);

    if ($values['browseable']) {
      $fields->createDirective('browseable', 'yes');
    } else {
      $fields->createDirective('browseable', 'no');
    }

    if ($values['available']) {
      $fields->createDirective('available', 'yes');
    } else {
      $fields->createDirective('available', 'no');
    }

    $write = array_merge(SambaShares::_EscapeGroups($values[groups_write]), $values['users_write']);
    if (count($write) > 0) {
      $fields->createDirective('write list', $write);
    }

    $read = array_merge(SambaShares::_EscapeGroups($values[groups_read]), $values['users_read']);
    if (count($read) > 0) {
      $fields->createDirective('read list', $read);
    }

    $fields->createDirective('hide dot files','yes'); 
    $fields->createDirective('csc policy', 'disabled');
    $fields->createDirective('nt acl support', 'yes');
    $fields->createDirective('dos filetime resolution', 'no');
    $fields->createDirective('dos filetimes', 'yes');
    $fields->createDirective('directory security mode', '0');
    $fields->createDirective('security mask', '0');
    $fields->createDirective('create mode', '0660');
    $fields->createDirective('directory mode', '0770');

    $fields->createBlank();
## PAREI AQUI ##
print_r($root->toArray());
    $root->writeDatasrc(SHARES_CONFIG_FILE, 'IniCommented');

/*
    [comment] => Teste de compartilhamento
    [groups_read] => Array
        (
            [0] => Domain Users
        )

    [users_read] => Array
        (
            [0] => outro
        )

    [groups_write] => Array
        (
            [0] => Domain Admins
            [1] => Execucao
        )

    [users_write] => Array
        (
            [0] => daniel
            [1] => mauro
        )

    [browseable] => 1
    [available] => 1
*/

  }










  function ShareFormCreate()
  {
    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/Renderer/Tableless.php';

    /** Create a new instance of QuickForm */
    $this->Form = new HTML_QuickForm('form_shares', 'post', null, null, array('class' => 'forms'));

    /** Define 'default' parameters of form */
    $this->Form->setRequiredNote($this->_tr('* obrigatory field'));
    $this->Form->setJsWarnings($this->_tr('Error:'), $this->_tr('please verify form.'));

    /** Cache action */
    $action_cache = & HTML_QuickForm::createElement('hidden', 'action');
    $action_cache->setValue($this->GetVar('action', 'view'));

    /** Define field elements */
    $share = & HTML_QuickForm::createElement('text', 'share', $this->_tr('Share name'), array('size' => 15, 'maxlength' => 25));
    $comment = & HTML_QuickForm::createElement('text', 'comment', $this->_tr('Comment'), array('size' => 40, 'maxlength' => 60));

    $groups_read = $this->QFGroupsMultiselect('groups_read', $this->_tr('Groups with read access:'));
    $users_read = $this->QFUsersMultiselect('users_read', $this->_tr('Users with read access:'));

    $groups_write = $this->QFGroupsMultiselect('groups_write', $this->_tr('Groups with write access:'));
    $users_write = $this->QFUsersMultiselect('users_write', $this->_tr('Users with write access:'));

    $browseable = & HTML_QuickForm::createElement('checkbox', 'browseable', null, $this->_tr('Browseable'));
    $available = & HTML_QuickForm::createElement('checkbox', 'available', null, $this->_tr('Available'));

    /** Add all elements in the form */
    $this->Form->addElement($action_cache);
    $this->Form->addElement($share);
    $this->Form->addElement($comment);
    $this->Form->addElement($groups_read);
    $this->Form->addElement($users_read);
    $this->Form->addElement($groups_write);
    $this->Form->addElement($users_write);
    $this->Form->addElement($browseable);
    $this->Form->addElement($available);
    $this->Form->addElement('submit', 'Insert', $this->_tr('Insert'));
  }

  function ShareFormFilters()
  {
    /** Apply some filters */
    $this->Form->applyFilter('__ALL__', 'trim');
    $this->Form->applyFilter('share', 'strtolower');
  }

  function ShareFormRules($action)
  {
    /** Share name validation */
    $this->Form->addRule('share', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Share'), 'maxlength', 25, 'client');
    $this->Form->addRule('share', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Share'),'alphanumeric', null, 'client');
    $this->Form->addRule('share', $this->_tr('Obrigatory field:') . ' ' . $this->_tr('Share'), 'required', null, 'client');

    /** Comment validation */
    $this->Form->addRule('comment', $this->_tr('Field exceeded maximum length:') . ' ' . $this->_tr('Comment'), 'maxlength', 60, 'client');
    $this->Form->addRule('comment', $this->_tr('Field must contains only numbers and letters:') . ' ' . $this->_tr('Comment'),'callback', array('MaglioneFramework', 'ValidateName'), 'server');
  }

  function ShareFormSetValues($share)
  {
    $share_arr = $this->GetShareByName($share);

    $share = & $this->Form->getElement('share');
    $comment = & $this->Form->getElement('comment');
    $browseable = & $this->Form->getElement('browseable');
    $available = & $this->Form->getElement('available');

    $share->setValue($share_arr['name']);
    $share->Freeze();

    $comment->setValue($share_arr['comment']);

    if ( (strtolower($share_arr['browseable']) == 'yes') || (strtolower($share_arr['browseable']) == 'ok') ) {
      $browseable->setValue(1);
    }

    if ( (strtolower($share_arr['available']) == 'yes') || (strtolower($share_arr['available']) == 'ok') ) {
      $available->setValue(1);
    }
  }

  function ShareFormSetDefaultValues()
  {
    $available = & $this->Form->getElement('available');

    $available->setValue(1);
  }

  function ShareFormDisplay()
  {
    /** Cria instancia do renderizador Tableless e desenha o form */
    $renderer = & new HTML_QuickForm_Renderer_Tableless();
    $this->Form->accept($renderer);
    $this->Template->outputBody .= $renderer->toHtml();
  }































  function SharesGridDisplay()
  {
    require_once 'Config.php';
    require_once 'Config/Container.php';

    $conf = new Config;
    $root =& $conf->parseConfig(SHARES_CONFIG_FILE, 'IniCommented');

    $shares = array();
    for ($i = 0; $i < $root->countChildren(); $i++)
    {
      $item =& $root->getChild($i);
      $item_arr = $item->toArray();

      $share['name'] = $item->getName();
      $share['browseable'] = strtolower($item_arr[$share['name']]['browseable']);
      $share['available'] = strtolower($item_arr[$share['name']]['available']);

      if (is_array($item_arr[$share['name']]['read list'])) {
        $share['read list'] = implode("\n", $item_arr[$share['name']]['read list']);
      } else {
        $share['read list'] = strtolower($item_arr[$share['name']]['read list']);
      }

      if (is_array($item_arr[$share['name']]['write list'])) {
        $share['write list'] = implode("\n", $item_arr[$share['name']]['write list']);
      } else {
        $share['write list'] = strtolower($item_arr[$share['name']]['write list']);
      }

      $shares[] = $share;
    }

    if ($this->Filter) {
      $shares = array_filter($shares, array('SambaShares', '_FilterArray'));
    }

    asort($shares);
    $this->Debug('Samba share file: ', $shares);

    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $shares array */
    $datagrid->bind($shares);

    /** Set columns */
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Share'), 'name', 'name', null, null, array('SambaShares', '_PrintShareLink'), array('field' => 'name')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Browseable'), 'browseable'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Available'), 'available'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Read list'), 'read list', null, null, null, array('SambaShares', '_PrintShareLink'), array('field' => 'read list')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Write list'), 'write list', null, null, null, array('SambaShares', '_PrintShareLink'), array('field' => 'write list')));
    $datagrid->addColumn(new Structures_DataGrid_Column('', null, null, null, null, array('MaglioneFramework', 'print_checkbox'), array('field_key' => 'name', )));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }

  function _PrintShareLink($params, $args)
  {
    extract($params);
    if ( ($args['field'] == 'write list') || ($args['field'] == 'read list') ) {
      return(nl2br(str_replace("'", "", $record[$args['field']])));
    }
    if ($args['field'] == 'name') {
      return('<a href="?action=update&share=' . htmlentities($record['name']) . '">' . $record['name'] . '</a>');
    }
  }

} # End of SambaComputers Class





/** Create a new instance of MaglioneFramework */
$Framework = new SambaShares();

/** Sets the page title */
$Framework->Title($Framework->_tr('Shares'), '/samba/shares.png');

/** Sets default action to view */
$action = $Framework->GetVar('action', 'view');

if ($action == 'insert' || $action == 'update') {
  $Framework->ShareFormCreate();
  $Framework->ShareFormFilters();
  $Framework->ShareFormRules($action);

  if ($action == 'update') {
    $Framework->ShareFormSetValues($Framework->GetVar('share', null));
  } else {
    $Framework->ShareFormSetDefaultValues();
  }

  /** Check form validation */
  if ($Framework->Form->validate()) {
    $Framework->Form->process(array('SambaShares', 'InsertModifyShare'));
    $action = 'view';
  } else {
    $Framework->Toolbar(true, false, false, false);

    /** Draw Form */
    $Framework->ShareFormDisplay();
  }
}




if ($action == 'view') {
  $Framework->Toolbar(true, true, true, true);
  $Framework->SharesGridDisplay();
}


#######################################################



#print_r($root);
#print_r($root->toArray());

#######################################################

/** Draw the page */
$Framework->Draw();
?>