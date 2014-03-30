<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * Uses the Steam Web API to obtain the current Steam status of forum users (with associated Steam IDs). It also provides User CP and Mod CP forms for obtaining a user's Steam ID.
 */

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

// Load installation functions if in Admin CP.
if(defined("IN_ADMINCP"))
{
   require_once MYBB_ROOT . "inc/plugins/mysteam/install.php";
}
else
{
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

global $mybb;

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
 * mysteam_verify_login()
 * 
 * Calls mysteam_check_cache(), then uses cache output to generate Steam status entry for each user.
 */
function mysteam_verify_login()
{	
	global $mybb;
	
	// Redirect user to Steam Community website for logging in through Steam.
	if ($mybb->input['action'] == 'steam_login')
	{
		mysteam_redirect_to_login();
	}
	
	if ($mybb->input['action'] == 'steam_return')
	{
		require_once MYBB_ROOT.'inc/plugins/mysteam/openid.php';
		require_once MYBB_ROOT.'inc/functions.php';
		require_once MYBB_ROOT.'inc/class_session.php';
		
		// Validate the Steam sign in using the LightOpenID library.
		$bburl = parse_url($mybb->settings['bburl']);
		$openid = new LightOpenID($bburl['host']);
		$openid->validate();
		
		// Parse the URL returned by Steam Community to obtain the user's Steam ID.
		$claimed_id = explode('/', $openid->identity);
		$steamid = end($claimed_id);
		
		
	}
}

/*
 * mysteam_sync_info()
 * 
 * Checks the user's current forum name and avatar against Steam's, and updates it if it is out of sync with Steam.
 *
 * return: (bool) false if user isn't logged in, doesn't have a Steam ID associated, or doesn't have any Steam info stored.
 */
function mysteam_sync_info()
{
	global $mybb, $cache;
	
	// If user isn't logged in or doesn't have a Steam ID associated, do nothing.
	if (!$mybb->user['uid'] || !$mybb->user['steamid'])
	{
		return false;
	}
	
	$steam_cache = $cache->read('mysteam');
	$steam = $steam_cache['users'][$mybb->user['uid']];
	
	// If there's no Steam info stored for user (may happen if Steam returns a bad response for the user), do nothing.
	if (!isset($steam['steamstatus']))
	{
		return false;
	}
	
	$maxavatardims = explode('x', $mybb->settings['maxavatardims'])
	
	// Set which Steam avatar size to use based upon maximum avatar dimensions on forums.
	if ($maxavatardims[0] >= '184' && $maxavatardims[1] >= '184')
	{
		$steam['steamavatar'] == $steam['steamavatar_full'];
	}
	elseif ($maxavatardims[0] >= '64' && $maxavatardims[1] >= '64')
	{
		$steam['steamavatar'] == $steam['steamavatar_medium'];
	}
	
	if ($mybb->settings['mysteam_login_username'] && $mybb->user['steamnamesync'] && $mybb->user['username'] != $steam['steamname'])
	{
		$update_array['username'] = $steam['steamname'];
	}
	if ($mybb->settings['mysteam_login_avatar'] && $mybb->user['steamavatarsync'] && $mybb->user['avatar'] != $steam['steamavatar'])
	{
		$update_array['avatar'] = $steam['steamavatar'];
	}
	
	if ($update_array)
	{
		$db->update_query("users", $update_array, "uid='" .$mybb->user['uid']. "'");
	}
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
?>