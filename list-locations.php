<?php
/*
Plugin Name: List Locations BMLT
Plugin URI: https://wordpress.org/plugins/list-locations-bmlt/
Author: BMLT Authors
Description: This plugin returns all unique towns or counties for given service body on your site Simply add [list_locations] shortcode to your page and set shortcode attributes accordingly. Required attributes are root_server and services.
Version: 2.1.4
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // die('Sorry, but you cannot access this page directly.');
}

if (!class_exists("ListLocations")) {
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
    class ListLocations
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
    {
        public $optionsName = 'list_locations_options';
        public $options = array();
        const HTTP_RETRIEVE_ARGS = array(
            'headers' => array(
                'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +ListLocationsBMLT'
            ),
            'timeout' => 60
        );
        public function __construct()
        {
            $this->getOptions();
            if (is_admin()) {
                // Back end
                add_action("admin_notices", array(&$this, "isRootServerMissing"));
                add_action("admin_enqueue_scripts", array(&$this, "enqueueBackendFiles"), 500);
                add_action("admin_menu", array(&$this, "adminMenuLink"));
            } else {
                // Front end
                add_shortcode('list_locations', array(
                    &$this,
                    "list_locations"
                ));
            }
            // Content filter
            add_filter('the_content', array(
                &$this,
                'filterContent'
            ), 0);
        }

        public function isRootServerMissing()
        {
            $root_server = $this->options['root_server'];
            if ($root_server == '') {
                echo '<div id="message" class="error"><p>Missing BMLT Root Server in settings for List Locations BMLT.</p>';
                $url = admin_url('options-general.php?page=list-locations.php');
                echo "<p><a href='$url'>List Locations BMLT Settings</a></p>";
                echo '</div>';
            }
            add_action("admin_notices", array(
                &$this,
                "clearAdminMessage"
            ));
        }

        public function clearAdminMessage()
        {
            remove_action("admin_notices", array(
                &$this,
                "isRootServerMissing"
            ));
        }
        // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        public function ListLocations()
        {
        // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            $this->__construct();
        }

        public function filterContent($content)
        {
            return $content;
        }

        /**
        * @param $hook
        */
        public function enqueueBackendFiles($hook)
        {
            if ($hook == 'settings_page_list-locations') {
                wp_enqueue_style('list-locations-admin-ui-css', plugins_url('css/start/jquery-ui.css', __FILE__), false, '1.11.4', false);
                wp_enqueue_style("chosen", plugin_dir_url(__FILE__) . "css/chosen.min.css", false, "1.2", 'all');
                wp_enqueue_style("list-locations-css", plugin_dir_url(__FILE__) . "css/list_locations.css", false);
                wp_enqueue_script("chosen", plugin_dir_url(__FILE__) . "js/chosen.jquery.min.js", array('jquery'), "1.2", true);
                wp_enqueue_script('list-locations-admin', plugins_url('js/list_locations_admin.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . "js/list_locations_admin.js"), false);
                wp_enqueue_script('common');
                wp_enqueue_script('jquery-ui-accordion');
            }
        }

        public function testRootServer($root_server)
        {
            $args = array(
                'timeout' => '10',
                'headers' => array(
                    'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +ListLocationsBMLT'
                )
            );
            $results = wp_remote_get("$root_server/client_interface/serverInfo.xml", $args);
            $httpcode = wp_remote_retrieve_response_code($results);
            $response_message = wp_remote_retrieve_response_message($results);
            if ($httpcode != 200 && $httpcode != 302 && $httpcode != 304 && ! empty($response_message)) {
                //echo '<p>Problem Connecting to BMLT Root Server: ' . $root_server . '</p>';
                return false;
            };
            $results = simplexml_load_string(wp_remote_retrieve_body($results));
            $results = json_encode($results);
            $results = json_decode($results, true);
            return $results['serverVersion']['readableString'];
        }

        // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        public function list_locations($atts, $content = null)
        {
        // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            global $unique_areas;
            $args = shortcode_atts(
                array(
                    "root_server" => '',
                    'services'    =>  '',
                    'recursive'   => '',
                    'state'       => '',
                    'delimiter'   => '',
                    'list'        => '',
                    'state_skip'  => '',
                    'city_skip'   => ''
                ),
                $atts
            );

            $area_data_dropdown   = explode(',', $this->options['service_body_dropdown']);
            $services_dropdown    = $area_data_dropdown[1];

            $root_server          = ($args['root_server'] != '' ? $args['root_server'] : $this->options['root_server']);
            $services             = ($args['services']    != '' ? $args['services']    : $services_dropdown);
            $recursive            = ($args['recursive']   != '' ? $args['recursive']   : $this->options['recursive']);
            $state                = ($args['state']       != '' ? $args['state']       : $this->options['state_checkbox']);
            $delimiter            = ($args['delimiter']   != '' ? $args['delimiter']   : $this->options['delimiter_textbox']);
            $list                 = ($args['list']        != '' ? $args['list']        : $this->options['list_select']);
            $state_skip           = ($args['state_skip']  != '' ? $args['state_skip']  : $this->options['state_skip_dropdown']);
            $city_skip            = ($args['city_skip']   != '' ? $args['city_skip']   : $this->options['city_skip_dropdown']);

            if ($delimiter == '' && $this->options['delimiter_textbox'] == '') {
                $delimiter = ', ';
            }

            if ($root_server == '') {
                return '<p><strong>List Locations Error: Root Server missing. Please Verify you have entered a Root Server using the \'root_server\' shortcode attribute</strong></p>';
            }
            if ($services == '') {
                return '<p><strong>List Locations Error: Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute</strong></p>';
            }

            $listResults = json_decode($this->getListResults($root_server, $services, $recursive), true);
            $unique_city = array();

            foreach ($listResults as $value) {
                if (strtoupper($value['location_province']) == strtoupper($state_skip)) {
                    $location_state = '';
                } else {
                    $location_state = ' ' . strtoupper($value['location_province']);
                }
                if (strtoupper($value['location_municipality']) == strtoupper($city_skip)) {
                    $value['location_municipality'] = '';
                }/*else{
                        $location_municipality = ' ' . strtoupper($value['location_municipality']);
                    }*/
                if ($list == 'town') {
                    if ($value['location_municipality'] != '') {
                        $finalResult = $state == "1" ? str_replace(',', '', trim(ucwords($value['location_municipality']))) . str_replace('.', '', $location_state) : str_replace(',', '', trim(ucwords($value['location_municipality'])));
                        array_push($unique_city, $finalResult);
                    }
                } else if ($list == 'county') {
                    if ($value['location_sub_province'] != '') {
                        $finalResult = $state == "1" ? str_replace(' County', '', trim(ucwords($value['location_sub_province']))) . str_replace('.', '', $location_state) : str_replace(' County', '', trim(ucwords($value['location_sub_province'])));
                        array_push($unique_city, $finalResult);
                    }
                } else if ($list == 'borough') {
                    if ($value['location_city_subsection'] != '') {
                        $finalResult = $state == "1" ? str_replace(',', '', trim(ucwords($value['location_city_subsection']))) . str_replace('.', '', $location_state) : str_replace(',', '', trim(ucwords($value['location_city_subsection'])));
                        array_push($unique_city, $finalResult);
                    }
                } else if ($list == 'neighborhood') {
                    if ($value['location_neighborhood'] != '') {
                        $finalResult = $state == "1" ? str_replace(',', '', trim(ucwords($value['location_neighborhood']))) . str_replace('.', '', $location_state) : str_replace(',', '', trim(ucwords($value['location_neighborhood'])));
                        array_push($unique_city, $finalResult);
                    }
                } else {
                    return '<p><strong>List Locations Error: List attribute incorrect. Please Verify you have entered either town or county.</strong></p>';
                }
            }
            $unique_city = array_unique($unique_city);
            asort($unique_city);
            $content .= implode(html_entity_decode($delimiter), $unique_city);
            return $content;
        }

        /**
         * @desc Adds the options sub-panel
         * @param $root_server
         * @return array|int
         */
        public function getAreas($root_server)
        {
                $results = wp_remote_get("$root_server/client_interface/json/?switcher=GetServiceBodies", ListLocations::HTTP_RETRIEVE_ARGS);
                $result = json_decode(wp_remote_retrieve_body($results), true);
            if (is_wp_error($results)) {
                echo '<div style="font-size: 20px;text-align:center;font-weight:normal;color:#F00;margin:0 auto;margin-top: 30px;"><p>Problem Connecting to BMLT Root Server</p><p>' . $root_server . '</p><p>Error: ' . $result->get_error_message() . '</p><p>Please try again later</p></div>';
                return 0;
            }

                $unique_areas = array();
            foreach ($result as $value) {
                $parent_name = 'None';
                foreach ($result as $parent) {
                    if ($value['parent_id'] == $parent['id']) {
                        $parent_name = $parent['name'];
                    }
                }
                $unique_areas[] = $value['name'] . ',' . $value['id'] . ',' . $value['parent_id'] . ',' . $parent_name;
            }
            return $unique_areas;
        }

        public function adminMenuLink()
        {
            // If you change this from add_options_page, MAKE SURE you change the filterPluginActions function (below) to
            // reflect the page file name (i.e. - options-general.php) of the page your plugin is under!
            add_options_page('List Locations BMLT', 'List Locations BMLT', 'activate_plugins', basename(__FILE__), array(
                &$this,
                'adminOptionsPage'
            ));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                &$this,
                'filterPluginActions'
            ), 10, 2);
        }
        /**
         * Adds settings/options page
         */
        public function adminOptionsPage()
        {
            if (!isset($_POST['listlocationssave'])) {
                $_POST['listlocationssave'] = false;
            }
            if ($_POST['listlocationssave']) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'listlocationsupdate-options')) {
                    die('Whoops! There was a problem with the data you posted. Please go back and try again.');
                }
                $this->options['root_server']            = esc_url_raw($_POST['root_server']);
                $this->options['service_body_dropdown']  = sanitize_text_field($_POST['service_body_dropdown']);
                $this->options['recursive']              = sanitize_text_field($_POST['recursive']);
                $this->options['delimiter_textbox']      = $_POST['delimiter_textbox'];
                $this->options['state_checkbox']         = sanitize_text_field($_POST['state_checkbox']);
                $this->options['list_select']            = sanitize_text_field($_POST['list_select']);
                $this->options['state_skip_dropdown']    = sanitize_text_field($_POST['state_skip_dropdown']);
                $this->options['city_skip_dropdown']     = sanitize_text_field($_POST['city_skip_dropdown']);
                $this->saveAdminOptions();
                echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
            }
            ?>
            <div class="wrap">
                <h2>List Locations BMLT</h2>
                <form style="display:inline!important;" method="POST" id="list_locations_options" name="list_locations_options">
                    <?php wp_nonce_field('listlocationsupdate-options'); ?>
                    <?php $this_connected = $this->testRootServer($this->options['root_server']); ?>
                    <?php $connect = "<p><div style='color: #f00;font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-no'></div><span style='color: #f00;'>Connection to Root Server Failed.  Check spelling or try again.  If you are certain spelling is correct, Root Server could be down.</span></p>"; ?>
                    <?php if ($this_connected != false) { ?>
                        <?php $connect = "<span style='color: #00AD00;'><div style='font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-smiley'></div>Version ".$this_connected."</span>"?>
                        <?php $this_connected = true; ?>
                    <?php } ?>
                    <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                        <h3>BMLT Root Server URL</h3>
                        <p>Example: https://domain.org/main_server</p>
                        <ul>
                            <li>
                                <label for="root_server">Default Root Server: </label>
                                <input id="root_server" type="text" size="50" name="root_server" value="<?php echo $this->options['root_server']; ?>" /> <?php echo $connect; ?>
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Service Body</h3>
                        <p>This service body will be used when no service body is defined in the shortcode.</p>
                        <ul>
                            <li>
                                <label for="service_body_dropdown">Default Service Body: </label>
                                <select style="display:inline;" onchange="getListLocationsValueSelected()" id="service_body_dropdown" name="service_body_dropdown"  class="list_locations_service_body_select">
                                <?php if ($this_connected) { ?>
                                    <?php $unique_areas = $this->getAreas($this->options['root_server']); ?>
                                    <?php asort($unique_areas); ?>
                                    <?php foreach ($unique_areas as $key => $unique_area) { ?>
                                        <?php $area_data          = explode(',', $unique_area); ?>
                                        <?php $area_name          = $area_data[0]; ?>
                                        <?php $area_id            = $area_data[1]; ?>
                                        <?php $area_parent        = $area_data[2]; ?>
                                        <?php $area_parent_name   = $area_data[3]; ?>
                                        <?php $option_description = $area_name . " (" . $area_id . ") " . $area_parent_name . " (" . $area_parent . ")" ?>
                                        <?php $is_data = explode(',', esc_html($this->options['service_body_dropdown'])); ?>
                                        <?php if ($area_id == $is_data[1]) { ?>
                                            <option selected="selected" value="<?php echo $unique_area; ?>"><?php echo $option_description; ?></option>
                                        <?php } else { ?>
                                            <option value="<?php echo $unique_area; ?>"><?php echo $option_description; ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } else { ?>
                                    <option selected="selected" value="<?php echo $this->options['service_body_dropdown']; ?>"><?php echo 'Not Connected - Can not get Service Bodies'; ?></option>
                                <?php } ?>
                                </select>
                                <div style="display:inline; margin-left:15px;" id="txtSelectedValues1"></div>
                                <p id="txtSelectedValues2"></p>

                                <input type="checkbox" id="recursive" name="recursive" value="1" <?php echo ($this->options['recursive'] == "1" ? "checked" : "") ?>/>
                                <label for="recursive">Recurse Service Bodies</label>
                            </li>
                        </ul>
                    </div>
                    <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                        <h3>Attribute Options</h3>
                        <ul>
                            <li>
                                <input type="checkbox" id="state_checkbox" name="state_checkbox" value="1" <?php echo ($this->options['state_checkbox'] == "1" ? "checked" : "") ?>/>
                                <label for="state_checkbox">Show State</label>
                            </li>
                            <li>
                                <label for="state_skip_dropdown">State Skip: </label>
                                <select style="display:inline;" id="state_skip_dropdown" name="state_skip_dropdown"  class="state_skip_dropdown">
                                    <option value=""></option>
                                    <?php
                                    $service_body_states_area          = explode(',', $this->options['service_body_dropdown']);
                                    $service_body_states               = $service_body_states_area[1];
                                    $service_body_states_dropdown      = $this->getStateList($this->options['root_server'], $service_body_states, $this->options['recursive']);
                                    foreach ($service_body_states_dropdown as $key => $unique_state) {
                                        if ($unique_state == $this->options['state_skip_dropdown']) { ?>
                                            <option selected="selected" value="<?php echo $unique_state; ?>"><?php echo $unique_state; ?></option>
                                        <?php } else { ?>
                                            <option value="<?php echo $unique_state; ?>"><?php echo $unique_state; ?></option>
                                        <?php }
                                    } ?>
                                </select>
                            </li>
                            <li>
                                <label for="city_skip_dropdown">City Skip: </label>
                                <select style="display:inline;" id="city_skip_dropdown" name="city_skip_dropdown"  class="city_skip_dropdown">
                                    <option value=""></option>
                                    <?php
                                    $service_body_cities_area          = explode(',', $this->options['service_body_dropdown']);
                                    $service_body_cities               = $service_body_cities_area[1];
                                    $service_body_cities_dropdown      = $this->getCityList($this->options['root_server'], $service_body_states, $this->options['recursive']);
                                    foreach ($service_body_cities_dropdown as $key => $unique_city) {
                                        if ($unique_city == $this->options['city_skip_dropdown']) { ?>
                                            <option selected="selected" value="<?php echo $unique_city; ?>"><?php echo $unique_city; ?></option>
                                        <?php } else { ?>
                                            <option value="<?php echo $unique_city; ?>"><?php echo $unique_city; ?></option>
                                        <?php }
                                    } ?>
                                </select>
                            </li>
                            <li>
                                <label for="list_select">List Type: </label>
                                <select style="display:inline;" id="list_select" name="list_select"  class="list_by_select">
                                <?php if ($this->options['list_select'] == 'county') { ?>
                                    <option value="town">Town</option>
                                    <option selected="selected" value="county">County</option>
                                    <option value="borough">Borough</option>
                                    <option value="neighborhood">Neighborhood</option>
                                <?php } else if ($this->options['list_select'] == 'borough') { ?>
                                    <option value="town">Town</option>
                                    <option value="county">County</option>
                                    <option selected="selected" value="borough">Borough</option>
                                    <option value="neighborhood">Neighborhood</option>
                                <?php } else if ($this->options['list_select'] == 'neighborhood') { ?>
                                    <option value="town">Town</option>
                                    <option value="county">County</option>
                                    <option value="borough">Borough</option>
                                    <option selected="selected" value="neighborhood">Neighborhood</option>
                                <?php } else { ?>
                                    <option selected="selected" value="town">Town</option>
                                    <option value="county">County</option>
                                    <option value="borough">Borough</option>
                                    <option value="neighborhood">Neighborhood</option>
                                    <?php
                                }
                                ?>
                                </select>
                            </li>
                            <li>
                                <label for="delimiter_textbox">Delimiter: </label>
                                <input id="delimiter_textbox" type="text" size="5" name="delimiter_textbox" value="<?php echo $this->options['delimiter_textbox']; ?>" /> (Default is ', ')
                            </li>
                        </ul>
                    </div>
                    <input type="submit" value="SAVE CHANGES" name="listlocationssave" class="button-primary" />
                </form>
                <br/><br/>
                <?php include 'partials/_instructions.php'; ?>
            </div>
            <script type="text/javascript">getListLocationsValueSelected();</script>
            <?php
        }

        /**
         * @desc Adds the Settings link to the plugin activate/deactivate page
         * @param $links
         * @param $file
         * @return mixed
         */
        public function filterPluginActions($links, $file)
        {
            // If your plugin is under a different top-level menu than Settings (IE - you changed the function above to something other than add_options_page)
            // Then you're going to want to change options-general.php below to the name of your top-level page
            $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link);
            // before other links
            return $links;
        }

        public function getOptions()
        {
            // Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array(
                    "root_server"           => '',
                    "service_body_dropdown" => '',
                    'recursive'             => '0',
                    'state_checkbox'        => '1',
                    'delimiter_textbox'     => ', ',
                    'list_select'           => 'town',
                    'state_skip_dropdown'   => '',
                    'city_skip_dropdown'    => ''
                );
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
        }
        /**
         * Saves the admin options to the database.
         */
        public function saveAdminOptions()
        {
            $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
            update_option($this->optionsName, $this->options);
            return;
        }

        /**
         * @param $root_server
         * @param $services
         * @param $recursive
         * @return string
         */
        public function getListResults($root_server, $services, $recursive)
        {

            $serviceBodies = explode(',', $services);
            $services_query = '';
            foreach ($serviceBodies as $serviceBody) {
                $services_query .= '&services[]=' . $serviceBody;
            }
            $listUrl = wp_remote_retrieve_body(wp_remote_get($root_server . "/client_interface/json/?switcher=GetSearchResults"
                . $services_query
                . "&data_field_key=location_municipality,location_province,location_sub_province,location_city_subsection,location_neighborhood"
                . ($recursive == "1" ? "&recursive=1" : "")));

            return $listUrl;
        }

        /**
         * @param $root_server
         * @param $services
         * @param $recursive
         * @return array
         */
        public function getStateList($root_server, $services, $recursive)
        {

            $serviceBodies = explode(',', $services);
            $services_query = '';
            foreach ($serviceBodies as $serviceBody) {
                $services_query .= '&services[]=' . $serviceBody;
            }
            $listUrl = wp_remote_retrieve_body(wp_remote_get($root_server . "/client_interface/json/?switcher=GetSearchResults"
                . $services_query
                . "&data_field_key=location_province"
                . ($recursive == "1" ? "&recursive=1" : "")));

            $listResults = json_decode($listUrl, true);
            $unique_states = array();
            foreach ($listResults as $value) {
                if ($value['location_province'] != '') {
                    $unique_states[] .= str_replace('.', '', strtoupper(trim($value['location_province'])));
                }
            }
            $unique_states = array_unique($unique_states);
            asort($unique_states);

            return $unique_states;
        }

        public function getCityList($root_server, $services, $recursive)
        {
            $serviceBodies = explode(',', $services);
            $services_query = '';
            foreach ($serviceBodies as $serviceBody) {
                $services_query .= '&services[]=' .$serviceBody;
            }
            $listUrl = wp_remote_retrieve_body(wp_remote_get($root_server . "/client_interface/json/?switcher=GetSearchResults"
                . $services_query
                . "&data_field_key=location_municipality"
                . ($recursive == "1" ? "&recursive=1" : "")));

            $listResults = json_decode($listUrl, true);
            $unique_cities = array();
            foreach ($listResults as $value) {
                if ($value['location_municipality'] != '') {
                    $unique_cities[] .= str_replace('.', '', strtoupper($value['location_municipality']));
                }
            }
            $unique_cities = array_unique($unique_cities);
            asort($unique_cities);

            return $unique_cities;
        }
    }
    //End Class ListLocations
}
// end if
// instantiate the class
if (class_exists("ListLocations")) {
    $ListLocations_instance = new ListLocations();
}
?>
