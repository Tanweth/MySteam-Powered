<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * Uses the Steam Web API to obtain the current Steam status of forum users (with associated Steam IDs). It also provides User CP and Mod CP forms for obtaining a user's Steam ID.
 */

// Disallow direct access to this file for security reasons
if (!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

// Load installation functions if in Admin CP.
if (defined('IN_ADMINCP'))
{
   require_once MYBB_ROOT . "inc/plugins/mysteam/functions_install.php";
   return;
}

global $mybb;

// Bring in module functions for each enabled module.
if ($mybb->settings['mysteam_login_enable'])
{
	require_once MYBB_ROOT . "inc/plugins/mysteam/functions_login.php";
}
if ($mybb->settings['mysteam_status_enable'])
{
	require_once MYBB_ROOT . "inc/plugins/mysteam/functions_status.php";
}
if ($mybb->settings['mysteam_other_enable'])
{
	require_once MYBB_ROOT . "inc/plugins/mysteam/functions_other.php";
}

/*
 * mysteam_templatelist()
 * 
 * Check the current page script, and adds appropriate templates to global template list.
 */
$plugins->add_hook('global_start', 'mysteam_templatelist');
function mysteam_templatelist()
{
	global $templatelist, $mybb;
	
	if(isset($templatelist))
	{
		if($mybb->settings['mysteam_list_enable'])
		{
			$templatelist .= ',mysteam_list,mysteam_list_user';
		}
	
		if(THIS_SCRIPT == 'showthread.php')
		{
			$templatelist .= ',mysteam_postbit,mysteam_profile';
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
	if ($mybb->settings['mysteam_login_enable'])
	{
		$plugins->add_hook('global_end', 'mysteam_sync_info');
	}
	if ($mybb->settings['mysteam_list_enable'])
	{
		$plugins->add_hook('global_end', 'mysteam_build_list');
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
 * mysteam_check_main_cache()
 * 
 * Checks main mysteam cache, calls mysteam_build_main_cache() if it is older than allowed lifespan, updates cache with new info, then returns cached info.
 * 
 * return: (mixed) an (array) of the cached Steam user info, or (bool) false on fail.
 */
function mysteam_check_main_cache()
{	
	global $mybb, $cache;
	
	// Ensure the minimum cache lifespan of 1 hour.
	if ($mybb->settings['mysteam_main_cache'] < 1)
	{
		$mybb->settings['mysteam_main_cache'] == 1;
	}
	
	$steam = $cache->read('mysteam');
	
	// Convert the cache lifespan setting into seconds.
	$cache_lifespan = 3600 * (int) $mybb->settings['mysteam_main_cache'];
	
	// If the cache is still current enough, or if last attempt to contact Steam failed and it hasn't been over a half-hour since then, just return the cached info.
	if ( (TIME_NOW - (int) $steam['time'] < $cache_lifespan) || (TIME_NOW - (int) $steam['lastattempt'] < 1800) )
	{	
		return $steam;
	}	
		
	$steam_update = mysteam_build_main_cache();
	
	// If response generated, update the cache.
	if ($steam_update['users'])
	{
		$steam_update['version'] = $steam['version'];
		$cache->update('mysteam', $steam_update);
		return $steam_update;
	}
	// If not, cache time of last attempt to contact Steam, so it can be checked later, and return last cached info.
	else
	{
		$steam['lastattempt'] = TIME_NOW;
		$steam['version'] = $steam['version'];
		$cache->update('mysteam', $steam);
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
 * mysteam_build_main_cache()
 * 
 * Queries database for users with Steam IDs (filtering as needed based on settings), contacts Steam servers to obtain current user info, then caches new info.
 * 
 * return: (mixed) an (array) of the Steam user info to be cached, or (bool) false on fail.
 */
function mysteam_build_main_cache()
{
	global $mybb, $db, $cache;
	
	// Don't update info for users who haven't visited since the cutoff time, if set.
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
		if (!$mybb->settings['mysteam_status_enable'])
		{
			$data[] = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $user['steamid'];
		}
		if ($mybb->settings['mysteam_other_enable'])
		{
			if ($mybb->settings['mysteam_level'])
			{
				$data[] = 'http://api.steampowered.com/IPlayerService/GetSteamLevel/v1/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $user['steamid'];
			}
			
			if ($mybb->settings['mysteam_recently_played'])
			{
				$data[] = 'http://api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v0001/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $user['steamid'];
			}
		}
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
			'steamlevel'			=> $db->escape_string($decoded->response->steamlevel)
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
 * mysteam_postbit()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate array of Steam info for every user on current show thread page.
 * 
 * @param - $post - (array) user information on the current poster.
 */
function mysteam_postbit(&$post)
{
	global $lang;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	if ($post['steamid'])
	{
		$steam = mysteam_check_cache();
		
		// Don't display anything for user if no status info stored (may happen if Steam returns a bad response for the user)
		if (isset($steam['users'][$post['uid']]['steamstatus']))
		{
			$post = array_merge($post, (array)$steam['users'][$post['uid']]);
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
	global $lang, $memprofile, $templates, $steamname;
	
	if (!$lang->mysteam)
	{
		$lang->load('mysteam');
	}
	
	// Only run if the profile owner has a Steam ID.
	if ($memprofile['steamid'])
	{
		$steam = mysteam_check_cache();
		
		// Don't display anything for user if there's no status info stored for the user (may happen if Steam returns a bad response for the user).
		if (isset($steam['users'][$memprofile['uid']]['steamstatus']))
		{
			$memprofile = array_merge($memprofile, (array)$steam['users'][$memprofile['uid']]);
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
						$db->update_query("users", array('steamid' => $steamid), "uid='" .$uid. "'");
						
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

	eval("\$steamform = \"".$templates->get("mysteam_modcp")."\";");
}

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