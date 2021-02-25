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
 * The class responsible for managing user fields config.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_token;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * The class responsible for managing user fields config.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fields_config {

    /**
     * A list of user matching fields from {user} table
     */
    const MATCH_FIELDS_FROM_USER_TABLE = [
        'id' => 'id',
        'username' => 'username',
        'email' => 'email',
        'idnumber' => 'idnumber',
    ];

    /**
     * A list of supported types of profile fields.
     */
    const SUPPORTED_TYPES_OF_PROFILE_FIELDS = [
        'text'
    ];

    /**
     * Prefix for profile fields in the config.
     */
    const PROFILE_FIELD_PREFIX = 'profile_';

    /**
     * Get a list of fields to be able to match by.
     *
     * @return string[]
     */
    public function get_supported_fields() : array {
        $choices = self::MATCH_FIELDS_FROM_USER_TABLE;

        $customfields = profile_get_custom_fields(true);

        if (!empty($customfields)) {
            // Remove not supported fields.
            $result = array_filter($customfields, function($customfield) {
                return in_array($customfield->datatype, self::SUPPORTED_TYPES_OF_PROFILE_FIELDS) &&
                    $customfield->forceunique == 1;
            });

            $customfieldoptions = array_column($result, 'shortname', 'shortname');

            foreach ($customfieldoptions as $key => $value) {
                // Modify keys to mark these fields as custom profile fields.
                $customfieldoptions[$this->prefix_custom_profile_field($key)] = $value;
                unset($customfieldoptions[$key]);
            }

            $choices = array_merge($choices, $customfieldoptions);
        }

        return $choices;
    }

    /**
     * Get a list on user match fields enabled to be able to match user by these fields.
     *
     * @return array
     */
    public function get_enabled_fields() : array {
        $result = [];

        $config = get_config('tool_token', 'usermatchfields');

        if (!empty($config)) {
            $userfields = explode(',', $config);

            // Remove all empty strings.
            $result = array_filter($userfields, function($value) {
                return trim($value) !== '';
            });
        }

        // ID must be in the list regardless.
        $result = ['id' => 'id'] + $result;

        return array_values($result);
    }

    /**
     * Check if provided field name is considerred as profile field.
     *
     * @param string $fieldname User field name.
     * @return bool
     */
    public function is_custom_profile_field(string $fieldname) : bool {
        // Basically all fields should be profile except MATCH_FIELDS_FROM_USER_TABLE.
        return !in_array($fieldname, self::MATCH_FIELDS_FROM_USER_TABLE);
    }

    /**
     * Check is the field is enabled.
     *
     * @param string $fieldname User field name.
     *
     * @return bool
     */
    public function is_field_enabled(string $fieldname) : bool {
        return in_array($this->normalise_field($fieldname), $this->get_enabled_fields());
    }

    /**
     * Normalise user match field.
     *
     * @param string $fieldname User match field shortname.
     *
     * @return string
     */
    protected function normalise_field(string $fieldname) : string {
        if ($this->is_custom_profile_field($fieldname)) {
            $fieldname = $this->prefix_custom_profile_field($fieldname);
        }

        return $fieldname;
    }

    /**
     * Build setting value for a  user profile field.
     *
     * @param string $shortname Short name of the profile field.
     *
     * @return string
     */
    protected function prefix_custom_profile_field(string $shortname) : string {
        return self::PROFILE_FIELD_PREFIX . $shortname;
    }

}