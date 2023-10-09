<?php

namespace ListLocations;

class Helpers
{
    const BASE_API_ENDPOINT = "/client_interface/json/?switcher=";
    const HTTP_RETRIEVE_ARGS = array(
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +ListLocationsBMLT'
        ),
        'timeout' => 601
    );

    public static function arraySafeGet(array $array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    private function getRemoteResponse(string $root_server, array $queryParams = [], string $switcher = 'GetSearchResults'): array
    {
        $url = $root_server . self::BASE_API_ENDPOINT . $switcher;

        if (!empty($queryParams)) {
            $url .= '&' . http_build_query($queryParams);
        }

        $response = wp_remote_get($url, self::HTTP_RETRIEVE_ARGS);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => 'Error fetching data from server: ' . $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data)) {
            return ['status' => 'error', 'message' => 'Received empty data from server.'];
        }

        return ['status' => 'success', 'data' => $data];
    }


    public function getListResults(string $root_server, string $services, bool $recursive, string $customQuery = null): array
    {
        if ($customQuery) {
            parse_str($customQuery, $queryParams);
        } else {
            $queryParams = [
                'services' => explode(',', $services),
                'data_field_key' => 'location_municipality,location_province,location_sub_province,location_city_subsection,location_neighborhood',
                'recursive' => $recursive ? 1 : 0
            ];
        }

        $response = $this->getRemoteResponse($root_server, $queryParams);

        if ($response['status'] === 'error') {
            return [];
        } else {
            return $response['data'];
        }
    }

    public function getStateList(string $root_server, string $services, bool $recursive, string $customQuery = null): array
    {
        if ($customQuery) {
            parse_str($customQuery, $queryParams);
        } else {
            $queryParams = [
                'services' => explode(',', $services),
                'data_field_key' => 'location_province',
                'recursive' => $recursive ? 1 : 0
            ];
        }

        $response = $this->getRemoteResponse($root_server, $queryParams);
        if ($response['status'] === 'error') {
            $listResults = [];
        } else {
            $listResults = $response['data'];
        }
        $unique_states = [];
        foreach ($listResults as $value) {
            if ($value['location_province'] != '') {
                $unique_states[] .= str_replace('.', '', strtoupper(trim($value['location_province'])));
            }
        }

        $unique_states = array_unique($unique_states);
        asort($unique_states);

        return $unique_states;
    }

    public function getCityList(string $root_server, string $services, bool $recursive, string $customQuery = null): array
    {
        if ($customQuery) {
            parse_str($customQuery, $queryParams);
        } else {
            $queryParams = [
                'services' => explode(',', $services),
                'data_field_key' => 'location_municipality',
                'recursive' => $recursive ? 1 : 0
            ];
        }

        $response = $this->getRemoteResponse($root_server, $queryParams);
        if ($response['status'] === 'error') {
            $listResults = [];
        } else {
            $listResults = $response['data'];
        }
        $unique_cities = [];
        foreach ($listResults as $value) {
            $city = $value['location_municipality'];
            if (!empty($city)) {
                $unique_cities[] = str_replace('.', '', strtoupper($city));
            }
        }

        $unique_cities = array_unique($unique_cities);
        asort($unique_cities);

        return $unique_cities;
    }

    public function testRootServer($root_server)
    {
        $response = $this->getRemoteResponse($root_server, [], 'GetServerInfo');
        if ($response['status'] === 'error' || !is_array($response['data'])) {
            return '';
        }

        $data = $response['data'];

        return (isset($data[0]) && is_array($data[0]) && array_key_exists("version", $data[0])) ? $data[0]["version"] : '';
    }


    public function getAreas($root_server)
    {
        $response = $this->getRemoteResponse($root_server, [], 'GetServiceBodies');
        if ($response['status'] === 'error') {
            $error_message = '<div style="font-size: 20px;text-align:center;font-weight:normal;color:#F00;margin:0 auto;margin-top: 30px;">';
            $error_message .= '<p>Problem Connecting to BMLT Root Server</p>';
            $error_message .= '<p>' . esc_html($root_server) . '</p>';
            $error_message .= '<p>Please try again later</p></div>';
            return $error_message;
        } else {
            $results = $response['data'];
        }

        $parent_map = [];
        foreach ($results as $entry) {
            $parent_map[$entry['id']] = $entry['name'];
        }

        $unique_areas = [];
        foreach ($results as $value) {
            $parent_name = $parent_map[$value['parent_id']] ?? 'None';
            $unique_areas[] = $value['name'] . ',' . $value['id'] . ',' . $value['parent_id'] . ',' . $parent_name;
        }

        return $unique_areas;
    }
}
