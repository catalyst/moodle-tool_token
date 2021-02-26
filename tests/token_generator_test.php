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

class tool_token_token_generator_testcase extends advanced_testcase {

    /**
     * Mocked services config instance.
     * @var \tool_token\services_config
     */
    protected $servicesconfig;

    /**
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        $builder = $this->getMockBuilder('\tool_token\services_config')
            ->setMethods(['is_service_enabled']);
        $this->servicesconfig = $builder->getMock();
    }

    /**
     * Simple test of generating a token.
     */
    public function test_generate_token() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $serviceid = $DB->insert_record('external_services', (object) [
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

        $this->servicesconfig->method('is_service_enabled')->willReturn(true);

        $tokengenerator = new token_generator($this->servicesconfig);
        $token = $tokengenerator->generate($user->id, 'fake WS');

        $actual = $DB->get_record('external_tokens', ['token' => $token]);

        $this->assertEquals($user->id, $actual->userid);
        $this->assertEquals($serviceid, $actual->externalserviceid);
        $this->assertEquals(EXTERNAL_TOKEN_PERMANENT, $actual->tokentype);
    }

}
