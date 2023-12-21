<?php

namespace ListLocations;

require_once 'Helpers.php';

class Settings
{

    private $helper;
    public $optionsName = 'list_locations_options';
    public $options = [];

    public function __construct()
    {
        $this->getOptions();
        $this->helper = new Helpers();
        add_action("admin_notices", [$this, "isRootServerMissing"]);
    }

    public function createMenu(string $baseFile): void
    {
        add_options_page(
            'List Locations BMLT', // Page Title
            'List Locations BMLT', // Menu Title
            'activate_plugins',    // Capability
            'list-locations-plugin', // Menu Slug
            [$this, 'adminOptionsPage'] // Callback function to display the page content
        );

        add_filter('plugin_action_links_' . $baseFile, [$this, 'filterPluginActions'], 10, 2);
    }

    public function adminOptionsPage()
    {
        if (!empty($_POST['listlocationssave']) && wp_verify_nonce($_POST['_wpnonce'], 'listlocationsupdate-options')) {
            $this->updateAdminOptions();
            $this->printSuccessMessage();
        }
        $this->printAdminForm();
    }


    private function updateAdminOptions()
    {
        $this->options['root_server'] = isset($_POST['root_server']) ? esc_url_raw($_POST['root_server']) : '';
        $this->options['service_body_dropdown'] = isset($_POST['service_body_dropdown']) ? sanitize_text_field($_POST['service_body_dropdown']) : '';
        $this->options['recursive'] = isset($_POST['recursive']) ? sanitize_text_field($_POST['recursive']) : '';
        $this->options['delimiter_textbox'] = isset($_POST['delimiter_textbox']) ? $this->helper->customSanitizeTextField($_POST['delimiter_textbox']) : '';
        $this->options['state_checkbox'] = isset($_POST['state_checkbox']) ? sanitize_text_field($_POST['state_checkbox']) : '';
        $this->options['list_select'] = isset($_POST['list_select']) ? sanitize_text_field($_POST['list_select']) : '';
        $this->options['state_skip_dropdown'] = isset($_POST['state_skip_dropdown']) ? sanitize_text_field($_POST['state_skip_dropdown']) : '';
        $this->options['city_skip_dropdown'] = isset($_POST['city_skip_dropdown']) ? sanitize_text_field($_POST['city_skip_dropdown']) : '';
        $this->options['custom_query'] = isset($_POST['custom_query']) ? sanitize_text_field($_POST['custom_query']) : '';
        $this->options['template'] = isset($_POST['template']) ? $this->helper->customSanitizeTextField($_POST['template']) : '';
        $this->saveAdminOptions();
    }

    private function printSuccessMessage()
    {
        echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
    }

    private function getConnectionStatus()
    {
        $this_connected = $this->helper->testRootServer($this->options['root_server']);
        return $this_connected ? [
            'msg' => "<span style='color: #00AD00;'><div style='font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-smiley'></div>Version {$this_connected}</span>",
            'status' => true
        ] : [
            'msg' => "<p><div style='color: #f00;font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-no'></div><span style='color: #f00;'>Connection to Root Server Failed.  Check spelling or try again.  If you are certain spelling is correct, Root Server could be down.</span></p>",
            'status' => false
        ];
    }

    private function printDropdownOptions($items, $selectedValue, $formatterCallback)
    {
        return implode('', array_map(function ($item) use ($selectedValue, $formatterCallback) {
            $isSelected = $item === $selectedValue ? ' selected="selected"' : '';
            return '<option' . $isSelected . ' value="' . $item . '">' . $formatterCallback($item) . '</option>';
        }, $items));
    }

    private function getAreaDropdownOptions($connectionStatus)
    {
        if ($connectionStatus['status']) {
            $unique_areas = $this->helper->getAreas($this->options['root_server']);
            asort($unique_areas);
            return $this->printDropdownOptions($unique_areas, $this->options['service_body_dropdown'], function ($unique_area) {
                // Extract area data and create a description
                $area_data = explode(',', $unique_area);
                $area_name = Helpers::arraySafeGet($area_data, 0);
                $area_id = Helpers::arraySafeGet($area_data, 1);
                $area_parent_name = Helpers::arraySafeGet($area_data, 3);
                $area_parent = Helpers::arraySafeGet($area_data, 2);
                return "{$area_name} ({$area_id}) {$area_parent_name} ({$area_parent})";
            });
        } else {
            return '<option selected="selected" value="' . $this->options['service_body_dropdown'] . '">Not Connected - Can not get Service Bodies</option>';
        }
    }

    private function getListSelectDropdownOptions()
    {
        $listOptions = ['town', 'county', 'borough', 'neighborhood'];
        return $this->printDropdownOptions($listOptions, $this->options['list_select'], function ($option) {
            return ucfirst($option);
        });
    }

    private function getStateSkipDropdownOptions()
    {
        if (!isset($this->options['root_server']) || empty($this->options['root_server'])) {
            return '<option value="">Root Server Not Set</option>';
        }
        $service_body_states_area = explode(',', $this->options['service_body_dropdown']);
        $service_body_states = Helpers::arraySafeGet($service_body_states_area, 1) ?? null;
        $service_body_states_dropdown = $this->helper->getList($this->options['root_server'], $this->options['recursive'], 'location_province', $service_body_states, $this->options['custom_query']);
        return $this->printDropdownOptions($service_body_states_dropdown, $this->options['state_skip_dropdown'], function ($state) {
            return $state;
        });
    }


    private function getCitySkipDropdownOptions(): string
    {
        if (!isset($this->options['root_server']) || empty($this->options['root_server'])) {
            return '<option value="">Root Server Not Set</option>';
        }
        $service_body_cities_area = explode(',', $this->options['service_body_dropdown']);
        $service_body_cities = Helpers::arraySafeGet($service_body_cities_area, 1);
        $service_body_cities_dropdown = $this->helper->getList($this->options['root_server'], $this->options['recursive'], 'location_municipality', $service_body_cities, $this->options['custom_query']);

        return $this->printDropdownOptions($service_body_cities_dropdown, $this->options['city_skip_dropdown'], function ($city) {
            return $city;
        });
    }


    private function printAdminForm()
    {
        $connectionStatus = $this->getConnectionStatus();
        $areaDropdownOptions = $this->getAreaDropdownOptions($connectionStatus);
        ?>
        <div class="wrap">
            <h2>List Locations BMLT</h2>
            <form style="display:inline!important;" method="POST" id="list_locations_options" name="list_locations_options">
                <?php wp_nonce_field('listlocationsupdate-options'); ?>

                <!-- Connection Status Display -->
                <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                    <h3>BMLT Root Server URL</h3>
                    <p>Example: https://domain.org/main_server</p>
                    <ul>
                        <li>
                            <label for="root_server">Default Root Server: </label>
                            <input id="root_server" type="text" size="50" name="root_server" value="<?php echo esc_attr($this->options['root_server']); ?>" />
                            <?php echo $connectionStatus['msg']; ?>
                        </li>
                    </ul>
                </div>

                <!-- Service Body Section -->
                <div style="padding: 0 15px;" class="postbox">
                    <h3>Service Body</h3>
                    <p>This service body will be used when no service body is defined in the shortcode.</p>
                    <ul>
                        <li>
                            <label for="service_body_dropdown">Default Service Body: </label>
                            <select style="display:inline;" onchange="getListLocationsValueSelected()" id="service_body_dropdown" name="service_body_dropdown" class="list_locations_service_body_select">
                                <?php echo $areaDropdownOptions; ?>
                            </select>
                            <div style="display:inline; margin-left:15px;" id="txtSelectedValues1"></div>
                            <p id="txtSelectedValues2"></p>
                            <input type="checkbox" id="recursive" name="recursive" value="1" <?php echo ($this->options['recursive'] == "1" ? "checked" : "") ?>/>
                            <label for="recursive">Recurse Service Bodies</label>
                        </li>
                    </ul>
                </div>

                <!-- Attribute Options Section -->
                <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                    <h3>Attribute Options</h3>
                    <ul>
                        <li>
                            <input type="checkbox" id="state_checkbox" name="state_checkbox" value="1" <?php echo ($this->options['state_checkbox'] == "1" ? "checked" : "") ?>/>
                            <label for="state_checkbox">Show State</label>
                        </li>
                        <!-- State Skip Dropdown -->
                        <li>
                            <label for="state_skip_dropdown">State Skip: </label>
                            <select style="display:inline;" id="state_skip_dropdown" name="state_skip_dropdown" class="state_skip_dropdown">
                                <option value=""></option>  <!-- Default empty option -->
                                <?php echo $this->getStateSkipDropdownOptions(); ?>
                            </select>
                        </li>

                        <!-- City Skip Dropdown -->
                        <li>
                            <label for="city_skip_dropdown">City Skip: </label>
                            <select style="display:inline;" id="city_skip_dropdown" name="city_skip_dropdown" class="city_skip_dropdown">
                                <option value=""></option>  <!-- Default empty option -->
                                <?php echo $this->getCitySkipDropdownOptions(); ?>
                            </select>
                        </li>
                        <!-- List Type Dropdown -->
                        <li>
                            <label for="list_select">List Type: </label>
                            <select style="display:inline;" id="list_select" name="list_select" class="list_by_select">
                                <?php echo $this->getListSelectDropdownOptions(); ?>
                            </select>
                        </li>
                        <!-- Delimiter Textbox -->
                        <li>
                            <label for="delimiter_textbox">Delimiter: </label>
                            <input id="delimiter_textbox" type="text" size="5" name="delimiter_textbox" value="<?php echo $this->options['delimiter_textbox']; ?>" /> (Default is ', ')
                        </li>
                    </ul>
                </div>
                <div style="padding: 0 15px;" class="postbox">
                    <h3>Custom Query</h3>
                    <p>Ex. &formats=54</p>
                    <ul>
                        <li>
                            <input type="text" id="custom_query" name="custom_query" value="<?php echo $this->options['custom_query']; ?>">
                        </li>
                    </ul>
                </div>
                <div style="padding: 0 15px;" class="postbox">
                    <h3>Template - Provide a custom template to render data.</h3>
                    <p>This will override most other rendering settings. There is one special magic var #br# this will insert breakrule tag.</p>
                    <p>Ex. #location_municipality#, #location_province#</p>
                    <ul>
                        <li>
                            <input type="text" id="template" name="template" value="<?php echo $this->options['template']; ?>">
                        </li>
                    </ul>
                </div>

                <!-- Save Button -->
                <input type="submit" value="SAVE CHANGES" name="listlocationssave" class="button-primary" />

            </form>
            <!-- Instructions Section -->
            <br/><br/>
            <?php include plugin_dir_path(__FILE__) . 'partials/_instructions.php'; ?>
        </div>

        <!-- Scripts -->
        <script type="text/javascript">getListLocationsValueSelected();</script>
        <?php
    }


    /**
     * @desc Adds the Settings link to the plugin activate/deactivate page
     * @param $links
     * @param $file
     * @return mixed
     */
    public function filterPluginActions($links)
    {
        // If your plugin is under a different top-level menu than Settings (IE - you changed the function above to something other than add_options_page)
        // Then you're going to want to change options-general.php below to the name of your top-level page
        $settings_link = '<a href="options-general.php?page=list-locations-plugin">Settings</a>';
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
                'city_skip_dropdown'    => '',
                'custom_query'          => '',
                'template'              => ''
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

    public function isRootServerMissing()
    {
        $root_server = $this->options['root_server'];
        if (empty($root_server)) {
            $url = esc_url(admin_url('options-general.php?page=list-locations-plugin'));
            echo '<div id="message" class="error">';
            echo '<p>Missing BMLT Root Server in settings for List Locations BMLT.</p>';
            echo "<p><a href='{$url}'>List Locations BMLT Settings</a></p>";
            echo '</div>';
        }
    }
}
