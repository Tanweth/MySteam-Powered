<?php
/* Module Name: MySteam Powered (Advanced Sidebox Edition)
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * ADVANCED SIDEBOX MODULE
 * Uses the Steam Web API to generate a sidebox with a list of forum users (with associated Steam IDs) who are currently on Steam and their status.
 */

// Include a check for Advanced Sidebox
if(!defined('IN_MYBB') || !defined('IN_ASB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/*
 * asb_mysteamlist_info()
 *
 * Provides info to ASB about the addon.
 *
 * @return: (array) the module info.
 */
function asb_mysteamlist_info()
{
	global $lang, $db, $mybb;

	if(!$lang->asb_addon)
	{
		$lang->load('asb_addon');
	}
	
	if(!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	$query = $db->simple_select("settinggroups", "gid", "name='mysteam_main_group'");
	$gid = $db->fetch_field($query, 'gid');
	
	// Generate the where to find other settings instructions, plus error flags if needed.
	$mysteam_settings_where_desc = $lang->mysteam_settings_where_desc . '<ul>';
	
	// Link to settings, if MyBB plugin is installed.
	if ($gid)
	{
		$mysteam_settings_where_desc .= '<li><img src="' .$mybb->settings['bburl']. '/images/mysteam/steam_icon.png"> <a href="index.php?module=config-settings&action=change&gid=' .$gid. '">' .$lang->mysteam_settings. '</a></li>';
	}
	
	// Check if MyBB plugin is installed. If not, display error.
	$query = $db->simple_select("templates", "tid", "title = 'mysteam_usercp'");
	if ($db->num_rows($query) == 0)
	{
		$mysteam_settings_where_desc .=  '<li><img src="' .$mybb->settings['bburl']. '/images/error.gif"> ' .$lang->mysteam_plugin_needed. '</li>';
	}
	
	$mysteam_settings_where_desc .= '</ul>';

	return array
	(
		'title' => $lang->asb_mysteam_title,
		'description' => $lang->asb_mysteam_desc,
		'author' => 'Tanweth',
		'module_site' => 'https://github.com/Tanweth/MySteam-Powered',
		'author_site' => 'http://kerfufflealliance.com',
		'wrap_content'	=> true,
		'version' => '1.2.2',
		'compatibility' => '2.1',
		'xmlhttp' => true,
		'settings' =>	array
		(
			'settings_where' => array
			(
				'sid' => 'NULL',
				'name' => 'settings_where',
				'title' => $lang->mysteam_settings_where_title,
				'description' => $mysteam_settings_where_desc,
				'optionscode' => 'text',
				'value' => $lang->mysteam_doesnt_do_anything
			),
			'asb_steam_list_cols' => array
			(
				'sid' => 'NULL',
				'name' => 'asb_steam_list_cols',
				'title' => $lang->mysteam_list_cols_title,
				'description' => $lang->mysteam_list_cols_desc,
				'optionscode' => 'text',
				'value' => '0'
			),
			'asb_steam_list_number' => array
			(
				'sid' => 'NULL',
				'name' => 'asb_steam_list_number',
				'title' => $lang->mysteam_list_number_title,
				'description' => $lang->mysteam_list_number_desc,
				'optionscode' => 'text',
				'value' => '0'
			),
			'xmlhttp_on' => array
			(
				'sid' => 'NULL',
				'name' => 'xmlhttp_on',
				'title' => $lang->asb_xmlhttp_on_title,
				'description' => $lang->asb_xmlhttp_on_description,
				'optionscode' => 'text',
				'value' => '0'
			)
		),
		'templates' => array
		(
			array
			(
				'title' => 'asb_mysteam',
				'template' => <<<EOF
<tr>
	<td class="trow1 smalltext">[<a href="{\$mybb->settings[\'bburl\']}/steam-list-complete.php">{\$lang->mysteam_complete_list}</a>]</td>
</tr>
<tr>
	<td class="trow1">
		{\$asb_list_entries}
	</td>
</tr>
EOF
			)
		)
	);
}

/*
 * asb_mysteamlist_build_template()
 *
 * Handles display of children of this addon at page load
 *
 * @param - $args - (array) the specific information from the child box
 * @return: (bool) true on success, false on fail/no content
 */
function asb_mysteamlist_build_template($args)
{	
	// retrieve side box box settings
	foreach(array('settings', 'template_var', 'width') as $key)
	{
		$$key = $args[$key];
	}

	// don't forget to declare your variable! will not work without this
	global $$template_var, $lang, $mybb, $asb_mysteamlist;
	
	if(!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	// Build the Steam statuses list. Only attempt if a Steam Web API key is provided.
	if ($mybb->settings['mysteam_apikey'])
	{
		$asb_mysteamlist = asb_mysteamlist_build_list($settings, $width);
	}

	// If there are Steam users to display . . .
	if($asb_mysteamlist)
	{
		// set out template variable to the returned statuses list and return true
		$$template_var = $asb_mysteamlist;
		return true;
	}
	else
	{
		$$template_var = <<<EOF
		<tr><td class="trow1">{$lang->mysteam_none_found}</td></tr>
EOF;
		return false;
	}
}

/*
 * asb_mysteamlist_xmlhttp()
 *
 * Handles display of children of this addon via AJAX
 *
 * @param - $args - (array) the specific information from the child box
 * @return: n/a
 */	
function asb_mysteamlist_xmlhttp($args)
{
	foreach(array('settings', 'dateline', 'width') as $key)
	{
		$$key = $args[$key];
	}
	
	$asb_mysteamlist = asb_mysteamlist_build_list($settings, $width);

	if($asb_mysteamlist)
	{
		return $asb_mysteamlist;
	}
	return 'nochange';
}

/*
 * asb_mysteamlist_build_list()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate Steam status entry for each user.
 *
 * @param - $settings (array) individual side box settings passed to the module
 *
 * @param - $width - (int) the width of the column in which the child is positioned
 *
 * @return: (mixed) a (string) containing the HTML side box markup or (bool) false on fail/no content
 */
function asb_mysteamlist_build_list($settings, $width)
{	
	global $mybb, $lang, $templates;
	
	// Make sure the main plugin's functions are available (may not be if it's disabled).
	if (function_exists(mysteam_check_cache))
	{
		// Read the cache, or refresh it if too old.
		$steam = mysteam_check_cache();
	}
	
	// If no users to display, show error.
	if (!$steam['users'])
	{
		return false;
	}
	
	// If set to display multiple columns, reduce each status entry's width accordingly.
	if ((int) $settings['asb_steam_list_cols'] < 2)
	{
		$entry_width = $width - 5;
	}
	else
	{
		$col_number = (int) $settings['asb_steam_list_cols'];
		$entry_width = ($width - (5 + (5 * $col_number))) / $col_number;
	}
	
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
	
	$steam['users'] = array_merge((array)$steam_presort_game, (array)$steam_presort_online);	
	$n = 0;

	// Check each user's info and generate status entry.
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
		// Remove capitals, numbers, and special characters name to minimize false negatives when checking if username and steamname are comparable.
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
				// If names are comparable, display the Steam name.
				else
				{
					$displayname = $user['steamname']. ' (' .$user['username']. ')';
				}
			}
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
		if ($settings['asb_steam_list_number'])
		{
			$n++;
			
			if ($n > (int) $settings['asb_steam_list_number'])
			{
				break;
			}
		}
		
		eval("\$asb_list_entries .= \"" . $templates->get("mysteam_list_user") . "\";");
	}
	
	if ($asb_list_entries)
	{
		// Set template variable to returned statuses list and return true
		eval("\$asb_mysteamlist = \"" . $templates->get("asb_mysteam") . "\";");
		return $asb_mysteamlist;
	}
	return false;
}
?>
