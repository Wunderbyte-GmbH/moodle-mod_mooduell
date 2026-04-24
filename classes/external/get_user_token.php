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
 * External API implementation for mod_mooduell.
 *
 * @package    mod_mooduell
 * @category   external
 * @copyright  2020 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\external;

use external_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function implementation.
 */
class get_user_token extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute() {
        global $USER, $DB;

        self::validate_parameters(self::execute_parameters(), []);

        $tokenobject = \mod_mooduell\manage_tokens::generate_token_for_user($USER->id, 'mod_mooduell_external', 0);
        $wstoken = optional_param('wstoken', '', PARAM_ALPHANUM);
        if (!empty($wstoken)) {
            $DB->delete_records('external_tokens', ['token' => $wstoken, 'userid' => $USER->id]);
        }
        $token = $tokenobject->token;
        $return['token'] = $token;

        return $return;
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'token' => new \external_value(PARAM_RAW, 'token'),
        ]);
    }
}
