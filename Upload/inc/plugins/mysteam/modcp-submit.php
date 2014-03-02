<?php
/* Plugin Name: MySteam Powered
 * Author: Tanweth
 * http://www.kerfufflealliance.com
 * 
 * Moderator CP Form Submit Processing
 * This script is used by the above plugin to process the Steam profile URL provided in the Steam ID form in the Moderator CP, and write the user's Steam ID to the database.
 */
 
$templatelist = 'mysteam_submit';

define('IN_MYBB', 1); require "../../../global.php";

// Ensure user is able to use the Moderator CP and there is a UID input (protection against direct execution by unauthorized users)
if ($mybb->usergroup['canmodcp'] && $mybb->input['uid'])
{
	$lang->load('modcp');
	$lang->load('mysteam');
	$uid = $db->escape_string($mybb->input['uid']);
	
	// If user has attempted to submit a Steam profile.
	if ($mybb->input['submit'])
	{
		// Add http:// to beginning if user was too lazy to do so.
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
					'<p><strong>' .$lang->mysteam_submit_success_modcp. '</strong></p>
					<p><strong>' .$lang->mysteam_steamname. '</strong>' .$xml->steamID. '</p>
					<p><strong>' .$lang->mysteam_steamid. '</strong>' .$xml->steamID64. '</p>';
			}
		}
	}
	// If user has requested to decouple.
	elseif ($mybb->input['decouple'])
	{
		$db->update_query("users", array('steamid' => ''), "uid='".$uid."'");
		$submit_message = $lang->mysteam_decouple_success_modcp;
	}

	add_breadcrumb($lang->nav_modcp, 'modcp.php');
	add_breadcrumb($lang->mysteam_integration, "modcp-submit.php"); 

	eval("\$html = \"".$templates->get("mysteam_submit")."\";"); 

	output_page($html);
}
else
{
	error_no_permission();
}
?>
