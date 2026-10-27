<?php

namespace ListLocations;

require_once 'Settings.php';
require_once 'Helpers.php';

/**
 * [list_locations_table]: one row group per service body, with its website and helpline, listing for each state
 * the towns where it meets. With recursive="1" every child service body gets a row of its own.
 */
class Table
{
    private $settings;
    private $helper;

    const FIELDS = 'service_body_bigint,location_municipality,location_province';

    const STYLE = '.list-locations-table{overflow-x:auto}'
        . '.list-locations-table table{width:100%;border-collapse:collapse;word-break:normal;overflow-wrap:break-word}'
        . '.list-locations-table tbody tr.list-locations-band-a > *{background:#e8f4fc}'
        . '.list-locations-table tbody tr.list-locations-band-b > *{background:#fff}'
        . '.list-locations-table tbody tr > *{border:0;padding:.65em .8em;vertical-align:top}'
        . '.list-locations-table tbody tr.list-locations-start > *{border-top:4px solid #6c93ad}'
        . '.list-locations-table tbody th[scope="rowgroup"]{text-align:left;font-weight:600}';

    const STATE_NAMES = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
        'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
        'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
        'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts',
        'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana',
        'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
        'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
        'DC' => 'District of Columbia', 'PR' => 'Puerto Rico', 'VI' => 'U.S. Virgin Islands', 'GU' => 'Guam',
        'AS' => 'American Samoa', 'MP' => 'Northern Mariana Islands',
    ];

    public function __construct()
    {
        $this->settings = new Settings();
        $this->helper = new Helpers();
    }

    public function render($atts = []): string
    {
        $args = shortcode_atts([
            'root_server' => $this->settings->options['root_server'] ?? '',
            'services' => $this->settings->options['service_bodies'] ?? '',
            'recursive' => $this->settings->options['recursive'] ?? '0',
            'custom_query' => $this->settings->options['custom_query'] ?? '',
        ], $atts, 'list_locations_table');

        $rootServer = untrailingslashit(trim((string) $args['root_server']));
        $ids = self::ids((string) $args['services']);
        if ($rootServer === '') {
            return $this->error('Root Server missing. Please verify you have entered a Root Server using the \'root_server\' shortcode attribute');
        }
        if (!$ids) {
            return $this->error('Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute');
        }

        $response = $this->helper->getServiceBodies($rootServer);
        if ($response['status'] === 'error') {
            return $this->error($response['message']);
        }
        $known = [];
        foreach ($response['data'] as $body) {
            if (is_array($body) && self::ids((string) ($body['id'] ?? ''))) {
                $known[(int) $body['id']] = $body;
            }
        }
        if ($args['recursive'] == '1') {
            $ids = self::descendants($ids, $known);
        }
        $bodies = [];
        foreach ($ids as $id) {
            if (isset($known[$id])) {
                $name = trim((string) ($known[$id]['name'] ?? ''));
                $bodies[$id] = [
                    'name' => $name !== '' ? $name : (string) $id,
                    'helpline' => trim((string) ($known[$id]['helpline'] ?? '')),
                    'url' => self::website((string) ($known[$id]['url'] ?? '')),
                ];
            }
        }
        if (!$bodies) {
            return '<p>No matching service bodies were found.</p>';
        }

        $queryParams = [];
        if ($args['custom_query']) {
            parse_str(ltrim((string) $args['custom_query'], '&'), $queryParams);
        }
        $queryParams = array_merge($queryParams, [
            'services' => array_keys($bodies),
            'recursive' => 0,
            'data_field_key' => self::FIELDS,
        ]);
        $response = $this->helper->getMeetings($rootServer, $queryParams);
        if ($response['status'] === 'error') {
            return $this->error($response['message']);
        }

        $groups = $this->group($response['data'], $bodies);
        $rows = $this->rows($groups, $bodies);
        if ($rows === '') {
            return '<p>No meeting locations were found for the selected service bodies.</p>';
        }
        if (!wp_style_is('list-locations-table', 'registered')) {
            wp_register_style('list-locations-table', false);
            wp_add_inline_style('list-locations-table', self::STYLE);
        }
        wp_enqueue_style('list-locations-table');
        return '<div class="list-locations-table"><table><thead><tr><th scope="col">Area - Phone Number</th>'
            . '<th scope="col">State</th><th scope="col">Localities</th></tr></thead><tbody>' . $rows . '</tbody></table></div>';
    }

    /**
     * The given service bodies and all service bodies below them, following parent_id.
     */
    private static function descendants(array $ids, array $known): array
    {
        $children = [];
        foreach ($known as $id => $body) {
            $children[(int) ($body['parent_id'] ?? 0)][] = $id;
        }
        $out = [];
        $queue = $ids;
        while ($queue) {
            $id = array_shift($queue);
            if (isset($out[$id])) {
                continue;
            }
            $out[$id] = $id;
            foreach ($children[$id] ?? [] as $child) {
                $queue[] = $child;
            }
        }
        return array_values($out);
    }

    /**
     * Towns by service body and state; each meeting counts for its own service body only.
     */
    private function group(array $meetings, array $bodies): array
    {
        $groups = [];
        foreach ($meetings as $meeting) {
            if (!is_array($meeting)) {
                continue;
            }
            $city = trim(preg_replace('/\s+/u', ' ', (string) ($meeting['location_municipality'] ?? '')));
            $state = self::stateName((string) ($meeting['location_province'] ?? ''));
            if ($city === '' || $state === '') {
                continue; // Virtual meetings without a place.
            }
            $stateKey = self::fold($state);
            $cityKey = self::fold($city);
            $id = (int) ($meeting['service_body_bigint'] ?? 0);
            if (isset($bodies[$id])) {
                $groups[$id][$stateKey]['name'] ??= $state;
                $groups[$id][$stateKey]['cities'][$cityKey] ??= $city;
            }
        }
        return $groups;
    }

    private function rows(array $groups, array $bodies): string
    {
        uasort($bodies, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });
        $rows = '';
        $band = 0;
        foreach ($bodies as $id => $body) {
            if (empty($groups[$id])) {
                continue;
            }
            $states = $groups[$id];
            uasort($states, function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });
            $area = $body['url'] !== '' ? '<a href="' . $body['url'] . '">' . esc_html($body['name']) . '</a>' : esc_html($body['name']);
            if ($body['helpline'] !== '') {
                $dial = preg_replace('/[^0-9+]/', '', $body['helpline']);
                $area .= ' - ' . (preg_match('/^\+?[0-9]{3,15}$/', $dial)
                    ? '<a href="' . esc_url('tel:' . $dial, ['tel']) . '">' . esc_html($body['helpline']) . '</a>'
                    : esc_html($body['helpline']));
            }
            $class = $band % 2 === 0 ? 'list-locations-band-a' : 'list-locations-band-b';
            $first = true;
            foreach ($states as $state) {
                $cities = $state['cities'];
                natcasesort($cities);
                $rows .= '<tr class="' . esc_attr($class . ($first ? ' list-locations-start' : '')) . '">';
                if ($first) {
                    $rows .= '<th scope="rowgroup" rowspan="' . count($states) . '">' . $area . '</th>';
                }
                $rows .= '<td>' . esc_html($state['name']) . '</td><td>' . esc_html(implode(', ', $cities)) . '</td></tr>';
                $first = false;
            }
            $band++;
        }
        return $rows;
    }

    /**
     * The reason for administrators, a short notice for visitors.
     */
    private function error(string $message): string
    {
        if (current_user_can('manage_options')) {
            return '<p><strong>List Locations Error: ' . esc_html($message) . '</strong></p>';
        }
        return '<p>Meeting locations are temporarily unavailable.</p>';
    }

    /**
     * Positive service body IDs from a comma-separated list.
     */
    public static function ids(string $value): array
    {
        $ids = [];
        foreach (explode(',', $value) as $id) {
            if (preg_match('/^\s*\d+\s*$/', $id) && (int) $id > 0) {
                $ids[] = (int) $id;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * A US postal code or state name in any letter case becomes the full name; anything else is kept as entered.
     */
    public static function stateName(string $value): string
    {
        $value = trim($value);
        $key = strtoupper(rtrim($value, '.'));
        if (isset(self::STATE_NAMES[$key])) {
            return self::STATE_NAMES[$key];
        }
        foreach (self::STATE_NAMES as $name) {
            if (strcasecmp($name, $value) === 0) {
                return $name;
            }
        }
        return $value;
    }

    /**
     * Case-insensitive key for grouping towns and states.
     */
    private static function fold(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value);
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    /**
     * A service body's website as a link target, or '' (only http and https; "example.org" means https://example.org).
     */
    public static function website(string $url): string
    {
        $url = trim($url);
        if ($url !== '' && !preg_match('~^[a-z][a-z0-9+.-]*:~i', $url) && preg_match('~^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}\.?([/?#:]|$)~i', $url)) {
            $url = 'https://' . $url;
        }
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true) || !wp_parse_url($url, PHP_URL_HOST)) {
            return '';
        }
        return esc_url($url, ['http', 'https']);
    }
}
