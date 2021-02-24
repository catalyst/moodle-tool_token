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

    $fields = new \tool_token\user_fields();
    $supportedfileds = $fields->get_supported_fields();
    unset($supportedfileds['id']);

    $settings->add(new admin_setting_configmulticheckbox(
        'tool_token/usermatchfields',
        get_string('usermatchfields', 'tool_token'),
        get_string('usermatchfields_desc', 'tool_token'),
        [],
        $supportedfileds
    ));

}
