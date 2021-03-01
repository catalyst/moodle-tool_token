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

defined('MOODLE_INTERNAL') || die();

/**
 * The class responsible for retrieving a user based on identifier.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_extractor {

    /** An instance of fields config class.
     * @var \tool_token\fields_config
     */
    protected $fieldscofnig;

    /**
     * Constructor.
     *
     * @param \tool_token\fields_config $fieldsconfig An instance of fields config class.
     */
    public function __construct(fields_config $fieldsconfig) {
        $this->fieldscofnig = $fieldsconfig;
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

        if (!$this->fieldscofnig->is_field_enabled($fieldname)) {
            throw new incorrect_field_exception($fieldname);
        }

        $user = null;

        $params = [];
        $joins = "";
        $where = "";

        if ($this->fieldscofnig->is_custom_profile_field($fieldname)) {
            $joins .= " LEFT JOIN {user_info_field} f ON f.shortname = :fieldname ";
            $joins .= " LEFT JOIN {user_info_data} d ON d.fieldid = f.id AND d.userid = u.id ";
            $where .= "AND d.data = :fieldvalue ";
            $params['fieldname'] = $fieldname;
            $params['fieldvalue'] = $fieldvalue;
        } else {
            $caseinsensitive = ($fieldname == 'id');
            $fieldselect = $DB->sql_equal($fieldname, ':fieldvalue', $caseinsensitive);

            $where .= "AND $fieldselect ";
            $params['fieldvalue'] = $fieldvalue;
        }

        $sql = "SELECT u.* FROM {user} u $joins WHERE u.deleted=0 $where";

        if ($result = $DB->get_records_sql($sql, $params)) {
            if (count($result) > 1) {
                throw new more_than_one_user_exception();
            }
            $user = reset($result);
            profile_load_custom_fields($user);
        }

        return $user;
    }
}