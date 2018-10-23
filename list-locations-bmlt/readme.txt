=== List Locations BMLT ===

Contributors: pjaudiomv
Plugin URI: https://wordpress.org/plugins/list-locations-bmlt/
Tags: bmlt, basic meeting list toolbox, List Locations, List Locations bmlt, narcotics anonymous, na
Requires at least: 4.0
Requires PHP: 5.6
Tested up to: 4.9.8
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

List Locations BMLT is a plugin that returns all unique towns or counties from your BMLT server for a given service body on your site.

SHORTCODE
Basic: [list_locations]
Attributes: root_server, services, recursive, state, delimiter, list

-- Shortcode parameters can be combined

== Usage ==

A minimum of root_server and services attribute are required, which would return all towns for that service body seperated by a comma.

Ex. [list_locations root_server="https://www.domain.org/main_server" services="50"]

**Recursive:** to recurse service bodies add recursive="1"
Ex. [list_locations root_server="https://www.domain.org/main_server" services="50" recursive="1"]

**State:** to remove appending of the state add state="0"
Ex. [list_locations root_server="https://www.domain.org/main_server" services="50" state="0"]

**Services:** to add multiple service bodies just seperate by a comma.
Ex. [list_locations root_server="https://www.domain.org/main_server" services="50,37,26"]

**Delimiter:** to change the delimiter to something besides a comma I would add delimiter=" - " or to create newlines between each I could do this delimiter="&lt;br&gt;", or delimiter="&lt;p&gt;&lt;/p&gt;"
Ex. [list_locations root_server="https://www.domain.org/main_server" delimiter="&lt;br&gt;"]

**List:** If I wanted to view counties instead of towns I would add list="county" the default is town.
Ex. [list_locations root_server="https://www.domain.org/main_server" list="town"]

== EXAMPLES ==

<a href="https://www.crna.org/area-service-committees/">https://www.crna.org/area-service-committees/</a>


== MORE INFORMATION ==

<a href="https://github.com/pjaudiomv/list-locations-bmlt" target="_blank">https://github.com/pjaudiomv/list-locations-bmlt</a>

== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Area Towns BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [list_locations] shortcode to your Wordpress page/post.
4. At a minimum assign root_server and services attributes.


== Changelog ==

= 1.0.1 =

* Initial Release