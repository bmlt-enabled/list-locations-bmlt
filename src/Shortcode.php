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

    public function render($atts = [], $content = null): string
    {
        $defaults = $this->getDefaultValues();
        $args = shortcode_atts($defaults, $atts);

        if (empty($args['root_server'])) {
            return '<p><strong>List Locations Error: Root Server missing. Please Verify you have entered a Root Server using the \'root_server\' shortcode attribute</strong></p>';
        }
        if (empty($args['services'])) {
            return '<p><strong>List Locations Error: Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute</strong></p>';
        }

        $listResults = $this->helper->getListResults($args['root_server'], $args['services'], $args['recursive'], $args['custom_query']);
        $uniqueResults = $this->getUniqueResults($args, $listResults);
        asort($uniqueResults);
        $delimiter = $args['template'] ? '' : html_entity_decode($args['delimiter']);
        $content .= implode($delimiter, $uniqueResults);
        return $content;
    }

    private function getDefaultValues(): array
    {
        return [
            "root_server" => $this->settings->options['root_server'],
            'services' => explode(',', $this->settings->options['service_body_dropdown'])[1] ?? null,
            'recursive' => $this->settings->options['recursive'],
            'state' => $this->settings->options['state_checkbox'],
            'delimiter' => $this->settings->options['delimiter_textbox'] ?: ', ',
            'list' => $this->settings->options['list_select'],
            'state_skip' => $this->settings->options['state_skip_dropdown'],
            'city_skip' => $this->settings->options['city_skip_dropdown'],
            'custom_query' => $this->settings->options['custom_query'] ?? '',
            'template' => $this->settings->options['template'] ?? ''
        ];
    }

    private function getUniqueResults($args, $listResults): array
    {
        $uniqueResults = [];

        foreach ($listResults as $value) {
            if ($args['template']) {
                $templateValue = $this->helper->templateReplace($args['template'], $value);
                if (!is_null($templateValue) && $templateValue !== '') {
                    $uniqueResults[] = $templateValue;
                }
            } else {
                $locationValue = $this->getLocationValue($args, $value);
                if (!is_null($locationValue) && $locationValue !== '') {
                    $uniqueResults[] = $locationValue;
                }
            }
        }

        return array_unique($uniqueResults);
    }


    private function getLocationValue($args, $value)
    {
        $locationState = strtoupper($value['location_province']) === strtoupper($args['state_skip'])
            ? '' : ' ' . strtoupper($value['location_province']);

        if (strtoupper($value['location_municipality']) === strtoupper($args['city_skip'])) {
            $value['location_municipality'] = '';
        }

        switch ($args['list']) {
            case 'town':
                $locationValue = $value['location_municipality'];
                break;
            case 'county':
                $locationValue = str_replace(' County', '', $value['location_sub_province']);
                break;
            case 'borough':
                $locationValue = $value['location_city_subsection'];
                break;
            case 'neighborhood':
                $locationValue = $value['location_neighborhood'];
                break;
            default:
                return '<p><strong>List Locations Error: List attribute incorrect. Please Verify you have entered either town or county.</strong></p>';
        }

        if ($args['state'] == '1') {
            $locationValue = str_replace(',', '', trim(ucwords($locationValue))) . str_replace('.', '', $locationState);
        }

        return $locationValue;
    }
}
