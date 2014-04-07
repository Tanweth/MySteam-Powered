<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * MODERATOR CP FORM SUBMISSION PROCESSING
 * This script is used by the above plugin to process the Steam profile URL provided in the Steam ID form in the Moderator CP, and write the user's Steam ID to the database.
 */
 
$templatelist = 'mysteam_submit';

define('IN_MYBB', 1);
require_once '../../../global.php';

// Ensure user is able to use the Moderator CP and there is a UID input (protection against direct execution by unauthorized users)
if (!$mybb->usergroup['canmodcp'] || !$mybb->input['uid'])
{
	error_no_permission();
}

$lang->load('modcp');
$lang->load('mysteam');

$uid = $db->escape_string($mybb->input['uid']);

// If user has attempted to submit a Steam profile . . .
if ($mybb->input['submit'])
{	
	// If user directly entered a Steam ID . . .
	if (is_numeric($mybb->input['steamprofile']) && (strlen($mybb->input['steamprofile']) === 17))
	{
		$steamid = $db->escape_string($mybb->input['steamprofile']);
		
		// Ensure the Steam ID is valid.
		$data = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $steamid;
		$response = multiRequest($data);
		
		if (!strpos($response[0], 'steamid'))
		{
			unset($steamid);
		}
		else
		{
			$decoded = json_decode($response[0]);
			$steamname = $decoded->response->players[0]->personaname;
		}
	}
	// If user directly entered a vanity URL name . . .
	elseif (!strpos($mybb->input['steamprofile'], '/'))
	{
		$vanity_url = $db->escape_string($mybb->input['steamprofile']);
	
		$data = 'http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=' .$mybb->settings['mysteam_apikey']. '&vanityurl=' . $vanity_url;
		$response = multiRequest($data);
		$decoded = json_decode($response[0]);
		
		if ($decoded->response->success == 1)
		{
			$steamid = $db->escape_string($decoded->response->steamid);
		}
	}
	// If user entered a non-vanity profile URL . . .
	elseif (strpos($mybb->input['steamprofile'], '/profiles/'))
	{	
		$trimmed_url = rtrim($mybb->input['steamprofile'], '/');
		$parsed_url = explode('/', $trimmed_url);
		$steamid = end($parsed_url);
		
		$data = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' .$mybb->settings['mysteam_apikey']. '&steamids=' . $steamid;
		$response = multiRequest($data);
		
		if (!strpos($response[0], 'steamid'))
		{
			unset($steamid);
		}
		else
		{
			$decoded = json_decode($response[0]);
			$steamname = $decoded->response->players[0]->personaname;
		}
	}
	// If user entered a vanity profile URL . . .
	elseif (strpos($mybb->input['steamprofile'], '/id/'))
	{		
		$trimmed_url = rtrim($mybb->input['steamprofile'], '/');
		$parsed_url = explode('/', $trimmed_url);
		$vanity_url = end($parsed_url);
		
		$data = 'http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=' .$mybb->settings['mysteam_apikey']. '&vanityurl=' . $vanity_url;
		$response = multiRequest($data);
		$decoded = json_decode($response[0]);
		
		if ($decoded->response->success == 1)
		{
			$steamid = $db->escape_string($decoded->response->steamid);
		}
	}
	
	// If we have a valid Steam ID . . .
	if ($steamid)
	{
		$query = $db->simple_select("users", "username", "steamid='".$steamid."'");
		$username_same = $db->fetch_field($query, 'username');
		
		// Don't run if Steam ID matches another user's current ID, and display error.
		if ($db->num_rows($query))
		{
			$submit_message = '
				<p><em>' .$lang->please_correct_errors. '</em></p>
				<p>' .$lang->mysteam_submit_same. $username_same. '</p>';
		}
		// Otherwise, write to the database and display success!
		else
		{
			$db->update_query("users", array('steamid' => $steamid), "uid='".$uid."'");
			
			if ($vanity_url)
			{
				$success_third_line = '<br />
				<strong>' .$lang->mysteam_vanityurl. '</strong>' .$vanity_url. '</p>';
			}
			else
			{
				$success_third_line = '<br />
				<strong>' .$lang->mysteam_name. '</strong>' .$steamname. '</p>';
			}
			
			$submit_message = 
				'<p><strong>' .$lang->mysteam_submit_success_modcp. '</strong></p>
				<p><strong>' .$lang->mysteam_steamid. '</strong>' .$steamid.
				$success_third_line;
		}
	}
	// If we don't have a valid Steam ID, display an error.
	else
	{
		$submit_message = 
			'<p><em>' .$lang->please_correct_errors. '</em></p>
			<p>' .$lang->mysteam_submit_invalid. '</p>';
	}
}
// If user has requested to decouple . . .
elseif ($mybb->input['decouple'])
{
	$db->update_query("users", array('steamid' => ''), "uid='".$uid."'");
	$submit_message = $lang->mysteam_decouple_success_modcp;
}

add_breadcrumb($lang->nav_modcp, 'modcp.php');
add_breadcrumb($lang->mysteam_integration, "modcp-submit.php"); 

eval("\$html = \"".$templates->get("mysteam_submit")."\";"); 

output_page($html);
?>
