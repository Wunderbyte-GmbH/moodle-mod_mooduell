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
 * Moolde external API
 *
 * @package    mod_quiz
 * @category   external
 * @copyright  2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

use mod_mooduell\mooduell;
use mod_mooduell\game_control;

defined('MOODLE_INTERNAL') || die;

require_once $CFG->libdir . '/externallib.php';
//require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once 'mooduell.php';

/**
 * Quiz external functions
 *
 * @package    mod_quiz
 * @category   external
 * @copyright  2020 Wunderbyte GmbH (info@wunderbyte.at)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
class mod_mooduell_external extends external_api
{

    /**
     * Describes the parameters for start_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function start_attempt_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'playerbid' => new external_value(PARAM_INT, 'player B id')
            )
        );
    }

    /**
     * function to start_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function start_attempt($courseid, $quizid, $playerbid)
    {

        $params = array(
            'courseid' => $courseid,
            'quizid' => $quizid,
            'playerbid' => $playerbid
        );

        $params = self::validate_parameters(self::start_attempt_parameters(), $params);

        // now security checks

        if (!$cm = get_coursemodule_from_id('mooduell', $quizid)) {
            throw new moodle_exception('invalidcoursemodule ' . $quizid, 'quiz', null, null,
                "Course module id: $quizid");
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        //require_capability('moodle/course:managegroups', $context);

        //we create Mooduell Instance
        $mooduell = new mooduell($quizid);

        //we create the game_controller Instance
        $gamecontroller = new game_control($mooduell);

        //we create a new game: Save parameters to DB & trigger notification event
        $start_game_result = $gamecontroller->start_new_game($playerbid);


        //TODO: Trigger Notification for other User

        $result = array();
        $result['status'] = true;
        return $result;

        return $result;
    }

    /**
     * Describes the returns for start_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */

    public static function start_attempt_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success')
            )
        );
    }
}
