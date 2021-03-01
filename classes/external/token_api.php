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
 * Token related external functions
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_token\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use tool_token\fields_config;
use tool_token\services_config;
use tool_token\token_generator;
use tool_token\user_extractor;


defined('MOODLE_INTERNAL') || die();

/**
 * Token related external functions
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_api extends external_api {

    /**
     * Get token for a user for a service.
     *
     * @param string $idtype Type of id with which to fetch a user.
     * @param string $idvalue Value of id with which to fetch a user.
     * @param string $service Service shortname to get a token for.
     *
     * @return array
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \tool_token\incorrect_field_exception
     * @throws \tool_token\more_than_one_user_exception
     */
    public static function get_token(string $idtype, string $idvalue, string $service) : array {
        $params = self::validate_parameters(self::get_token_parameters(), [
            'idtype'    => $idtype,
            'idvalue'   => $idvalue,
            'service'   => $service,
        ]);

        $userextractor = new user_extractor(new fields_config());
        $tokengenerator = new token_generator(new services_config());

        if (empty($user = $userextractor->get_user($params['idtype'], $params['idvalue']))) {
            throw new \invalid_parameter_exception('User not found!');
        }

        $token = $tokengenerator->generate($user->id, $params['service']);

        return [
            'userid' => $user->id,
            'token' => $token,
        ];
    }

    /**
     * The parameters for get_token.
     *
     * @return external_function_parameters
     */
    public static function get_token_parameters() : external_function_parameters {
        return new external_function_parameters([
            'idtype' => new external_value(PARAM_ALPHAEXT, 'Type of id with which to fetch a user.'), // TODO: get a list of enabled idtypes from config.
            'idvalue' => new external_value(PARAM_RAW, 'Value of id with which to fetch a user.'),
            'service' => new external_value(PARAM_ALPHAEXT, 'Service shortname to get a token for.'), // TODO: get a list of enabled services form config.
        ]);
    }

    /**
     * The return configuration for get_token.
     *
     * @return external_single_structure
     */
    public static function get_token_parameters_returns() : external_single_structure {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'Internal Moodle ID of the found user.', VALUE_OPTIONAL),
            'token' => new external_value(PARAM_RAW, 'Generated token', VALUE_OPTIONAL),
        ]);
    }

}
