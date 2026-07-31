<?php
defined('ABSPATH') || exit;

class GHBC_Tools {
    private static $instance = null;
    private $registry = [];

    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->register('search_code', 'Search the Ghana Building Code', [
            'name'        => 'search_code',
            'description' => 'Search the Ghana Building Code GS 1207:2018 for relevant clauses. Returns matching code sections with part numbers and full text.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'query' => [
                        'type'        => 'string',
                        'description' => 'The search query, e.g. "minimum ceiling height habitable room"',
                    ],
                ],
                'required' => ['query'],
            ],
        ], [$this, 'tool_search_code']);

        $this->register('get_clause', 'Look up specific code clause', [
            'name'        => 'get_clause',
            'description' => 'Retrieve the full text of a specific code clause by its reference number.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'clause' => [
                        'type'        => 'string',
                        'description' => 'Clause reference (e.g. "13.1301.2", "8.801.2", "35.3501.1")',
                    ],
                ],
                'required' => ['clause'],
            ],
        ], [$this, 'tool_get_clause']);

        $this->register('calculate_travel_distance', 'Calculate max travel distance', [
            'name'        => 'calculate_travel_distance',
            'description' => 'Calculate the maximum allowable travel distance based on occupancy type and sprinkler status. Returns meters.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'occupancy' => [
                        'type'        => 'string',
                        'description' => 'Occupancy group (A-1, A-2, A-3, B, E, F, H, I, M, R-1, R-2, S-1, S-2)',
                    ],
                    'sprinklered' => [
                        'type'        => 'boolean',
                        'description' => 'Whether the building has an automatic sprinkler system',
                    ],
                ],
                'required' => ['occupancy', 'sprinklered'],
            ],
        ], [$this, 'tool_calc_travel_distance']);

        $this->register('calculate_exit_width', 'Calculate required exit width', [
            'name'        => 'calculate_exit_width',
            'description' => 'Calculate the required total exit width in mm based on occupant load. Formula: OccupantLoad x 0.2 in/mm for stairs.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'occupant_load' => [
                        'type'        => 'integer',
                        'description' => 'Total number of occupants',
                    ],
                    'exit_type' => [
                        'type'        => 'string',
                        'enum'        => ['stairs', 'doors', 'corridors'],
                        'description' => 'Type of exit component',
                    ],
                ],
                'required' => ['occupant_load', 'exit_type'],
            ],
        ], [$this, 'tool_calc_exit_width']);

        $this->register('calculate_fire_rating', 'Calculate fire resistance requirements', [
            'name'        => 'calculate_fire_rating',
            'description' => 'Calculate required fire resistance ratings (hours) for building elements based on occupancy group, building height, and sprinkler status. Returns fire rating for structural frame, bearing walls, floors, roofs.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'occupancy' => [
                        'type'        => 'string',
                        'description' => 'Occupancy group (A-1, A-2, A-3, B, E, F-1, F-2, H, I-1, I-2, I-3, M, R-1, R-2, R-3, R-4, S-1, S-2, U)',
                    ],
                    'height' => [
                        'type'        => 'string',
                        'description' => 'Building height category: "up to 2 storeys", "3 to 4 storeys", "5 to 6 storeys", "7+ storeys"',
                    ],
                    'sprinklered' => [
                        'type'        => 'boolean',
                        'description' => 'Whether the building has an automatic sprinkler system throughout',
                    ],
                    'type' => [
                        'type'        => 'string',
                        'enum'        => ['structural_frame', 'bearing_walls', 'floors', 'roofs'],
                        'description' => 'Type of building element to check',
                    ],
                ],
                'required' => ['occupancy', 'height', 'type'],
            ],
        ], [$this, 'tool_calc_fire_rating']);

        $this->register('check_sprinkler_requirement', 'Check if sprinklers are required', [
            'name'        => 'check_sprinkler_requirement',
            'description' => 'Determine if automatic sprinkler system is required based on occupancy group, building area, and height per GhBC.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'occupancy' => [
                        'type'        => 'string',
                        'description' => 'Occupancy group (A, B, E, F, H, I, M, R, S)',
                    ],
                    'total_area' => [
                        'type'        => 'number',
                        'description' => 'Total building floor area in square meters',
                    ],
                    'storeys' => [
                        'type'        => 'integer',
                        'description' => 'Number of storeys',
                    ],
                    'has_basement' => [
                        'type'        => 'boolean',
                        'description' => 'Whether the building has a basement',
                    ],
                ],
                'required' => ['occupancy', 'total_area', 'storeys'],
            ],
        ], [$this, 'tool_check_sprinkler']);

        $this->register('check_small_building', 'Check if Part 35 Small Buildings applies', [
            'name'        => 'check_small_building',
            'description' => 'Check if a building qualifies as a "small building" under Part 35 of the Ghana Building Code.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'storeys' => [
                        'type'        => 'integer',
                        'description' => 'Number of storeys',
                    ],
                    'area' => [
                        'type'        => 'number',
                        'description' => 'Floor area in square meters',
                    ],
                    'height' => [
                        'type'        => 'number',
                        'description' => 'Building height in meters',
                    ],
                    'occupancy' => [
                        'type'        => 'string',
                        'description' => 'Occupancy type (residential, retail, office, etc.)',
                    ],
                ],
                'required' => ['storeys', 'area', 'height', 'occupancy'],
            ],
        ], [$this, 'tool_check_small_building']);
    }

    private function register($name, $desc, $schema, $callback) {
        $this->registry[$name] = [
            'schema'   => $schema,
            'callback' => $callback,
        ];
    }

    public function get_tool_schemas() {
        return array_values(array_map(function ($t) { return $t['schema']; }, $this->registry));
    }

    public function execute($tool_name, $args) {
        if (!isset($this->registry[$tool_name])) {
            return "Unknown tool: $tool_name";
        }
        return call_user_func($this->registry[$tool_name]['callback'], $args);
    }

    private function tool_search_code($args) {
        $search = GHBC_Search::instance();
        $results = $search->search($args['query']);
        if (empty($results)) {
            return "No results found for: {$args['query']}";
        }
        $out = [];
        foreach ($results as $r) {
            $clause = $r['clause'] ? " (Clause {$r['clause']})" : '';
            $out[] = "--- Part {$r['part']}{$clause}: {$r['title']} ---\n{$r['content']}";
        }
        return implode("\n\n", $out);
    }

    private function tool_get_clause($args) {
        $search = GHBC_Search::instance();
        $result = $search->get_by_clause($args['clause']);
        if (!$result) {
            return "Clause {$args['clause']} not found.";
        }
        return "--- Part {$result['part']}, Clause {$result['clause']}: {$result['title']} ---\n{$result['content']}";
    }

    private function tool_calc_travel_distance($args) {
        $distances = [
            'A-1' => ['ns' => 45, 's' => 60],
            'A-2' => ['ns' => 45, 's' => 60],
            'A-3' => ['ns' => 45, 's' => 60],
            'B'   => ['ns' => 60, 's' => 75],
            'E'   => ['ns' => 45, 's' => 60],
            'F'   => ['ns' => 60, 's' => 75],
            'H'   => ['ns' => 30, 's' => 45],
            'I'   => ['ns' => 30, 's' => 45],
            'M'   => ['ns' => 45, 's' => 60],
            'R-1' => ['ns' => 45, 's' => 60],
            'R-2' => ['ns' => 45, 's' => 60],
            'S-1' => ['ns' => 60, 's' => 75],
            'S-2' => ['ns' => 60, 's' => 75],
        ];
        $occ = strtoupper($args['occupancy']);
        $spr = !empty($args['sprinklered']);
        $entry = $distances[$occ] ?? ['ns' => 45, 's' => 60];
        $max = $spr ? $entry['s'] : $entry['ns'];
        return "Maximum allowable travel distance for Group $occ" . ($spr ? ' (sprinklered)' : ' (non-sprinklered)') . ": $max meters.";
    }

    private function tool_calc_exit_width($args) {
        $load = (int)$args['occupant_load'];
        $type = $args['exit_type'];
        $factor = $type === 'stairs' ? 0.2 : ($type === 'doors' ? 0.15 : 0.1);
        $width_mm = ceil($load * $factor);
        $width_m = $width_mm / 1000;
        return "Required total exit width for $load occupants ($type): $width_mm mm ($width_m m). Minimum per exit: 900 mm (0.9 m).";
    }

    private function tool_calc_fire_rating($args) {
        $occ = strtoupper($args['occupancy']);
        $height = $args['height'] ?? 'up to 2 storeys';
        $spr = !empty($args['sprinklered']);
        $type = $args['type'] ?? 'structural_frame';

        $ratings = [
            'A-1' => ['sf' => ['structural_frame' => 3, 'bearing_walls' => 3, 'floors' => 2, 'roofs' => 1.5]],
            'A-2' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'A-3' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'B'   => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'E'   => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'F-1' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'F-2' => ['sf' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5]],
            'H'   => ['sf' => ['structural_frame' => 4, 'bearing_walls' => 4, 'floors' => 3, 'roofs' => 2]],
            'I-1' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'I-2' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'I-3' => ['sf' => ['structural_frame' => 3, 'bearing_walls' => 3, 'floors' => 2, 'roofs' => 1.5]],
            'M'   => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'R-1' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'R-2' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'R-3' => ['sf' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5]],
            'R-4' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'S-1' => ['sf' => ['structural_frame' => 2, 'bearing_walls' => 2, 'floors' => 2, 'roofs' => 1]],
            'S-2' => ['sf' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5]],
            'U'   => ['sf' => ['structural_frame' => 1, 'bearing_walls' => 1, 'floors' => 1, 'roofs' => 0.5]],
        ];

        $entry = $ratings[$occ] ?? $ratings['B'];
        $hours = $entry['sf'][$type] ?? 1;
        $group_label = isset($ratings[$occ]) ? "Group $occ" : "Group $occ (defaulting to Group B)";
        $type_labels = ['structural_frame' => 'Structural frame', 'bearing_walls' => 'Bearing walls', 'floors' => 'Floor construction', 'roofs' => 'Roof construction'];

        $result = "Fire Resistance Rating for $group_label — {$type_labels[$type]}: $hours hour(s).\n";
        if ($spr && $hours > 1) {
            $reduced = max(1, $hours - 1);
            $result .= "Note: Building is sprinklered. Fire resistance may be reduced by 1 hour (to $reduced hours) per code exceptions.\n";
        }
        if ($height === '7+ storeys' && $hours < 2) {
            $result .= "WARNING: Buildings 7+ storeys require minimum 2-hour fire resistance for structural elements regardless of occupancy.\n";
        }
        $result .= "Reference: Part 8 (Fire Resistance), GhBC GS 1207:2018. This is advisory.";
        return $result;
    }

    private function tool_check_sprinkler($args) {
        $occ = strtoupper($args['occupancy']);
        $area = (float)($args['total_area'] ?? 0);
        $storeys = (int)($args['storeys'] ?? 1);
        $basement = !empty($args['has_basement']);

        $thresholds = [
            'A' => ['area' => 500, 'storeys' => 3, 'msg' => 'Assembly occupancies > 500m\u00b2 or 3+ storeys'],
            'B' => ['area' => 2000, 'storeys' => 4, 'msg' => 'Business occupancies > 2,000m\u00b2 or 4+ storeys'],
            'E' => ['area' => 500, 'storeys' => 2, 'msg' => 'Educational occupancies > 500m\u00b2 or 2+ storeys'],
            'F' => ['area' => 2000, 'storeys' => 3, 'msg' => 'Factory occupancies > 2,000m\u00b2 or 3+ storeys'],
            'H' => ['area' => 100, 'storeys' => 1, 'msg' => 'High-hazard occupancies > 100m\u00b2 or any multi-storey'],
            'I' => ['area' => 300, 'storeys' => 2, 'msg' => 'Institutional occupancies > 300m\u00b2 or 2+ storeys'],
            'M' => ['area' => 1500, 'storeys' => 3, 'msg' => 'Mercantile occupancies > 1,500m\u00b2 or 3+ storeys'],
            'R' => ['area' => 500, 'storeys' => 3, 'msg' => 'Residential occupancies > 500m\u00b2 or 3+ storeys'],
            'S' => ['area' => 3000, 'storeys' => 3, 'msg' => 'Storage occupancies > 3,000m\u00b2 or 3+ storeys'],
        ];

        $occ_short = substr($occ, 0, 1);
        $entry = $thresholds[$occ_short] ?? null;

        if (!$entry) {
            return "No specific sprinkler requirement threshold for Group $occ. Check local amendments.";
        }

        $reasons = [];
        $required = false;

        if ($area > $entry['area']) {
            $reasons[] = "Area {$area}m\u00b2 exceeds {$entry['area']}m\u00b2 threshold";
            $required = true;
        }
        if ($storeys >= $entry['storeys']) {
            $reasons[] = "Storeys ({$storeys}) meets/exceeds {$entry['storeys']} threshold";
            $required = true;
        }
        if ($basement) {
            $reasons[] = "Building has a basement (typically requires sprinklers)";
            $required = true;
        }

        if ($required) {
            return "Sprinklers REQUIRED for Group {$occ}.\nReasons:\n- " . implode("\n- ", $reasons) . "\n{$entry['msg']}. Reference: Part 8, GhBC GS 1207:2018.";
        }
        return "Sprinklers NOT required for Group {$occ} (area: {$area}m\u00b2, {$storeys} storeys).\n{$entry['msg']}. Reference: Part 8, GhBC GS 1207:2018. This is advisory.";
    }

    private function tool_check_small_building($args) {
        $reasons = [];
        $pass = true;

        if ((int)$args['storeys'] > 2) {
            $reasons[] = "Max 2 storeys (building has {$args['storeys']})";
            $pass = false;
        }
        if ((float)$args['area'] > 200) {
            $reasons[] = "Max 200m\u00b2 floor area (building has {$args['area']}m\u00b2)";
            $pass = false;
        }
        if ((float)$args['height'] > 6) {
            $reasons[] = "Max 6m height (building is {$args['height']}m)";
            $pass = false;
        }
        $simple_types = ['residential', 'residential', 'dwelling', 'house', 'home'];
        $occ = strtolower($args['occupancy']);
        if (!in_array($occ, $simple_types)) {
            $reasons[] = "Occupancy must be residential/simple (given: {$args['occupancy']})";
            $pass = false;
        }

        if ($pass) {
            return "YES — This building qualifies as a Small Building under Part 35. Use the simplified compliance checklist.\nSimplified checks: minimum room width 1.8m, ceiling height 2.4m, window area 10% floor, setbacks 3m/1.5m/3m, stair width 0.9m, handrail 0.9m, max riser 175mm, min tread 250mm, ventilation 5%, DPC 150mm, foundation 900mm, concrete Grade C20.";
        }
        return "NO — This building does NOT qualify as a Small Building under Part 35.\nReasons:\n- " . implode("\n- ", $reasons) . "\nEscalate to full Compliance Checker for detailed code review.";
    }
}
