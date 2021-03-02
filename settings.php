<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     tool_token
 * @category    admin
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('tool_token', get_string('pluginname', 'tool_token'));
    $ADMIN->add('tools', $settings);

    // Token lifetime.
    $settings->add(new admin_setting_configduration(
            'tool_token/tokenlifetime',
            get_string('tokenlifetime', 'tool_token'),
            get_string('tokenlifetime_desc', 'tool_token'),
            0)
    );

    // Enabled user matching fields.
    $fieldsconfig = new \tool_token\fields_config();
    $options = $fieldsconfig->get_supported_fields();
    unset($options['id']);

    $settings->add(new admin_setting_configmulticheckbox(
        'tool_token/usermatchfields',
        get_string('usermatchfields', 'tool_token'),
        get_string('usermatchfields_desc', 'tool_token'),
        [],
        $options
    ));

    // Enabled supported services.
    $servicesconfig = new \tool_token\services_config();
    $supportedservicess = $servicesconfig->get_supported_services();
    $options = [];
    foreach ($servicesconfig->get_supported_services() as $service) {
        $options[$service->shortname] = $service->name . ' (' . $service->shortname . ')';
    }

    $settings->add(new admin_setting_configmulticheckbox(
        'tool_token/services',
        get_string('services', 'tool_token'),
        get_string('services_desc', 'tool_token'),
        [],
        $options
    ));

}
