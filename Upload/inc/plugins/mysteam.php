<?php
/* Plugin Name: MySteam Powered
 * Author: Tanweth
 * http://www.kerfufflealliance.com
 *
 * Uses the Steam Web API to obtain the current Steam status of forum users (with associated Steam IDs). It also provides User CP and Mod CP forms for obtaining a user's Steam ID.
 */

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/*
 * mysteam_info()
 * 
 * @return - (array) info on the current plugin.
 */
function mysteam_info()
{
	global $lang, $db, $mybb;

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
		$mysteam_desc .= '</ul>';
	}
	else
	{
		$mysteam_desc = $lang->mysteam_desc;
	}

	return array(
		'name'			=> $lang->mysteam_title,
		'description'	=> $mysteam_desc,
		'website'		=> 'http://kerfufflealliance.com',
		'author'		=> 'Tanweth',
		'authorsite'	=> 'http://kerfufflealliance.com',
		'version'		=> '1.0',
		'guid' 			=> 'c6c646c000efdee91b3f6de2fd7dd59a',
		'compatibility' => '16*'
	);
}

/*
 * mysteam_install()
 * 
 * Installs plugin, creating Steam ID database entry, and generating settings. Also calls mysteam_activate() function.
 */
 function mysteam_install()
 {
	global $db, $lang;

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
	
	// Move complete list file to MyBB root directory
	@rename(MYBB_ROOT.'inc/plugins/mysteam/steam-list-complete.php', MYBB_ROOT.'steam-list-complete.php');

	// Add non-Advanced Sidebox status list settings group, then settings.
	$group = array(
		'gid'			=> 'NULL',
		'title'			=> $lang->mysteam_list_group_title,
		'name'			=> 'mysteam_list_group',
		'description'	=> $lang->mysteam_list_group_desc,
		'disporder'		=> '212',
		'isdefault'		=> '0',
	);
	$group = array_map(array($db, 'escape_string'), $group);
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
		'disporder'		=> '211',
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
no='.$lang->mysteam_postbit_no
							,
		'value'			=> 'img',
		'disporder'		=> '7',
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
		'disporder'		=> '8',
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
		'disporder'		=> '9',
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
		'disporder'		=> '10',
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

 function mysteam_uninstall()
 {
	global $db, $theme;
	
	// Make sure ASB module is uninstalled before continuing.
	if (file_exists(MYBB_ROOT.'inc/plugins/asb/modules/mysteamlist.php'))
	{
		flash_message('The Steam Status module for Advanced Sidebox is still installed. Please delete it from within the Advanced Sidebox menu before uninstalling this plugin.', 'error');
		admin_redirect('index.php?module=config-plugins');
	}
	
	// Move complete list file back to plugin directory
	@rename(MYBB_ROOT.'steam-list-complete.php', MYBB_ROOT.'inc/plugins/mysteam/steam-list-complete.php');

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
 * mysteam_activate()
 * 
 * Creates templates and generates edits to default templates
 */
function mysteam_activate()
{
	global $mybb, $db;

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
		<img src="images/mysteam/steam_logo.jpg" height="16">
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

/*
 * mysteam_templatelist()
 * 
 * Check the current page script, and adds appropriate templates to global template list.
 */
$plugins->add_hook('global_start', 'mysteam_templatelist');
function mysteam_templatelist()
{
	global $templatelist;
	
	if(isset($templatelist))
	{
		if(THIS_SCRIPT == 'showthread.php')
		{
			$templatelist .= ',mysteam_postbit,mysteam_profile';
		}
		if(THIS_SCRIPT == 'index.php' || THIS_SCRIPT == 'portal.php')
		{
			$templatelist .= ',mysteam_list,mysteam_list_user';
		}
		if(THIS_SCRIPT == 'member.php')
		{
			$templatelist .= ',mysteam_profile,mysteam_contact';
		}
		if(THIS_SCRIPT == 'usercp.php')
		{
			$templatelist .= ',mysteam_usercp,mysteam_usercp_nav';
		}
		if(THIS_SCRIPT == 'modcp.php')
		{
			$templatelist .= ',mysteam_modcp';
		}
	}
}


// Check which plugins hooks should be run based on plugin settings. Don't run any if no API key supplied.
if ($mybb->settings['mysteam_apikey'])
{
	if ($mybb->settings['mysteam_list_enable'])
	{
		if ($mybb->settings['mysteam_index'])
		{
			$plugins->add_hook('index_start', 'mysteam_build_list');
		}
		if ($mybb->settings['mysteam_portal'])
		{
			$plugins->add_hook('portal_start', 'mysteam_build_list');
		}
	}
	if ($mybb->settings['mysteam_postbit'])
	{
		$plugins->add_hook('postbit', 'mysteam_postbit');
	}
	if ($mybb->settings['mysteam_profile'])
	{
		$plugins->add_hook('member_profile_end', 'mysteam_profile');
	}
	if ($mybb->settings['mysteam_usercp'])
	{
		$plugins->add_hook('usercp_start', 'mysteam_usercp');
		$plugins->add_hook('usercp_menu', 'mysteam_usercp_nav', 30);
	}
	if ($mybb->settings['mysteam_modcp'])
	{
		$plugins->add_hook('modcp_editprofile_end', 'mysteam_modcp');
	}
}

/*
 * mysteam_check_cache()
 * 
 * Checks mysteam cache, calls mysteam_build_cache() if it is older than allowed lifespan, updates cache with new info, then reads new cache.
 * 
 * return: (mixed) an (array) of the cached Steam user info, or (bool) false on fail.
 */
// Only load if function not already called (may be if using ASB sidebox on same page).
if (!function_exists('mysteam_check_cache'))
{
	function mysteam_check_cache()
	{	
		global $mybb, $cache;
		
		// Don't touch the cache if disabled, just return the results from Steam's network.
		if (!$mybb->settings['mysteam_cache'])
		{
			$steam = mysteam_build_cache();
			
			if ($steam['users'])
			{
				return $steam;
			}
			return false;
		}
		
		$steam = $cache->read('mysteam');
		
		// Convert the cache lifespan setting into seconds.
		$cache_lifespan = 60 * (int) $mybb->settings['mysteam_cache'];
		
		// Attempt to update the cache if it is too old.
		if (!$steam['time'] || TIME_NOW - $steam['time'] > $cache_lifespan)
		{	
			// Cache time of last attempt to contact Steam, then on future loads check if it has been 3 minutes since then. If not, return false.
			if (!$steam['lastattempt'] || TIME_NOW - $steam['lastattempt'] > 180)
			{
				$steam_update['lastattempt'] = TIME_NOW;
				$cache->update('mysteam', $steam_update);
			}
			else
			{
				return false;
			}
			
			$steam_update = mysteam_build_cache();
			
			// If response generated, update the cache.
			if ($steam_update['users'])
			{
				$cache->update('mysteam', $steam_update);
				$steam = $cache->read('mysteam');
			}
			else
			{
				return false;
			}
		}
		return $steam;
	}
}

/*
 * mysteam_filter_groups()
 * 
 * Queries database for users with Steam IDs (filtering as needed based on settings), contacts Steam servers to obtain current user info, then caches new info.
 * 
 * @param - $user - (array) the user to be checked against allowed groups.
 *
 * return: (bool) true if user in allowed group (or limiting by group is disabled), false otherwise.
 */
function mysteam_filter_groups($user)
{
	global $mybb;
	static $gids;

	// Return true if no usergroups to filter from.
	if (!$mybb->settings['mysteam_limitbygroup'])
	{
		return true;
	}
	
	// Check if the user is in an authorized usergroup.
	if (empty($gids))
	{
		$gids = explode(',', $mybb->settings['mysteam_limitbygroup']);
			
		if (is_array($gids))
		{
			$gids = array_map('intval', $gids);
		}
		else
		{
			$gids = (int) $mybb->settings['mysteam_limitbygroup'];
		}
	}
		
	$usergroups = explode(',', $user['additionalgroups']);
	$usergroups[] = $user['usergroup'];
	
	if (!is_array($usergroups))
	{
		$usergroups = array($usergroups);
	}
	
	if (array_intersect($usergroups, $gids))
	{
		return true;
	}
	return false;
}

/*
 * mysteam_build_cache()
 * 
 * Queries database for users with Steam IDs (filtering as needed based on settings), contacts Steam servers to obtain current user info, then caches new info.
 * 
 * return: (mixed) an (array) of the Steam user info to be cached, or (bool) false on fail.
 */
// Only load if function not already called (may be if using ASB sidebox on same page).
if (!function_exists('mysteam_build_cache'))
{
	function mysteam_build_cache()
	{
		global $mybb, $db, $cache;
		
		// Prune users who haven't visited since the cutoff time if set.
		if ($mybb->settings['mysteam_prune'])
		{
			$cutoff = TIME_NOW - (86400 * (int) $mybb->settings['mysteam_prune']);
			$cutoff_query = 'AND lastvisit > ' .$cutoff;
		}
		
		// Retrieve all members who have Steam IDs from the database.
		$query = $db->simple_select("users", "uid, username, usergroup, additionalgroups, steamid", "steamid IS NOT NULL AND steamid<>''" .$cutoff_query, array("order_by" => 'username'));

		// Check if there are usergroups to limit the results to.
		if ($mybb->settings['mysteam_limitbygroup'])
		{
			// Loop through results, casting aside those not in the allowed usergroups like the dry remnant of a garden flower.
			while ($user = $db->fetch_array($query))
			{	
				$is_allowed = mysteam_filter_groups($user);
				
				if ($is_allowed)
				{
					$users[] = $user;
				}
			}
		}
		else
		{	
			while ($user = $db->fetch_array($query))
			{
				$users[] = $user;
			}
		}

		// Only run if users to display
		if (!$users)
		{
			return false;
		}
		
		// Generate list of URLs for contacting Steam's servers.
		foreach ($users as $user)
		{
			$data[] = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $user['steamid'];
		}

		// Fetch data from Steam's servers.
		$responses = multiRequest($data);	
		
		// Check that there was a response (i.e. ensure Steam's servers aren't down).
		if (strpos($responses[0], 'response') === FALSE)
		{	
			return false;
		}
		
		for ($n = 0; $n <= count($users); $n++)
		{
			$user = $users[$n];
			$response = $responses[$n];
			
			// Occasionally Steam's servers return a response with no values. If so, don't update info for the current user.
			if (strpos($response, 'steamid') === FALSE)
			{
				continue;
			}

			// Decode response (returned in JSON), then create array of important fields. Escape them and remove nasty special characters.
			$decoded = json_decode($response);

			$steam_update['users'][$user['uid']] = array (
				'username' => $db->escape_string($user['username']),
				'steamname' => $db->escape_string(preg_replace("/[^a-zA-Z 0-9-,:&_]+/", "", $decoded->response->players[0]->personaname)),
				'steamurl' => $db->escape_string($decoded->response->players[0]->profileurl),
				'steamavatar' => $decoded->response->players[0]->avatar,
				'steamstatus' => $decoded->response->players[0]->personastate,
				'steamgame' => $db->escape_string(preg_replace("/[^a-zA-Z 0-9-,:&_]+/", "", $decoded->response->players[0]->gameextrainfo))
			);	
		}
		$steam_update['time'] = TIME_NOW;
		
		return $steam_update;
	}
}

/*
 * mysteam_build_list()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate Steam status entry for each user.
 * 
 */
function mysteam_build_list()
{	
	global $mybb, $lang, $templates, $list_entries, $mysteamlist;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	// Read the cache, or refresh it if too old.
	$steam = mysteam_check_cache();
	
	// If we could not get any data, don't build list and display error.
	if (!$steam['users'])
	{
		$list_entries = $lang->mysteam_none_found;
		eval("\$mysteamlist = \"" . $templates->get("mysteam_list") . "\";");
		return;
	}
	
	$entry_width = (int) $mybb->settings['mysteam_list_width'];
	
	// Sort users who are in-game to top of list.
	foreach ($steam['users'] as $steam_presort)
	{	
		if ($steam_presort['steamgame'])
		{
			$steam_presort_game[] = $steam_presort;
		}
		elseif ($steam_presort['steamstatus'] > 0)
		{
			$steam_presort_online[] = $steam_presort;
		}
	}
	
	if(!empty($steam_presort_game))
	{
		$steam['users'] = array_merge($steam_presort_game, $steam_presort_online);
	}
	else
	{
		$steam['users'] = $steam_presort_online;
	}
	
	$n = 0;
	
	// Check each user's info and generate a status entry.
	foreach ($steam['users'] as $user)
	{
		// Check display name setting, and set displayed name appropriately.
		if ($mybb->settings['mysteam_displayname'] == 'steam')
		{
			$displayname = $user['steamname'];
		}
		elseif ($mybb->settings['mysteam_displayname'] == 'forum')
		{
			$displayname = $user['username'];
		}
		// Remove capitals, numbers, and special characters from name to minimize false negatives when checking if username and steamname are comparable.
		else
		{
			$username_clean = preg_replace("/[^a-zA-Z]+/", "", strtolower($user['username']));
			$steamname_clean = preg_replace("/[^a-zA-Z]+/", "", strtolower($user['steamname']));
			
			// If names aren't comparable, display both steam name and forum username.
			if (strpos($steamname_clean, $username_clean) === FALSE && strpos($username_clean, $steamname_clean) === FALSE)
			{
				// If status entry is too narrow, place names on separate lines.
				if ($entry_width < '200')
				{
					$displayname = $user['steamname']. '<br />(' .$user['username']. ')';
					$position = 'bottom: 3px;';
				}
				else
				{
					$displayname = $user['steamname']. ' (' .$user['username']. ')';
				}
			}
			// If names are comparable, display the Steam name.
			else
			{
				$displayname = $user['steamname'];
			}
		}
		
		// Generate status text and display style based on current status.
		if (!empty($user['steamgame']))
		{
			$steam_state = $user['steamgame'];
			$avatar_class = 'steam_avatar_in-game';
			$color_class = 'steam_in-game';
		}
		elseif ($user['steamstatus'] == '1')
		{
			$steam_state = $lang->mysteam_online;
			$avatar_class = 'steam_avatar_online';
			$color_class = 'steam_online';
		}
		elseif ($user['steamstatus'] == '3')
		{
			$steam_state = $lang->mysteam_away;
			$avatar_class = 'steam_avatar_online';
			$color_class = 'steam_online';
		}
		elseif ($user['steamstatus'] == '4')
		{
			$steam_state = $lang->mysteam_snooze;
			$avatar_class = 'steam_avatar_online';
			$color_class = 'steam_online';
		}
		elseif ($user['steamstatus'] == '2')
		{
			$steam_state = $lang->mysteam_busy;
			$avatar_class = 'steam_avatar_online';
			$color_class = 'steam_online';
		}
		elseif ($user['steamstatus'] == '5')
		{
			$steam_state = $lang->mysteam_looking_to_trade;
			$avatar_class = 'steam_avatar_online';
			$color_class = 'steam_online';
		}
		elseif ($user['steamstatus'] == '6')
		{
			$steam_state = $lang->mysteam_looking_to_play;
			$avatar_class = 'steam_avatar_online';
			$color_class = 'steam_online';
		}
		
		// Don't generate entries for users in excess of the maximum number setting.
		if ($mybb->settings['mysteam_list_number'])
		{
			$n++;
			
			if ($n > $mybb->settings['mysteam_list_number'])
			{
				continue;
			}
		}

		eval("\$list_entries .= \"" . $templates->get("mysteam_list_user") . "\";");
	}

	if ($list_entries)
	{
		// Set the template variable to returned statuses list
		eval("\$mysteamlist = \"" . $templates->get("mysteam_list") . "\";");
	}
}

/*
 * mysteam_status()
 * 
 * Determines display style and status text for the supplied user based on their current status info.
 * 
 * @param - $steam - (array) the cached Steam user info
 */ 
function mysteam_status(&$steam)
{
	global $lang, $mybb, $templates, $post, $steam_status;
	
	// Determine user's current status and appropriate visual style.
	if ($steam['steamstatus'] == '0')
	{
		$steam_state = $lang->mysteam_offline;
		$avatar_class = 'steam_avatar_offline';
		$color_class = 'steam_offline';
	}
	elseif (!empty($steam['steamgame']))
	{
		$steam_state = $steam['steamgame'];
		$avatar_class = 'steam_avatar_in-game';
		$color_class = 'steam_in-game';
	}
	elseif ($steam['steamstatus'] == '1')
	{
		$steam_state = $lang->mysteam_online;
		$avatar_class = 'steam_avatar_online';
		$color_class = 'steam_online';
	}
	elseif ($steam['steamstatus'] == '3')
	{
		$steam_state = $lang->mysteam_away;
		$avatar_class = 'steam_avatar_online';
		$color_class = 'steam_online';
	}
	elseif ($steam['steamstatus'] == '4')
	{
		$steam_state = $lang->mysteam_snooze;
		$avatar_class = 'steam_avatar_online';
		$color_class = 'steam_online';
	}
	elseif ($steam['steamstatus'] == '2')
	{
		$steam_state = $lang->mysteam_busy;
		$avatar_class = 'steam_avatar_online';
		$color_class = 'steam_online';
	}
	elseif ($steam['steamstatus'] == '5')
	{
		$steam_state = $lang->mysteam_looking_to_trade;
		$avatar_class = 'steam_avatar_online';
		$color_class = 'steam_online';
	}
	elseif ($steam['steamstatus'] == '6')
	{
		$steam_state = $lang->mysteam_looking_to_play;
		$avatar_class = 'steam_avatar_online';
		$color_class = 'steam_online';
	}
	
	// Some things to check if running this on postbit.
	if ($post)
	{
		// If set to display status as an image.
		if ($mybb->settings['mysteam_postbit'] == 'img')
		{
			// Special display style for classic post bit.
			if ($mybb->user['classicpostbit'])
			{
				$steam_icon_status = 'steam_icon_status_classic';
			}
			else
			{
				$steam_icon_status = 'steam_icon_status';
			}
			eval("\$steam['steam_status_img'] = \"".$templates->get("mysteam_postbit")."\";");
		}
		// If set to display status as text
		elseif ($mybb->settings['mysteam_postbit'] == 'text')
		{
			eval("\$steam['steam_status'] = \"".$templates->get("mysteam_profile")."\";");
		}
		// If set not to display
		else
		{
			// Display nothing.
		}
	}
	else
	{
		eval("\$steam_status = \"".$templates->get("mysteam_profile")."\";");
	}
}

/*
 * mysteam_postbit()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate array of Steam info for every user on current show thread page.
 * 
 * @param - $post - (array) user information on the current poster.
 */
function mysteam_postbit(&$post)
{
	global $lang, $mybb;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	if ($post['steamid'])
	{
		$steam = mysteam_check_cache();
		
		if ($steam)
		{
			$post = $post + $steam['users'][$post['uid']];
			mysteam_status($post);
		}
	}
}

/*
 * mysteam_profile()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate array of Steam info for user on current member profile page. Also generates Steam contact link.
 */
function mysteam_profile()
{
	global $lang, $memprofile, $mybb, $templates, $steamname;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	// Only run if the profile owner has a Steam ID.
	if ($memprofile['steamid'])
	{
		$steam = mysteam_check_cache();
		
		if ($steam)
		{
			$memprofile = $memprofile + $steam['users'][$memprofile['uid']];
			mysteam_status($memprofile);
			eval("\$steamname = \"".$templates->get("mysteam_contact")."\";");
		}
	}
}

/*
 * mysteam_usercp_nav()
 * 
 * Loads mysteam language phrases so that new User CP nav menu item can be displayed.
 */ 
function mysteam_usercp_nav()
{
	global $lang, $mybb, $templates, $usercpmenu;

	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	
	$is_allowed = mysteam_filter_groups($mybb->user);
	
	if ($is_allowed)
	{
		eval("\$usercpmenu .= \"".$templates->get("mysteam_usercp_nav")."\";");
	}
}

/*
 * mysteam_usercp()
 * 
 * Generates new Steam Integration page on User CP and processes data submitted through form.
 */ 
function mysteam_usercp()
{
	global $lang, $mybb;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}

	// Check if current User CP page is Steam Integration.
	if ($mybb->input['action'] == 'steamid')
	{
		global $db, $theme, $templates, $headerinclude, $header, $footer, $plugins, $usercpnav, $steamform;
		
		// Make sure user is in an allowed usergroup if set.
		$is_allowed = mysteam_filter_groups($mybb->user);
				
		if (!$is_allowed)
		{
			error_no_permission();
		}
		
		add_breadcrumb($lang->nav_usercp, 'usercp.php');
		add_breadcrumb($lang->mysteam_integration, 'usercp.php?action=steamid');

		$submit_display = 'display: none;';
		
		if (!$mybb->user['steamid'])
		{
			$decouple_display = 'display: none;';
		}
		
		// Process the form submission if something has been submitted.
		if ($mybb->input['uid'])
		{
			$submit_display = '';
			$uid = $db->escape_string($mybb->input['uid']);
			
			// If user has attempted to submit a Steam profile.
			if ($mybb->input['submit'])
			{
				// Add http:// to the link if the user was too lazy to do so.
				if (strpos($mybb->input['steamprofile'], 'http://') === FALSE)
				{
					$steamprofile = 'http://' . $mybb->input['steamprofile'];
				}
				else
				{
					$steamprofile = $mybb->input['steamprofile'];
				}
				
				// Generate XML URL and load it.
				$xml_url = $steamprofile . '?xml=1';
				$xml = @simplexml_load_file($xml_url);

				// Don't run if URL isn't proper XML file, and display error.
				if (!$xml->steamID64)
				{
					$submit_message = '
						<p><em>' .$lang->please_correct_errors. '</em></p>
						<p>' .$lang->mysteam_submit_invalid. '</p>';
				}
				else
				{
					$steamid = $db->escape_string($xml->steamID64);
					$query = $db->simple_select("users", "username", "steamid='".$steamid."'");
					$username_same = $db->fetch_field($query, 'username');
					
					// Don't run if Steam ID matches another user's current ID, and display error.
					if ($db->num_rows($query) > 0)
					{
						$submit_message = '
							<p><em>' .$lang->please_correct_errors. '</em></p>
							<p>' .$lang->mysteam_submit_same. $username_same. '</p>';
					}
					// Otherwise, write to the database and display success!
					else
					{
						$steamid = $db->escape_string($xml->steamID64);
						$db->update_query("users", array('steamid' => $steamid), "uid='".$uid."'");
						
						$submit_message = 
							'<p><strong>' .$lang->mysteam_submit_success. '</strong></p>
							<p><strong>' .$lang->mysteam_steamname. '</strong>' .$xml->steamID. '</p>
							<p><strong>' .$lang->mysteam_steamid. '</strong>' .$xml->steamID64. '</p>';
					}
				}
			}
			// If user has requested to decouple.
			elseif ($mybb->input['decouple'])
			{
				$submit_display = '';
				$uid = $db->escape_string($mybb->input['uid']);
			
				$db->update_query("users", array('steamid' => ''), "uid='".$uid."'");
				$submit_message = $lang->mysteam_decouple_success;
			}
		}
		
		eval("\$steamform = \"".$templates->get("mysteam_usercp")."\";");
		output_page($steamform);
	}
}

/*
 * mysteam_modcp()
 * 
 * Generates form on Moderator CP profile editor page.
 */
function mysteam_modcp()
{
	global $lang, $mybb, $templates, $user, $steamform;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	if (!$user['steamid'])
	{
		$decouple_display = 'display: none;';
	}
	
	define('IN_MODCP', 1);

	eval("\$steamform = \"".$templates->get("mysteam_modcp")."\";");
}

// Only load if function not already called (may be if using with ASB sidebox).
if (!function_exists('multiRequest'))
{
	// Function for making multiple requests to a server to get file contents (http://www.phpied.com/simultaneuos-http-requests-in-php-with-curl/).
	function multiRequest($data, $options = array()) 
	{
		// array of curl handles
		$curly = array();
		// data to be returned
		$result = array();

		// multi handle
		$mh = curl_multi_init();

		// loop through $data and create curl handles
		// then add them to the multi-handle
		foreach ($data as $id => $d) {

		$curly[$id] = curl_init();

		$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
		curl_setopt($curly[$id], CURLOPT_URL, $url);
		curl_setopt($curly[$id], CURLOPT_HEADER, 0);
		curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

		// post?
		if (is_array($d)) {
		  if (!empty($d['post'])) {
			curl_setopt($curly[$id], CURLOPT_POST,       1);
			curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
		  }
		}

		// extra options?
		if (!empty($options)) {
		  curl_setopt_array($curly[$id], $options);
		}

		curl_multi_add_handle($mh, $curly[$id]);
		}

		// execute the handles
		$running = null;
		do {
		curl_multi_exec($mh, $running);
		} while($running > 0);


		// get content and remove handles
		foreach($curly as $id => $c) {
		$result[$id] = curl_multi_getcontent($c);
		curl_multi_remove_handle($mh, $c);
		}

		// all done
		curl_multi_close($mh);

		return $result;
	}
}
?>
