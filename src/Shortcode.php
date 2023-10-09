<?php

namespace ListLocations;

require_once 'Settings.php';
require_once 'Helpers.php';

class Shortcode
{
    private $settings;
    private $helper;

    public function __construct()
    {
        $this->settings = new Settings();
        $this->helper = new Helpers();
    }

    public function render($atts = [], $content = null)
    {
        $defaults = [
            "root_server" => $this->settings->options['root_server'],
            'services' => explode(',', $this->settings->options['service_body_dropdown'])[1] ?? null,
            'recursive' => $this->settings->options['recursive'],
            'state' => $this->settings->options['state_checkbox'],
            'delimiter' => $this->settings->options['delimiter_textbox'] ?: ', ',
            'list' => $this->settings->options['list_select'],
            'state_skip' => $this->settings->options['state_skip_dropdown'],
            'city_skip' => $this->settings->options['city_skip_dropdown'],
            'custom_query' => $this->settings->options['custom_query'] ?? ''
        ];
        $args = shortcode_atts($defaults, $atts);

        if (empty($args['root_server'])) {
            return '<p><strong>List Locations Error: Root Server missing. Please Verify you have entered a Root Server using the \'root_server\' shortcode attribute</strong></p>';
        }
        if (empty($args['services'])) {
            return '<p><strong>List Locations Error: Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute</strong></p>';
        }

        $listResults = $this->helper->getListResults($args['root_server'], $args['services'], $args['recursive'], $args['custom_query']);
        $unique_city = [];

        foreach ($listResults as $value) {
            $location_state = strtoupper($value['location_province']) === strtoupper($args['state_skip']) ? '' : ' ' . strtoupper($value['location_province']);
            if (strtoupper($value['location_municipality']) === strtoupper($args['city_skip'])) {
                $value['location_municipality'] = '';
            }

            // Consider using a switch-case or a mapping for this logic
            switch ($args['list']) {
                case 'town':
                    $location_value = $value['location_municipality'];
                    break;
                case 'county':
                    $location_value = str_replace(' County', '', $value['location_sub_province']);
                    break;
                case 'borough':
                    $location_value = $value['location_city_subsection'];
                    break;
                case 'neighborhood':
                    $location_value = $value['location_neighborhood'];
                    break;
                default:
                    return '<p><strong>List Locations Error: List attribute incorrect. Please Verify you have entered either town or county.</strong></p>';
            }

            if (!empty($location_value)) {
                $finalResult = $args['state'] == "1"
                    ? str_replace(',', '', trim(ucwords($location_value))) . str_replace('.', '', $location_state)
                    : str_replace(',', '', trim(ucwords($location_value)));
                $unique_city[] = $finalResult;
            }
        }

        $unique_city = array_unique($unique_city);
        asort($unique_city);

        $content .= implode(html_entity_decode($args['delimiter']), $unique_city);
        return $content;
    }
}
