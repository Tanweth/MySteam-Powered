<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * INSTALL FUNCTIONS
 * This file is used by the above plugin to handle installation and activation.
 */

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/*
 * mysteam_info()
 *
 * Displays info on the current plugin. Also generates messages about plugin's status on Plugins page.
 * 
 * @return - (array) info on the current plugin.
 */
function mysteam_info()
{
	global $lang, $db, $mybb, $cache;

	if(!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	// If plugin is installed, generate error flags if needed.
	if($db->field_exists('steamid', 'users'))
	{
		$query = $db->simple_select("settinggroups", "gid", "name='mysteam_main_group'");
		$gid = $db->fetch_field($query, 'gid');
		
		// Generate description and settings link.
		if ($gid)
		{
			$mysteam_desc = $lang->mysteam_desc . '<ul><li><img src="' .$mybb->settings['bburl']. '/images/mysteam/steam_icon.png"> <a href="index.php?module=config-settings&action=change&gid=' .$gid. '">' .$lang->mysteam_settings. '</a></li>';
		}
		// If ASB module is detected, display success.
		if (file_exists(MYBB_ROOT.'inc/plugins/asb.php') && file_exists(MYBB_ROOT.'inc/plugins/asb/modules/mysteamlist.php'))
		{
			$mysteam_desc .=  '<li><img src="' .$mybb->settings['bburl']. '/images/valid.gif"> ' .$lang->mysteam_asb_success. '</li>';
		}
		// Check if API key is provided. If not, display error.
		if (!$mybb->settings['mysteam_apikey'])
		{
			$mysteam_desc .=  '<li><img src="' .$mybb->settings['bburl']. '/images/error.gif"> ' .$lang->mysteam_apikey_needed. '</li>';
		}
		// Check if any users have Steam IDs associated. If not, display error.
		$query = $db->simple_select("users", "uid", "steamid IS NOT NULL AND steamid<>''"); 
		if ($db->num_rows($query) == 0)
		{
			$mysteam_desc .=  '<li><img src="' .$mybb->settings['bburl']. '/images/error.gif"> ' .$lang->mysteam_steamids_needed. '</li>';
		}
		// Check if the current ASB version is the minimum required.
		$asb = $cache->read('asb');
		if (file_exists(MYBB_ROOT.'inc/plugins/asb.php') && version_compare($asb['version'], '2.1', '<'))
		{
			$mysteam_desc .= '<li><img src="' .$mybb->settings['bburl']. '/images/error.gif"> ' .$lang->mysteam_asb_upgrade. '</li>';
		}
		$mysteam_desc .= '</ul>';
	}
	else
	{
		$mysteam_desc = $lang->mysteam_desc;
	}

	return array(
		'name'			=> $lang->mysteam_title,
		'description'	=> $mysteam_desc,
		'website'		=> 'http://github.com/Tanweth/MySteam-Powered',
		'author'		=> 'Tanweth',
		'authorsite'	=> 'http://kerfufflealliance.com',
		'version'		=> '1.2.2',
		'guid' 			=> 'c6c646c000efdee91b3f6de2fd7dd59a',
		'compatibility' => '16*'
	);
}

/*
 * mysteam_install()
 * 
 * Installs plugin, creating Steam ID database entry, generating settings, installing ASB module (if applicable), and adding stylesheet. Also calls mysteam_activate() function.
 */
 function mysteam_install()
 {
	global $db, $lang;
	
	if(!$lang->mysteam)
	{
		$lang->load('mysteam');
	}

	// Add Steam ID database field
 	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users ADD steamid varchar(30) NOT NULL");

	// If Advanced Sidebox is installed, install ASB module and disable plugin's list functionality by default.
	if (file_exists(MYBB_ROOT.'inc/plugins/asb.php'))
	{
		@copy(MYBB_ROOT.'inc/plugins/mysteam/mysteamlist.php', MYBB_ROOT.'inc/plugins/asb/modules/mysteamlist.php');
		$list_enable_value = 'no';
	}
	else
	{
		$list_enable_value = 'yes';
	}

	// Add non-Advanced Sidebox status list settings group, then settings.
	$group = array(
		'gid'			=> 'NULL',
		'title'			=> $lang->mysteam_list_group_title,
		'name'			=> 'mysteam_list_group',
		'description'	=> $lang->mysteam_list_group_desc,
		'disporder'		=> '222',
		'isdefault'		=> '0',
	);
	$db->insert_query('settinggroups', $group);
	$gid = $db->insert_id();
	$group_gid = (int) $gid;	
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_index',
		'title'			=> $lang->mysteam_index_title,
		'description'	=> $lang->mysteam_index_desc,
		'optionscode'	=> 'yesno',
		'value'			=> 'yes',
		'disporder'		=> '1',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_portal',
		'title'			=> $lang->mysteam_portal_title,
		'description'	=> $lang->mysteam_portal_desc,
		'optionscode'	=> 'yesno',
		'value'			=> 'yes',
		'disporder'		=> '2',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_list_width',
		'title'			=> $lang->mysteam_list_width_title,
		'description'	=> $lang->mysteam_list_width_desc,
		'optionscode'	=> 'text',
		'value'			=> '200',
		'disporder'		=> '3',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_list_number',
		'title'			=> $lang->mysteam_list_number_title,
		'description'	=> $lang->mysteam_list_number_desc,
		'optionscode'	=> 'text',
		'value'			=> '0',
		'disporder'		=> '4',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	// Generate link to status list settings.	
	if ($group_gid)
	{
		$mysteam_list_enable_desc = $lang->mysteam_list_enable_desc. ' (<a href="index.php?module=config-settings&action=change&gid=' .$group_gid. '">' .$lang->mysteam_list_settings. '</a>)';
	}
	else
	{
		$mysteam_list_enable_desc = $lang->mysteam_list_enable_desc;
	}
	
	// Add main settings group, then settings.
	$group = array(
		'gid'			=> 'NULL',
		'title'			=> $lang->mysteam_title,
		'name'			=> 'mysteam_main_group',
		'description'	=> $lang->mysteam_main_group_desc,
		'disporder'		=> '221',
		'isdefault'		=> '0',
	);
	$db->insert_query('settinggroups', $group);
	$gid = $db->insert_id(); 
	$group_gid = (int) $gid;
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_list_enable',
		'title'			=> $lang->mysteam_list_enable_title,
		'description'	=> $mysteam_list_enable_desc,
		'optionscode'	=> 'yesno',
		'value'			=> $list_enable_value,
		'disporder'		=> '1',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_apikey',
		'title'			=> $lang->mysteam_apikey_title,
		'description'	=> $lang->mysteam_apikey_desc,
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '2',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_limitbygroup',
		'title'			=> $lang->mysteam_limitbygroup_title,
		'description'	=> $lang->mysteam_limitbygroup_desc,
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> '3',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_cache',
		'title'			=> $lang->mysteam_cache_title,
		'description'	=> $lang->mysteam_cache_desc,
		'optionscode'	=> 'text',
		'value'			=> '10',
		'disporder'		=> '4',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_displayname',
		'title'			=> $lang->mysteam_displayname_title,
		'description'	=> $lang->mysteam_displayname_desc,
		'optionscode'	=> 'radio
steam='.$lang->mysteam_displayname_steam.'
forum='.$lang->mysteam_displayname_forum.'
both='.$lang->mysteam_displayname_both,
		'value'			=> 'forum',
		'disporder'		=> '5',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_profile',
		'title'			=> $lang->mysteam_profile_title,
		'description'	=> $lang->mysteam_profile_desc,
		'optionscode'	=> 'yesno',
		'value'			=> 'yes',
		'disporder'		=> '6',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_postbit',
		'title'			=> $lang->mysteam_postbit_title,
		'description'	=> $lang->mysteam_postbit_desc,
		'optionscode'	=> 'radio
img='.$lang->mysteam_postbit_img.'
text='.$lang->mysteam_postbit_text.'
no='.$lang->mysteam_postbit_no,
		'value'			=> 'img',
		'disporder'		=> '7',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_hover',
		'title'			=> $lang->mysteam_hover_title,
		'description'	=> $lang->mysteam_hover_desc,
		'optionscode'	=> 'yesno',
		'value'			=> 'yes',
		'disporder'		=> '8',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);
	
	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_prune',
		'title'			=> $lang->mysteam_prune_title,
		'description'	=> $lang->mysteam_prune_desc,
		'optionscode'	=> 'text',
		'value'			=> '0',
		'disporder'		=> '9',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);

	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_usercp',
		'title'			=> $lang->mysteam_usercp_title,
		'description'	=> $lang->mysteam_usercp_desc,
		'optionscode'	=> 'yesno',
		'value'			=> 'yes',
		'disporder'		=> '10',
		'gid'			=> $group_gid,
	);
	$db->insert_query('settings', $setting);

	$setting = array(
		'sid'			=> 'NULL',
		'name'			=> 'mysteam_modcp',
		'title'			=> $lang->mysteam_modcp_title,
		'description'	=> $lang->mysteam_modcp_desc,
		'optionscode'	=> 'yesno',
		'value'			=> 'yes',
		'disporder'		=> '11',
		'gid'			=> $group_gid,
	);	
	$db->insert_query('settings', $setting);
	
	rebuild_settings();
	
	// Insert template group.
	$tgroup = array(
		'prefix'		=> 'mysteam',
		'title'			=> $lang->mysteam_template_group,
	);
	$db->insert_query('templategroups', $tgroup);
	
	// Add CSS stylesheet.
	require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
	
	$stylesheet = @file_get_contents(MYBB_ROOT.'inc/plugins/mysteam/mysteam.css');
	
	$stylesheet_array = array(
		'sid'			=> NULL,
		'name'			=> 'mysteam.css',
		'tid'			=> '1',
		'stylesheet'	=> $db->escape_string($stylesheet),
		'cachefile'		=> 'mysteam.css',
		'lastmodified'	=> TIME_NOW
	);
	$db->insert_query('themestylesheets', $stylesheet_array);
	cache_stylesheet(1, 'mysteam.css', $stylesheet);
	update_theme_stylesheet_list(1);
}
 
/*
 * mysteam_is_installed()
 * 
 * Checks if the plugin is installed by querying the database to see if the new Steam ID field exists.
 * 
 * @return: (bool) true if plugin is installed, false if plugin not installed.
 */
 function mysteam_is_installed()
 {
	global $db;
	
	if($db->field_exists('steamid', 'users'))
	{
		return true;
	}
	return false;
 }

/*
 * mysteam_uninstall()
 * 
 * Uninstalls plugin by removing new settings, database field, and stylesheet. Also calls mysteam_deactivate() if applicable.
 */
 function mysteam_uninstall()
 {
	global $db, $theme;
	
	// Make sure ASB module is uninstalled before continuing.
	if (file_exists(MYBB_ROOT.'inc/plugins/asb/modules/mysteamlist.php'))
	{
		flash_message('The Steam Status module for Advanced Sidebox is still installed. Please delete it from within the Advanced Sidebox menu before uninstalling this plugin.', 'error');
		admin_redirect('index.php?module=config-plugins');
	}

	// Delete Steam ID database field
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP steamid");
	
	// Delete settings
	$db->delete_query("settinggroups", "name LIKE 'mysteam_%'");
	$db->delete_query("settings", "name LIKE 'mysteam_%'");
	$db->delete_query("templategroups", "prefix = 'mysteam'");
	
	
    // Remove stylesheet from theme cache directories if present.
	require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
	
	$query = $db->simple_select("themes", "tid, name", "name='mysteam.css'");
	while ($stylesheet = $db->fetch_array($query))
	{
		@unlink(MYBB_ROOT."cache/themes/{$stylesheet['tid']}_{$stylesheet['name']}");
		@unlink(MYBB_ROOT."cache/themes/theme{$stylesheet['tid']}/{$stylesheet['name']}");
	}
	$db->delete_query("themestylesheets", "name='mysteam.css'");
	update_theme_stylesheet_list(1);
 }
 
/*
 * mysteam_upgrade()
 * 
 * Includes special routines for upgrading from the previous version.
 */
function mysteam_upgrade()
{
	global $cache, $db, $lang;
	
	if(!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	$mysteam_cache = $cache->read('mysteam');
	$version['cache'] = $mysteam_cache['version'];
	$mysteam_info = mysteam_info();
	$version['new'] = $mysteam_info['version'];
	
	// If no version specified (was the case with v1.0) and ASB is installed, upgrade the ASB module.
	if (!$version['cache'] && file_exists(MYBB_ROOT.'inc/plugins/asb.php'))
	{
		@copy(MYBB_ROOT.'inc/plugins/mysteam/mysteamlist.php', MYBB_ROOT.'inc/plugins/asb/modules/mysteamlist.php');
	}
	
	// If current version is earlier than v1.1, add the new hover setting and CSS stylesheet.
	if (version_compare($version['cache'], '1.1', '<'))
	{
		$query = $db->simple_select("settinggroups", "gid", "name='mysteam_main_group'");
		$gid = $db->fetch_field($query, 'gid');
		
		$setting = array(
			'sid'			=> 'NULL',
			'name'			=> 'mysteam_hover',
			'title'			=> $lang->mysteam_hover_title,
			'description'	=> $lang->mysteam_hover_desc,
			'optionscode'	=> 'yesno',
			'value'			=> 'yes',
			'disporder'		=> '7',
			'gid'			=> $gid,
		);
		$db->insert_query('settings', $setting);
		rebuild_settings();
		
		require_once MYBB_ADMIN_DIR.'inc/functions_themes.php';
		
		$stylesheet = @file_get_contents(MYBB_ROOT.'inc/plugins/mysteam/mysteam.css');
		
		$stylesheet_array = array(
			'stylesheet'	=> $db->escape_string($stylesheet),
			'lastmodified'	=> TIME_NOW
		);
		
		$query = $db->simple_select("themestylesheets", "sid", "tid='1' AND cachefile='mysteam.css'");
		$sid = (int) $db->fetch_field($query, 'sid');
		$db->update_query('themestylesheets', $stylesheet_array, "sid='".$sid."'");
	}
	
	// If ASB is installed and the current version is earlier than 1.2.2, update the ASB module for ASB v2.1 compatibility.
	if (file_exists(MYBB_ROOT.'inc/plugins/asb.php') && version_compare($version['cache'], '1.2.2', '<'))
	{
		@copy(MYBB_ROOT.'inc/plugins/mysteam/mysteamlist.php', MYBB_ROOT.'inc/plugins/asb/modules/mysteamlist.php');
	}
	
	return $version;
}
 
/*
 * mysteam_activate()
 * 
 * Creates templates and generates edits to default templates
 */
function mysteam_activate()
{
	global $mybb, $cache, $db;
	
	// Run upgrade script (if needed).
	$version = mysteam_upgrade();
	
	// Update the cached version number (if needed).
	if ($version['cache'] != $version['new'])
	{	
		$mysteam_update['version'] = $version['new'];
		$cache->update('mysteam', $mysteam_update);
	}
	
	// Move complete list file to MyBB root directory
	@rename(MYBB_ROOT.'inc/plugins/mysteam/steam-list-complete.php', MYBB_ROOT.'steam-list-complete.php');

	// Add new templates
	$template = array(
		'title'		=> 'mysteam_list',
		'template'	=> $db->escape_string('
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead"><strong>{$lang->asb_mysteam_title}</strong></td>
	</tr>
	<tr>
		<td class="smalltext trow1">[<a href="{$mybb->settings[\'bburl\']}/steam-list-complete.php">{$lang->mysteam_complete_list}</a>]</td>
	</tr>
	<tr>
		<td class="trow2">{$list_entries}</td>
	</tr>
</table>
<br />
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_list_complete',
		'template'	=> $db->escape_string('
<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->asb_mysteam_title} ({$lang->mysteam_complete_list})</title>
{$headerinclude}
</head>
<body>
{$header}

<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead"><strong>{$lang->asb_mysteam_title} ({$lang->mysteam_complete_list})</strong></td>
	</tr>
	<tr>
		<td class="trow1">{$list_entries}</td>
	</tr>
</table>

{$footer}
</body>
</html>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_list_user',
		'template'	=> $db->escape_string('
<div class="mysteam_list_wrapper" style="width: {$entry_width}px">
	<div class="{$avatar_class} steam_avatar mysteam_list_avatar">
		<div>
			<a href="{$user[\'steamurl\']}"><img src="{$user[\'steamavatar\']}"></a>
		</div>
	</div>
	<span class="{$color_class} smalltext">
		<div class="mysteam_list_status" style="{$position}">
			<div class="mysteam_status_wrapper">
				<a href="{$user[\'steamurl\']}">{$displayname}</a><br />
				{$steam_state}<br />
			</div>
		</div>
	</span>
</div>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_postbit',
		'template'	=> $db->escape_string('
<div class="{$avatar_class} steam_icon">
	<div>
		<a href="{$steam[\'steamurl\']}"><img src="images/mysteam/steam_logo.jpg" height="16"></a>
	</div>
</div>
<div class="{$color_class} {$steam_icon_status}">{$steam_state}</div>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_profile',
		'template'	=> $db->escape_string('
<div style="position: relative">
	<div class="mysteam_status_wrapper">
		{$lang->mysteam_status} <span class="{$color_class}" style="font-weight: bold"> {$steam_state}</span>
	</div>
</div>
<br />
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_contact',
		'template'	=> $db->escape_string('
</tr>
<tr>
	<td class="trow1"><strong>{$lang->mysteam_name}</strong></td>
	<td class="trow1"><a href="{$memprofile[\'steamurl\']}">{$memprofile[\'steamname\']}</a></td>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_usercp',
		'template'	=> $db->escape_string('
<html>
<head>
	<title>{$mybb->settings[\'bbname\']} - {$lang->mysteam_integration}</title>
	{$headerinclude}
</head>
<body>
{$header}
<form method="post" action="usercp.php?action=steamid">
	<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
	<table width="100%" border="0" align="center">
		<tr>
			{$usercpnav}
			<td valign="top">
				<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
					<tr>
						<td class="thead"><strong>{$lang->mysteam_integration}</strong></td>
					</tr>
					<tr>
						<td class="trow1" valign="top">
							<div class="error" style="{$submit_display}">
								<div>
									{$submit_message}
								</div>
							</div>
							<p>{$lang->mysteam_usercp_intro}</p>
							<p>{$lang->mysteam_usercp_instruct}</p>
							<p><a href="http://steamcommunity.com/actions/Search?K={$mybb->user[\'username\']}">{$lang->mysteam_search}</a> | <a href="http://steamcommunity.com/actions/Search">{$lang->mysteam_search_manual}</a></p>
							<p>{$lang->mysteam_usercp_note}</p>
							{$lang->mysteam_url} <input type="text" name="steamprofile" class="textbox">
							<input type="hidden" name="uid" value="{$mybb->user[\'uid\']}">
							<input type="hidden" name="steamid" value="{$mybb->user[\'steamid\']}">
							<input type="hidden" name="action" value="steamid">
							<p><strong>{$lang->mysteam_current}</strong> {$mybb->user[\'steamid\']}</p>
							<p style="{$decouple_display}"><strong>{$lang->mysteam_decouple_body}</strong>  {$lang->mysteam_usercp_decouple}</p>
						</td>
					</tr>
				</table>
				<br />
				<div align="center">
					<input type="submit" class="button" name="submit" value="{$lang->mysteam_integrate}" /> 
					<span style="{$decouple_display}"><input type="submit" class="button" name="decouple" value="{$lang->mysteam_decouple}" /></span>
				</div>
			</td>
		</tr>
	</table>
</form>
{$footer}
</body>
</html>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);
	
	$template = array(
		'title'		=> 'mysteam_usercp_nav',
		'template'	=> $db->escape_string('
<tr>
	<td class="trow1 smalltext">
		<a href="usercp.php?action=steamid" class="usercp_nav_item mysteam_usercp_nav">{$lang->mysteam_integration}</a>
	</td>
</tr>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);

	$template = array(
		'title'		=> 'mysteam_modcp',
		'template'	=> $db->escape_string('
<form method="post" action="{$mybb->settings[\'bburl\']}/inc/plugins/mysteam/modcp-submit.php">
	<div>
		<fieldset class="trow2">
			<legend><strong>{$lang->mysteam_integration}</strong></legend>
			<p>{$lang->mysteam_modcp_intro}</p>
			<p>{$lang->mysteam_modcp_instruct}</p>
			<p><a href="http://steamcommunity.com/actions/Search?K={$user[\'username\']}">{$lang->mysteam_search}</a> | <a href="http://steamcommunity.com/actions/Search">{$lang->mysteam_search_manual}</a></p>
			<p>{$lang->mysteam_modcp_note}</p>
			{$lang->mysteam_url} <input type="text" name="steamprofile" class="textbox">
			<input type="hidden" name="uid" value="{$user[\'uid\']}">
			<input type="hidden" name="steamid" value="{$user[\'steamid\']}">
			<input type="submit" name="submit" value="{$lang->mysteam_integrate}"/>
			<p><strong>{$lang->mysteam_current}</strong> {$user[\'steamid\']}</p>
			<p style="{$decouple_display}"><strong>{$lang->mysteam_decouple_body}</strong>  {$lang->mysteam_modcp_decouple} <input type="submit" class="button" name="decouple" value="{$lang->mysteam_decouple}" /></p>
		</fieldset>
	</div>
</form>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);

	$template = array(
		'title'		=> 'mysteam_submit',
		'template'	=> $db->escape_string('
<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->mysteam_integration}</title>
{$headerinclude}
</head>
<body>
{$header}

<div class="thead"><strong>{$lang->mysteam_integration}</strong></div>
<div class="trow1">
	<div class="error">
		<div>
			{$submit_message}
		</div>
	</div>
	<form action="../../../modcp.php">
		<p align="center"><input type="submit" value="{$lang->mysteam_modcp_back}"></p>
	</form>
</div>

{$footer}
</body>
</html>
		'),
		'sid'		=> '-2',
		'version'	=> $mybb->version + 1,
		'dateline'	=> TIME_NOW,
	);
	$db->insert_query('templates', $template);

	// Generate edits to default templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';

	find_replace_templatesets('index', '#'.preg_quote('{$header}').'#', '{$header}{$mysteamlist}');
	find_replace_templatesets('portal', '#'.preg_quote('{$header}').'#', '{$header}{$mysteamlist}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$online_status}').'#', '{$online_status}<strong>{$steam_status}</strong>');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$lang->send_pm}</a></td>').'#', '{$lang->send_pm}</a></td>{$steamname}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'onlinestatus\']}').'#', '{$post[\'onlinestatus\']}{$post[\'steam_status_img\']}');
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'steam_status\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'onlinestatus\']}').'#', '{$post[\'onlinestatus\']}{$post[\'steam_status_img\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'steam_status\']}');		
	find_replace_templatesets('modcp_editprofile', '#'.preg_quote('	{$footer}').'#', '	{$steamform}{$footer}');
	find_replace_templatesets('usercp_nav_profile', '#'.preg_quote('{$changesigop}').'#', '{$changesigop}{$steamintegration}');
	find_replace_templatesets('footer', '#'.preg_quote('<br class="clear" />').'#', 'Powered By <a href="http://steampowered.com">Steam</a>.<br class="clear" />');
}

/*
 * mysteam_deactivate()
 * 
 * Removes new templates and reverts edits to default templates.
 */
function mysteam_deactivate()
{
	global $db;
	
	// Move complete list file back to plugin directory
	@rename(MYBB_ROOT.'steam-list-complete.php', MYBB_ROOT.'inc/plugins/mysteam/steam-list-complete.php');
	
	// Delete templates
	$db->delete_query("templates", "title LIKE 'mysteam_%' AND sid= '-2'");

	// Delete edits to default templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';

	find_replace_templatesets('index', '#'.preg_quote('{$mysteamlist}').'#', '');
	find_replace_templatesets('portal', '#'.preg_quote('{$mysteamlist}').'#', '');
	find_replace_templatesets('member_profile', '#'.preg_quote('<strong>{$steam_status}</strong>').'#', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$steamname}').'#', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'steam_status_img\']}').'#', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'steam_status\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'steam_status\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'steam_status_img\']}').'#', '', 0);
	find_replace_templatesets('modcp_editprofile', '#'.preg_quote('{$steamform}').'#', '', 0);
	find_replace_templatesets('usercp_nav_profile', '#'.preg_quote('{$steamintegration}').'#', '', 0);
	find_replace_templatesets('footer', '#'.preg_quote('Powered By <a href="http://steampowered.com">Steam</a>.').'#', '', 0);
} 
?>
