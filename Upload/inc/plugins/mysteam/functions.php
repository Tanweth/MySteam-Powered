<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * GLOBAL FUNCTIONS
 * This file is used by the above plugin to handle global functions (i.e. ones not attached to a particular plugin hook).
 */

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/*
 * mysteam_check_cache()
 * 
 * Checks mysteam cache, calls mysteam_build_cache() if it is older than allowed lifespan, updates cache with new info, then reads new cache.
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
	
	// Generate list of Steam IDs for contacting Steam's servers, and create array of forum user info with Steam ID as key, so that we can associate forum info with the Steam responses.
	foreach ($users as $user)
	{
		$steamids_array[] = $user['steamid'];
		$users_sorted[$user['steamid']] = $user;
	}
	
	$steamids = implode(',', $steamids_array);
	$data = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $steamids;

	// Fetch data from Steam's servers.
	$response = multiRequest($data);	
	
	// Check that there was a response (i.e. ensure Steam's servers aren't down).
	if (!strpos($response[0], 'steamid'))
	{	
		return false;
	}
	
	// Cache time of cache update.
	$steam_update['time'] = TIME_NOW;
	
	// Decode response (returned in JSON), then create array of important fields.
	$decoded = json_decode($response[0]);
	
	// Loop through results from Steam and associate them with the users from database query.
	foreach ($decoded->response->players as $player)
	{			
		$steam_update['users'][$users_sorted[$player->steamid]['uid']] = array (
			'username'	=> $users_sorted[$player->steamid]['username'],
			'steamname'	=> preg_replace("/[^a-zA-Z 0-9-,!@#$%^*()=+&_{};:'<>?]+/", "", $player->personaname),
			'steamurl'	=> $db->escape_string($player->profileurl),
			'steamavatar'	=> $db->escape_string($player->avatar),
			'steamstatus'	=> $db->escape_string($player->personastate),
			'steamgame'	=> preg_replace("/[^a-zA-Z 0-9-,!@#$%^*()=+&_{};:'<>?]+/", "", $player->gameextrainfo)
		);	
	}
	return $steam_update;
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
				if ($mybb->settings['mysteam_hover'])
				{
					$steam_icon_status = 'steam_icon_status_classic';
				}
				else
				{
					$steam_icon_status = 'steam_icon_status_classic_nohover';
				}
			}
			else
			{
				if ($mybb->settings['mysteam_hover'])
				{
					$steam_icon_status = 'steam_icon_status';
				}
				else
				{
					$steam_icon_status = 'steam_icon_status_nohover';
				}		
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

// Function for making multiple requests to a server to get file contents (http://www.phpied.com/simultaneuos-http-requests-in-php-with-curl/).
function multiRequest($data, $options = array()) 
{
	$data = (array) $data;

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
	curl_setopt($curly[$id], CURLOPT_TIMEOUT, 10);
	curl_setopt($curly[$id], CURLOPT_CONNECTTIMEOUT, 10);

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
?>
