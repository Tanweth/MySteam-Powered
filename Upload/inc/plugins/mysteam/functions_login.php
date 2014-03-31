<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * STEAM LOGIN FUNCTIONS
 * This file is used by the above plugin to handle functions related to the Steam Login module.
 */

// Disallow direct access to this file for security reasons
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

/*
 * mysteam_redirect_to_login()
 * 
 * Redirects user signing in through Steam to Steam Community website for authentication.
 */
function mysteam_redirect_to_login()
{
	global $mybb;
	
	if (!$mybb->user['uid'])
	{
		// Pull in the LightOpenID library to allow OpenID authentication.
		require_once MYBB_ROOT.'inc/plugins/mysteam/openid.php';
		
		// Obtain the forums' domain name, then set parameters for authentication
		$bburl = parse_url($mybb->settings['bburl']);
		$openid = new LightOpenID($bburl['host']);
		$openid->returnUrl = $mybb->settings['bburl'].'/member.php?action=steam-return';
		$openid->identity = 'http://steamcommunity.com/openid';
		
		// Redirect to Steam Community website for authentication
		redirect($openid->authUrl(), $lang->mysteam_login_redirect)
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
?>