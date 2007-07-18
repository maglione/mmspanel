<?php
/**
 * samba/samba.php - Samba main class
 * 
 * @author Daniel Maglione <daniel@maglione.com.br>
 * @version 1.0
 * @package netpanel-samba
 * @project MAGLIONE NetPanel
 * @copyright Maglione Informatica 2007
 */

/** Netpanel pre setup */
require_once '../../config.php';

/** Samba module pre setup */
require_once 'config.php';

@ini_set('include_path', REAL_PATH . PATH_SEPARATOR . REAL_PATH . '/libs' . PATH_SEPARATOR . REAL_PATH . '/libs/pear' . PATH_SEPARATOR . REAL_PATH . '/libs/php-gettext');

/** Framework library */
require_once 'Maglione_NetPanel.php';

class Samba extends NetPanel
{
  function Samba()
  {
    /** Initialize Maglione Framework */
    $this->NetPanel();
  }

  function UserInfoGet($uid)
  {
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('uidNumber', 'uid','gidNumber', 'displayName', 'mail', 'homeDirectory', 'sambaLogonTime', 'sambaLogoffTime', 'sambaPwdCanChange', 'sambaSID', 'sambaAcctFlags', 'sambaPwdLastSet', 'sambaPwdMustChange')
              );
    $search = $this->Ldap->search(LDAP_USERS_OU . ', ' . LDAP_ROOT_DN, "(uid=$uid)", $options);

    $user = array();
    if($entry = $search->shiftEntry()) {
      $user['dn'] = $entry->dn();
      $user['uidNumber'] = ($entry->exists('uidNumber')) ? $entry->getValue('uidNumber', 'single') : "";
      $user['uid'] = ($entry->exists('uid')) ? $entry->getValue('uid', 'single') : "";
      $user['gidNumber'] = ($entry->exists('gidNumber')) ? $entry->getValue('gidNumber', 'single') : "";
      $user['displayName'] = ($entry->exists('displayName')) ? $entry->getValue('displayName', 'single') : "";
      $user['mail'] = ($entry->exists('mail')) ? $entry->getValue('mail', 'single') : "";
      $user['homeDirectory'] = ($entry->exists('homeDirectory')) ? $entry->getValue('homeDirectory', 'single') : "";
      $user['sambaLogonTime'] = ($entry->exists('sambaLogonTime')) ? $entry->getValue('sambaLogonTime', 'single') : "";
      $user['sambaLogoffTime'] = ($entry->exists('sambaLogoffTime')) ? $entry->getValue('sambaLogoffTime', 'single') : "";
      $user['sambaPwdCanChange'] = ($entry->exists('sambaPwdCanChange')) ? $entry->getValue('sambaPwdCanChange', 'single') : "";
      $user['sambaSID'] = ($entry->exists('sambaSID')) ? $entry->getValue('sambaSID', 'single') : "";
      $user['sambaAcctFlags'] = ($entry->exists('sambaAcctFlags')) ? $entry->getValue('sambaAcctFlags', 'single') : "";
      $user['sambaPwdLastSet'] = ($entry->exists('sambaPwdLastSet')) ? $entry->getValue('sambaPwdLastSet', 'single') : "";
      $user['sambaPwdMustChange'] = ($entry->exists('sambaPwdMustChange')) ? $entry->getValue('sambaPwdMustChange', 'single') : "";
    }

    $this->Debug("UserInfoGet($uid): user", $user);

    return ($user);
  }

  function GroupInfoGet($cn)
  {
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('dn', 'cn', 'description', 'memberUid')
              );
    $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, "(cn=$cn)", $options);

    $group = array();
    if($entry = $search->shiftEntry()) {
      $group['dn'] = $entry->dn();
      $group['cn'] = ($entry->exists('cn')) ? $entry->getValue('cn', 'single') : "";
      $group['description'] = ($entry->exists('description')) ? $entry->getValue('description', 'single') : "";
      $group['memberUid'] = ($entry->exists('memberUid')) ? $entry->getValue('memberUid', 'all') : "";
    }

    $this->Debug("GroupInfoGet($cn): cn", $group);

    return ($group);
  }

  function ComputerInfoGet($computer_uid)
  {
    $computer_uid = str_replace('$', '', $computer_uid) . '$';

    $options = array(
                    'scope' => 'one',
                    'attributes' => array('uid', 'uidNumber', 'description')
              );
    $search = $this->Ldap->search(LDAP_COMPUTERS_OU . ', ' . LDAP_ROOT_DN, "(uid=$computer_uid)", $options);

    $computer = array();
    if($entry = $search->shiftEntry()) {
      $computer['dn'] = $entry->dn();
      $computer['uidNumber'] = ($entry->exists('uidNumber')) ? $entry->getValue('uidNumber', 'single') : "";
      $computer['uid'] = ($entry->exists('uid')) ? $entry->getValue('uid', 'single') : "";
      $computer['description'] = ($entry->exists('description')) ? $entry->getValue('description', 'single') : "";
    }

    $this->Debug("ComputerInfoGet($uid): computer", $computer);

    return ($computer);
  }

  /**
  * Returns all groups array (gidNumber => cn)
  */
  function GetGroupsArray()
  {
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('dn', 'gidNumber', 'cn')
               );
    $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, "(objectclass=sambaGroupMapping)", $options);

    $groups_array = array();
    while($entry = $search->shiftEntry()) {
      $groups_array[$entry->getValue('gidNumber', 'single')] = $entry->getValue('cn', 'single');
    }
    asort($groups_array);

    $this->Debug("GetGroupsArray(): groups_array", $groups_array);

    return($groups_array);
  }

  /**
  * Returns all supplementary groups of any uid
  *
  * @param string $uid uid (ex: daniel)
  * @param boolean $only_keys if true return only key values, otherwise return key->description array
  */
  function GetSupGroupsArray($uid = null, $only_keys = false)
  {
    $user = array();
    $options = array(
                    'scope' => 'one',
                    'attributes' => array('dn', 'cn')
               );
    if ($uid) {
      $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, "(& (objectclass=sambaGroupMapping)(memberUid=$uid))", $options);
    } else {
      $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, "(objectclass=sambaGroupMapping)", $options);
    }

    $groups_array = array();
    while($entry = $search->shiftEntry()) {
      if ($only_keys) {
        $groups_array[] = $entry->getValue('cn', 'single');
      } else {
        $groups_array[$entry->getValue('cn', 'single')] = $entry->getValue('cn', 'single');
      }
    }
    asort($groups_array);

    $this->Debug("GetSupGroupsArray($uid, $only_keys): groups_array", $groups_array);

    return($groups_array);
  }

  /**
  * Generate quickform advmultiselect of supplementary groups
  *
  * @param string $uid uid (ex: daniel)
  */
  function QFGroupsMultiselect($name, $title, $uid = null)
  {
    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/advmultiselect.php';

    $groups = HTML_QuickForm::createElement('advmultiselect', $name, null, $this->GetSupGroupsArray(), array('size' => 10, 'class' => 'pool', 'style' => 'width:200px;'), SORT_ASC);

    $groups->setLabel(array($title, $this->_tr('Available'), $this->_tr('Selected')));
    $groups->setButtonAttributes('add',    array('value' => $this->_tr('Add >>'), 'class' => 'inputCommand'));
    $groups->setButtonAttributes('remove', array('value' => $this->_tr('<< Remove'), 'class' => 'inputCommand'));

    $this->Template->outputJShead .= $groups->getElementJs(true);

    return($groups);
  }

  /**
  * Returns all users members of any group_cn
  *
  * @param string $group_cn cn (ex: Domain Admins)
  * @param boolean $only_keys if true return only key values, otherwise return key->displayName array
  */
  function GetUsersArray($group_cn = null, $only_keys = false)
  {
    $user = array();
    $options = array(
                    'scope' => 'one'
               );
    if ($group_cn) {
      $options['attributes'] = array('memberUid');
      $search = $this->Ldap->search(LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN, "(& (objectclass=sambaGroupMapping)(cn=$group_cn))", $options);
      if ($entry = $search->shiftEntry()) {
        $users = $entry->getValue('memberUid', 'all');
        if (! $only_keys) {
          foreach($users as $user) {
            $users_tmp[$user] = $user;
          }
          $users = $users_tmp;
        }
      }
    } else {
      $options['attributes'] = array('uid');
      $search = $this->Ldap->search(LDAP_USERS_OU . ', ' . LDAP_ROOT_DN, "(objectclass=sambaSamAccount)", $options);
      while ($entry = $search->shiftEntry()) {
        if ($only_keys) {
          $users[] = $entry->getValue('uid', 'single');
        } else {
          $users[$entry->getValue('uid', 'single')] = $entry->getValue('uid', 'single');
        }
      }
    }

    if (is_array($users)) {
      asort($users);
    }

    $this->Debug("GetUsersArray($group_cn, $only_keys): users", $users);

    return($users);
  }
  
  /**
  * Generate quickform advmultiselect of users of supplementary groups
  *
  * @param string $group_cn group_cn (ex: Domain Admins)
  */
  function QFUsersMultiselect($name, $title)
  {
    require_once 'HTML/QuickForm.php';
    require_once 'HTML/QuickForm/advmultiselect.php';

    $users = HTML_QuickForm::createElement('advmultiselect', $name, null, $this->GetUsersArray(), array('size' => 10, 'class' => 'pool', 'style' => 'width:200px;'), SORT_ASC);

    $users->setLabel(array($title, $this->_tr('Available'), $this->_tr('Selected')));
    $users->setButtonAttributes('add',    array('value' => $this->_tr('Add >>'), 'class' => 'inputCommand'));
    $users->setButtonAttributes('remove', array('value' => $this->_tr('<< Remove'), 'class' => 'inputCommand'));

    $this->Template->outputJShead .= $users->getElementJs(true);

    return($users);
  }

  function UserAdd($uid, $fullname, $password, $primary_group, $groups = null, $email = null, $flags_arr = array('U'), $sambaPwdCNT = 0, $sambaPwdMST = 0)
  {
    // Take first and last name and write then in $cn and $sn
    $name_arr = explode(" ", $fullname);
    $cn = $name_arr[0];
    $sn = $name_arr[count($name_arr) - 1];

    $flags = '';
    foreach ($flags_arr as $flag) {
      $flags .= $flag;
    }
    $flags = "[$flags]";

    /** Prepare smbldap-useradd command */
    $command  = SUDO . " ";            # Execute using sudo
    $command .= SMBLDAP_USERADD . " "; # Full path of smbldap-useradd
    $command .= "-a ";                 # is a Windows User
    $command .= "-m ";                 # creates home directory and copies /etc/skel
    $command .= "-s /bin/false ";      # shell
    $command .= "-g $primary_group ";  # gid
    $command .= "-d " . SAMBA_HOMES . "/" . $uid . " "; # home
    $command .= "-c '" . $fullname . "' ";              # gecos
    $command .= "-H '$flags' ";        # sambaAcctFlags (samba account control bits like '[NDHTUMWSLKI]')
    if ($sambaPwdCNT == 1) {
      $command .= "-A 0 "; # can change password ? 0 if no, 1 if yes
    } else {
      $command .= "-A 1 "; # can change password ? 0 if no, 1 if yes
    }
    if ($sambaPwdMST == 1) {
      $command .= "-B 1 "; # must change password ? 0 if no, 1 if yes
    } else {
      $command .= "-B 0 "; # must change password ? 0 if no, 1 if yes
    }
    if ($cn) {
      $command .= "-N '$cn' "; # canonical name
    }
    if ($sn) {
      $command .= "-S '$sn' "; # surname
    }
    if ($email) {
      $command .= "-M '$email' "; # mailToAddress (forward address) (comma seperated)
    }
    if ($groups) {
      $command .= "-G $groups ";
    }
    $command .= $uid;          # username

    $this->ExecuteExternalCommand($command);

    $this->SetPasswd($uid, $password);
  }

  function UserMod($uid, $fullname, $primary_group, $groups = null, $email = null, $flags_arr = array('U'), $sambaPwdCNT = 0, $sambaPwdMST = 0)
  {
    // Take first and last name and write then in $cn and $sn
    $name_arr = explode(" ", $fullname);
    $cn = $name_arr[0];
    $sn = $name_arr[count($name_arr) - 1];

    $flags = '';
    foreach ($flags_arr as $flag) {
      $flags .= $flag;
    }
    $flags = "[$flags]";
  
    /** Prepare smbldap-usermod command */
    $command  = SUDO . " ";            # Execute using sudo
    $command .= SMBLDAP_USERMOD . " "; # Full path of smbldap-usermod
    $command .= "-s /bin/false ";      # shell
    $command .= "-g $primary_group ";  # gid
    $command .= "-d " . SAMBA_HOMES . "/" . $uid . " "; # home
    $command .= "-c '" . $fullname . "' ";              # gecos
    $command .= "-H '$flags' ";        # sambaAcctFlags (samba account control bits like '[NDHTUMWSLKI]')
    if ($sambaPwdCNT == 1) {
      $command .= "-A 0 "; # can change password ? 0 if no, 1 if yes
    } else {
      $command .= "-A 1 "; # can change password ? 0 if no, 1 if yes
    }
    if ($sambaPwdMST == 1) {
      $command .= "-B 1 "; # must change password ? 0 if no, 1 if yes
    } else {
      $command .= "-B 0 "; # must change password ? 0 if no, 1 if yes
    }
    if ($cn) {
      $command .= "-N '$cn' "; # canonical name
    }
    if ($sn) {
      $command .= "-S '$sn' "; # surname
    }
    $command .= "-M '$email' ";# mailToAddress (forward address) (comma seperated)
    if ($groups) {
      $command .= "-G $groups ";
    }
    $command .= $uid;          # username

    $this->ExecuteExternalCommand($command);
  }
  
  function UserDel($uid)
  {
    /** Prepare smbldap-usermod command */
    $command  = SUDO . " ";            # Execute using sudo
    $command .= SMBLDAP_USERDEL . " "; # Full path of smbldap-usermod
    $command .= escapeshellarg($uid);  # username

    $this->ExecuteExternalCommand($command);
  }

  function SetPasswd($uid, $password)
  {
    /** Prepare smbldap-passwd command */

    $command_passwd  = ECHO_CMD . ' -e "' . $password .'\n' . $password . '" ';
    $command_passwd .= '| ' . SUDO . " ";            # Execute using sudo
    $command_passwd .= SMBLDAP_PASSWD . ' ' . $uid;

    $this->ExecuteExternalCommand($command_passwd);
  }

  /** GROUP FUNCTIONS */

  function GroupAdd($group, $description, $members)
  {
    /** Prepare smbldap-useradd command */
    $command  = SUDO . " ";              # Execute using sudo
    $command .= SMBLDAP_GROUPADD . " ";  # Full path of smbldap-groupadd
    $command .= "-a ";                   # add automatic group mapping entry
    $command .= escapeshellarg($group);  # groupname

    $this->ExecuteExternalCommand($command);
    
    /** Add all members and set description */
    $this->GroupMod($group, $description, $members);
  }

  function GroupMod($group, $description, $members)
  {    
    /** Prepare smbldap-useradd command  to remove all members of group*/
    $members_remove = $this->GetUsersArray($group, true);
    if (is_array($members_remove)) {
      $members_remove = implode(',', $members_remove);
    }      
    if ($members_remove) {
      $$members_remove = escapeshellarg($members_remove);
    
      $command  = SUDO . " ";                    # Execute using sudo
      $command .= SMBLDAP_GROUPMOD . " ";        # Full path of smbldap-groupmod
      $command .= "-x " . $members_remove . " "; # delete members (comma delimted)
      $command .= escapeshellarg($group);        # groupname
      
      $this->ExecuteExternalCommand($command);
    }
      
    if ($members) {
      /** Prepare smbldap-useradd command to add selected members*/
      
      $command  = SUDO . " ";              # Execute using sudo
      $command .= SMBLDAP_GROUPMOD . " ";  # Full path of smbldap-groupmod
      $command .= "-m " . $members . " ";  # add members (comma delimited)
      $command .= escapeshellarg($group);  # groupname

      $this->ExecuteExternalCommand($command);
    }

    /** Add / modify group description */
    $dn = "cn=$group, " . LDAP_GROUPS_OU . ', ' . LDAP_ROOT_DN;
    $entry =& $this->Ldap->getEntry($dn);
    $entry->replace(array("description" => $description));   // replace the attributes values with the new number
    $entry->update();
  }

  function GroupDel($group_cn)
  {
    /** Prepare smbldap-usermod command */
    $command  = SUDO . " ";                # Execute using sudo
    $command .= SMBLDAP_GROUPDEL . " ";    # Full path of smbldap-usermod
    $command .= escapeshellarg($group_cn); # groupname

    $this->ExecuteExternalCommand($command);
  }

  function ComputerAdd($computer_uid, $description = null)
  {
    $computer_uid = str_replace('$', '', $computer_uid);

    /** Prepare smbldap-useradd command */
    $command  = SUDO . " ";            # Execute using sudo
    $command .= SMBLDAP_USERADD . " "; # Full path of smbldap-useradd
    $command .= "-w ";                 # is a Windows Workstation (otherwise, Posix stuff only)
    $command .= $computer_uid;         # computer_uid

    $this->ExecuteExternalCommand($command);

    $this->ComputerMod($computer_uid, $description);
  }

  function ComputerMod($computer_uid, $description = null)
  {
    $computer_uid = str_replace('$', '', $computer_uid);

    $computer_uid .= '$';

    /** Add / modify computer description */
    $dn = "uid=$computer_uid, " . LDAP_COMPUTERS_OU . ', ' . LDAP_ROOT_DN;

    $entry =& $this->Ldap->getEntry($dn);
    $entry->replace(array("description" => $description));   // replace the attributes values with the new number
    $entry->update();
  }

  function PingHost($host)
  {
    $command = PING_CMD . " -n -W 1 -c 4 $host";
    $this->ExecuteExternalCommand($command, &$output);

    return($output);
  }

}

?>