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
        require_capability('tool/token:generatetoken', \context_user::instance($userid));

        if (!$this->servicesconfig->is_service_enabled($serviceshortname)) {
            throw new \moodle_exception('servicenotavailable', 'webservice');
        }

        if (!$service = $this->servicesconfig->get_service_by_shortname($serviceshortname)) {
            throw new \moodle_exception('servicenotavailable', 'webservice');
        }

        // TODO: fire event that token was generated.
        return external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $userid, \context_system::instance());
    }

}