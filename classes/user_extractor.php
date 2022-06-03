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
 * The class responsible for retrieving a user based on identifier.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_token;

/**
 * The class responsible for retrieving a user based on identifier.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_extractor {

    /**
     * An instance of fields config class.
     * @var \tool_token\fields_config
     */
    protected $fieldsconfig;

    /**
     * Constructor.
     *
     * @param \tool_token\fields_config $fieldsconfig An instance of fields config class.
     */
    public function __construct(fields_config $fieldsconfig) {
        $this->fieldsconfig = $fieldsconfig;
    }

    /**
     * Get extracted from DB user.
     *
     * @param string $fieldname Field name to search by.
     * @param string $fieldvalue Field value to search by.
     *
     * @return \stdClass|null
     */
    public function get_user(string $fieldname, string $fieldvalue) : ?\stdClass {
        global $DB;

        if (!$this->fieldsconfig->is_field_enabled($fieldname)) {
            throw new incorrect_field_exception($fieldname);
        }

        $user = null;

        // At least one auth method should be enabled.
        if ($auths = $this->fieldsconfig->get_enabled_auth_methods()) {
            $joins = "";
            $fieldsql = "";

            list($authsql, $params) = $DB->get_in_or_equal($auths, SQL_PARAMS_NAMED, 'auth');

            if ($this->fieldsconfig->is_custom_profile_field($fieldname)) {
                $joins .= " LEFT JOIN {user_info_field} f ON f.shortname = :fieldname ";
                $joins .= " LEFT JOIN {user_info_data} d ON d.fieldid = f.id AND d.userid = u.id ";
                $fieldselect = $DB->sql_equal('d.data', ':fieldvalue');
                $fieldsql .= " AND $fieldselect ";
                $params['fieldname'] = $fieldname;
                $params['fieldvalue'] = $fieldvalue;
            } else {
                if ($fieldname == 'id') {
                    // Hack to make MySQL happy as sql_equal doesn't work in MySQL of id field.
                    $fieldselect = 'id = :fieldvalue';
                } else {
                    // Always perform case insensitive search of fields like email, shortname.
                    $fieldselect = $DB->sql_equal($fieldname, ':fieldvalue', false);
                }

                $fieldsql .= " AND $fieldselect ";
                $params['fieldvalue'] = $fieldvalue;
            }

            $sql = "SELECT u.* FROM {user} u $joins WHERE u.deleted=0 AND u.auth $authsql $fieldsql";

            if ($result = $DB->get_records_sql($sql, $params)) {
                if (count($result) > 1) {
                    throw new more_than_one_user_exception();
                }
                $user = reset($result);
                profile_load_custom_fields($user);
            }
        }

        return $user;
    }
}
