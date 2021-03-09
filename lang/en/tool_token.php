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
 * Plugin strings are defined here.
 *
 * @package     tool_token
 * @category    string
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Token generator';
$string['token:generatetoken'] = 'Generate Token';
$string['privacy:metadata'] = 'The Token generator plugin does not store any personal data.';
$string['error:morethanoneuser'] = 'More than one user found.';
$string['error:servicenotavailable'] = 'Service is not available!';
$string['error:incorrectfield'] = 'Field is not enabled for fetching users';
$string['auth'] = 'Enabled auth methods';
$string['auth_desc'] = 'Token can be generated for users with enabled auth methods only.';
$string['services'] = 'Enabled services';
$string['services_desc'] = 'Token can be generated for enabled services only.';
$string['tokenlifetime'] = 'Token lifetime';
$string['tokenlifetime_desc'] = 'Once generated tokens will be valid for configured time. Note: 0 means no restriction.';
$string['usermatchfields'] = 'Enabled user fields';
$string['usermatchfields_desc'] = 'These fields could be used in a web service call to match users in Moodle. Note: id is always enabled to have at least one field to match by.';