<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The class responsible for managing services config.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_token;

defined('MOODLE_INTERNAL') || die();

/**
 * The class responsible for managing services config.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class services_config {

    /**
     * Get the list of supported services.
     * @return array
     */
    public function get_supported_services() : array {
        global $DB;

        return $DB->get_records_select(
            'external_services',
            "shortname IS NOT NULL AND shortname <> ?",
            [''],
            'name',
            'shortname, *'
        );
    }

    /**
     * Get the list of enabled for generating a token services.
     *
     * @return array
     */
    public function get_enabled_services() : array {
        $result = [];

        $config = get_config('tool_token', 'services');

        if (!empty($config)) {
            $services = explode(',', $config);
            // Remove all empty strings.
            $result = array_filter($services, function($value) {
                return trim($value) !== '';
            });
        }

        return array_values($result);
    }

    /**
     * Check if provided service is enabled for generating a token.
     *
     * @param string $shortname Service shortname.
     * @return bool
     */
    public function is_service_enabled(string $shortname) : bool {
        return in_array($shortname, $this->get_enabled_services());
    }

    /**
     * Return instance of supported service.
     *
     * @param string $shortname Service shortname.
     * @return \stdClass|null
     */
    public function get_service_by_shortname(string $shortname) : ?\stdClass {
        return $this->get_supported_services()[$shortname] ?? null;
    }

}