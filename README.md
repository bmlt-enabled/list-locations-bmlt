# Description

List Locations BMLT is a plugin that returns all unique towns or counties from your BMLT server for a given service body on your site.

# SHORTCODE
**Basic:** `[list_locations root_server="https://www.domain.org/main_server" services="12"]`

**Attributes:** root_server, services, recursive, state, delimiter, list, state_skip, city_skip

-- Shortcode parameters can be combined.

# Usage

A minimum of root_server and services attribute are required, which would return all towns for that service body seperated by a comma.

`Ex. [list_locations root_server="https://www.domain.org/main_server" services="50"]`

**Recursive:** to recurse service bodies add `recursive="1"`

`Ex. [list_locations root_server="https://www.domain.org/main_server" services="50" recursive="1"]`

**State:** to remove appending of the state add `state="0"`

`Ex. [list_locations root_server="https://www.domain.org/main_server" services="50" state="0"]`

**State Skip:** to skip the inclusion of a state when using `state="1"` add `state_skip="NC"`

`Ex. [list_locations root_server="https://www.domain.org/main_server" services="50" state="1" state_skip="NC"]`

**City Skip:** to skip the inclusion of a city add `city_skip="Indianapolis"` This can be useful when mentioning a city out of order or in a different part of the text.

`Ex. [list_locations root_server="https://www.domain.org/main_server" services="50" state="1" city_skip="Indianapolis"]`

**Services:** to add multiple service bodies just seperate by a comma.

`Ex. [list_locations root_server="https://www.domain.org/main_server" services="50,37,26"]`

**Delimiter:** to change the delimiter to something besides a comma I would add `delimiter=" - "` or to create newlines between each I could do this `delimiter="&lt;br&gt;", or delimiter="&lt;p&gt;&lt;/p&gt;"

Ex. [list_locations root_server="https://www.domain.org/main_server" delimiter="&lt;br&gt;"]

**List:** You can list by the following town, county, borough, neighborhood. The default is town..

`Ex. [list_locations root_server="https://www.domain.org/main_server" list="town"]`

# EXAMPLES

<a href="https://www.crna.org/area-service-committees/">https://www.crna.org/area-service-committees/</a>

<a href="https://heartoflongislandna.org" target="_blank">https://heartoflongislandna.org</a>

<a href="https://eanaonline.org" target="_blank">https://eanaonline.org</a>

# MORE INFORMATION

<a href="https://github.com/pjaudiomv/list-locations-bmlt" target="_blank">https://github.com/pjaudiomv/list-locations-bmlt</a>

# Installation

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Area Towns BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [list_locations] shortcode to your Wordpress page/post.
4. At a minimum assign root_server and services attributes.

# Changelog

= 2.1.1 =

* Added Support for Skipping a city using city_skip attribute.
* Code cleanup.

= 2.1.0 =

* Added list by borough and neighborhood.

= 1.1.1 =

* convert quotes to html entity for wordpress readme examples.

= 1.1.0 =

* Add logo.

= 1.0.2 =

* Cleanup readme.

= 1.0.1 =

* Initial Release
