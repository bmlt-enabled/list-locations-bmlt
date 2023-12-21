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

    public function getList(string $root_server, bool $recursive, string $dataFieldKey, string $services = null, string $customQuery = null): array
    {
        if ($customQuery) {
            parse_str($customQuery, $queryParams);
        } else {
            $queryParams = $services ? [
                'services' => explode(',', $services),
                'data_field_key' => $dataFieldKey,
                'recursive' => $recursive ? 1 : 0
            ] : [
                'data_field_key' => $dataFieldKey,
                'recursive' => $recursive ? 1 : 0
            ];
        }

        $response = $this->getRemoteResponse($root_server, $queryParams);
        if ($response['status'] === 'error') {
            $listResults = [];
        } else {
            $listResults = $response['data'];
        }
        $unique_list = [];
        foreach ($listResults as $value) {
            $fieldValue = $value[$dataFieldKey];
            if (!empty($fieldValue)) {
                $unique_list[] = str_replace('.', '', strtoupper(trim($fieldValue)));
            }
        }

        $unique_list = array_unique($unique_list);
        asort($unique_list);

        return $unique_list;
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

    public function templateReplace($template, $data)
    {
        // Replace the special #br# template with <br> before any other processing
        $template = str_replace('#br#', '<br>', $template);

        // Check if any keys in template exists in data
        $requiredKeys = $this->extractKeysFromTemplate($template);
        if (!array_intersect_key($data, array_flip($requiredKeys))) {
            return '';
        }

        // Replace known keys from data
        $isAnyValueReplaced = false;
        foreach ($data as $key => $value) {
            $oldTemplate = $template;
            $template = str_replace("#{$key}#", ($value !== null && $value !== '') ? $value : '', $template);

            // Check if any actual replacement occurred
            if ($oldTemplate !== $template && ($value !== null && $value !== '')) {
                $isAnyValueReplaced = true;
            }
        }

        // Replace remaining unmatched template variables with empty strings
        $template = preg_replace('/#[^#]+#/', '', $template);

        // If no actual value got replaced in the template, return an empty string
        if (!$isAnyValueReplaced) {
            return '';
        }

        return $template;
    }

    public function extractKeysFromTemplate($template)
    {
        preg_match_all('/#([^#]+)#/', $template, $matches);
        return $matches[1] ?? [];
    }

    // We must use this for Delimiter as wordpress stock function trims whitespace which we don't want
    public function customSanitizeTextField($str)
    {
        if (is_object($str) || is_array($str)) {
            return '';
        }

        $str = (string) $str;

        $filtered = wp_check_invalid_utf8($str);

        if (str_contains($filtered, '<')) {
            $filtered = wp_pre_kses_less_than($filtered);
            // This will strip extra whitespace for us.
            $filtered = wp_strip_all_tags($filtered, false);

            /*
             * Use HTML entities in a special case to make sure that
             * later newline stripping stages cannot lead to a functional tag.
             */
            $filtered = str_replace("<\n", "&lt;\n", $filtered);
        }

        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);

        // Remove percent-encoded characters.
        $found = false;
        while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
            $filtered = str_replace($match[0], '', $filtered);
            $found    = true;
        }

        if ($found) {
            // Strip out the whitespace that may now exist after removing percent-encoded characters.
            $filtered = preg_replace('/ +/', ' ', $filtered);
        }

        return $filtered;
    }
}
