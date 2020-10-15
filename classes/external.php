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

use mod_mooduell\game_control;
use mod_mooduell\mooduell;

defined('MOODLE_INTERNAL') || die();

// require_once($CFG->libdir . '/externallib.php');
// require_once $CFG->dirroot . '/mod/quiz/locallib.php';
require_once('mooduell.php');

/**
 * Class mod_mooduell_external
 */
class mod_mooduell_external extends external_api {

    /**
     * @param $courseid
     * @param $quizid
     * @param $playerbid
     * @return int|stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function start_attempt($courseid, $quizid, $playerbid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid,
                'playerbid' => $playerbid
        );

        $params = self::validate_parameters(self::start_attempt_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'quiz', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // require_capability('moodle/course:managegroups', $context);

        // we create Mooduell Instance.
        $mooduell = new mooduell($params['quizid']);

        // we create the game_controller Instance.
        $gamecontroller = new game_control($mooduell);

        // we create a new game: Save parameters to DB & trigger notification event.
        $startgameresult = $gamecontroller->start_new_game($params['playerbid']);

        // TODO: Trigger Notification for other User.

        $result = array();
        $result['status'] = $startgameresult;
        return $startgameresult;
    }

    /**
     * @return external_function_parameters
     */
    public static function start_attempt_parameters() {
        return new external_function_parameters(array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'playerbid' => new external_value(PARAM_INT, 'player B id')
        ));
    }

    /**
     * @return external_single_structure
     */
    public static function start_attempt_returns() {
        return self::get_game_data_returns();
    }

    /**
     * We answer a question with the array of ids of the answers. Depending on the internal setting of the MooDuell Instance...
     * ... we might either retrieve an array of the correct answer-ids ...
     * ... or an array of with one value 0 for incorrect and 1 for correctly answered.
     *
     * @param $quizid
     * @param $gameid
     * @param $questionid
     * @param $answerids
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function answer_question($quizid, $gameid, $questionid, $answerids) {
        $params = array(
                'quizid' => $quizid,
                'gameid' => $gameid,
                'questionid' => $questionid,
                'answerids' => $answerids
        );

        $params = self::validate_parameters(self::answer_question_parameters(), $params);

        // now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'quiz', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // require_capability('moodle/course:managegroups', $context);

        // we create Mooduell Instance.
        $mooduell = new mooduell($params['quizid']);

        // we create the game_controller Instance.
        $gamecontroller = new game_control($mooduell, $params['gameid']);

        $result['response'] = $gamecontroller->validate_question($params['questionid'], $params['answerids']);

        return $result;
    }

    /**
     * @return external_function_parameters
     */
    public static function answer_question_parameters() {
        return new external_function_parameters(array(
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'gameid' => new external_value(PARAM_INT, 'gameid id'),
                'questionid' => new external_value(PARAM_INT, 'question id'),
                'answerids' => new external_multiple_structure(new external_value(PARAM_INT, 'answer ids'),
                        'Array of answer ids'),
        ));
    }

    /**
     * @return external_single_structure
     */
    public static function answer_question_returns() {
        return new external_single_structure(array(
                'response' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'ids of correct answers OR 0 if false, 1 if true')
                )
        ));
    }

    /**
     * @return external_single_structure
     */
    public static function get_quizzes_by_courses_returns() {
        return new external_single_structure(array(
                'quizzes' => new external_multiple_structure(new external_single_structure(array(
                        'quizid' => new external_value(PARAM_INT, 'id of coursemodule'),
                        'quizname' => new external_value(PARAM_RAW, 'name of quiz'),
                        'courseid' => new external_value(PARAM_INT, 'courseid'),
                        'coursename' => new external_value(PARAM_ > RAW, 'coursename'),
                        'usefullnames' => new external_value(PARAM_INT, 'usefullnames'),
                        'showcorrectanswer' => new external_value(PARAM_INT, 'showcorrectanswer'),
                        'showcontinuebutton' => new external_value(PARAM_INT, 'showcontinuebutton'),
                        'countdown' => new external_value(PARAM_INT, 'countdown'),
                        'waitfornextquestion' => new external_value(PARAM_INT, 'waitfornextquestion'),
                        'isteacher' => new external_value(PARAM_INT, 'isteacher'),
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
                        'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'),
                                'Array of course ids', VALUE_DEFAULT, array()),
                        'timemodified' => new external_value(PARAM_INT, 'timemodified to reduce number of returned items',
                                VALUE_DEFAULT, -1),
                )
        );
    }

    /**
     * Get all the open or closed MooDuell Games. By providing a date, we can limit the treated entries...
     * .. to those which were upadted since the last glance we took.
     * An empty courseid will return all the games of all the MooDuell Instances visible to this user.
     *
     * @param $courseids
     * @param $timemodified
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_games_by_courses($courseids, $timemodified) {

        // We just call our function here to get all the quizzes.
        $returnedquizzes = self::get_quizzes_by_courses($courseids, $timemodified);

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
            // This function is used by the external webservice, here we only provide studentsview.
            // This means we only provide games where the active player is involved.
            $games = $mooduell->return_games_for_this_instance(true, $timemodified);

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
            } else {
                $quiz['games'] = array();
            }

            $returnedquizzes[] = $quiz;
        }

        $result = array();
        $result['quizzes'] = $returnedquizzes;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Get all the quizzes (Mooduell Instances) by courses
     *
     * @param $courseids
     * @param $timemodfied
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function get_quizzes_by_courses($courseids, $timemodfied) {
        $warnings = array();
        $returnedquizzes = array();

        $params = array(
                'courseids' => $courseids,
                'timemodified' => $timemodfied
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

            // If there are not MooDuell Instances at all in the courses we wanted, we return an exception.
            if (count($quizzes) == 0) {
                throw new moodle_exception('nomooduellincourses ', 'mooduell', null, null,
                        "There are no MooDuell instances in the courses you were looking in");
            }

            foreach ($quizzes as $quiz) {
                $context = context_module::instance($quiz->coursemodule);
                $course = get_course($quiz->course);

                // Entry to return.
                $quizdetails = array();
                // First, we return information that any user can see in the web interface.
                $quizdetails['quizid'] = $quiz->coursemodule;
                $quizdetails['quizname'] = 'testname';
                $quizdetails['usefullnames'] = $quiz->usefullnames;
                $quizdetails['showcontinuebutton'] = $quiz->showcontinuebutton;
                $quizdetails['showcorrectanswer'] = $quiz->showcorrectanswer;
                $quizdetails['countdown'] = $quiz->countdown;
                $quizdetails['waitfornextquestion'] = $quiz->waitfornextquestion;
                $quizdetails['courseid'] = $quiz->course;
                $quizdetails['coursename'] = $course->fullname;
                $quizdetails['coursemodule'] = $quiz->coursemodule;
                $quizdetails['quizname'] = external_format_string($quiz->name, $context->id);

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
     * @return external_function_parameters
     */
    public static function get_quizzes_by_courses_parameters() {
        return new external_function_parameters(array(
                        'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'),
                                'Array of course ids', VALUE_DEFAULT, array()),
                        'timemodified' => new external_value(PARAM_INT, 'timemodified to reduce number of returned items',
                                VALUE_DEFAULT, -1),
                )
        );
    }

    /**
     * @return external_single_structure
     */
    public static function get_games_by_courses_returns() {
        return new external_single_structure(array(
                'quizzes' => new external_multiple_structure(new external_single_structure(array(
                        'quizid' => new external_value(PARAM_INT, 'id of coursemodule'),
                        'quizname' => new external_value(PARAM_RAW, 'name of quiz'),
                        'courseid' => new external_value(PARAM_INT, 'courseid'),
                        'coursename' => new external_value(PARAM_RAW, 'coursename'),
                        'usefullnames' => new external_value(PARAM_INT, 'usefullnames'),
                        'showcorrectanswer' => new external_value(PARAM_INT, 'showcorrectanswer'),
                        'showcontinuebutton' => new external_value(PARAM_INT, 'showcontinuebutton'),
                        'countdown' => new external_value(PARAM_INT, 'countdown'),
                        'waitfornextquestion' => new external_value(PARAM_INT, 'waitfornextquestion'),
                        'isteacher' => new external_value(PARAM_INT, 'isteacher'),
                        'games' => new external_multiple_structure(new external_single_structure(array(
                                'gameid' => new external_value(PARAM_INT, 'id of game'),
                                'playeraid' => new external_value(PARAM_INT, 'id of player A'),
                                'playerbid' => new external_value(PARAM_INT, 'id of player B'),
                                'playeratime' => new external_value(PARAM_INT, 'time of player B'),
                                'playerbtime' => new external_value(PARAM_INT, 'time of player B'),
                                'status' => new external_value(PARAM_INT,
                                        'status, NULL is open game, 1 is player A\'s turn, 2 is player B\'s turn, 3 is finished'),
                                'winnerid' => new external_value(PARAM_INT, 'id of winner, 0 is not yet finished'),
                                'timemodified' => new external_value(PARAM_INT, 'time modified')
                        )))
                )))
        ));
    }

    /**
     * Return array of quiz data
     *
     * @param $courseid
     * @param $quizid
     * @param $gameid
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function get_game_data($courseid, $quizid, $gameid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid,
                'gameid' => $gameid
        );

        $params = self::validate_parameters(self::get_game_data_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'quiz', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create Mooduell Instance.
        $mooduell = new mooduell($params['quizid']);

        // We create the game_controller Instance.
        $gamecontroller = new game_control($mooduell, $params['gameid']);

        // We can now retrieve the questions and add them to our gamedata
        return $gamecontroller->get_questions();
    }

    /**
     * Describes the parameters for get_game_data.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_game_data_parameters() {
        return new external_function_parameters(array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'gameid' => new external_value(PARAM_INT, 'gameid id')
        ));
    }

    /**
     * @return external_single_structure
     */
    public static function get_game_data_returns() {
        return new external_single_structure(array(
                        'mooduellid' => new external_value(PARAM_INT, 'mooduellid'),
                        'gameid' => new external_value(PARAM_INT, 'gameid'),
                        'playeraid' => new external_value(PARAM_INT, 'player A id'),
                        'playerbid' => new external_value(PARAM_INT, 'player B id'),
                        'winnerid' => new external_value(PARAM_INT, 'winner id'),
                        'status' => new external_value(PARAM_INT, 'status'),
                        'questions' => new external_multiple_structure(new external_single_structure(array(
                                                'questionid' => new external_value(PARAM_INT, 'questionid'),
                                                'questiontext' => new external_value(PARAM_RAW, 'question text'),
                                                'questiontype' => new external_value(PARAM_RAW, 'qtype'),
                                                'category' => new external_value(PARAM_INT, 'category'),
                                                'playeraanswered' => new external_value(PARAM_INT, 'answer player a'),
                                                'playerbanswered' => new external_value(PARAM_INT, 'answer player a'),
                                                'imageurl' => new external_value(PARAM_RAW, 'image URL'),
                                                'answers' => new external_multiple_structure(new external_single_structure(array(
                                                                        'id' => new external_value(PARAM_INT, 'answerid'),
                                                                        'answertext' => new external_value(PARAM_RAW,
                                                                                'answer text'),
                                                                )
                                                        )
                                                )
                                        )
                                )
                        )
                )
        );
    }

    /**
     * Return array of quiz data
     * @param $courseid
     * @param $quizid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function get_quiz_users($courseid, $quizid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid
        );

        $params = self::validate_parameters(self::get_quiz_users_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'quiz', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create Mooduell Instance.
        $mooduell = new mooduell($params['quizid']);

        // We can now retrieve the questions and add them to our gamedata
        return game_control::return_users_for_game($mooduell);
    }

    /**
     * Describes the parameters for get_quiz_users.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_quiz_users_parameters() {
        return new external_function_parameters(array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id')
        ));
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_quiz_users_returns() {
        return new external_multiple_structure(new external_single_structure(array(
                                'id' => new external_value(PARAM_INT, 'userid'),
                                'firstname' => new external_value(PARAM_RAW, 'firstname'),
                                'lastname' => new external_value(PARAM_RAW, 'lastname'),
                                'username' => new external_value(PARAM_RAW, 'username'),
                                'alternatename' => new external_value(PARAM_RAW, 'nickname'),
                                'lang' => new external_value(PARAM_RAW, 'lastname'),
                                'profileimageurl' => new external_value(PARAM_RAW, 'profileimageurl')
                        )
                )
        );
    }

    /**
     * @param $userid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function get_user_stats($userid) {
        $params = array(
                'userid' => $userid
        );

        $params = self::validate_parameters(self::get_user_stats_parameters(), $params);

        return game_control::get_user_stats($params['userid']);

    }

    /**
     * Describes the parameters for get_user_stats
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_user_stats_parameters() {
        return new external_function_parameters(array(
                'userid' => new external_value(PARAM_INT, 'user id')
        ));
    }

    /**
     * @return external_single_structure
     */
    public static function get_user_stats_returns() {
        return new external_single_structure(array(
                        'userid' => new external_value(PARAM_INT, 'userid'),
                        'playedgames' => new external_value(PARAM_INT, 'playedgames'),
                        'wongames' => new external_value(PARAM_INT, 'wongames'),
                        'nemesisuserid' => new external_value(PARAM_INT, 'nemesisuserid')
                )
        );
    }

    /**
     * @param $quizid
     * @return mixed
     * @throws invalid_parameter_exception
     */
    public static function get_highscores($quizid) {
        $params = array(
                'quizid' => $quizid
        );

        $params = self::validate_parameters(self::get_highscores_parameters(), $params);

        return mooduell::get_highscores($params['quizid']);

    }

    /**
     * @return external_function_parameters
     */
    public static function get_highscores_parameters() {
        return new external_function_parameters(array(
                'quizid' => new external_value(PARAM_INT, 'quiz id')
        ));
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_highscores_returns() {
        return new external_multiple_structure(new external_single_structure(array(
                                'userid' => new external_value(PARAM_INT, 'userid'),
                                'won' => new external_value(PARAM_INT, 'won'), // games won
                                'lost' => new external_value(PARAM_INT, 'lost'), // games lost
                                'score' => new external_value(PARAM_INT, 'firstname'), // games played
                                'played' => new external_value(PARAM_INT, 'played')
                        )
                )
        );
    }
}
