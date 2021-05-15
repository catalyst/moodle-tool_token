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
 * Tests for services_config class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_token\services_config;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests for services_config class.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_token_services_config_testcase extends advanced_testcase {

    /**
     * Test getting supported services.
     */
    public function test_get_supported_services() {
        global $DB;

        $this->resetAfterTest();
        $servicesconfig = new services_config();

        $this->assertIsArray($servicesconfig->get_supported_services());

        foreach ($servicesconfig->get_supported_services() as $shortname => $service) {
            $this->assertObjectHasAttribute('id', $service);
            $this->assertObjectHasAttribute('name', $service);
            $this->assertObjectHasAttribute('enabled', $service);
            $this->assertObjectHasAttribute('requiredcapability', $service);
            $this->assertObjectHasAttribute('restrictedusers', $service);
            $this->assertObjectHasAttribute('component', $service);
            $this->assertObjectHasAttribute('shortname', $service);
            $this->assertObjectHasAttribute('downloadfiles', $service);
            $this->assertObjectHasAttribute('uploadfiles', $service);
            // We support only services that have shortnames.
            $this->assertNotEmpty($service->shortname);
            $this->assertSame($service->shortname, $shortname);
        }

        $totalsupported = count($servicesconfig->get_supported_services());
        $service = (object) [
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
        ];

        // Insert one service with shortname.
        $DB->insert_record('external_services', $service);

        // Insert another one with null shortname.
        $service->shortname = null;
        $service->name = 'Tool Token Test WS with NULL shortname';
        $DB->insert_record('external_services', $service);

        // Insert another one with empty shortname.
        $service->shortname = '';
        $service->name = 'Tool Token Test WS with emtoy shortname';
        $DB->insert_record('external_services', $service);

        // Make sure that the total number of supported services changed by 1.
        $this->assertCount($totalsupported + 1, $servicesconfig->get_supported_services());
        $this->assertArrayHasKey('fake WS', $servicesconfig->get_supported_services());
    }

    /**
     * A data provider for testing test_get_enabled_services.
     *
     * @return array[]
     */
    public function get_enabled_services_data_provider() : array {
        return [
            ['', []],
            ['test', ['test']],
            ['test,1, null, , ,0,false', ['test', '1', ' null', '0', 'false']]
        ];
    }

    /**
     * Test a list of enabled services from config.
     *
     * @dataProvider get_enabled_services_data_provider
     *
     * @param string $configvalue A value for saving to config.
     * @param array $expected Expected list of enabled services.
     */
    public function test_get_enabled_services(string $configvalue, array $expected) {
        $this->resetAfterTest();

        $servicesconfig = new services_config();

        $this->assertIsArray($servicesconfig->get_enabled_services());
        $this->assertEmpty($servicesconfig->get_enabled_services());;

        set_config('services', $configvalue, 'tool_token');
        $this->assertEquals($expected, $servicesconfig->get_enabled_services());
    }

    /**
     * Test is_service_enabled method.
     */
    public function test_is_service_enabled() {
        $this->resetAfterTest();

        set_config('services', 'service1,ser vice2, service3, ,   service4', 'tool_token');

        $servicesconfig = new services_config();

        $this->assertFalse($servicesconfig->is_service_enabled(' '));
        $this->assertFalse($servicesconfig->is_service_enabled(''));
        $this->assertTrue($servicesconfig->is_service_enabled('service1'));
        $this->assertTrue($servicesconfig->is_service_enabled('ser vice2'));
        $this->assertFalse($servicesconfig->is_service_enabled('service3'));
        $this->assertTrue($servicesconfig->is_service_enabled(' service3'));
        $this->assertFalse($servicesconfig->is_service_enabled('service4'));
        $this->assertTrue($servicesconfig->is_service_enabled('   service4'));
        $this->assertFalse($servicesconfig->is_service_enabled('service5'));
        $this->assertFalse($servicesconfig->is_service_enabled('random string'));
    }

    /**
     * Test get service by shortname.
     */
    public function test_get_service_by_shortname() {
        global $DB;

        $this->resetAfterTest();
        $servicesconfig = new services_config();

        $service = (object) [
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
        ];

        // Insert one service with shortname.
        $DB->insert_record('external_services', $service);

        // Insert another one without shortname.
        $service->shortname = '';
        $service->name = 'Tool Token Test WS with empty shortname';
        $DB->insert_record('external_services', $service);

        $this->assertNull($servicesconfig->get_service_by_shortname(''));
        $this->assertSame('Tool Token Test WS', $servicesconfig->get_service_by_shortname('fake WS')->name);
    }
}
