MySteam Powered
========

* Version: 1.0
* Compatibility: MyBB 1.6.x (last tested on 1.6.12)
* Author: Tanweth
* GitHub: https://github.com/Tanweth/MySteam-Powered
* Release thread: 
* Website: http://kerfufflealliance.com

Owners of gaming-related forums take heed! Here be a plugin for the MyBB forum software that seamlessly integrates features from Valve's industrious gaming client with your forums. Also integrates with Advanced Sidebox (https://github.com/WildcardSearch/Advanced-Sidebox) if installed.

Features

* Uses the Steam Web API to display current Steam status (online, offline, what games they're playing, etc.) and contact information for all users who have integrated their Steam accounts.

* A status list (modeled on Steam's built-in friends list) that lists all integrated forum users currently on Steam, including their avatars, their names, and their current statuses. This can either be displayed on the Index or Portal pages, or displayed in a sidebox on any page if you have Advanced Sidebox installed.

* Shows a Steam status icon on the post bit (or status text if preferred) which changes based upon the user's current status. Also reveals the full plain text status (e.g. what game the user is playing) when the mouse hovers over the icon.

* Shows the user's current Steam status and a Steam contact field under Contact Details in the Member Profile page.

* Ensures minimal impact on forum performance by using a speedy cURL-based method for contacting the Steam network and integrating with the MyBB cache to reduce the number of requests needed.

* Includes a new User CP form for users to use to integrate their Steam accounts. Also includes a new section on the Moderator CP Profile Editor to allow moderators to integrate Steam for their users.

* Which users are displayed (and can see the User CP form) can be filtered by allowed usergroups, and by how long it has been since they last visited the forums.

* Advanced Sidebox edition includes support for AJAX automatic updating of the status list without needing a full page refresh.

* Highly customizable. Nearly every feature can be modified to your liking.

How to Install

* Simply upload the files to your forum's directory, then go to your Admin CP > Configuration > Plugins. Find "MySteam Powered" on the list, and click "Install & Activate." Warnings about current plugin issues that need addressing will be displayed on this page.

* To use with Advanced Sidebox (ASB), you MUST have ASB v2.0.5 or later. If ASB is installed, the ASB module will be automatically installed into your ASB directory when you install the plugin. 

Setting It Up

* Steam Web API Key: Before anything will work, you must acquire (if you haven't already) a Steam Web API Key. A link for obtaining one is listed in Settings.

* Advanced Sidebox Module: As with any sidebox, you must go to Admin CP > Configuration > Advanced Sidebox, and drag the "Steam Status" module to whichever side you want it to display. You can configure it in the resulting popup. Note that many configuration options that impact the sidebox reside in the Settings menu for the plugin under Admin CP > Configuration > Settings > MySteam Powered.

* Built-In Status List: The plugin's built-in status list is disabled automatically if Advanced Sidebox is detected. However, both the built-in status list and the ASB module can be used together (even on the same pages) if you'd prefer to combine them. You can enable it in Settings.

* Built-In Status List vs. Advanced Sidebox Module: The built-in status list is automatically set to appear at the top of your Index and Portal pages (it can be moved, see below under Customization). It can only appear on those pages. The ASB module can be set to appear on any (or every) page, and can be set to automatically refresh without needing a page reload.

Customization

* You can configure most of the plugin from the Admin CP > Configuration > Settings > MySteam Powered and MySteam Powered Status List (Non-Advanced Sidebox) categories. If you have Advanced Sidebox, you can configure the ASB module from Admin CP > Configuration > Advanced Sidebox and accessing the sidebox's popup configuration menu.

* Templates are generated for every theme you have installed under the "MySteam Powered Templates" category. Your template edits won't be lost on a plugin update. You can also use "Diff Report" to see what changes were made by an update and integrate them into your custom templates.

* You can modify the CSS used by MySteam Powered from the normal stylesheets list under each theme. The file is named mysteam.css.

Known Issues

* The code for handling when the Steam network is down is largely theoretical. Steam's network is rarely down, and if it is down it is rarely down for very long, so I haven't been able to test it in a "live" downtime scenario. It should work, but it could go a bit wild. :P

Support

If you notice a bug, you should report it in the Issues sections of the GitHub page: https://github.com/Tanweth/MySteam-Powered

You can also ask for support (bug-related or not) in the release thread: 

Changelog

* 1.0 - Initial release.

Special Thanks

* Wildcard - for making Advanced Sidebox (an awesome plugin), and for routinely assisting me with issues.

* technophilly - for making some very useful suggestions during the plugin's development.