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
 * Tests for user_fields class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_token\user_fields;

defined('MOODLE_INTERNAL') || die();

global $CFG;

class tool_token_user_fields_testcase extends advanced_testcase {

    /**
     * A helper function to create a custom profile field.
     *
     * @param string $shortname Short name of the field.
     * @param string $datatype Type of the field, e.g. text, checkbox, datetime, menu and etc.
     * @param bool $unique Should the field to be unique?
     *
     * @return \stdClass
     */
    protected function add_user_profile_field(string $shortname, string $datatype, bool $unique = false) : stdClass {
        global $DB;

        // Create a new profile field.
        $data = new \stdClass();
        $data->shortname = $shortname;
        $data->datatype = $datatype;
        $data->name = 'Test ' . $shortname;
        $data->description = 'This is a test field';
        $data->required = false;
        $data->locked = false;
        $data->forceunique = $unique;
        $data->signup = false;
        $data->visible = '0';
        $data->categoryid = '0';

        $DB->insert_record('user_info_field', $data);

        return $data;
    }

    /**
     * Test class constants.
     */
    public function test_constants() {
        $this->assertSame([
            'id' => 'id',
            'username' => 'username',
            'email' => 'email'
        ], user_fields::MATCH_FIELDS_FROM_USER_TABLE);

        $this->assertSame(['text'], user_fields::SUPPORTED_TYPES_OF_PROFILE_FIELDS);
        $this->assertSame('profile_', user_fields::PROFILE_FIELD_PREFIX);
    }

    /**
     * Test values for user match fields when no profile fields in the system.
     */
    public function test_get_supported_fields_without_profile_fields() {
        $fields = new user_fields();
        $expected = [
            'id' => 'id',
            'username' => 'username',
            'email' => 'email'
        ];
        $actual = $fields->get_supported_fields();
        $this->assertSame($expected, $actual);
    }

    /**
     * Test values for user match fields when there are profile fields in the system.
     */
    public function test_get_supported_fields_with_profile_fields() {
        $this->resetAfterTest();

        $fields = new user_fields();

        // Create bunch of profile fields.
        $this->add_user_profile_field('text1', 'text', true);
        $this->add_user_profile_field('checkbox1', 'checkbox', true);
        $this->add_user_profile_field('checkbox2', 'checkbox');
        $this->add_user_profile_field('text2', 'text', false);
        $this->add_user_profile_field('datetime1', 'datetime');
        $this->add_user_profile_field('menu1', 'menu');
        $this->add_user_profile_field('textarea1', 'textarea');
        $this->add_user_profile_field('text3', 'text', true);

        $userfields = [
            'id' => 'id',
            'username' => 'username',
            'email' => 'email'
        ];

        $profilefields = [
            'profile_text1' => 'text1',
            'profile_text3' => 'text3'
        ];
        $expected = array_merge($userfields, $profilefields);

        $this->assertEquals($expected, $fields->get_supported_fields());
    }

    /**
     * A data provider for testing test_get_enabled_fields.
     *
     * @return array[]
     */
    public function get_enabled_fields_data_provider() : array {
        return [
            ['', ['id']],
            ['test', ['id', 'test']],
            ['test,1, null, , ,0,false', ['id', 'test', '1', ' null', '0', 'false']]
        ];
    }

    /**
     * Test a list of enabled user match fields from config.
     *
     * @dataProvider get_enabled_fields_data_provider
     *
     * @param string $configvalue A value for saving to config.
     * @param array $expected Expected list of enabled fields.
     */
    public function test_get_enabled_fields(string $configvalue, array $expected) {
        $this->resetAfterTest();

        $fields = new user_fields();

        // ID must be presented all the time.
        $this->assertTrue(is_array($fields->get_enabled_fields()));
        $this->assertEquals(['id'], $fields->get_enabled_fields());

        set_config('usermatchfields', $configvalue, 'tool_token');
        $this->assertTrue(is_array($fields->get_enabled_fields()));
        $this->assertEquals($expected, $fields->get_enabled_fields());
    }
}

