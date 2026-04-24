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
class start_attempt extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'course id'),
            'quizid' => new \external_value(PARAM_INT, 'quizid id'),
            'playerbid' => new \external_value(PARAM_INT, 'player B id'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute(int $courseid, int $quizid, int $playerbid) {
        $params = [
                'courseid' => $courseid,
                'quizid' => $quizid,
                'playerbid' => $playerbid,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new \moodle_exception(
                'invalidcoursemodule ' . $params['quizid'],
                'mooduell',
                null,
                null,
                'Course module id:' . $params['quizid']
            );
        }
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $mooduell = new \mod_mooduell\mooduell($params['quizid']);
        $gamecontroller = new \mod_mooduell\game_control($mooduell);
        $startgameresult = $gamecontroller->start_new_game($params['playerbid']);

        $startgameresult->challenges = \mod_mooduell\completion\completion_utils::get_completion_challenges_array($mooduell);

        return $startgameresult;
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return get_game_data::execute_returns();
    }
}
