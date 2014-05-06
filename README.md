MySteam Powered
========

<p align="center">
  <img title="MySteam Powered" alt="MySteam Powered" src="http://kerfufflealliance.com/pictures/mysteam/logo_mysteam.jpg" />
</p>

* Version: 1.2.2
* Compatibility: MyBB 1.6.x (last tested on 1.6.12)
* Author: Tanweth
* GitHub: https://github.com/Tanweth/MySteam-Powered
* Release thread: http://community.mybb.com/thread-151564.html
* Website: http://kerfufflealliance.com

Owners of gaming-related forums take heed! Here be a plugin for the MyBB forum software that seamlessly integrates features from Valve's industrious gaming client with your forums. Also integrates with Advanced Sidebox (https://github.com/WildcardSearch/Advanced-Sidebox) if installed.

## Features

* Uses the Steam Web API to display current Steam status (online, offline, what games they're playing, etc.) and contact information for all users who have integrated their Steam accounts.

* A status list (modeled on Steam's built-in friends list) that lists all integrated forum users currently on Steam, including their avatars, their names, and their current statuses. This can either be displayed on the Index or Portal pages, or displayed in a sidebox on any page if you have Advanced Sidebox installed.

* Shows a Steam status icon on the post bit (or status text if preferred) which changes based upon the user's current status. Also reveals the full plain text status (e.g. what game the user is playing) when the mouse hovers over the icon.

* Shows the user's current Steam status and a Steam contact field under Contact Details in the Member Profile page.

* Ensures minimal impact on forum performance by using a speedy cURL-based method for contacting the Steam network and integrating with the MyBB cache to reduce the number of requests needed.

* Includes a new User CP form for users to use to integrate their Steam accounts. Also includes a new section on the Moderator CP Profile Editor to allow moderators to integrate Steam for their users.

* Which users are displayed (and can see the User CP form) can be filtered by allowed usergroups, and by how long it has been since they last visited the forums.

* Advanced Sidebox edition includes support for AJAX automatic updating of the status list without needing a full page refresh.

* Highly customizable. Nearly every feature can be modified to your liking.

## How to Install

* Simply upload the files to your forum's directory, then go to your Admin CP > Configuration > Plugins. Find "MySteam Powered" on the list, and click "Install & Activate." Warnings about current plugin issues that need addressing will be displayed on this page.

* To use with Advanced Sidebox (ASB), you MUST have ASB v2.1 or later. If ASB is installed, the ASB module will be automatically installed into your ASB directory when you install the plugin. 

## How to Upgrade

Simply deactivate the current version on your forum, upload the new version, then reactivate it.

If you are upgrading to v1.1 and you have made any edits to the CSS file (mysteam.css), you must revert the CSS file and add back your custom edits if you want to be able to use the new display on hover setting (see change log below).

## Setting It Up

* Steam Web API Key: Before anything will work, you must acquire (if you haven't already) a Steam Web API Key. A link for obtaining one is listed in Settings.

* Advanced Sidebox Module: As with any sidebox, you must go to Admin CP > Configuration > Advanced Sidebox, and drag the "Steam Status" module to whichever side you want it to display. You can configure it in the resulting popup. Note that many configuration options that impact the sidebox reside in the Settings menu for the plugin under Admin CP > Configuration > Settings > MySteam Powered.

* Built-In Status List: The plugin's built-in status list is disabled automatically if Advanced Sidebox is detected. However, both the built-in status list and the ASB module can be used together (even on the same pages) if you'd prefer to combine them. You can enable it in Settings.

* Built-In Status List vs. Advanced Sidebox Module: The built-in status list is automatically set to appear at the top of your Index and Portal pages (it can be moved, see below under Customization). It can only appear on those pages. The ASB module can be set to appear on any (or every) page, and can be set to automatically refresh without needing a page reload.

## Customization

* You can configure most of the plugin from the Admin CP > Configuration > Settings > MySteam Powered and MySteam Powered Status List (Non-Advanced Sidebox) categories. If you have Advanced Sidebox, you can configure the ASB module from Admin CP > Configuration > Advanced Sidebox and accessing the sidebox's popup configuration menu.

* Templates are generated for every theme you have installed under the "MySteam Powered Templates" category. Your template edits won't be lost on a plugin update. You can also use "Diff Report" to see what changes were made by an update and integrate them into your custom templates.

* You can modify the CSS used by MySteam Powered from the normal stylesheets list under each theme. The file is named mysteam.css.

## Known Issues

* The code for handling when the Steam network is down is largely theoretical. Steam's network is rarely down, and if it is down it is rarely down for very long, so I haven't been able to test it in a "live" downtime scenario. It should work, but it could go a bit wild. :P

* A user is not shown as in-game if he is currently playing a non-Steam game. This is due to how the Steam Web API works, and unfortunately there is no fix unless Valve decides to return this feature (in 2013 the API used to recognize non-Steam games).

## Support

If you notice a bug, you should report it in the Issues sections of the GitHub page: https://github.com/Tanweth/MySteam-Powered

You can also ask for support (bug-related or not) in the release thread: http://community.mybb.com/thread-151564.html

## Changelog

* 1.2.2
	* Added compatibility with ASB v2.1 on the ASB module.
	* Fixed display of backward slashes in names whenever an apostrophe appeared.

* 1.2.1
	* Reintroduced filtering of special characters from Steam usernames and game names, since some characters were preventing the cache from being generated. The filtering is more relaxed than in v1.1 (i.e. it will allow more special characters).
	* Various code optimizations.

* 1.2
	* Status updates for all users are now obtained in a single Steam API call, rather than an API call for each user (experimental).
	* Changed the method for Steam Integration on the User CP and Moderator CP. The Steam Community Data API is no longer used at all, and the form now accepts a directly entered 64-bit Steam ID or vanity URL name.
	* Fixed an undefined function error on post bit and profile pages.
	* Fixed an undefined function error if the plugin is disabled but the ASB module is still in place. Now the ASB module will just disappear or display its stock error instead.

* 1.1.1
	* Special characters will now show up in Steam names on the Steam status list.
	* The non-ASB status list templates are now cached on all pages, rather than just the Index and Portal pages (since the list can now be displayed on nearly any forum page).
	* Split many plugin functions into separate files from main plugin file to improve code readability.

* 1.1
	* Globalized the $mysteamlist variable. Now if you are using the non-ASB status list, you can add the Steam status list to any page of your forums by placing "{$mysteamlist}" in any template (with some exceptions, including the header template). By default it still only displays on Index and Portal, and display on those pages can still be enabled/disabled in settings.
	* Added a setting to allow a user's plain text status to be shown at all times if using the image display style on the post bit (as opposed to the default, where the plain text status is only revealed when you hover your mouse over the Steam icon).
	* Made the Steam status icon in the post bit a hyperlink that directs to the current user's steam profile. 

* 1.0.2
	* Fixed an issue where no plugin hooks would be deployed (causing nothing generated by the plugin to be visible on the forum) (GitHub Issue #1).
	* Fixed (hopefully) an issue where PHP would throw an "argument is not an array" error on the profile and post bit pages (GitHub Issue #6).
	* Set a timeout on the cURL function used for accessing the Steam network. This should mean that it should no longer cause your forum to stop loading (or to take a VERY long time to load) if it is having difficulty contacting Steam.

* 1.0.1 - A small hotfix release. The following issues SHOULD be fixed:
	* Fixed an issue on the Complete List page that would cause PHP to throw errors if there were no users in-game or online (GitHub Issue #2).
	* Fixed an issue that would sometimes cause PHP to throw an "unsupported operand" error on the profile and post bit pages (GitHub Issue #3).
	* Version number is now cached (to make future upgrades easier).
	* Various code optimizations (including removing some leftover code from development builds that I missed before).

* 1.0 - Initial release.

## Special Thanks

* Wildcard - for making Advanced Sidebox (an awesome plugin), and for routinely assisting me with issues.

* technophilly - for making some very useful suggestions during the plugin's development.

* Kiibakun - for being a very helpful tester and writing the Spanish language pack.

* michaelkr1 - for being a very helpful tester and making multiple helpful feature suggestions.
