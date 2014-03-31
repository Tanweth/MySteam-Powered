<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * STEAM STATUS FUNCTIONS
 * This file is used by the above plugin to handle functions related to the Steam Status module.
 */

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/*
 * mysteam_check_cache()
 * 
 * Checks mysteam cache, calls mysteam_build_cache() if it is older than allowed lifespan, updates cache with new info, then returns cached info.
 * 
 * return: (mixed) an (array) of the cached Steam user info, or (bool) false on fail.
 */
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
	
	// If the cache is still current enough, just return the cached info.
	if (TIME_NOW - (int) $steam['time'] < $cache_lifespan)
	{	
		return $steam;
	}	
	
	// If last attempt to contact Steam failed, check if it has been over 3 minutes since then. If not, return false (i.e. do not attempt another contact).
	if (TIME_NOW - (int) $steam['lastattempt'] < 180)
	{
		return false;
	}
		
	$steam_update = mysteam_build_cache();
	
	// If response generated, update the cache.
	if ($steam_update['users'])
	{
		$steam_update['version'] = $steam['version'];
		$cache->update('mysteam', $steam_update);
		return $steam_update;
	}
	// If not, cache time of last attempt to contact Steam, so it can be checked later.
	else
	{
		$steam_update['lastattempt'] = TIME_NOW;
		$steam_update['version'] = $steam['version'];
		$cache->update('mysteam', $steam_update);
		return false;
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
function mysteam_build_cache()
{
	global $mybb, $db, $cache;
	
	// Prune users who haven't visited since the cutoff time if set.
	if ($mybb->settings['mysteam_prune'])
	{
		$cutoff = TIME_NOW - (86400 * (int) $mybb->settings['mysteam_prune']);
		$cutoff_query = 'AND lastvisit > ' . $cutoff;
	}
	
	// Retrieve all members who have Steam IDs from the database.
	$query = $db->simple_select("users", "uid, username, avatar, usergroup, additionalgroups, steamid", "steamid IS NOT NULL AND steamid<>''" . $cutoff_query, array("order_by" => 'username'));

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
	
	// Cache time of cache update.
	$steam_update['time'] = TIME_NOW;
	
	// Loop through results from Steam and associate them with the users from database query.
	for ($n = 0; $n <= count($users); $n++)
	{
		$user = $users[$n];
		$response = $responses[$n];
		
		// Occasionally Steam's servers return a response with no values. If so, don't update info for the current user.
		if (strpos($response, 'personastate') === FALSE)
		{
			continue;
		}

		// Decode response (returned in JSON), then create array of important fields.
		$decoded = json_decode($response);

		$steam_update['users'][$user['uid']] = array (
			'username'				=> $user['username'],
			'steamname'				=> htmlspecialchars_uni($decoded->response->players[0]->personaname)),
			'steamurl'				=> $db->escape_string($decoded->response->players[0]->profileurl),
			'steamavatar'			=> $db->escape_string($decoded->response->players[0]->avatar),
			'steamavatar_medium'	=> $db->escape_string($decoded->response->players[0]->avatarmedium),
			'steamavatar_full'		=> $db->escape_string($decoded->response->players[0]->avatarfull),
			'steamstatus'			=> $db->escape_string($decoded->response->players[0]->personastate),
			'steamgame'				=> htmlspecialchars_uni($decoded->response->players[0]->gameextrainfo))
		);
	}
	return $steam_update;
}

/*
 * mysteam_build_list()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate Steam status entry for each user.
 */
function mysteam_build_list()
{	
	global $mybb, $lang, $templates, $list_entries, $mysteamlist;
	
	if (THIS_SCRIPT == 'index.php' && !$mybb->settings['mysteam_index'])
	{
		return false;
	}
	elseif (THIS_SCRIPT == 'portal.php' && !$mybb->settings['mysteam_portal'])
	{	
		return false;
	}
	
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

	$steam['users'] = array_merge((array)$steam_presort_game, (array)$steam_presort_online);
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
				break;
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
?>