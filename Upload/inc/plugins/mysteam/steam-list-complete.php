<?php 
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * STEAM STATUS COMPLETE LIST
 * This page is used by the above plugin to generate a complete list of Steam users' current statuses if there are more users with statuses than allowed on the main list.
 */
 
$templatelist = 'mysteam_list_user,mysteam_list_complete';

define('IN_MYBB', 1); require "./global.php";

$lang->load('mysteam');

add_breadcrumb($lang->asb_mysteam_title. ' (' .$lang->mysteam_complete_list. ')', 'steam-status-complete.php');
	
// Read the cache, or refresh it if too old.
$steam = mysteam_check_cache();

if (!$steam['users'])
{	
	$list_entries = $lang->mysteam_none_found;
	eval("\$html = \"" . $templates->get("mysteam_list_complete") . "\";");
	output_page($html);
	return;
}

$entry_width = (int) $mybb->settings['mysteam_list_width'];

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
	else
	{
		$steam_presort_offline[] = $steam_presort;
	}
}
	
$steam_sort = array_merge((array)$steam_presort_game, (array)$steam_presort_online, (array)$steam_presort_offline);

// Now it is time to generate each user's Steam status.
foreach ($steam_sort as $user)
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
		
		if (strpos($steamname_clean, $username_clean) === FALSE && strpos($username_clean, $steamname_clean) === FALSE)
		{
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
		else
		{
			$displayname = $user['steamname'];
		}
	}

	// Generate status text and display styles.
	if (!$user['steamstatus'])
	{
		$steam_state = $lang->mysteam_offline;
		$avatar_class = 'steam_avatar_offline';
		$color_class = 'steam_offline';
	}
	elseif (!empty($user['steamgame']))
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
	
	eval("\$list_entries .= \"" . $templates->get("mysteam_list_user") . "\";");
}

eval("\$html = \"" . $templates->get("mysteam_list_complete") . "\";");
output_page($html);
?>
