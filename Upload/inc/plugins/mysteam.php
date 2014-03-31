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