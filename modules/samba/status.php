<?php
/**
 * samba/status.php - Samba samba status
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

class SambaStatus extends Samba
{
  function SambaStatus()
  {
    $this->Samba();
  }

  function SmbStatus()
  {
    $output = '';
    $this->ExecuteExternalCommand(SMBSTATUS, &$output);

    return($output);
  }

  function GetSambaVersion()
  {
    $smbstatus = $this->SmbStatus();

    foreach ($smbstatus as $status_line) {
      if(ereg('^Samba version .+$', $status_line)) {
        return($status_line);
      }
    }
  }

  function GetUserByPID($sambapid)
  {
    $smbstatus = $this->SmbStatus();

    $start = false;

    foreach ($smbstatus as $status_line) {
      if(ereg('^PID +Username +Group +Machine', $status_line)) {
        $start = true;
      }
      if ( (! $status_line) && ($start) ) {
        return(false);
      }

      if ($start) {
        if (ereg('^([0-9]+) +([a-zA-Z0-9._-]+) +([a-zA-Z0-9._-]+) +([a-zA-Z0-9._-]+) +\(([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\)$', $status_line, $regs)) {
          $user = array();

          $user['computer'] = $regs[4];
          $user['ip'] = $regs[5];
          $user['username'] = $regs[2];
          $user['group'] = $regs[3];
          $user['sambapid'] = $regs[1];

          if ($user['sambapid'] == $sambapid) {
            return($user['username']);
          }
        }
      }
    }
    return(false);
  }

  function GetConnectedUsers()
  {
    $smbstatus = $this->SmbStatus();

    $start = false;
    $users = array();

    foreach ($smbstatus as $status_line) {
      if(ereg('^PID +Username +Group +Machine', $status_line)) {
        $start = true;
      }
      if ( (! $status_line) && ($start) ) {
        asort($users);
        return($users);
      }

      if ($start) {
        if (ereg('^([0-9]+)  +([a-zA-Z0-9._-]+)  +([a-zA-Z0-9._-]+)  +([a-zA-Z0-9._-]+) +\(([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\)$', $status_line, $regs)) {
          $user = array();

          $user['computer'] = $regs[4];
          $user['ip'] = $regs[5];
          $user['username'] = $regs[2];
          $user['group'] = $regs[3];
          $user['sambapid'] = $regs[1];

          $users[] = $user;
        }
      }
    }
    asort($users);
    return($users);
  }
  
  function GetConnectedShares($samba_pid)
  {
    $smbstatus = $this->SmbStatus();

    $start = false;
    $shares = array();

    foreach ($smbstatus as $status_line) {
      if(ereg('^Service +pid +machine +Connected at', $status_line)) {
        $start = true;
      }
      if ( (! $status_line) && ($start) ) {
        asort($shares);
        return($shares);
      }

      if ($start) {
        if (eregi('^([[:alnum:][:punct:]]+) +([0-9]+) +([[:alnum:][:punct:]]+) +([a-zA-Z]{3} +[a-zA-Z]{3} +[0-9]{1,2} +[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2} +[0-9]{4})$', $status_line, $regs)) {
          $share = array();

          $share['service'] = $regs[1];
          $share['machine'] = $regs[3];
          $share['date'] = $this->FormatTimestamp(strtotime($regs[4]));
          $share['sambapid'] = $regs[2];

          if ($share['sambapid'] == $samba_pid) {
            $shares[] = $share;
          }
        }
      }
    }
    asort($shares);
    return($shares);
  }

  function GetOpenFiles($samba_pid)
  {
    $smbstatus = $this->SmbStatus();

    $start = false;
    $files = array();

    foreach ($smbstatus as $status_line) {
      if(ereg('^Pid +DenyMode +Access +R/W +Oplock +Name', $status_line)) {
        $start = true;
      }
      if ( (! $status_line) && ($start) ) {
        asort($files);
        return($files);
      }

      if ($start) {
        if (eregi('^([0-9]+) +([[:alnum:][:punct:]]+) +(0x[0-9ABCDEF]+) +([[:alnum:][:punct:]]+) +([[:alnum:][:punct:]]+)? +(.+) +([a-zA-Z]{3} +[a-zA-Z]{3} +[0-9]{1,2} +[0-9]{1,2}:[0-9]{1,2}:[0-9]{1,2} +[0-9]{4})$', $status_line, $regs)) {
          $file = array();

          $file['name'] = $regs[6];
          $file['date'] = $this->FormatTimestamp(strtotime($regs[7]));
          $file['deny'] = $regs[2];
          $file['access'] = $regs[3];
          $file['rw'] = $regs[4];
          $file['oplock'] = $regs[5];
          $file['sambapid'] = $regs[1];

          if ($file['sambapid'] == $samba_pid) {
            $files[] = $file;
          }
        }
      }
    }
    asort($files);
    return($files);
  }

  function _PrintStatusLink($params, $args)
  {
    extract($params);

    if ($args['field'] == 'computer') {
      return('<a href="computers.php?action=update&uid=' . htmlentities($record['computer']) . '">' . $record['computer'] . '</a>');
    } elseif ($args['field'] == 'username') {
      return('<a href="users.php?action=update&uid=' . htmlentities($record['username']) . '">' . $record['username'] . '</a>');
    } elseif ($args['field'] == 'group') {
      return('<a href="groups.php?action=update&cn=' . htmlentities($record['group']) . '">' . $record['group'] . '</a>');
    } elseif ($args['field'] == 'ip') {
      return('<a href="?action=pinghost&host=' . htmlentities($record['computer']) . '&ip=' . htmlentities($record['ip']) . '">' . $record['ip'] . '</a>');
    } elseif ($args['field'] == 'sambapid') {
      return('<a href="?action=status&sambapid=' . htmlentities($record['sambapid']) . '">' . $record['sambapid'] . '</a>');
    }
  }

  function ConnectedUsersGridDisplay()
  {
    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $users array */
    $datagrid->bind($this->GetConnectedUsers());

    /** Set columns */
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Computer'), 'computer', 'computer', null, null, array('SambaStatus', '_PrintStatusLink'), array('field' => 'computer')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('IP Address'), 'ip', 'ip', null, null, array('SambaStatus', '_PrintStatusLink'), array('field' => 'ip')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('User'), 'username', 'username', null, null, array('SambaStatus', '_PrintStatusLink'), array('field' => 'username')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Group'), 'group', 'group', null, null, array('SambaStatus', '_PrintStatusLink'), array('field' => 'group')));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Samba PID'), 'sambapid', 'sambapid', null, null, array('SambaStatus', '_PrintStatusLink'), array('field' => 'sambapid')));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }

  function ConnectedSharesGridDisplay($sambapid)
  {
    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $users array */
    $datagrid->bind($this->GetConnectedShares($sambapid));

    /** Set columns */

    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Share'), 'service', 'service'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Computer'), 'machine', 'machine'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Connect time'), 'date', 'date'));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }

  function OpenFilesStatusGridDisplay($sambapid)
  {
    /** Create a new instance of Structures_DataGrid */
    $datagrid = & new Structures_DataGrid(DG_NUM_ROWS);

    /** Bind $users array */
    $datagrid->bind($this->GetOpenFiles($sambapid));

    /** Set columns */

    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('File name'), 'name', 'name'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Open time'), 'date', 'date'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Deny'), 'deny', 'deny'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Access'), 'access', 'access'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('R/W'), 'rw', 'rw'));
    $datagrid->addColumn(new Structures_DataGrid_Column($this->_tr('Lock'), 'oplock', 'oplock'));

    /** Display listform and datagrid */
    $this->Template->outputBody .= '<form name="listForm" method="post">';
    $this->Template->outputBody .= '<input type="hidden" name="action">';
    $this->Template->outputBody .= $this->Datagrid($datagrid);
    $this->Template->outputBody .= '</form>';
  }


  function Ping($host)
  {
    $this->Template->outputBody .= '<div id="black_panel">';
    $this->Template->outputBody .= implode('<br>', $this->PingHost($host));
    $this->Template->outputBody .= '</div>';  
  }

} # End of SambaStatus Class

/** Create a new instance of MaglioneFramework */
$Framework = new SambaStatus();

/** Sets the page title */
$Framework->Title($Framework->_tr('Network Status'), '/samba/status.png');

/** Get action */
$action = $Framework->GetVar('action', 'view');

if (($action == 'pinghost') && ($Framework->GetVar('host', '')) && ($Framework->GetVar('ip', ''))) {
  $Framework->Ping($Framework->GetVar('ip', ''));
  $Framework->Ping($Framework->GetVar('host', ''));
}

if (($action == 'status') && ($Framework->GetVar('sambapid', '')) ) {
  $Framework->Template->outputBody .= '<p><strong>' . $Framework->GetSambaVersion() . '</strong></p>';
  $Framework->Template->outputBody .= '<p>' . $Framework->_tr('User') . ': <strong>' . $Framework->GetUserByPID($Framework->GetVar('sambapid', '')) . '</strong></p>';

  $Framework->Template->outputBody .= '<fieldset><legend title="' . $Framework->_tr('Connected shares') . '">' . $Framework->_tr('Connected shares') . '</legend>';
  $Framework->ConnectedSharesGridDisplay($Framework->GetVar('sambapid', ''));
  $Framework->Template->outputBody .= '</fieldset>';

  $Framework->Template->outputBody .= '<fieldset><legend title="' . $Framework->_tr('Open Files') . '">' . $Framework->_tr('Open Files') . '</legend>';
  $Framework->OpenFilesStatusGridDisplay($Framework->GetVar('sambapid', ''));
  $Framework->Template->outputBody .= '</fieldset>';

  $Framework->Template->outputBody .= '<p align="center"><strong><a href="?action=view">' . $Framework->_tr('View all users') . '</a></strong></p>';
}

if (($action == 'view') || ($action == 'pinghost')) {
  $Framework->ConnectedUsersGridDisplay();
}

#$Framework->GetSambaVersion();
#print_r($Framework->GetConnectedUsers());

/** Draw the page */
$Framework->Draw();
?>