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
 * The class representing generated token data.
 *
 * @package     tool_token
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_token;

/**
 * The class representing generated token data.
 *
 * @copyright   2021 Catalyst IT
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class token {

    /**
     * Token hash.
     * @var string
     */
    private $token;

    /**
     * Unix stamp token expiry date.
     * @var int
     */
    private $validuntil;

    /**
     * Constructor.
     *
     * @param string $token
     * @param int $validuntil
     */
    public function __construct(string $token, int $validuntil) {
        $this->token = $token;
        $this->validuntil = $validuntil;
    }

    /**
     * Get token.
     * @return string
     */
    public function get_token(): string {
        return $this->token;
    }

    /**
     * Get unix stamp token expiry date.
     * @return int
     */
    public function get_validuntil(): int {
        return $this->validuntil;
    }

}
