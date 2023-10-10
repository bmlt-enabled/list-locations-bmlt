<h2>Instructions</h2>
<p> Please open a ticket <a href="https://github.com/bmlt-enabled/list-locations-bmlt/issues" target="_top">https://github.com/bmlt-enabled/list-locations-bmlt/issues</a> with problems, questions or comments.</p>
<div id="list_locations_accordion">
    <h3 class="help-accordian"><strong>Basic</strong></h3>
    <div>
        <p>[list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;12&quot;]</p>
        <p>Multiple service bodies can be added seperated by a comma like so services=&quot;12,14,15&quot;</p>
        <strong>Attributes:</strong> root_server, services, recursive, state, state_skip, delimiter, list
        <p><strong>Shortcode parameters can be combined.</strong></p>
    </div>
    <h3 class="help-accordian"><strong>Shortcode Attributes</strong></h3>
    <div>
        <p>The following shortcode attributes may be used.</p>
        <p><strong>root_server</strong></p>
        <p><strong>services</strong></p>
        <p><strong>recursive</strong></p>
        <p><strong>state</strong></p>
        <p><strong>state_skip</strong></p>
        <p><strong>delimiter</strong></p>
        <p><strong>list</strong></p>
        <p><strong>custom_query</strong></p>
        <p>A minimum of root_server and services attribute are required, which would return all towns for that service body seperated by a comma.</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- root_server</strong></h3>
    <div>
        <p><strong>root_server (required)</strong></p>
        <p>The url to your BMLT root server.</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- services</strong></h3>
    <div>
        <p><strong>services (required)</strong></p>
        <p>The Service Body ID of the service body you would like to include, to add multiple service bodies just seperate by a comma.</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50,37,26&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- recursive</strong></h3>
    <div>
        <p><strong>recursive</strong></p>
        <p>To recurse service bodies add recursive=&quot;1&quot;. This can be useful when using a Service Body Parent ID</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; recursive=&quot;1&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- state</strong></h3>
    <div>
        <p><strong>state</strong></p>
        <p>To remove appending of the state add state=&quot;0&quot;</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;0&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- state_skip</strong></h3>
    <div>
        <p><strong>state_skip</strong></p>
        <p>To skip the inclusion of a state when using state=&quot;1&quot; add state_skip=&quot;NC&quot;. This can be useful if you want to include the state for all states but one.</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;1&quot; state_skip=&quot;NC&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- city_skip</strong></h3>
    <div>
        <p><strong>city_skip</strong></p>
        <p>To skip the inclusion of a city add city_skip=&quot;Indianapolis&quot;. This can be useful when mentioning a city out of order or in a different part of the text.</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;1&quot; city_skip=&quot;Indianapolis&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- delimiter</strong></h3>
    <div>
        <p><strong>delimiter</strong></p>
        <p>To change the delimiter to something besides a comma I would add delimiter=&quot; - &quot; or to create newlines between each I could do this delimiter=&quot;&lt;br&gt;&quot;, or delimiter=&quot;&lt;p&gt;&lt;/p&gt;&quot;</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; delimiter=&quot;&lt;br&gt;&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- list</strong></h3>
    <div>
        <p><strong>list</strong></p>
        <p>You can list by the following town, county, borough, neighborhood. The default is town.</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; list=&quot;town&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- custom_query</strong></h3>
    <div>
        <p><strong>custom_query</strong></p>
        <p>You can add a custom query from semantic api to filter results, for ex by format &formats=54 .</p>
        <p>Ex. [list_locations root_server=&quot;https://www.domain.org/main_server&quot; custom_query=&quot;&formats=54"]</p>
    </div>
</div>
