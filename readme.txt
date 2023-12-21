=== List Locations BMLT ===

Contributors: pjaudiomv, klgrimley, bmltenabled
Plugin URI: https://wordpress.org/plugins/list-locations-bmlt/
Tags: bmlt, basic meeting list toolbox, List Locations, List Locations bmlt, narcotics anonymous, na
Requires PHP: 8.0
Tested up to: 6.4.1
Stable tag: 2.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

List Locations BMLT is a plugin that returns all unique towns or counties from your BMLT server for a given service body on your site.

SHORTCODE
Basic: [list_locations]
Attributes: root_server, services, recursive, state, delimiter, list, state_skip, city_skip

-- Shortcode parameters can be combined

== Usage ==

A minimum of root_server and services attribute are required, which would return all towns for that service body seperated by a comma.

Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]

**Recursive:** to recurse service bodies add recursive=&quot;1&quot;
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; recursive=&quot;1&quot;]

**State:** to remove appending of the state add state=&quot;0&quot;
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;0&quot;]

**State Skip:** to skip the inclusion of a state when using state=&quot;1&quot; add state_skip=&quot;NC&quot;
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;1&quot; state_skip=&quot;NC&quot;]

**City Skip** To skip the inclusion of a city add city_skip=&quot;Indianapolis&quot;. This can be useful when mentioning a city out of order or in a different part of the text.
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;1&quot; city_skip=&quot;Indianapolis&quot;]

**Services:** to add multiple service bodies just seperate by a comma.
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50,37,26&quot;]

**Delimiter:** to change the delimiter to something besides a comma I would add delimiter=&quot; - &quot; or to create newlines between each I could do this delimiter=&quot;&lt;br&gt;&quot;, or delimiter=&quot;&lt;p&gt;&lt;/p&gt;&quot;
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; delimiter=&quot;&lt;br&gt;&quot;]

**List:** You can list by the following town, county, borough, neighborhood. The default is town.
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; list=&quot;town&quot;]

**custom_query** You can add a custom query from semantic api to filter results, for ex by format `&formats=54`.
Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; custom_query=&quot;&formats=54&quot;]

== EXAMPLES ==

<a href="https://www.crna.org/area-service-committees/">https://www.crna.org/area-service-committees/</a>

<a href="https://heartoflongislandna.org" target="_blank">https://heartoflongislandna.org</a>

<a href="https://eanaonline.org" target="_blank">https://eanaonline.org</a>


== MORE INFORMATION ==

<a href="https://github.com/bmlt-enabled/list-locations-bmlt" target="_blank">https://github.com/bmlt-enabled/list-locations-bmlt</a>


== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Area Towns BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [list_locations] shortcode to your Wordpress page/post.
4. At a minimum assign root_server and services attributes.

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 2.3.2 =

* Fixed issue with settings page not fully rendering.

= 2.3.1 =

* Corrected an issue where the settings link was not being displayed on the plugins page.

= 2.3.0 =

* You can now specify a template using magic variables from the BMLT.
* Added option to specify a custom query.
* Refactored codebase.

= 2.2.2 =

* Fix for various PHP warnings.

= 2.2.1 =

* Fix for User-Agent issue that appears to be present on SiteGround hosted root servers.

= 2.2.0 =

* Updated version logic for BMLT 3.0.0 compatibility.

= 2.1.4 =

* Version bump, fix php issue.

= 2.1.3 =

* Fix to better comply with WordPress best practices.

= 2.1.2 =

* Minor bug fixes.

= 2.1.1 =

* Added Support for Skipping a city using city_skip attribute.
* Code cleanup.

= 2.1.0 =

* Added list by borough and neighborhood.

= 2.0.0 =

* Added Settings option page, ability to skip a state when using state shortcode using state_skip.

= 1.1.1 =

* convert quotes to html entity for wordpress readme examples.

= 1.1.0 =

* Add logo.

= 1.0.2 =

* Cleanup readme.

= 1.0.1 =

* Initial Release
