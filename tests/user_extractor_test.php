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
 * Tests for user_extractor class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_token\user_extractor;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests for user_extractor class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_token_user_extractor_testcase extends advanced_testcase {

    /**
     * Helper method to mock fields_config.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function build_mocked_fieldsconfig() {
        return $this->getMockBuilder('\tool_token\fields_config')
            ->setMethods([
                'is_field_enabled',
                'is_custom_profile_field',
                'get_enabled_auth_methods'
            ])->getMock();
    }

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
     * Test a correct exception is thrown when trying to search by disable field.
     */
    public function test_get_user_throw_exception_on_not_enabled_field() {
        $this->resetAfterTest();

        $fieldsconfig = $this->build_mocked_fieldsconfig();
        $fieldsconfig->method('is_field_enabled')->willReturn(false);
        $fieldsconfig->method('is_custom_profile_field')->willReturn(false);
        $fieldsconfig->method('get_enabled_auth_methods')->willReturn(['manual']);

        $extractor = new user_extractor($fieldsconfig);

        $this->expectException('tool_token\incorrect_field_exception');
        $this->expectExceptionMessage('Field is not enabled for fetching users (Field: "disabled")');

        $extractor->get_user('disabled', 'test');
    }

    /**
     * Test a correct exception is thrown when trying to search by disable field.
     */
    public function test_get_user_throw_exception_if_found_more_than_one_user() {
        $this->resetAfterTest();

        $fieldsconfig = $this->build_mocked_fieldsconfig();
        $fieldsconfig->method('is_field_enabled')->willReturn(true);
        $fieldsconfig->method('is_custom_profile_field')->willReturn(false);
        $fieldsconfig->method('get_enabled_auth_methods')->willReturn(['manual']);

        // Two users with empty idnumber.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $extractor = new user_extractor($fieldsconfig);

        $this->expectException('tool_token\more_than_one_user_exception');
        $this->expectExceptionMessage('More than one user found.');

        $extractor->get_user('idnumber', '');
    }

    /**
     * Test getting a user by user field.
     */
    public function test_get_user_by_user_field() {
        $this->resetAfterTest();

        $fieldsconfig = $this->build_mocked_fieldsconfig();
        $fieldsconfig->method('is_field_enabled')->willReturn(true);
        $fieldsconfig->method('is_custom_profile_field')->willReturn(false);
        $fieldsconfig->method('get_enabled_auth_methods')->willReturn(['manual']);

        $extractor = new user_extractor($fieldsconfig);

        $user1 = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['idnumber' => 'user2']);

        $actual = $extractor->get_user('email', $user1->email);
        $this->assertSame($user1->id, $actual->id);
        $this->assertEmpty($actual->profile);

        $actual = $extractor->get_user('id', $user2->id);
        $this->assertSame($user2->id, $actual->id);
        $this->assertEmpty($actual->profile);

        $actual = $extractor->get_user('username', 'random user name');
        $this->assertNull($actual);

        // Test that any of the allowed user fields should be case insensitive.
        foreach (\tool_token\fields_config::MATCH_FIELDS_FROM_USER_TABLE as $fieldname) {
            $actual = $extractor->get_user($fieldname, strtoupper($user1->{$fieldname}));
            $this->assertSame($user1->id, $actual->id);
        }

        // Now emulate disabling auth methods and see that we won't get any users matched.
        $fieldsconfig = $this->build_mocked_fieldsconfig();
        $fieldsconfig->method('is_field_enabled')->willReturn(true);
        $fieldsconfig->method('is_custom_profile_field')->willReturn(false);
        $fieldsconfig->method('get_enabled_auth_methods')->willReturn([]);

        $extractor = new user_extractor($fieldsconfig);
        $this->assertNull($extractor->get_user('email', $user1->email));
        $this->assertNull($extractor->get_user('id', $user2->id));
    }

    /**
     * Test getting a user by custom profile field.
     */
    public function test_get_user_by_custom_profile_field() {
        $this->resetAfterTest();

        $fieldsconfig = $this->build_mocked_fieldsconfig();
        $fieldsconfig->method('is_field_enabled')->willReturn(true);
        $fieldsconfig->method('is_custom_profile_field')->willReturn(true);
        $fieldsconfig->method('get_enabled_auth_methods')->willReturn(['manual']);

        $extractor = new user_extractor($fieldsconfig);

        $field1 = $this->add_user_profile_field('field1', 'text', true);
        $field2 = $this->add_user_profile_field('field2', 'text', true);

        $user1 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field2->shortname => 'User 1 Field 2']);

        $user2 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field1->shortname => 'User 2 Field 1']);
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field2->shortname => 'User 2 Field 2']);

        $actual = $extractor->get_user('field1', 'User 1 Field 1');
        $this->assertSame($user1->id, $actual->id);
        $this->assertNotEmpty($actual->profile);

        $actual = $extractor->get_user('field2', 'User 2 Field 2');
        $this->assertSame($user2->id, $actual->id);
        $this->assertNotEmpty($actual->profile);

        $actual = $extractor->get_user('field1', 'User 1 Field 2');
        $this->assertNull($actual);

        $actual = $extractor->get_user('random', 'User 1 Field 2');
        $this->assertNull($actual);

        // Test by custom user profile field should be case sensitive.
        $actual = $extractor->get_user('field1', 'user 1 field 1');
        $this->assertNull($actual);

        // Now emulate disabling auth methods and see that we won't get any users matched.
        $fieldsconfig = $this->build_mocked_fieldsconfig();
        $fieldsconfig->method('is_field_enabled')->willReturn(true);
        $fieldsconfig->method('is_custom_profile_field')->willReturn(false);
        $fieldsconfig->method('get_enabled_auth_methods')->willReturn([]);

        $extractor = new user_extractor($fieldsconfig);
        $this->assertNull($extractor->get_user('field1', 'User 1 Field 1'));
        $this->assertNull($extractor->get_user('field2', 'User 2 Field 2'));
    }

}
