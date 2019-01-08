<?php
$root_server = 'https://bmlt.newyorkna.org/main_server';
$services = '1';
$recursive = '1';
$list = 'town';
$state = '0';
$town = '1';
$delimiter = "\n";
$state_skip = '';

            $listResults = json_decode(getListResults($root_server, $services, $recursive), true);
            $unique_city = array();

            foreach($listResults as $value) {
                if ($value['location_province'] != '') {
                    $locationValueState        = str_replace('.', '', trim(strtoupper($value['location_province'])));
                }
                if ($value['location_province'] != '') {
                    $locationValueState        = str_replace('.', '', trim(strtoupper($value['location_province'])));
                }
                if ($value['location_municipality'] != '') {
                    $locationValueTown         = str_replace(',', '', trim(ucwords(strtolower($value['location_municipality']))));
                }
                if ($value['location_sub_province'] != '') {
                    $locationValueCounty       = str_replace(' County', '', trim(ucwords(strtolower($value['location_sub_province']))));
                }
                if ($value['location_city_subsection'] != '') {
                    $locationValueBorough      = str_replace(',', '', trim(ucwords(strtolower($value['location_city_subsection']))));
                }
                if ($value['location_neighborhood'] != '') {
                    $locationValueNeighborhood = str_replace(',', '', trim(ucwords(strtolower($value['location_neighborhood']))));
                }

                if ($locationValueState == $state_skip) {
                    $location_state = '';
                } else {
                    $location_state = ' ' . $locationValueState;
                }

                if ($list == 'town') {
                    $finalResult = $state == "1" ? $locationValueTown . $location_state : $locationValueTown;
                    array_push($unique_city, $finalResult);

                } else if ($list == 'county') {
                    $finalResult = $state == "1" ? $locationValueCounty . $location_state : $locationValueCounty;
                    array_push($unique_city, $finalResult);

                } else if ($list == 'borough') {
                    if ($locationValueBorough != '') {
                        if ($state == 1 && $town !=1) {
                            $finalResult = $locationValueBorough . $location_state;
                        }
                        else if ($state !=1 && $town == 1 ) {
                            $finalResult = $locationValueBorough .' '. $locationValueTown;
                        }
                        else if ($state !=0 && $town !=0) {
                            $finalResult = $locationValueBorough . ' ' . $locationValueTown . $location_state;
                        }
                        else {
                            $finalResult = $locationValueBorough;
                        }
                        array_push($unique_city, $finalResult);
                    }
                 } else if ($list == 'neighborhood') {
                        if ($state == 1 && $town !=1) {
                            $finalResult = $locationValueNeighborhood . $location_state;
                        }
                        else if ($state !=1 && $town == 1 ) {
                            $finalResult = $locationValueNeighborhood .' '. $locationValueTown;
                        }
                        else if ($state !=0 && $town !=0) {
                            $finalResult = $locationValueNeighborhood . ' ' . $locationValueTown . $location_state;
                        }
                        else {
                            $finalResult = $locationValueNeighborhood;
                        }
                        array_push($unique_city, $finalResult);
                        } else {
                            echo '<p><strong>List Locations Error: List attribute incorrect. Please Verify you have entered either town, county, borough or neighborhood.</strong></p>';
                        }
            }
            $unique_city = array_unique($unique_city);
            asort($unique_city);
            $content .= implode(html_entity_decode($delimiter), $unique_city);
            echo $content;


        function getListResults($root_server, $services, $recursive)
        {

            $serviceBodies = explode(',', $services);
            $services_query = '';
            foreach ($serviceBodies as $serviceBody) {
                $services_query .= '&services[]=' . $serviceBody;
            }
            $listUrl = file_get_contents($root_server . "/client_interface/json/?switcher=GetSearchResults"
                . $services_query
                . "&data_field_key=location_municipality,location_province,location_sub_province,location_city_subsection,location_neighborhood"
                . ($recursive == "1" ? "&recursive=1" : ""));

            return $listUrl;
        }
