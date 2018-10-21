<?php
/*
Plugin Name: List Locations BMLT
Author: Patrick J NERNA
Description: This plugin returns all unique towns or counties for given service body on your site Simply add [list_locations] shortcode to your page and set shortcode attributes accordingly. Required attributes are root_server and services.
Version: 1.0.1
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	die('Sorry, but you cannot access this page directly.');
}

function list_locations_func($atts = []) {
	extract(shortcode_atts(array(
        'services'	=>	'',
        'root_server' => '',
        'recursive' => '0',
        'state' => '1',
        'delimiter' => ',',
        'list' => 'town'
    ), $atts));
    $services = trim($services);
    $root_server = trim(strtolower($root_server));
    $recursive = trim($recursive);
    $state = trim($state);
    $list = trim($list);

    if ($root_server == '') {
        return '<p><strong>List Locations Error: Root Server missing. Please Verify you have entered a Root Server using the \'root_server\' shortcode attribute</strong></p>';
    }
    if ($services == '') {
        return '<p><strong>List Locations Error: Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute</strong></p>';
    }

    $serviceBodies = explode(',', $services);
    foreach ($serviceBodies as $serviceBody) {
        $services .= '&services[]=' . $serviceBody;
    }
    $results = wp_remote_retrieve_body(wp_remote_get($root_server . "/client_interface/json/?switcher=GetSearchResults&services="
        . $services
        . "&data_field_key=location_municipality,location_province,location_sub_province"
        . ($recursive == "1" ? "&recursive=1" : "")));

    $result = json_decode($results, true);
    $unique_city = array();
    foreach($result as $value) {
        if ($list == 'county') {
            $finalResult = $state == "1" ? str_replace ( ' County', '', trim(ucfirst($value['location_sub_province']))) . " " . str_replace ( '.', '', trim($value['location_province'])) : str_replace ( ' County', '', trim(ucfirst($value['location_sub_province'])));
            array_push($unique_city, $finalResult);
        }
        else {
            $finalResult = $state == "1" ? str_replace ( ',', '', trim(ucfirst($value['location_municipality']))) . " " . str_replace ( '.', '', trim($value['location_province'])) : str_replace ( ',', '', trim(ucfirst($value['location_municipality'])));
            array_push($unique_city, $finalResult);
        }
    }
    $unique_city = array_unique($unique_city);
    asort($unique_city);
    $implode_by = $delimiter != "," ? $delimiter : ', ';
    $content .= implode(html_entity_decode($implode_by), $unique_city);
    return $content;
}

// create [list_locations] shortcode
add_shortcode( 'list_locations', 'list_locations_func' );

?>