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
 * The class responsible for generating a token for provided user.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_token;

defined('MOODLE_INTERNAL') || die();

/**
 * The class responsible for generating a token for provided user.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token_generator {

    /**
     * The instance of services config.
     * @var \tool_token\services_config
     */
    private $servicesconfig;

    /**
     * Constructor.
     *
     * @param \tool_token\services_config $servicesconfig The instance of services config.
     */
    public function __construct(services_config $servicesconfig) {
        $this->servicesconfig = $servicesconfig;
    }

    /**
     * Get configured lifetime for generated tokens.
     *
     * @return int
     */
    protected function get_token_lifetime() : int {
        return (int) get_config('tool_token', 'tokenlifetime');
    }

    /**
     * Get valid until date.
     *
     * @return int
     */
    protected function get_valid_until() : int {
        $validuntil = $this->get_token_lifetime();

        if ($validuntil > 0) {
            $validuntil = $validuntil + time();
        }

        return (int) $validuntil;
    }

    /**
     * Generate token.
     *
     * @param int $userid User id.
     * @param string $serviceshortname Service short name.
     *
     * @return token
     */
    public function generate(int $userid, string $serviceshortname) : token {
        global $DB;

        require_capability('tool/token:generatetoken', \context_user::instance($userid));

        if (!$this->servicesconfig->is_service_enabled($serviceshortname)) {
            throw new \moodle_exception('error:servicenotavailable', 'tool_token', '', null, $serviceshortname);
        }

        if (is_null($service = $this->servicesconfig->get_service_by_shortname($serviceshortname))) {
            throw new \moodle_exception('error:servicenotavailable', 'tool_token', '', null, $serviceshortname);
        }

        // Check if a token has already been created for this user and this service.
        $conditions = [
            'userid' => $userid,
            'externalserviceid' => $service->id,
            'tokentype' => EXTERNAL_TOKEN_PERMANENT
        ];

        // Check existing tokens.
        $tokens = $DB->get_records('external_tokens', $conditions, 'timecreated ASC');
        foreach ($tokens as $key => $token) {
            // Checks related to a specific token. (script execution continue).
            $unsettoken = false;
            // If sid is set then we don't need this token regardless.
            if (!empty($token->sid)) {
                $unsettoken = true;
            }

            // Seems like lifetime settings are changed and we need to generate a new token.
            if (empty($token->validuntil) && !empty($this->get_valid_until())) {
                $DB->delete_records('external_tokens', ['token' => $token->token, 'tokentype' => EXTERNAL_TOKEN_PERMANENT]);
                $unsettoken = true;
            }

            // Remove token is not valid anymore.
            if (!empty($token->validuntil) && $token->validuntil < time()) {
                $DB->delete_records('external_tokens', ['token' => $token->token, 'tokentype' => EXTERNAL_TOKEN_PERMANENT]);
                $unsettoken = true;
            }

            // Remove token if its ip not in whitelist.
            if (isset($token->iprestriction) && !address_in_subnet(getremoteaddr(), $token->iprestriction)) {
                $unsettoken = true;
            }

            if ($unsettoken) {
                unset($tokens[$key]);
            }
        }

        // If some valid tokens exist then use the most recent.
        if (count($tokens) > 0) {
            $existingtoken = array_pop($tokens);
            $token = $existingtoken->token;
            $validuntil = (int) $existingtoken->validuntil;
        } else {
            $validuntil = $this->get_valid_until();
            $token = external_generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                $service,
                $userid,
                \context_system::instance(),
                $validuntil
            );
        }

        return new token($token, $validuntil);
    }

}