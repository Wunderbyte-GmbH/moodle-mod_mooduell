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
 * Moodle external API
 *
 * @package mod_mooduell
 * @category external
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mooduell\game_control;
use mod_mooduell\mooduell;
use mod_mooduell\completion\custom_completion;
use mod_mooduell\completion\completion_utils;

defined('MOODLE_INTERNAL') || die();

require_once('mooduell.php');

/**
 * Class mod_mooduell_external
 */
class mod_mooduell_external extends external_api {

    /**
     * Starts new game against another user.
     * @param int $courseid
     * @param int $quizid
     * @param int $playerbid
     * @return false|mixed|stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function start_attempt(int $courseid, int $quizid, int $playerbid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid,
                'playerbid' => $playerbid
        );

        $params = self::validate_parameters(self::start_attempt_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'mooduell', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create Mooduell Instance.
        $mooduell = new mooduell($params['quizid']);

        // We create the game_controller Instance.
        $gamecontroller = new game_control($mooduell);

        // We create a new game: Save parameters to DB & trigger notification event.
        $startgameresult = $gamecontroller->start_new_game($params['playerbid']);

        $result = array();
        $result['status'] = $startgameresult;

        // Add challenges array with completion data to $startgameresult.
        $startgameresult->challenges = completion_utils::get_completion_challenges_array($mooduell);

        return $startgameresult;
    }

    /**
     * Defines the parameters for start_attempt.
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
     * Defines the return values for start_attempt.
     * @return external_single_structure
     */
    public static function start_attempt_returns() {
        return self::get_game_data_returns();
    }

    /**
     * We answer a question with the array of ids of the answers. Depending on the internal setting of the MooDuell Instance...
     * ... we might either retrieve an array of the correct answer-ids ...
     * ... or an array of with one value 0 for incorrect and 1 for correctly answered.
     * @param int $quizid
     * @param int $gameid
     * @param int $questionid
     * @param array $answerids IDs of answers given to single or multiple choice questions.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function answer_question(int $quizid, int $gameid, int $questionid, array $answerids = []) {
        global $DB;

        $params = array(
                'quizid' => $quizid,
                'gameid' => $gameid,
                'questionid' => $questionid,
                'answerids' => $answerids
        );

        $params = self::validate_parameters(self::answer_question_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'mooduell', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create the MooDuell instance.
        $mooduell = new mooduell($params['quizid']);

        // We create the game_controller instance.
        $gamecontroller = new game_control($mooduell, $params['gameid']);

        // Validate the question.
        list ($response, $iscorrect, $answersfeedback) =
            $gamecontroller->validate_question($params['questionid'], $params['answerids']);
        $result['response'] = $response;
        $result['iscorrect'] = $iscorrect;

        // Answer-specific feedback and param to show or not show it.
        if (!empty($answersfeedback)) {
            $result['showanswersfeedback'] = $mooduell->settings->showanswersfeedback;

            if ($mooduell->settings->showanswersfeedback == 1) {
                // We only transfer the feedback JSON if the setting is turned on.
                $result['answersfeedback'] = $answersfeedback;
            } else {
                $result['answersfeedback'] = [];
            }
        } else {
            $result['answersfeedback'] = [];
            // If there is no answer-specific feedback we always set the param to zero.
            $result['showanswersfeedback'] = 0;
        }

        // Get the general feedback and set the param to show or not show it.
        if ($generalfeedback = $DB->get_field('question', 'generalfeedback', ['id' => $questionid])) {
            $result['generalfeedback'] = strip_tags($generalfeedback);
            // Show (1) or don't show (0) general feedback depending on setting.
            $result['showgeneralfeedback'] = $mooduell->settings->showgeneralfeedback;
        } else {
            $result['generalfeedback'] = '';
            $result['showgeneralfeedback'] = 0;
        }

        $result['showgeneralfeedback'] = $mooduell->settings->showgeneralfeedback;

        return $result;
    }

    /**
     * Defines the paramters of answer_question.
     * @return external_function_parameters
     */
    public static function answer_question_parameters() {
        return new external_function_parameters(array(
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'gameid' => new external_value(PARAM_INT, 'gameid id'),
                'questionid' => new external_value(PARAM_INT, 'question id'),
                'answerids' => new external_multiple_structure(new external_value(PARAM_RAW, 'answer id'),
                        'Array of answer ids')
        ));
    }

    /**
     * Defines the return value of answer_question.
     * @return external_single_structure
     */
    public static function answer_question_returns() {
        return new external_single_structure(
            array(
                'response' => new external_multiple_structure(
                        // For numerical questions, it will contain the correct answer.
                        new external_value(PARAM_RAW, 'ids of correct answers, correct answer OR 0 if false, 1 if true')
                ),
                'iscorrect' => new external_value(PARAM_INT, '0 if false, 1 if true'),
                'generalfeedback' => new external_value(PARAM_TEXT, 'general feedback'),
                'showgeneralfeedback' => new external_value(PARAM_INT, '0 if false, 1 if true'),
                'answersfeedback' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            "answerid" => new external_value(PARAM_RAW, 'answer id'),
                            "answertext" => new external_value(PARAM_RAW, 'answer text'),
                            "feedback" => new external_value(PARAM_RAW, 'answer-specific feedback')
                        )
                    )
                ),
                'showanswersfeedback' => new external_value(PARAM_INT, '0 if false, 1 if true'),
            )
        );
    }

    /**
     * Get all the open or closed MooDuell Games. By providing a date, we can limit the treated entries...
     * ... to those which were upadted since the last glance we took.
     * An empty courseid will return all the games of all the MooDuell Instances visible to this user.
     *
     * @param array $courseids
     * @param int $timemodified
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_games_by_courses(array $courseids, int $timemodified) {

        $context = context_system::instance();
        self::validate_context($context);

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
            $games = $mooduell->return_games_for_this_instance(true, null, $timemodified);

            $quiz['challenges'] = completion_utils::get_completion_challenges_array($mooduell);

            if ($games && count($games) > 0) {

                foreach ($games as $game) {

                    $quiz['games'][] = [
                            'gameid' => $game->id,
                            'playeraid' => $game->playeraid,
                            'playerbid' => $game->playerbid,
                            'playeratime' => $game->playeratime,
                            'playerbtime' => $game->playerbtime,
                            'winnerid' => $game->winnerid,
                            'status' => $game->status,
                            'timecreated' => $game->timecreated,
                            'timemodified' => $game->timemodified
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
     * Describes the parameters for get_games_by_courses.
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
     * Describes the values returned by get_games_by_courses.
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
                        'showgeneralfeedback' => new external_value(PARAM_INT, 'showgeneralfeedback'),
                        'showanswersfeedback' => new external_value(PARAM_INT, 'showanswersfeedback'),
                        'countdown' => new external_value(PARAM_INT, 'countdown'),
                        'waitfornextquestion' => new external_value(PARAM_INT, 'waitfornextquestion'),
                        'isteacher' => new external_value(PARAM_INT, 'isteacher'),
                        'challenges' => new external_multiple_structure(new external_single_structure(array(
                                                'challengename' => new external_value(PARAM_TEXT, 'challenge name'),
                                                'challengetype' => new external_value(PARAM_TEXT, 'challenge type'),
                                                'actualnumber' => new external_value(PARAM_INT, 'actual number'),
                                                'targetnumber' => new external_value(PARAM_INT, 'target number'),
                                                'challengepercentage' => new external_value(PARAM_INT, 'challenge percentage'),
                                                'targetdate' => new external_value(PARAM_INT, 'unix timestamp of expected completion date'),
                                                'challengerank' => new external_value(PARAM_INT, 'a user\'s ranking within a challenge')
                                        )
                                )
                        ),
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
     * Get all the quizzes (Mooduell Instances) by courses.
     *
     * @param array $courseids
     * @param int $timemodfied
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function get_quizzes_by_courses(array $courseids, int $timemodfied) {
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

            list ($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses, false, true);

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
                $quizdetails['showgeneralfeedback'] = $quiz->showgeneralfeedback;
                $quizdetails['showanswersfeedback'] = $quiz->showanswersfeedback;
                $quizdetails['countdown'] = $quiz->countdown;
                $quizdetails['waitfornextquestion'] = $quiz->waitfornextquestion;
                $quizdetails['courseid'] = $quiz->course;
                $quizdetails['coursename'] = $course->fullname;
                $quizdetails['coursemodule'] = $quiz->coursemodule;
                $quizdetails['quizname'] = external_format_string($quiz->name, $context->id);

                if (has_capability('mod/quiz:view', $context)) {

                    // Fields only for managers.
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        // We could do something here.
                        $quizdetails['isteacher'] = 1;
                    } else {
                        $quizdetails['isteacher'] = 0;
                    }
                } else {
                    $quizdetails['isteacher'] = 0;
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
     * Defines the parameters for get_quizzes_by_courses.
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
     * Defines the return value of get_quizzes_by_courses.
     * @return external_single_structure
     */
    public static function get_quizzes_by_courses_returns() {
        return new external_single_structure(array(
                'quizzes' => new external_multiple_structure(new external_single_structure(array(
                        'quizid' => new external_value(PARAM_INT, 'id of coursemodule'),
                        'quizname' => new external_value(PARAM_RAW, 'name of quiz'),
                        'courseid' => new external_value(PARAM_INT, 'courseid'),
                        'coursename' => new external_value(PARAM_RAW, 'coursename'),
                        'usefullnames' => new external_value(PARAM_INT, 'usefullnames'),
                        'showcorrectanswer' => new external_value(PARAM_INT, 'showcorrectanswer'),
                        'showcontinuebutton' => new external_value(PARAM_INT, 'showcontinuebutton'),
                        'showgeneralfeedback' => new external_value(PARAM_INT, 'showgeneralfeedback'),
                        'showanswersfeedback' => new external_value(PARAM_INT, 'showanswersfeedback'),
                        'countdown' => new external_value(PARAM_INT, 'countdown'),
                        'waitfornextquestion' => new external_value(PARAM_INT, 'waitfornextquestion'),
                        'isteacher' => new external_value(PARAM_INT, 'isteacher'),
                )))
        ));
    }

    /**
     * Returns data of active game.
     *
     * @param int $courseid
     * @param int $quizid
     * @param int $gameid
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function get_game_data(int $courseid, int $quizid, int $gameid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid,
                'gameid' => $gameid
        );

        $params = self::validate_parameters(self::get_game_data_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'mooduell', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create the Mooduell instance.
        $mooduell = new mooduell($params['quizid']);

        // We create the game_controller Instance.
        $gamecontroller = new game_control($mooduell, $params['gameid']);

        // We can now retrieve the questions and add them to our gamedata.
        $gamedata = $gamecontroller->get_questions();

        // Add challenges JSON string with completion data to $gamedata.
        $gamedata->challenges = completion_utils::get_completion_challenges_array($mooduell);

        return $gamedata;
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
     * Describes the return values for get_game_data.
     * @return external_single_structure
     */
    public static function get_game_data_returns() {
        return new external_single_structure(array(
                        'mooduellid' => new external_value(PARAM_INT, 'mooduellid'),
                        'gameid' => new external_value(PARAM_INT, 'gameid'),
                        'playeraid' => new external_value(PARAM_INT, 'player A id'),
                        'playerbid' => new external_value(PARAM_INT, 'player B id'),
                        'winnerid' => new external_value(PARAM_INT, 'winner id'),
                        'timemodified' => new external_value(PARAM_INT, 'time modified'),
                        'status' => new external_value(PARAM_INT, 'status'),
                        'challenges' => new external_multiple_structure(new external_single_structure(array(
                                                'challengename' => new external_value(PARAM_TEXT, 'challenge name'),
                                                'challengetype' => new external_value(PARAM_TEXT, 'challenge type'),
                                                'actualnumber' => new external_value(PARAM_INT, 'actual number'),
                                                'targetnumber' => new external_value(PARAM_INT, 'target number'),
                                                'challengepercentage' => new external_value(PARAM_INT, 'challenge percentage'),
                                                'targetdate' => new external_value(PARAM_INT, 'unix timestamp of expected completion date'),
                                                'challengerank' => new external_value(PARAM_INT, 'a user\'s ranking within a challenge')
                                        )
                                )
                        ),
                        'questions' => new external_multiple_structure(new external_single_structure(array(
                                                'questionid' => new external_value(PARAM_INT, 'questionid'),
                                                'questiontext' => new external_value(PARAM_RAW, 'question text'),
                                                'questiontype' => new external_value(PARAM_RAW, 'qtype'),
                                                'category' => new external_value(PARAM_INT, 'category'),
                                                'playeraanswered' => new external_value(PARAM_INT, 'answer player a'),
                                                'playerbanswered' => new external_value(PARAM_INT, 'answer player a'),
                                                'imageurl' => new external_value(PARAM_RAW, 'image URL'),
                                                'imagetext' => new external_value(PARAM_RAW, 'image Text'),
                                                'answers' => new external_multiple_structure(new external_single_structure(array(
                                                                        'id' => new external_value(PARAM_INT, 'answerid'),
                                                                        'answertext' => new external_value(PARAM_RAW,
                                                                                'answer text')
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
     * Returns available users for quiz.
     * @param int $courseid
     * @param int $quizid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function get_quiz_users(int $courseid, int $quizid) {
        $params = array(
                'courseid' => $courseid,
                'quizid' => $quizid
        );

        $params = self::validate_parameters(self::get_quiz_users_parameters(), $params);

        // Now security checks.

        if (!$cm = get_coursemodule_from_id('mooduell', $params['quizid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['quizid'], 'mooduell', null, null,
                    "Course module id:" . $params['quizid']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We create Mooduell Instance.
        $mooduell = new mooduell($params['quizid']);

        // We can now retrieve the questions and add them to our gamedata.
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
     * Describes the return values for get_quiz_users.
     * @return external_multiple_structure
     */
    public static function get_quiz_users_returns() {
        return new external_multiple_structure(new external_single_structure(array(
                                'id' => new external_value(PARAM_INT, 'userid'),
                                'firstname' => new external_value(PARAM_RAW, 'firstname'),
                                'lastname' => new external_value(PARAM_RAW, 'lastname'),
                                'username' => new external_value(PARAM_RAW, 'username'),
                                'alternatename' => new external_value(PARAM_RAW,
                                        'nickname, stored as custom profile filed mooduell_alias'),
                                'lang' => new external_value(PARAM_RAW, 'language'),
                                'profileimageurl' => new external_value(PARAM_RAW, 'profileimageurl')
                        )
                )
        );
    }

    /**
     * Set alternate name of user.
     * Actually, this doesn't save to alternatename but to the user profile filed "mooduell_alias".
     * @param int $userid
     * @param string $alternatename
     * @return bool
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function set_alternatename(int $userid, string $alternatename) {

        global $CFG;

        require_once("$CFG->dirroot/user/profile/lib.php");

        $params = array(
                'userid' => $userid,
                'alternatename' => $alternatename
        );

        $params = self::validate_parameters(self::set_alternatename_parameters(), $params);

        global $USER;

        // Every user can only set his/her own name.
        if ($params['userid'] != $USER->id) {
            throw new moodle_exception('norighttosetnameofthisuser ' . $params['userid'], 'mooduell', null, null,
                    "Course module id:" . $params['quizid']);
        }

        $newuser = $USER;

        $newuser->profile_field_mooduell_alias = core_user::clean_field($params['alternatename'], 'alternatename');

        profile_save_data($newuser);

        return array('status' => 1);
    }


    /**
     * Describes the parameters for set_alternatename.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function set_alternatename_parameters() {
        return new external_function_parameters(array(
                'userid' => new external_value(PARAM_INT, 'user id'),
                'alternatename' => new external_value(PARAM_RAW, 'alternate name')
        ));
    }

    /**
     * Describes the return value for set_alternatename.
     * @return external_single_structure
     */
    public static function set_alternatename_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }

    /**
     * Retrieves some stats about the user.
     * @param int $userid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function get_user_stats(int $userid) {
        $params = array(
                'userid' => $userid
        );

        $params = self::validate_parameters(self::get_user_stats_parameters(), $params);

        return game_control::get_user_stats($params['userid']);

    }

    /**
     * Describes the parameters for get_user_stats.
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
     * Describes the return values for get_user_stats.
     * @return external_single_structure
     */
    public static function get_user_stats_returns() {
        return new external_single_structure(array(
                        'playedgames' => new external_value(PARAM_INT, 'playedgames'),
                        'wongames' => new external_value(PARAM_INT, 'wongames'),
                        'lostgames' => new external_value(PARAM_INT, 'lostgames'),
                        'correctlyanswered' => new external_value(PARAM_INT, 'correctlyanswered'),
                        'playedquestions' => new external_value(PARAM_INT, 'playedquestions')
                )
        );
    }

    /**
     * Retrieves the highscore list for a given quiz.
     * @param int $quizid
     * @return mixed
     * @throws invalid_parameter_exception
     */
    public static function get_highscores(int $quizid) {

        $params = array(
                'quizid' => $quizid
        );

        $params = self::validate_parameters(self::get_highscores_parameters(), $params);

        return mooduell::get_highscores(null, $params['quizid']);
    }

    /**
     * Describes the parameters of get_highscores.
     * @return external_function_parameters
     */
    public static function get_highscores_parameters() {
        return new external_function_parameters(array(
                'quizid' => new external_value(PARAM_INT, 'quiz id')
        ));
    }

    /**
     * Describes the return values of get_highscores.
     * @return external_multiple_structure
     */
    public static function get_highscores_returns() {
        return new external_multiple_structure(new external_single_structure(array(
                                'quizid' => new external_value(PARAM_INT, 'quizid'),
                                'userid' => new external_value(PARAM_INT, 'userid'),
                                'won' => new external_value(PARAM_INT, 'won'), // Games won.
                                'lost' => new external_value(PARAM_INT, 'lost'), // Games lost.
                                'score' => new external_value(PARAM_INT, 'firstname'),
                                'played' => new external_value(PARAM_INT, 'played') // Games played.
                        )
                )
        );
    }

    /**
     * Returns the availalbe pushtokens for a given user.
     * @param int $userid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_pushtokens(int $userid) {

        global $DB, $USER;

        $params = array(
                'userid' => $userid
        );

        $params = self::validate_parameters(self::get_pushtokens_parameters(), $params);

        $activeuserid = $USER->id;

        // We only allow to set a pushToken for another user, if there is an active game going on.
        $data = $DB->get_records_sql('SELECT * FROM {mooduell_games}
            WHERE (playeraid = ' . $userid . ' OR playerbid =' . $userid . ')
            AND (playeraid = ' . $activeuserid . ' OR playerbid =' . $activeuserid . ')
            AND status != 3');

        if (!$data || count($data) == 0) {
            throw new moodle_exception('cantgetpushtoken', 'mooduell', null, null,
                    "You can't get pushtoken of this user " . $params['userid']);
        }

        return mooduell::get_pushtokens($params['userid']);

    }

    /**
     * Describes the parameters of get_pushtokens.
     * @return external_function_parameters
     */
    public static function get_pushtokens_parameters() {
        return new external_function_parameters(array(
                'userid' => new external_value(PARAM_INT, 'user id')
        ));
    }

    /**
     * Describes the return value of get_pushtokens.
     * @return external_multiple_structure
     */
    public static function get_pushtokens_returns() {
        return new external_single_structure(array(
                        'userid' => new external_value(PARAM_INT, 'quizid'),
                        'pushtokens' => new external_multiple_structure(new external_single_structure(array(
                                                'identifier' => new external_value(PARAM_RAW, 'identifier'),
                                                'model' => new external_value(PARAM_RAW, 'model'),
                                                'pushtoken' => new external_value(PARAM_RAW, 'pushtoken'))
                                )
                        )
                )
        );
    }

    /**
     * Sets the pushtokens for a user.
     * @param int $userid
     * @param string $identifier
     * @param string $model
     * @param string $pushtoken
     * @return int[]
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function set_pushtokens(int $userid, string $identifier, string $model, string $pushtoken) {

        global $DB, $USER;

        $params = array(
                'userid' => $userid,
                'identifier' => $identifier,
                'model' => $model,
                'pushtoken' => $pushtoken,
        );

        $params = self::validate_parameters(self::set_pushtokens_parameters(), $params);

        $activeuserid = $USER->id;

        if ($activeuserid != $params['userid']) {
            // We only allow to set a pushToken for another user, if there is an active game going on.
            $data = $DB->get_records_sql('SELECT * FROM {mooduell_games}
            WHERE (playeraid = ' . $userid . ' OR playerbid =' . $userid . ')
            AND (playeraid = ' . $activeuserid . ' OR playerbid =' . $activeuserid . ')
            AND status != 3');

            if (!$data || count($data) == 0) {
                throw new moodle_exception('cantsetpushtoken', 'mooduell', null, null,
                        "You can't set pushtoken of this user " . $params['userid']);
            }
        }

        return mooduell::set_pushtoken($params['userid'], $params['identifier'], $params['model'], $params['pushtoken']);
    }

    /**
     * Describes the paramters for set_pushtokens.
     * @return external_function_parameters
     */
    public static function set_pushtokens_parameters() {
        return new external_function_parameters(array(
                        'userid' => new external_value(PARAM_INT, 'user id'),
                        'model' => new external_value(PARAM_RAW, 'identifier'),
                        'identifier' => new external_value(PARAM_RAW, 'model'),
                        'pushtoken' => new external_value(PARAM_RAW, 'pushtoken')
                )
        );
    }

    /**
     * Describes the return value for set_pushtokens.
     * @return external_multiple_structure
     */
    public static function set_pushtokens_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }

    /**
     * Allows a user to give up a game.
     * @param int $gameid
     * @return mixed
     * @throws invalid_parameter_exception
     */
    public static function giveup_game(int $gameid) {

        global $DB, $USER;

        $params = array(
                'gameid' => $gameid,
        );

        $params = self::validate_parameters(self::giveup_game_parameters(), $params);

        $entry = $DB->get_record('mooduell_games', array('id' => $params['gameid']));

        if ($entry) {
            // Player A gives up.
            if ($entry->playeraid === $USER->id) {
                // ... so player B is the winner.
                $entry->winnerid = $entry->playerbid;
                // Set 9 played questions for player A so the percentage ...
                // ... of correct answers will be calculated correctly.
                $entry->playeraqplayed = 9;
            } else if ($entry->playerbid === $USER->id) {
                // Player B gives up...
                // ...so player A is the winner.
                $entry->winnerid = $entry->playeraid;
                // Set 9 played questions for player B so the percentage...
                // ... of correct answers will be calculated correctly.
                $entry->playerbqplayed = 9;
            } else {
                return ['status' => 0];
            }
        } else {
            return ['status' => 0];
        }
        $entry->status = 3;

        $now = new DateTime("now", core_date::get_server_timezone_object());
        $entry->timemodified = $now->getTimestamp();

        $DB->update_record('mooduell_games', $entry);

        return ['status' => 1];
    }

    /**
     * Describes the paramters for giveup_game.
     * @return external_function_parameters
     */
    public static function giveup_game_parameters() {
        return new external_function_parameters(array(
                        'gameid' => new external_value(PARAM_INT, 'game id')
                )
        );
    }

    /**
     * Describes the return value for giveup_game.
     * @return external_single_structure
     */
    public static function giveup_game_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }
    /**
     * Updates the profile picture of a user.
     * @param string $filename
     * @param string $filecontent
     * @return int[]
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function update_profile_picture(string $filename, string $filecontent) {

        global $USER, $CFG, $DB;

        $fileinfo = self::validate_parameters(self::update_profile_picture_parameters(), array(
                'filename' => $filename, 'filecontent' => $filecontent));

        if (!isset($fileinfo['filecontent'])) {
            throw new moodle_exception('nofile');
        }
        // Saving file.
        $dir = make_temp_directory('wsupload').'/';

        if (empty($fileinfo['filename'])) {
            $filename = uniqid('wsupload', true).'_'.time().'.tmp';
        } else {
            $filename = $fileinfo['filename'];
        }

        if (file_exists($dir.$filename)) {
            $savedfilepath = $dir.uniqid('m').$filename;
        } else {
            $savedfilepath = $dir.$filename;
        }

        $fileinfo['filecontent'] = strtr($fileinfo['filecontent'], '._-', '+/=');

        file_put_contents($savedfilepath, base64_decode($fileinfo['filecontent']));

        require_once( $CFG->libdir . '/gdlib.php' );

        // Uppload avatar from the temporary file.
        $usericonid = process_new_icon( context_user::instance( $USER->id, MUST_EXIST ), 'user', 'icon', 0, $savedfilepath );
        // Specify icon id for the desired user with id $newuser->id (in our case).
        if ( $usericonid ) {
            $DB->set_field( 'user', 'picture', $usericonid, array( 'id' => $USER->id ) );
        }

        @chmod($savedfilepath, $CFG->filepermissions);
        unset($fileinfo['filecontent']);

        // Delete temporary files.
        unset( $savedfilepath );

        return ['status' => 1];
    }

    /**
     * Describes the paramters for update_profile_picture.
     * @return external_function_parameters
     */
    public static function update_profile_picture_parameters() {
        return new external_function_parameters(array(
                        'filename'  => new external_value(PARAM_FILE, 'file name'),
                        'filecontent' => new external_value(PARAM_TEXT, 'file content'),
                )
        );
    }


    /**
     * Describes the return value for update_profile_picture.
     * @return external_single_structure
     */
    public static function update_profile_picture_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }

    /**
     * Loads highscore data via ajax to display on the website.
     * @param int $quizid
     * @param int|null $pageid
     * @param string|null $tsort
     * @param string|null $thide
     * @param string|null $tshow
     * @param int|null $tdir
     * @param int|null $treset
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function load_highscore_data(int $quizid,
            $pageid = null,
            $tsort = null,
            $thide = null,
            $tshow = null,
            $tdir = null,
            $treset = null) {
        global $DB, $COURSE, $CFG;

        $params = array(
                'quizid' => $quizid,
                'pageid' => $pageid,
                'tsort' => $tsort,
                'thide' => $thide,
                'tshow' => $tshow,
                'tdir' => $tdir,
                'treset' => $treset
        );

        $params = self::validate_parameters(self::load_highscore_data_parameters(), $params);

        // We set the (optional) parameters for tablelib to fetch them.
        $_POST['page'] = $params['pageid'];
        $_POST['tsort'] = $params['tsort'];
        $_POST['thide'] = $params['thide'];
        $_POST['tshow'] = $params['tshow'];
        $_POST['tdir'] = $params['tdir'];
        $_POST['treset'] = $params['treset'];

        $_POST['action'] = 'highscores';
        $_POST['quizid'] = $params['quizid'];

        // Differentiate between teacher and student views.
        $context = context_course::instance($COURSE->id);
        $view = 'student';
        if (has_capability('moodle/course:manageactivities', $context)) {
            $view = 'teacher'; // Because of the capability to manage activities.
        }
        // Now we set the view parameter for tablelib to fetch it.
        $_POST['view'] = $view;

        ob_start();

        include("$CFG->dirroot/mod/mooduell/mooduell_table.php");

        $result['content'] = ob_get_clean();

        return $result;
    }

    /**
     * Describes the paramters for load_highscore_data.
     * @return external_function_parameters
     */
    public static function load_highscore_data_parameters() {
        return new external_function_parameters(array(
                        'quizid'  => new external_value(PARAM_INT, 'quizid'),
                        'pageid'  => new external_value(PARAM_INT, 'pageid', VALUE_OPTIONAL),
                        'tsort'   => new external_value(PARAM_RAW, 'sort value', VALUE_OPTIONAL),
                        'thide'   => new external_value(PARAM_RAW, 'hide value', VALUE_OPTIONAL),
                        'tshow'   => new external_value(PARAM_RAW, 'show value', VALUE_OPTIONAL),
                        'tdir'    => new external_value(PARAM_INT, 'dir value', VALUE_OPTIONAL),
                        'treset'  => new external_value(PARAM_INT, 'reset value', VALUE_OPTIONAL),
                )
        );
    }

    /**
     * Describes the return value for load_highscore_data.
     * @return external_multiple_structure
     */
    public static function load_highscore_data_returns() {
        return new external_single_structure(array(
                        'content' => new external_value(PARAM_RAW, 'content of table')
                )
        );
    }

    /**
     * Loads question data to display on the website.
     * @param int $quizid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function load_questions_data(int $quizid) {

        $params = array(
                'quizid' => $quizid,
        );

        $params = self::validate_parameters(self::load_questions_data_parameters(), $params);

        $mooduell = new mooduell($params['quizid']);

        return $mooduell->return_list_of_all_questions_in_quiz();
    }

    /**
     * Describes the paramters for load_question_data.
     * @return external_function_parameters
     */
    public static function load_questions_data_parameters() {
        return new external_function_parameters(array(
                        'quizid'  => new external_value(PARAM_FILE, 'quizid')
                )
        );
    }

    /**
     * Describes the return value for load_highscore_data.
     * @return external_multiple_structure
     */
    public static function load_questions_data_returns() {
        return new external_multiple_structure(new external_single_structure(array(
                                'questionid' => new external_value(PARAM_INT, 'question id'),
                                'imageurl' => new external_value(PARAM_RAW, 'iamgeurl'),
                                'imagetext' => new external_value(PARAM_RAW, 'iamgetext'),
                                'questiontext' => new external_value(PARAM_RAW, 'questiontext'),
                                'questiontype' => new external_value(PARAM_RAW, 'questiontype'),
                                'category' => new external_value(PARAM_RAW, 'category'),
                                'courseid' => new external_value(PARAM_INT, 'courseid'),
                                'status' => new external_value(PARAM_RAW, 'status'),
                                'warnings' => new external_multiple_structure(new external_single_structure(array(
                                                'message' => new external_value(PARAM_RAW, 'message'))
                                )),
                                'answers' => new external_multiple_structure(new external_single_structure(array(
                                                'answertext' => new external_value(PARAM_RAW, 'answertext'),
                                                'fraction' => new external_value(PARAM_RAW, 'fraction'))
                                ))
                        )
                )
        );
    }
    /**
     * Returns the list of open games to display on the website.
     * @param int $quizid
     * @param int|null $pageid
     * @param string|null $tsort
     * @param string|null $thide
     * @param string|null $tshow
     * @param int|null $tdir
     * @param int|null $treset
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function load_opengames_data(
            $quizid,
            $pageid = null,
            $tsort = null,
            $thide = null,
            $tshow = null,
            $tdir = null,
            $treset = null) {
        global $COURSE, $CFG;

        $params = array(
                'quizid' => $quizid,
                'pageid' => $pageid,
                'tsort' => $tsort,
                'thide' => $thide,
                'tshow' => $tshow,
                'tdir' => $tdir,
                'treset' => $treset
        );

        $params = self::validate_parameters(self::load_opengames_data_parameters(), $params);

        // We set the (optional) parameters for tablelib to fetch them.
        $_POST['page'] = $params['pageid'];
        $_POST['tsort'] = $params['tsort'];
        $_POST['thide'] = $params['thide'];
        $_POST['tshow'] = $params['tshow'];
        $_POST['tdir'] = $params['tdir'];
        $_POST['treset'] = $params['treset'];

        $_POST['action'] = 'opengames';
        $_POST['quizid'] = $params['quizid'];

        // Differentiate between teacher and student views.
        $context = context_course::instance($COURSE->id);
        $view = 'student'; // Default.
        if (has_capability('moodle/course:manageactivities', $context)) {
            $view = 'teacher'; // Because of the capability to manage activities.
        }
        // Now we set the view parameter for tablelib to fetch it.
        $_POST['view'] = $view;

        ob_start();

        include("$CFG->dirroot/mod/mooduell/mooduell_table.php");

        $result['content'] = ob_get_clean();

        return $result;
    }

    /**
     * Describes the paramters for load_opengames_data.
     * @return external_function_parameters
     */
    public static function load_opengames_data_parameters() {
        return new external_function_parameters(array(
                        'quizid'  => new external_value(PARAM_INT, 'quizid'),
                        'pageid'  => new external_value(PARAM_INT, 'pageid', VALUE_OPTIONAL),
                        'tsort'   => new external_value(PARAM_RAW, 'sort value', VALUE_OPTIONAL),
                        'thide'   => new external_value(PARAM_RAW, 'hide value', VALUE_OPTIONAL),
                        'tshow'   => new external_value(PARAM_RAW, 'show value', VALUE_OPTIONAL),
                        'tdir'    => new external_value(PARAM_INT, 'dir value', VALUE_OPTIONAL),
                        'treset'  => new external_value(PARAM_INT, 'reset value', VALUE_OPTIONAL),
                )
        );
    }

    /**
     * Describes the return value for load_question_data.
     * @return external_multiple_structure
     */
    public static function load_opengames_data_returns() {
        return new external_single_structure(array(
                        'content' => new external_value(PARAM_RAW, 'content of table')
                )
        );
    }

    /**
     * Returns the list of finished games to display on the website.
     * @param int $quizid
     * @param int|null $pageid
     * @param string|null $tsort
     * @param string|null $thide
     * @param string|null $tshow
     * @param int|null $tdir
     * @param int|null $treset
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function load_finishedgames_data(int $quizid,
            $pageid = null,
            $tsort = null,
            $thide = null,
            $tshow = null,
            $tdir = null,
            $treset = null) {
        global $COURSE, $CFG;

        $params = array(
                'quizid' => $quizid,
                'pageid' => $pageid,
                'tsort' => $tsort,
                'thide' => $thide,
                'tshow' => $tshow,
                'tdir' => $tdir,
                'treset' => $treset
        );

        $params = self::validate_parameters(self::load_finishedgames_data_parameters(), $params);

        // We set the (optional) parameters for tablelib to fetch them.
        $_POST['page'] = $params['pageid'];
        $_POST['tsort'] = $params['tsort'];
        $_POST['thide'] = $params['thide'];
        $_POST['tshow'] = $params['tshow'];
        $_POST['tdir'] = $params['tdir'];
        $_POST['treset'] = $params['treset'];

        $_POST['action'] = 'finishedgames';
        $_POST['quizid'] = $params['quizid'];

        // Differentiate between teacher and student views.
        $context = context_course::instance($COURSE->id);
        $view = 'student'; // Default.
        if (has_capability('moodle/course:manageactivities', $context)) {
            $view = 'teacher'; // Because of the capability to manage activities.
        }
        // Now we set the view parameter for tablelib to fetch it.
        $_POST['view'] = $view;

        ob_start();

        include("$CFG->dirroot/mod/mooduell/mooduell_table.php");

        $result['content'] = ob_get_clean();

        return $result;
    }

    /**
     * Describes the paramters for load_finishedgames_data.
     * @return external_function_parameters
     */
    public static function load_finishedgames_data_parameters() {
        return new external_function_parameters(array(
                        'quizid'  => new external_value(PARAM_INT, 'quizid'),
                        'pageid'  => new external_value(PARAM_INT, 'pageid', VALUE_OPTIONAL),
                        'tsort'   => new external_value(PARAM_RAW, 'sort value', VALUE_OPTIONAL),
                        'thide'   => new external_value(PARAM_RAW, 'hide value', VALUE_OPTIONAL),
                        'tshow'   => new external_value(PARAM_RAW, 'show value', VALUE_OPTIONAL),
                        'tdir'    => new external_value(PARAM_INT, 'dir value', VALUE_OPTIONAL),
                        'treset'  => new external_value(PARAM_INT, 'reset value', VALUE_OPTIONAL),
                )
        );
    }

    /**
     * Describes the return values for load_finished_data.
     * @return external_multiple_structure
     */
    public static function load_finishedgames_data_returns() {
        return new external_single_structure(array(
                    'content' => new external_value(PARAM_RAW, 'html content')
                )
        );
    }

}
