<?php

/**
 * This file is part of the SysCP project.
 * Copyright (c) 2003-2009 the SysCP Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.syscp.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Florian Lippert <flo@syscp.org>
 * @license    GPLv2 http://files.syscp.org/misc/COPYING.txt
 * @package    Panel
 * @version    $Id$
 */

define('AREA', 'customer');

/**
 * Include our init.php, which manages Sessions, Language etc.
 */

require ("./lib/init.php");

if(isset($_POST['id']))
{
	$id = intval($_POST['id']);
}
elseif(isset($_GET['id']))
{
	$id = intval($_GET['id']);
}

if($page == 'overview')
{
	$log->logAction(USR_ACTION, LOG_NOTICE, "viewed customer_ftp");
	eval("echo \"" . getTemplate("ftp/ftp") . "\";");
}
elseif($page == 'accounts')
{
	if($action == '')
	{
		$log->logAction(USR_ACTION, LOG_NOTICE, "viewed customer_ftp::accounts");
		$fields = array(
			'username' => $lng['login']['username'],
			'homedir' => $lng['panel']['path']
		);
		$paging = new paging($userinfo, $db, TABLE_FTP_USERS, $fields, $settings['panel']['paging'], $settings['panel']['natsorting']);
		$result = $db->query("SELECT `id`, `username`, `homedir` FROM `" . TABLE_FTP_USERS . "` WHERE `customerid`='" . $userinfo['customerid'] . "' " . $paging->getSqlWhere(true) . " " . $paging->getSqlOrderBy() . " " . $paging->getSqlLimit());
		$paging->setEntries($db->num_rows($result));
		$sortcode = $paging->getHtmlSortCode($lng);
		$arrowcode = $paging->getHtmlArrowCode($filename . '?page=' . $page . '&s=' . $s);
		$searchcode = $paging->getHtmlSearchCode($lng);
		$pagingcode = $paging->getHtmlPagingCode($filename . '?page=' . $page . '&s=' . $s);
		$i = 0;
		$count = 0;
		$accounts = '';

		while($row = $db->fetch_array($result))
		{
			if($paging->checkDisplay($i))
			{
				if(strpos($row['homedir'], $userinfo['documentroot']) === 0)
				{
					$row['documentroot'] = substr($row['homedir'], strlen($userinfo['documentroot']));
				}
				else
				{
					$row['documentroot'] = $row['homedir'];
				}

				$row['documentroot'] = makeCorrectDir($row['documentroot']);
				
				$row = htmlentities_array($row);
				eval("\$accounts.=\"" . getTemplate("ftp/accounts_account") . "\";");
				$count++;
			}

			$i++;
		}

		$ftps_count = $db->num_rows($result);
		eval("echo \"" . getTemplate("ftp/accounts") . "\";");
	}
	elseif($action == 'delete'
	       && $id != 0)
	{
		$result = $db->query_first("SELECT `id`, `username`, `homedir`, `up_count`, `up_bytes`, `down_count`, `down_bytes` FROM `" . TABLE_FTP_USERS . "` WHERE `customerid`='" . (int)$userinfo['customerid'] . "' AND `id`='" . (int)$id . "'");

		if(isset($result['username'])
		   && $result['username'] != $userinfo['loginname'])
		{
			if(isset($_POST['send'])
			   && $_POST['send'] == 'send')
			{
				$db->query("UPDATE `" . TABLE_FTP_USERS . "` SET `up_count`=`up_count`+'" . (int)$result['up_count'] . "', `up_bytes`=`up_bytes`+'" . (int)$result['up_bytes'] . "', `down_count`=`down_count`+'" . (int)$result['down_count'] . "', `down_bytes`=`down_bytes`+'" . (int)$result['down_bytes'] . "' WHERE `username`='" . $db->escape($userinfo['loginname']) . "'");
				$db->query("DELETE FROM `" . TABLE_FTP_USERS . "` WHERE `customerid`='" . (int)$userinfo['customerid'] . "' AND `id`='" . (int)$id . "'");
				$log->logAction(USR_ACTION, LOG_INFO, "deleted ftp-account '" . $result['username'] . "'");
				$db->query("UPDATE `" . TABLE_FTP_GROUPS . "` SET `members`=REPLACE(`members`,'," . $db->escape($result['username']) . "','') WHERE `customerid`='" . (int)$userinfo['customerid'] . "'");

				//					$db->query("DELETE FROM `".TABLE_FTP_GROUPS."` WHERE `customerid`='".$userinfo['customerid']."' AND `id`='$id'");

				if($userinfo['ftps_used'] == '1')
				{
					$resetaccnumber = " , `ftp_lastaccountnumber`='0'";
				}
				else
				{
					$resetaccnumber = '';
				}

				$result = $db->query("UPDATE `" . TABLE_PANEL_CUSTOMERS . "` SET `ftps_used`=`ftps_used`-1 $resetaccnumber WHERE `customerid`='" . (int)$userinfo['customerid'] . "'");
				redirectTo($filename, Array('page' => $page, 's' => $s));
			}
			else
			{
				ask_yesno('ftp_reallydelete', $filename, array('id' => $id, 'page' => $page, 'action' => $action), $result['username']);
			}
		}
		else
		{
			standard_error('ftp_cantdeletemainaccount');
		}
	}
	elseif($action == 'add')
	{
		if($userinfo['ftps_used'] < $userinfo['ftps']
		   || $userinfo['ftps'] == '-1')
		{
			if(isset($_POST['send'])
			   && $_POST['send'] == 'send')
			{
				$path = validate($_POST['path'], 'path');
				$password = validate($_POST['ftp_password'], 'password');

				if($settings['customer']['ftpatdomain'] == '1')
				{
					$ftpusername = validate($_POST['ftp_username'], 'username', '/^[a-zA-Z0-9][a-zA-Z0-9\-_]+\$?$/');
					if($ftpusername == '')
					{
						standard_error(array('stringisempty', 'username'));
					}
					$ftpdomain = $idna_convert->encode(validate($_POST['ftp_domain'], 'domain'));
					$ftpdomain_check = $db->query_first("SELECT `id`, `domain`, `customerid` FROM `" . TABLE_PANEL_DOMAINS . "` WHERE `domain`='" . $db->escape($ftpdomain) . "' AND `customerid`='" . (int)$userinfo['customerid'] . "'");
					if($ftpdomain_check['domain'] != $ftpdomain)
					{
						standard_error('maindomainnonexist', $domain);
					}
					$username = $ftpusername . "@" . $ftpdomain;
				}
				else
				{
					$username = $userinfo['loginname'] . $settings['customer']['ftpprefix'] . (intval($userinfo['ftp_lastaccountnumber']) + 1);
				}
				
				$username_check = $db->query_first('SELECT * FROM `' . TABLE_FTP_USERS .'` WHERE `username` = \'' . $db->escape($username) . '\'');
				
				if(!empty($username_check) && $username_check['username'] = $username)
				{
					standard_error('usernamealreadyexists', $username);
				}
				elseif($password == '')
				{
					standard_error(array('stringisempty', 'mypassword'));
				}
				elseif($path == '')
				{
					standard_error('patherror');
				}
				else
				{
					$userpath = makeCorrectDir($path);
					$path = makeCorrectDir($userinfo['documentroot'] . '/' . $path);
					
					$db->query("INSERT INTO `" . TABLE_FTP_USERS . "` (`customerid`, `username`, `password`, `homedir`, `login_enabled`, `uid`, `gid`) VALUES ('" . (int)$userinfo['customerid'] . "', '" . $db->escape($username) . "', ENCRYPT('" . $db->escape($password) . "'), '" . $db->escape($path) . "', 'y', '" . (int)$userinfo['guid'] . "', '" . (int)$userinfo['guid'] . "')");
					$db->query("UPDATE `" . TABLE_FTP_GROUPS . "` SET `members`=CONCAT_WS(',',`members`,'" . $db->escape($username) . "') WHERE `customerid`='" . $userinfo['customerid'] . "' AND `gid`='" . (int)$userinfo['guid'] . "'");

					//						$db->query("INSERT INTO `".TABLE_FTP_GROUPS."` (`customerid`, `groupname`, `gid`, `members`) VALUES ('".$userinfo['customerid']."', '$username', '$uid', '$username')");

					$db->query("UPDATE `" . TABLE_PANEL_CUSTOMERS . "` SET `ftps_used`=`ftps_used`+1, `ftp_lastaccountnumber`=`ftp_lastaccountnumber`+1 WHERE `customerid`='" . (int)$userinfo['customerid'] . "'");

					//						$db->query("UPDATE `".TABLE_PANEL_SETTINGS."` SET `value`='$uid' WHERE settinggroup='ftp' AND varname='lastguid'");

					$log->logAction(USR_ACTION, LOG_INFO, "added ftp-account '" . $username . " (" . $path . ")'");
					inserttask(5);
					redirectTo($filename, Array('page' => $page, 's' => $s));
				}
			}
			else
			{
				$pathSelect = makePathfield($userinfo['documentroot'], $userinfo['guid'], $userinfo['guid'], $settings['panel']['pathedit']);

				if($settings['customer']['ftpatdomain'] == '1')
				{
					$domains = '';

					$result_domains = $db->query("SELECT `domain` FROM `" . TABLE_PANEL_DOMAINS . "` WHERE `customerid`='" . (int)$userinfo['customerid'] . "'");

					while($row_domain = $db->fetch_array($result_domains))
					{
						$domains.= makeoption($idna_convert->decode($row_domain['domain']), $row_domain['domain']);
					}
				}

				eval("echo \"" . getTemplate("ftp/accounts_add") . "\";");
			}
		}
	}
	elseif($action == 'edit'
	       && $id != 0)
	{
		$result = $db->query_first("SELECT `id`, `username`, `homedir` FROM `" . TABLE_FTP_USERS . "` WHERE `customerid`='" . (int)$userinfo['customerid'] . "' AND `id`='" . (int)$id . "'");

		if(isset($result['username'])
		   && $result['username'] != '')
		{
			if(isset($_POST['send'])
			   && $_POST['send'] == 'send')
			{
				$password = validate($_POST['ftp_password'], 'password');

				if($password == '')
				{
					standard_error(array('stringisempty', 'mypassword'));
					exit;
				}
				else
				{
					$db->query("UPDATE `" . TABLE_FTP_USERS . "` SET `password`=ENCRYPT('" . $db->escape($password) . "') WHERE `customerid`='" . (int)$userinfo['customerid'] . "' AND `id`='" . (int)$id . "'");
					$log->logAction(USR_ACTION, LOG_INFO, "edited ftp-account '" . $result['username'] . "'");
					redirectTo($filename, Array('page' => $page, 's' => $s));
				}
			}
			else
			{
				eval("echo \"" . getTemplate("ftp/accounts_edit") . "\";");
			}
		}
	}
}

?>