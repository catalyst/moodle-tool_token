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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Tests for token_api class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_token_token_api_testcase extends advanced_testcase {

    /**
     * Test  user 1.
     * @var \stdClass
     */
    protected $user1;

    /**
     * Test  user 2.
     * @var \stdClass
     */
    protected $user2;

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
     * Set up users and fields.
     */
    protected function set_up_users() {
        $field1 = $this->add_user_profile_field('field1', 'text', true);
        $field2 = $this->add_user_profile_field('field2', 'text', true);

        $this->user1 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $this->user1->id, 'profile_field_' . $field1->shortname => 'User 1 Field 1']);
        profile_save_data((object)['id' => $this->user1->id, 'profile_field_' . $field2->shortname => 'User 1 Field 2']);

        $this->user2 = $this->getDataGenerator()->create_user();
        profile_save_data((object)['id' => $this->user2->id, 'profile_field_' . $field1->shortname => 'User 2 Field 1']);
        profile_save_data((object)['id' => $this->user2->id, 'profile_field_' . $field2->shortname => 'User 2 Field 2']);
    }

    /**
     * Test getting a token without required permissions.
     */
    public function test_get_token_without_permissions() {
        $this->resetAfterTest();

        set_config('auth', 'manual', 'tool_token');

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $token = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => 2,
            'service' => 'fake WS'
        ]);

        $this->assertIsArray($token);
        $this->assertArrayHasKey('error', $token);
        $this->assertArrayHasKey('exception', $token);
        $this->assertTrue($token['error']);
        $this->assertSame(
            'Sorry, but you do not currently have permissions to do that (Generate Token).',
            $token['exception']->message
        );
    }

    /**
     * Test get token.
     */
    public function test_get_token_with_by_user_with_permissions() {
        global $DB;

        $this->resetAfterTest();

        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/token:generatetoken', CAP_ALLOW, $roleid, context_system::instance());

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user->id, context_system::instance()->id);

        $this->setUser($user);

        $serviceid = $this->create_service();
        $this->set_up_users();

        set_config('usermatchfields', 'username,profile_field1,profile_field2', 'tool_token');
        set_config('services', 'fake WS', 'tool_token');
        set_config('auth', 'manual', 'tool_token');

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $token1 = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => $this->user1->id,
            'service' => 'fake WS'
        ]);

        $expected = $DB->get_record('external_tokens', ['userid' => $this->user1->id, 'externalserviceid' => $serviceid]);

        $this->assertIsArray($token1);
        $this->assertArrayHasKey('error', $token1);
        $this->assertArrayHasKey('data', $token1);
        $this->assertFalse($token1['error']);
        $this->assertIsArray($token1['data']);
        $this->assertArrayHasKey('userid', $token1['data']);
        $this->assertArrayHasKey('token', $token1['data']);
        $this->assertArrayHasKey('validuntil', $token1['data']);
        $this->assertEquals($this->user1->id, $token1['data']['userid']);
        $this->assertSame($expected->token, $token1['data']['token']);
        $this->assertEquals($expected->validuntil, $token1['data']['validuntil']);

        $token2 = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'username',
            'idvalue' => $this->user1->username,
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

    /**
     * Test trying to get a token for not existing user.
     */
    public function test_get_token_for_not_existing_user() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $token = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => 777777,
            'service' => 'fake WS'
        ]);

        $this->assertIsArray($token);
        $this->assertArrayHasKey('error', $token);
        $this->assertArrayHasKey('exception', $token);
        $this->assertTrue($token['error']);
        $this->assertSame('Invalid parameter value detected', $token['exception']->message);
        $this->assertStringContainsString('User not found!', $token['exception']->debuginfo);
    }

    /**
     * Test get token by not enabled field.
     */
    public function test_get_token_for_by_not_enabled_field() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->create_service();
        $this->set_up_users();

        set_config('usermatchfields', 'username,profile_field1', 'tool_token');
        set_config('services', 'fake WS', 'tool_token');
        set_config('auth', 'manual', 'tool_token');

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $token = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'field2',
            'idvalue' => 'User 1 Field 2',
            'service' => 'fake WS'
        ]);

        $this->assertIsArray($token);
        $this->assertArrayHasKey('error', $token);
        $this->assertArrayHasKey('exception', $token);
        $this->assertTrue($token['error']);
        $this->assertSame('Field is not enabled for fetching users', $token['exception']->message);
        $this->assertStringContainsString('Field: "field2"', $token['exception']->debuginfo);
    }

    /**
     * Test getting a token by incorrect service.
     */
    public function test_get_token_for_incorrect_service() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->create_service();
        $this->set_up_users();

        set_config('usermatchfields', 'username,profile_field1', 'tool_token');
        set_config('services', '', 'tool_token');
        set_config('auth', 'manual', 'tool_token');

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        // Not enabled service.
        $token = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => $this->user1->id,
            'service' => 'fake WS'
        ]);
        $this->assertIsArray($token);
        $this->assertArrayHasKey('error', $token);
        $this->assertArrayHasKey('exception', $token);
        $this->assertTrue($token['error']);
        $this->assertSame('Service is not available!', $token['exception']->message);
        $this->assertStringContainsString('fake WS', $token['exception']->debuginfo);

        // Not existing service.
        $token = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => $this->user1->id,
            'service' => 'Not Existing'
        ]);
        $this->assertIsArray($token);
        $this->assertArrayHasKey('error', $token);
        $this->assertArrayHasKey('exception', $token);
        $this->assertTrue($token['error']);
        $this->assertSame('Service is not available!', $token['exception']->message);
        $this->assertStringContainsString('Not Existing', $token['exception']->debuginfo);
    }

    /**
     * Test getting a token if not auth methods enabled.
     */
    public function test_get_token_is_no_auth_method_enabled() {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->create_service();
        $this->set_up_users();

        set_config('usermatchfields', 'username,profile_field1', 'tool_token');
        set_config('services', 'fake WS', 'tool_token');
        set_config('auth', '', 'tool_token');

        // Workaround to be able to call external_api::call_external_function.
        $_POST['sesskey'] = sesskey();

        $token = external_api::call_external_function('tool_token_get_token', [
            'idtype' => 'id',
            'idvalue' => $this->user1->id,
            'service' => 'fake WS'
        ]);
        $this->assertIsArray($token);
        $this->assertArrayHasKey('error', $token);
        $this->assertArrayHasKey('exception', $token);
        $this->assertTrue($token['error']);
        $this->assertSame('Invalid parameter value detected', $token['exception']->message);
        $this->assertStringContainsString('User not found', $token['exception']->debuginfo);
    }

}
