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
 * Tests for token_api class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_token\fields_config;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/user/profile/lib.php');

class tool_token_token_api_testcase extends advanced_testcase {

    /**
     * A helper function to create a new service.
     *
     * @return int ID of a newly created service.
     */
    protected function create_service() : int {
        global $DB;

        return $DB->insert_record('external_services', (object) [
            'name' => 'Tool Token Test WS',
            'enabled' => 1,
            'requiredcapability' => '',
            'restrictedusers' => 0,
            'component' => null,
            'timecreated' => time(),
            'timemodified' => time(),
            'shortname' => 'fake WS',
            'downloadfiles' => 0,
            'uploadfiles' => 0,
        ]);
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
        $data->visible = '1';
        $data->categoryid = '0';

        $DB->insert_record('user_info_field', $data);

        return $data;
    }

    /**
     * Test get token.
     */
    public function test_get_token() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $serviceid = $this->create_service();
        $field1 = $this->add_user_profile_field('field1', 'text', true);
        $field2 = $this->add_user_profile_field('field2', 'text', true);

        $user1 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_' . $field2->shortname => 'User 1 Field 2']);

        $user2 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field1->shortname => 'User 2 Field 1']);
        profile_save_data((object)['id' => $user2->id, 'profile_field_' . $field2->shortname => 'User 2 Field 2']);

        set_config('usermatchfields', 'username,profile_field1,profile_field2', 'tool_token');
        set_config('services', 'fake WS', 'tool_token');

        $_POST['sesskey'] = sesskey();

        $token1 = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => $user1->id,
            'service' => 'fake WS'
        ]);

        $expected = $DB->get_record('external_tokens', ['userid' => $user1->id, 'externalserviceid' => $serviceid]);

        $this->assertIsArray($token1);
        $this->assertArrayHasKey('error', $token1);
        $this->assertArrayHasKey('data', $token1);
        $this->assertFalse($token1['error']);
        $this->assertIsArray($token1['data']);
        $this->assertArrayHasKey('userid', $token1['data']);
        $this->assertArrayHasKey('token', $token1['data']);
        $this->assertEquals($user1->id, $token1['data']['userid']);
        $this->assertSame($expected->token, $token1['data']['token']);

        $token2 = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'username',
            'idvalue' => $user1->username,
            'service' => 'fake WS'
        ]);

        $token3 = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'field1',
            'idvalue' => 'User 1 Field 1',
            'service' => 'fake WS'
        ]);

        $this->assertSame($token1, $token2);
        $this->assertSame($token1, $token3);
    }

}
