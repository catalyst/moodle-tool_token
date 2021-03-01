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
     * Generate token.
     *
     * @param int $userid User id.
     * @param string $serviceshortname Service short name.
     *
     * @return string
     */
    public function generate(int $userid, string $serviceshortname) : string {
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

        $tokens = $DB->get_records('external_tokens', $conditions, 'timecreated ASC');

        // A bit of sanity checks.
        foreach ($tokens as $key => $token) {
            // Checks related to a specific token. (script execution continue).
            $unsettoken = false;
            // If sid is set then there must be a valid associated session no matter the token type.
            if (!empty($token->sid)) {
                if (!\core\session\manager::session_exists($token->sid)) {
                    // This token will never be valid anymore, delete it.
                    $DB->delete_records('external_tokens', array('sid' => $token->sid));
                    $unsettoken = true;
                }
            }

            // Remove token is not valid anymore.
            if (!empty($token->validuntil) and $token->validuntil < time()) {
                $DB->delete_records('external_tokens', array('token' => $token->token, 'tokentype' => EXTERNAL_TOKEN_PERMANENT));
                $unsettoken = true;
            }

            // Remove token if its ip not in whitelist.
            if (isset($token->iprestriction) and !address_in_subnet(getremoteaddr(), $token->iprestriction)) {
                $unsettoken = true;
            }

            if ($unsettoken) {
                unset($tokens[$key]);
            }
        }

        // If some valid tokens exist then use the most recent.
        if (count($tokens) > 0) {
            $token = array_pop($tokens)->token;
        } else {
            $token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $userid, \context_system::instance());
        }

        return $token;
    }

}