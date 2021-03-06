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
 * Tests for token_generator class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_token\token_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests for token_generator class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_token_token_generator_testcase extends advanced_testcase {

    /**
     * Helper method to mock services_config.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function build_mocked_servicesconfig() {
        return $this->getMockBuilder('\tool_token\services_config')
            ->setMethods(['is_service_enabled'])->getMock();
    }

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
     * Test exception if using incorrect user.
     */
    public function test_generate_token_and_user_is_not_exist() {
        $this->resetAfterTest();
        $this->create_service();
        $tokengenerator = new token_generator($this->build_mocked_servicesconfig());

        $this->expectException('dml_missing_record_exception');
        $this->expectExceptionMessage('Invalid user');
        $tokengenerator->generate(7777, 'fake WS');
    }

    /**
     * Test can't generate token without permissions.
     */
    public function test_generate_token_without_permissions() {
        $this->resetAfterTest();
        $this->create_service();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $tokengenerator = new token_generator($this->build_mocked_servicesconfig());

        $this->expectException('required_capability_exception');
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Generate Token).');
        $tokengenerator->generate($user->id, 'fake WS');
    }

    /**
     * Test exception when generating token for disabled service.
     */
    public function test_generate_token_for_disabled_service() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $this->create_service();
        $user = $this->getDataGenerator()->create_user();

        $servicesconfig = $this->build_mocked_servicesconfig();
        $servicesconfig->method('is_service_enabled')->willReturn(false);
        $tokengenerator = new token_generator($servicesconfig);

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('Service is not available! (fake WS)');
        $tokengenerator->generate($user->id, 'fake WS');
    }

    /**
     * Test exception when generating token for not existing service.
     */
    public function test_generate_token_for_not_existing_service() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $this->create_service();
        $user = $this->getDataGenerator()->create_user();

        $builder = $this->getMockBuilder('\tool_token\services_config')
            ->setMethods([
                'is_service_enabled',
                'get_service_by_shortname'
            ]);
        $servicesconfig = $builder->getMock();
        $servicesconfig->method('is_service_enabled')->willReturn(true);
        $servicesconfig->method('get_service_by_shortname')->willReturn(null);

        $tokengenerator = new token_generator($servicesconfig);

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('Service is not available! (fake WS)');
        $tokengenerator->generate($user->id, 'fake WS');
    }

    /**
     * Simple test of generating a token.
     */
    public function test_generate_token() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $servicesconfig = $this->build_mocked_servicesconfig();
        $servicesconfig->method('is_service_enabled')->willReturn(true);
        $serviceid = $this->create_service();

        $tokengenerator = new token_generator($servicesconfig);
        $token1 = $tokengenerator->generate($user->id, 'fake WS');
        $this->assertInstanceOf(\tool_token\token::class, $token1);

        $actual = $DB->get_record('external_tokens', ['token' => $token1->get_token()]);

        $this->assertEquals($user->id, $actual->userid);
        $this->assertEquals($serviceid, $actual->externalserviceid);
        $this->assertEquals(EXTERNAL_TOKEN_PERMANENT, $actual->tokentype);
        $this->assertEquals(0, $actual->validuntil);

        // Test that it's not generate a new token if there is a current one exists.
        $token2 = $tokengenerator->generate($user->id, 'fake WS');
        $this->assertEquals($token1, $token2);

        // Test that it's not generate a new token if there is a current one exists.
        $token3 = $tokengenerator->generate($user->id, 'fake WS');
        $this->assertEquals($token1, $token3);
    }

    /**
     * Test that we respect lifetime settings when generate tokens.
     */
    public function test_generate_token_with_valid_until_configured() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $servicesconfig = $this->build_mocked_servicesconfig();
        $servicesconfig->method('is_service_enabled')->willReturn(true);
        $this->create_service();

        $tokengenerator = new token_generator($servicesconfig);
        $token1 = $tokengenerator->generate($user->id, 'fake WS');
        $this->assertInstanceOf(\tool_token\token::class, $token1);

        $actual = $DB->get_record('external_tokens', ['token' => $token1->get_token()]);
        $this->assertEmpty($actual->validuntil);

        sleep(2);
        $token2 = $tokengenerator->generate($user->id, 'fake WS');
        $this->assertEquals($token1, $token2);

        // Token valid for 1 second.
        set_config('tokenlifetime', '1', 'tool_token');
        sleep(2);

        // Should generate a new token as the old one is expired.
        $token3 = $tokengenerator->generate($user->id, 'fake WS');
        $actual = $DB->get_record('external_tokens', ['token' => $token3->get_token()]);
        $this->assertEquals($token3->get_validuntil(), $actual->validuntil);
        $this->assertNotEquals($token1, $token3);
    }

    /**
     * Test generate correct token when existing token has null as valid until date.
     */
    public function test_generate_token_correctly_if_existing_token_has_null_validuntil_date() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $servicesconfig = $this->build_mocked_servicesconfig();
        $servicesconfig->method('is_service_enabled')->willReturn(true);
        $serviceid = $this->create_service();

        // Create an existing token with null validuntil.
        $newtoken = new stdClass();
        $newtoken->token = md5(uniqid(rand(), 1));
        $newtoken->externalserviceid = $serviceid;
        $newtoken->tokentype = EXTERNAL_TOKEN_PERMANENT;
        $newtoken->userid = $user->id;
        $newtoken->contextid = context_system::instance()->id;
        $newtoken->creatorid = $user->id;
        $newtoken->timecreated = time();
        $newtoken->validuntil = null;
        $newtoken->privatetoken = random_string(64);
        $DB->insert_record('external_tokens', $newtoken);

        $tokengenerator = new token_generator($servicesconfig);
        $token = $tokengenerator->generate($user->id, 'fake WS');
        $this->assertSame($newtoken->token, $token->get_token());
        $this->assertSame(0, $token->get_validuntil());
    }
}
