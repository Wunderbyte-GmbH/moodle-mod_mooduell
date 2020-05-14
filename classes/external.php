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
 * @package mod_quiz
 * @category external
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.1
 */

use mod_mooduell\mooduell;
use mod_mooduell\game_control;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
// require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once('mooduell.php');

/**
 * Quiz external functions
 *
 * @package mod_quiz
 * @category external
 * @copyright 2020 Wunderbyte GmbH (info@wunderbyte.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.1
 */
class mod_mooduell_external extends external_api {

    /**
     * function to start_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function start_attempt($courseid, $quizid, $playerbid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid,
                'playerbid' => $playerbid
        );

        $params = self::validate_parameters(self::start_attempt_parameters(), $params);

        // now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $quizid)) {
            throw new moodle_exception('invalidcoursemodule ' . $quizid, 'quiz', null, null, "Course module id: $quizid");
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // require_capability('moodle/course:managegroups', $context);

        // we create Mooduell Instance.
        $mooduell = new mooduell($quizid);

        // we create the game_controller Instance.
        $gamecontroller = new game_control($mooduell);

        // we create a new game: Save parameters to DB & trigger notification event.
        $startgameresult = $gamecontroller->start_new_game($playerbid);

        // TODO: Trigger Notification for other User.

        $result = array();
        $result['status'] = $startgameresult;
        return $result;
    }

    /**
     * Describes the parameters for start_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function start_attempt_parameters() {
        return new external_function_parameters(array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'playerbid' => new external_value(PARAM_INT, 'player B id')
        ));
    }

    /**
     * Describes the returns for start_attempt.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function start_attempt_returns() {
        return new external_single_structure(array(
                'status' => new external_value(PARAM_INT, 'number of added questions')
        ));
    }

    /**
     * Describes the returns for get_quizzes_for_user.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quizzes_by_courses_returns() {
        return new external_single_structure(array(
                'quizzes' => new external_multiple_structure(new external_single_structure(array(
                        'id' => new external_value(PARAM_INT, 'id of coursemodule'),
                        'name' => new external_value(PARAM_RAW, 'name of quiz'),
                        'course' => new external_value(PARAM_INT, 'courseid'),
                        'coursemodule' => new external_value(PARAM_INT, 'coursemodule'),
                        'isteacher' => new external_value(PARAM_INT, 'isteacher')
                )))
        ));
    }

    /**
     * Describes the parameters for get_games_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_games_by_courses_parameters() {
        return new external_function_parameters(array(
                'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'), 'Array of course ids',
                        VALUE_DEFAULT, array())
        ));
    }

    /**
     * function to get_games_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_games_by_courses($courseids) {

        // We just call our function here to get all the quizzes.
        $returnedquizzes = self::get_quizzes_by_courses($courseids);

        // But we only want the quizzes array, no warnings.
        $warnings = $returnedquizzes['warnings'];
        $quizzes = $returnedquizzes['quizzes'];
        $returnedquizzes = array();

        // Now we run through all the quizzes to find the matching games.

        foreach ($quizzes as $quiz) {

            // We create Mooduell Instance.
            $instanceid = $quiz['coursemodule'];
            $mooduell = new mooduell($instanceid);

            // We create the game_controller Instance.
            $games = $mooduell->return_games_for_this_instance();

            if ($games && count($games) > 0) {

                foreach ($games as $game) {

                    $quiz['games'][] = [
                            'gameid' => $game->gamedata->gameid,
                            'playeraid' => $game->gamedata->playeraid,
                            'playerbid' => $game->gamedata->playerbid,
                            'playeratime' => $game->gamedata->playeratime,
                            'playerbtime' => $game->gamedata->playerbtime,
                            'winnerid' => $game->gamedata->winnerid,
                            'status' => $game->gamedata->status,
                            'timecreated' => $game->gamedata->timecreated,
                            'timemodified' => $game->gamedata->timemodified
                    ];
                }
            }

            $returnedquizzes[] = $quiz;
        }

        $result = array();
        $result['quizzes'] = $returnedquizzes;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * function to get_quizzes_for_user
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quizzes_by_courses($courseids) {
        $warnings = array();
        $returnedquizzes = array();

        $params = array(
                'courseids' => $courseids
        );
        $params = self::validate_parameters(self::get_quizzes_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }
        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list ($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the quizzes in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $quizzes = get_all_instances_in_courses("mooduell", $courses);
            foreach ($quizzes as $quiz) {
                $context = context_module::instance($quiz->coursemodule);

                // Entry to return.
                $quizdetails = array();
                // First, we return information that any user can see in the web interface.
                $quizdetails['id'] = $quiz->id;

                $quizdetails['course'] = $quiz->course;
                $quizdetails['coursemodule'] = $quiz->coursemodule;
                $quizdetails['name'] = external_format_string($quiz->name, $context->id);

                if (has_capability('mod/quiz:view', $context)) {

                    // Fields only for managers.
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        // we could do something here.
                        $quizdetails['isteacher'] = 1;
                    } else {
                        $quizdetails['isteacher'] = 0;
                    }
                }
                $returnedquizzes[] = $quizdetails;
            }
        }
        $result = array();
        $result['quizzes'] = $returnedquizzes;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the parameters for get_quizzes_for_user.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quizzes_by_courses_parameters() {
        return new external_function_parameters(array(
                'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'), 'Array of course ids',
                        VALUE_DEFAULT, array())
        ));
    }

    /**
     * Describes the returns for get_games_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_games_by_courses_returns() {
        return new external_single_structure(array(
                'quizzes' => new external_multiple_structure(new external_single_structure(array(
                        'id' => new external_value(PARAM_INT, 'id of coursemodule'),
                        'name' => new external_value(PARAM_RAW, 'name of quiz'),
                        'course' => new external_value(PARAM_INT, 'courseid'),
                        'isteacher' => new external_value(PARAM_INT, 'isteacher'),
                        'games' => new external_multiple_structure(new external_single_structure(array(
                                'gameid' => new external_value(PARAM_INT, 'id of game'),
                                'playeraid' => new external_value(PARAM_INT, 'id of player A'),
                                'playerbid' => new external_value(PARAM_INT, 'id of player B')
                        )))
                )))
        ));
    }

    /**
     * function to get_quiz_data
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quiz_data($courseid, $quizid, $gameid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid,
                'gameid' => $gameid
        );

        $params = self::validate_parameters(self::get_quiz_data_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $quizid)) {
            throw new moodle_exception('invalidcoursemodule ' . $quizid, 'quiz', null, null, "Course module id: $quizid");
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create Mooduell Instance.
        $mooduell = new mooduell($quizid);

        // We create the game_controller Instance.
        $gamecontroller = new game_control($mooduell, $gameid);

        // We can now retrieve our game data.
        $gamedata = $gamecontroller->return_game_data();

        return $gamedata;
    }

    /**
     * Describes the parameters for get_quiz_data.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quiz_data_parameters() {
        return new external_function_parameters(array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'gameid' => new external_value(PARAM_INT, 'gameid id')
        ));
    }

    /**
     * Describes the returns for get_quiz_data.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quiz_data_returns() {
        return new external_single_structure(array(
                'mooduellid' => new external_value(PARAM_INT, 'mooduellid'),
                'gameid' => new external_value(PARAM_INT, 'gameid'),
                'playeraid' => new external_value(PARAM_INT, 'player A id'),
                'playerbid' => new external_value(PARAM_INT, 'player B id'),
                'winnerid' => new external_value(PARAM_INT, 'winner id'),
                'status' => new external_value(PARAM_INT, 'stauts'),
                'questions' => new external_multiple_structure(new external_single_structure(array(
                        'id' => new external_value(PARAM_INT, 'questionid'),
                        'questiontext' => new external_value(PARAM_RAW, 'question text'),
                        'id' => new external_value(PARAM_INT, 'questionid'),
                        'qtype' => new external_value(PARAM_RAW, 'qtype'),
                        'category' => new external_value(PARAM_INT, 'category'),
                        'playeraanswered' => new external_value(PARAM_INT, 'answer player a'),
                        'playerbanswered' => new external_value(PARAM_INT, 'answer player a')
                )))
        ));
    }
}
