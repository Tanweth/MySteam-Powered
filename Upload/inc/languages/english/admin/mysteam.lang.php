<?php
/* Plugin Name: MySteam Powered
 * License: MIT (http://opensource.org/licenses/MIT)
 * Copyright © 2014 Aryndel Lamb-Marsh (aka Tanweth)
 *
 * ENGLISH LANGUAGE FILE
 * Provides English language text for use with the above plugin.
 */

// Title and description for the plugin
$l['mysteam_title'] = "MySteam Powered";
$l['mysteam_desc'] = "Uses the Steam Web API to obtain the current Steam status of forum users (with associated Steam IDs). It also provides User CP and Mod CP forms for obtaining a user's Steam ID.";

// Plugins page messages
$l['mysteam_settings'] = "Settings";
$l['mysteam_profile_editor'] = "Profile Editor";
$l['mysteam_asb_success'] = "Advanced Sidebox detected. The ASB module was successfully integrated.";
$l['mysteam_apikey_needed'] = "No Steam Web API Key has been provided. Please go to Settings and enter an API key.";
$l['mysteam_steamids_needed'] = "No users currently have Steam IDs associated. Please encourage your users to use the new User CP form for integration, or you can use the Moderator CP to do it on their behalf.";
$l['mysteam_asb_upgrade'] = "Your Advanced Sidebox installation does not meet the minimum requirements. Please upgrade to Advanced Sidebox v2.1 or later.";
 
// Title and description for the ASB module
$l['asb_mysteam_title'] = "Steam Status";
$l['asb_mysteam_desc'] = "REQUIRES MySteam Powered MYBB plugin! Uses the Steam Web API to display current Steam statuses of users.";
$l['mysteam_plugin_needed'] = "The MySteam Powered plugin is not activated. Please activate it before continuing.";

// Settings groups and template group
$l['mysteam_main_group_desc'] = "Configure general settings for the MySteam Powered plugin and its Advanced Sidebox module (if in use).";
$l['mysteam_list_group_title'] = "MySteam Powered Status List (Non-Advanced Sidebox)";
$l['mysteam_list_group_desc'] = "Configure the built-in (non-Advanced Sidebox) Steam status list.";
$l['mysteam_template_group'] = "MySteam Powered";

// Main settings
$l['mysteam_list_enable_title'] = "Enable non-Advanced Sidebox Status List?";
$l['mysteam_list_enable_desc'] = "If yes, a status list like the one in the Advanced Sidebox module will be displayed on the Index and/or Portal pages. It can be used with the ASB module, though it will repeat functionality if used on the same page.";
$l['mysteam_list_settings'] = "List Settings";
$l['mysteam_apikey_title'] = "Steam Web API Key";
$l['mysteam_apikey_desc'] = "Enter the Steam Web API key for your website (obtainable <a href=\"http://steamcommunity.com/dev/apikey\">here</a>).";
$l['mysteam_limitbygroup_title'] = "Limit Usergroups Displayed?";
$l['mysteam_limitbygroup_desc'] = "Enter the gid of each group you want displayed, separated by commas. The gid can be found in the manage URL for the group in the Admin CP (e.g. /index.php?module=user-groups&action=edit&gid=<strong>123</strong>). Changes take effect on the next cache refresh.";
$l['mysteam_cache_title'] = "Cache Lifespan";
$l['mysteam_cache_desc'] = "Specify how long (in minutes) the cache should be used before refreshing it. Reducing this increases the recency of the Steam info at the cost of increased server load. 0 disables the cache.";
$l['mysteam_displayname_title'] = "Displayed Name";
$l['mysteam_displayname_desc'] = "Choose which name to display for users. Both displays the forum name beside the Steam name, but only if the names are not comparable.";
$l['mysteam_displayname_steam'] = "Display Steam profile name";
$l['mysteam_displayname_forum'] = "Display forum username";
$l['mysteam_displayname_both'] = "Display both Steam profile name and forum username";
$l['mysteam_profile_title'] = "Display on Profile?";
$l['mysteam_profile_desc'] = "If yes, the current Steam status of the user and a Steam contact field will appear on the profile page.";
$l['mysteam_postbit_title'] = "Display on Post Bit?";
$l['mysteam_postbit_desc'] = "If yes, the current Steam status of the poster will be displayed in the post bit.";
$l['mysteam_postbit_img'] = "Yes. Display the status as an image.";
$l['mysteam_postbit_text'] = "Yes. Display the status as text.";
$l['mysteam_postbit_no'] = "No";
$l['mysteam_hover_title'] = "Display Status on Hover?";
$l['mysteam_hover_desc'] = "If yes, the current plain text status of the poster will be displayed on mouse hover over the status image. If no, the text status will be displayed at all times. Only applies if the post bit status display style is set to image (see above).";
$l['mysteam_prune_title'] = "Prune Inactive Users from List";
$l['mysteam_prune_desc'] = "Specify after how many days since the last visit that a user should no longer appear on the list. 0 disables pruning. Changes take effect on the next cache refresh.";
$l['mysteam_usercp_title'] = "Enable User CP Form?";
$l['mysteam_usercp_desc'] = "If yes, users will be able to use a User CP form to add their own Steam profile info (if they are in an allowed usergroup).";
$l['mysteam_modcp_title'] = "Enable Mod CP Form?";
$l['mysteam_modcp_desc'] = "If yes, moderators will be able to use a Moderator CP form to add the Steam profile info of other users.";

// Settings for both ASB and non-ASB status lists
$l['mysteam_list_width_title'] = "Width of Each Status Entry";
$l['mysteam_list_width_desc'] = "Set the width (in pixels) of each entry in the status list. This setting also controls how many rows and columns there are (lower widths result in more columns).";
$l['mysteam_list_number_title'] = "Maximum Number of Users to Display";
$l['mysteam_list_number_desc'] = "Set the maximum number of users you want to be displayed. 0 disables this, so all online users will be listed. Changes take effect on the next cache refresh.";

// Settings for ASB module only
$l['mysteam_settings_where_title'] = "Where Are All The Settings?";
$l['mysteam_settings_where_desc'] = "Most settings that configure this sidebox reside in the main MyBB Settings menu. Don't forget to go there and configure as needed (the sidebox won't work otherwise!). <strong>NB: any warnings below are only valid as of when this sidebox was added!</strong>";
$l['mysteam_doesnt_do_anything'] = 'This does not do anything, honest!';
$l['mysteam_list_cols_title'] = "Number of columns";
$l['mysteam_list_cols_desc'] = "If you wish the sidebox to have multiple columns, enter the number here.";

// Settings for non-ASB status list only
$l['mysteam_index_title'] = "Display Status List on Index?";
$l['mysteam_index_desc'] = "If enabled, a Steam status list will be displayed on the Index page.";
$l['mysteam_portal_title'] = "Display Status List on Portal?";
$l['mysteam_portal_desc'] = "If enabled, a Steam status list will be displayed on the Portal page.";

// Steam status list
$l['mysteam_in_game'] = "In-Game";
$l['mysteam_offline'] = "Offline";
$l['mysteam_online'] = "Online";
$l['mysteam_busy'] = "Busy";
$l['mysteam_away'] = "Away";
$l['mysteam_snooze'] = "Snooze";
$l['mysteam_looking_to_trade'] = "Looking to Trade";
$l['mysteam_looking_to_play'] = "Looking to Play";
$l['mysteam_none_found'] = "Could not connect to the Steam network. This could be due to a problem with the Steam network, a problem with the forum's configuration, or because no users currently have Steam IDs integrated. A new connection will be attempted every 3 minutes (or on every page load if caching is disabled).";
$l['mysteam_complete_list'] = "Complete List";

// Member profile page
$l['mysteam_status'] = "Steam:";
$l['mysteam_name'] = "Steam Name: ";

// Steam ID form
$l['mysteam_integration'] = "Steam Integration";
$l['mysteam_url'] = "Steam Profile URL:";
$l['mysteam_current'] = "Current Steam ID:";
$l['mysteam_integrate'] = "Integrate Steam";
$l['mysteam_search'] = "Steam profile search using forum username";
$l['mysteam_search_manual'] = "Manual search";
$l['mysteam_decouple'] = "Decouple Steam";
$l['mysteam_decouple_body'] = "Decouple Steam:";

// Steam ID form (User CP)
$l['mysteam_usercp_intro'] = "This forum includes the ability to display your current Steam status (whether online/offline, and if you're in a game), and display a Steam contact field for you on your profile.";
$l['mysteam_usercp_instruct'] = "To activate this feature, enter the URL to your Steam profile, the name on your Steam vanity URL if set (steamcommunity.com/id/[name]), or your 64-bit Steam ID in the box below, then submit. You can use the links below to search for your Steam profile.";
$l['mysteam_usercp_note'] = "NB: For your status to be displayed, your Steam profile must NOT be set to private!";
$l['mysteam_usercp_decouple'] = "If you wish to decouple your Steam ID from your MyBB account, hit the appropriate button below.";

// Steam ID form (Moderator CP)
$l['mysteam_modcp_intro'] = "This forum includes the ability to display members' current Steam status (whether online/offline, and if in a game), and display a Steam contact field on their profiles.";
$l['mysteam_modcp_instruct'] = "To activate this feature for this member, enter the URL to the member's Steam profile, the name on the member's Steam vanity url if set (steamcommunity.com/id/[name]), or the member's 64-bit Steam ID in the box below, then submit. You can use the links below to search for the member's Steam profile.";
$l['mysteam_modcp_note'] = "NB: For the status to be displayed, the member's Steam profile must NOT be set to private!";
$l['mysteam_modcp_back'] = "Back to Moderator CP";
$l['mysteam_modcp_decouple'] = "If you wish to decouple the Steam ID from this user's MyBB account, hit this button:";

// Steam ID form (submit)
$l['mysteam_submit_invalid'] = "The URL or ID you entered did not return a valid response. It may be incorrect, or Steam Community may currently be unavailable.";
$l['mysteam_submit_same'] = "The Steam ID associated with the profile URL you entered is identical to the Steam ID currently associated with the following user: ";
$l['mysteam_steamid'] = "Steam ID: ";
$l['mysteam_vanityurl'] = "Vanity URL Name: ";

// Steam ID form (User CP submit)
$l['mysteam_submit_success'] = "Success! Your Steam ID has been integrated. The following information is associated with the profile you submitted:";
$l['mysteam_decouple_success'] = "Your Steam ID has successfully been decoupled from your account.";

// Steam ID form (Mod CP submit)
$l['mysteam_submit_success_modcp'] = "Success! The user's Steam ID has been integrated. The following information is associated with the profile you submitted:";
$l['mysteam_decouple_success_modcp'] = "The Steam ID has successfully been decoupled from the user's account.";
?>
