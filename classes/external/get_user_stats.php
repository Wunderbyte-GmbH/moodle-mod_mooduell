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
class get_user_stats extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                'userid' => new \external_value(PARAM_INT, 'user id'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @param int $userid
     * @return mixed
     */
    public static function execute(int $userid) {
        $params = [
                'userid' => $userid,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        return \mod_mooduell\game_control::get_user_stats($params['userid']);
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
                        'playedgames' => new \external_value(PARAM_INT, 'playedgames'),
                        'wongames' => new \external_value(PARAM_INT, 'wongames'),
                        'lostgames' => new \external_value(PARAM_INT, 'lostgames'),
                        'correctlyanswered' => new \external_value(PARAM_INT, 'correctlyanswered'),
                        'playedquestions' => new \external_value(PARAM_INT, 'playedquestions'),
                ]);
    }
}
