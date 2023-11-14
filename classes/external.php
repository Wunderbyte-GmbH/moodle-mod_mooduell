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
use mod_mooduell\manage_tokens;
use mod_mooduell\mooduell;
use mod_mooduell\completion\completion_utils;

defined('MOODLE_INTERNAL') || die();

require_once('mooduell.php');


/**
 * Class mod_mooduell_external
 */
class mod_mooduell_external extends external_api {

    /**
     * Starts new game against another user.
     *
     * @param  int $courseid
     * @param  int $quizid
     * @param  int $playerbid
     * @return mixed
     */
    public static function start_attempt(int $courseid, int $quizid, int $playerbid) {
        $params = [
                'courseid' => $courseid,
                'quizid' => $quizid,
                'playerbid' => $playerbid,
        ];

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

        $result = [];
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
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'playerbid' => new external_value(PARAM_INT, 'player B id'),
        ]);
    }

    /**
     * Defines the return values for start_attempt.
     * @return external_single_structure
     */
    public static function start_attempt_returns() {
        return self::get_game_data_returns();
    }

    /**
     * Deletes a single purchase with an id as input
     *
     * @param int $itemid
     * @return void
     */
    public static function delete_iapurchases(int $itemid) {
        global $DB, $USER;
        if ($DB->record_exists('mooduell_purchase', ['id' => $itemid])) {
            $DB->delete_records('mooduell_purchase', ['id' => $itemid]);
            $returnarray['status'] = 1;
        } else {
            $returnarray['status'] = 0;
        }
        return $returnarray;
    }

    /**
     * Define return of iapurchases
     *
     * @return void
     */
    public static function delete_iapurchases_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
        ]);
    }

    /**
     * Defines paramters for deleting iapurchases
     *
     * @return void
     */
    public static function delete_iapurchases_parameters() {
        return new external_function_parameters(['itemid' => new external_value(PARAM_INT, 'itemid')]);
    }


    /**
     * Returns external web token while using QR webservice token
     */
    public static function get_usertoken() {
        global $USER;
        $params = [];
        self::validate_parameters(self::get_usertoken_parameters(), $params);

        // Returns mooduell_external token and delete mooduell_tokens token.
        $tokenobject = manage_tokens::generate_token_for_user($USER->id, 'mod_mooduell_external', 0);
        manage_tokens::delete_user_token('mod_mooduell_tokens');
        $token = $tokenobject->token;
        $return['token'] = $token;

        return $return;
    }

    /**
     * Defines return structure for get_ustertoken()
     *
     * @return external_single_structure
     */
    public static function get_usertoken_returns() {
        return new external_single_structure([
            'token' => new external_value(PARAM_RAW, 'token'),
        ]);
    }

    /**
     * Defines parameters for get_ustertoken()
     *
     */
    public static function get_usertoken_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Returns all courses for user with capabilities
     *
     * @return array
     */
    public static function get_courses_with_caps() {
        global $USER;
        $userid = $USER->id;
        self::validate_parameters(self::get_courses_with_caps_parameters(), []);
        $allcourses = enrol_get_users_courses($userid);
        $capcourses = [];
        foreach ($allcourses as $course) {
            $context = context_course::instance($course->id);
            $hascaps = has_capability('mod/mooduell:canpurchase', $context);
            if ($hascaps) {
                $item = [
                    'courseid' => $course->id,
                    'coursename' => $course->fullname,
                ];
                $capcourses[] = $item;
            }
        }

        $return['courses'] = $capcourses;

        return $return;
    }

    /**
     * Get the courses with caps returns
     *
     * @return external_single_structure
     */
    public static function get_courses_with_caps_returns() {
            return new external_single_structure([
                    'courses' => new external_multiple_structure(new external_single_structure([
                            'courseid' => new external_value(PARAM_INT, 'id of course'),
                            'coursename' => new external_value(PARAM_TEXT, 'name of course'),
                    ])),
            ]);
    }

    /**
     * Defines params structure for get_courses_with_caps()
     *
     * @return external_function_parameters
     */
    public static function get_courses_with_caps_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Returns all quizzes for user with capabilities
     *
     * @param  int $userid
     * @return array
     */
    public static function get_quizzes_with_caps(int $userid = null) {
        global $USER;

        $userid = $USER->id;
        self::validate_parameters(self::get_quizzes_with_caps_parameters(), []);

        $allcourses = enrol_get_users_courses($userid);
        $capcourses = [];
        foreach ($allcourses as $index => $course) {
            $context = context_course::instance($course->id);
            $hascaps = has_capability('mod/mooduell:canpurchase', $context);
            if ($hascaps) {
                $capcourses[$index] = $course;
            }
        }
        $quizzes = get_all_instances_in_courses("mooduell", $capcourses);
        if (!empty($quizzes)) {
            $returquizzes = [];
            foreach ($quizzes as $quiz) {
                    // Entry to return.
                    $quizdetails = [];

                    $quizdetails['quizid'] = $quiz->coursemodule;
                    $quizdetails['quizname'] = $quiz->name;
                    $quizdetails['courseid'] = $quiz->course;
                    $returnquizzes[] = $quizdetails;
            }
        } else {
            $returnquizzes = [];
        }
        $returnarray['quizzes'] = $returnquizzes;
        return $returnarray;
    }

    /**
     * Defines return structure for get_quizzes_with_caps()
     *
     * @return external_single_structure
     */
    public static function get_quizzes_with_caps_returns() {
        return new external_single_structure([
            'quizzes' => new external_multiple_structure(new external_single_structure([
                    'quizid' => new external_value(PARAM_INT, 'id of quiz'),
                    'quizname' => new external_value(PARAM_TEXT, 'name of quiz'),
                    'courseid' => new external_value(PARAM_INT, 'id of course'),
            ])),
         ]);
    }

    /**
     * Defines params structure for get_quizzes_with_caps()
     *
     * @return external_function_parameters
     */
    public static function get_quizzes_with_caps_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Return support information to client.
     * @return array
     */
    public static function get_mooduell_support() {
        global $DB;
        $url = get_config('mooduell', 'supporturl');
        $pay = get_config('mooduell', 'unlockplatform');
        $theme = get_config('mooduell', 'theme');
        $themeimg = get_config('mooduell', 'companylogo');

        // Set minimum requirem App Version here.
        $versions = [
            "ios" => '1.0.0',
            "android" => '0.9.0',
        ];
        $support = [
            'url' => $url,
            'unlock' => $pay,
            'versions' => $versions,
            'theme' => $theme,
            'themeimg' => $themeimg
        ];

        self::validate_parameters((self::get_mooduell_support_parameters()), []);
        return $support;
    }
    /**
     * Defines support return structure.
     *
     * @return external_single_structure
     */
    public static function get_mooduell_support_returns() {
           return new external_single_structure([
                        'url' => new external_value(PARAM_TEXT, 'url'),
                        'unlock' => new external_value(PARAM_BOOL, 'unlock'),
                        'versions' => new external_single_structure(
                        [
                            "ios" => new external_value(PARAM_RAW, 'ios app version'),
                        ]),
                        'theme' => new external_value(PARAM_TEXT, 'theme'),
                        'themeimg' => new external_value(PARAM_TEXT, 'themeimg'),
                    ]);
    }
    /**
     * Defines support input parameters.
     *
     * @return external_function_parameters
     */
    public static function get_mooduell_support_parameters() {
        return new external_function_parameters([]);
    }
    /**
     * Gets purchases from Database.
     *
     * @return void
     */
    public static function get_mooduell_purchases() {
        global $COURSE, $USER;

        $context = context_course::instance($COURSE->id);
        self::validate_context($context);
        $enrolledcourses = enrol_get_users_courses($USER->id, true);
        $quizzes = get_all_instances_in_courses("mooduell", $enrolledcourses);
        self::validate_parameters(self::get_mooduell_purchases_parameters(), []);
        return mooduell::get_purchases($enrolledcourses, $quizzes);
    }
    /**
     * Defines return Parameters for get_mooduell_purchases.
     *
     * @return void
     */
    public static function get_mooduell_purchases_returns() {
        return new external_single_structure([
            'purchases' => new external_multiple_structure(new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'id'),
                    'productid' => new external_value(PARAM_INT, 'productid'),
                    'purchasetoken' => new external_value(PARAM_TEXT, 'purchasetoken'),
                    'receipt' => new external_value(PARAM_TEXT, 'receipt', VALUE_OPTIONAL, ''),
                    'signature' => new external_value(PARAM_TEXT, 'signature', VALUE_OPTIONAL, ''),
                    'orderid' => new external_value(PARAM_INT, 'orderid', VALUE_OPTIONAL, ''),
                    'free' => new external_value(PARAM_INT, 'free', VALUE_OPTIONAL, 0),
                    'userid' => new external_value(PARAM_INT, 'userid'),
                    'mooduellid' => new external_value(PARAM_INT, 'mooduellid', VALUE_OPTIONAL, 0),
                    'platformid' => new external_value(PARAM_TEXT, 'platformid', VALUE_OPTIONAL, ''),
                    'courseid' => new external_value(PARAM_INT, 'courseid', VALUE_OPTIONAL, 0),
                    'store' => new external_value(PARAM_TEXT, 'store', VALUE_OPTIONAL, ''),
                    'ispublic' => new external_value(PARAM_INT, 'ispublic'),
                    'timecreated' => new external_value(PARAM_INT, 'timecreated', VALUE_OPTIONAL, 0),
                ])),
            ]);
    }
    /**
     * Defines Webservice Parameters for get_mooduell purchases.
     *
     * @return external_function_parameters
     */
    public static function get_mooduell_purchases_parameters() {
        return new external_function_parameters([]);
    }
    /**
     * Stores a purchases to Database.
     *
     * @param  string $productid
     * @param  string $purchasetoken
     * @param  string $receipt
     * @param  string $signature
     * @param  string $orderid
     * @param  string $free
     * @param  int $mooduellid
     * @param  int $courseid
     * @param  string $store
     * @param  int $ispublic
     * @return void
     */
    public static function update_iapurchases(string $productid, string $purchasetoken, string $receipt = null,
     string $signature = null, string $orderid = null, string $free = null, int $mooduellid,
     int $courseid = null, string $store, int $ispublic ) {
        global $USER, $CFG;

        $params = [
            'productid' => $productid,
            'purchasetoken' => $purchasetoken,
            'receipt' => $receipt,
            'signature' => $signature,
            'orderid' => $orderid,
            'free' => $free,
            'mooduellid' => $mooduellid,
            'courseid' => $courseid,
            'store' => $store,
            'ispublic' => $ispublic,
        ];

        $params = self::validate_parameters(self::update_iapurchases_parameters(), $params);

        $params['userid'] = $USER->id;

        if ($params['productid'] === 'unlockplatformsubscription') {
            $params['platformid'] = $CFG->wwwroot;
        }
        return mooduell::purchase_item($params);

    }
    /**
     * Return params for iapurchases.
     *
     * @return external_single_sctructure
     */
    public static function update_iapurchases_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'itemid' => new external_value(PARAM_INT, 'itemid'),
            'type' => new external_value(PARAM_TEXT, 'type'),
        ]);
    }
    /**
     * Webservice params for iapurchases.
     *
     * @return external_function_parameters
     */
    public static function update_iapurchases_parameters() {
        return new external_function_parameters([
            'productid' => new external_value(PARAM_RAW, 'productid'),
            'purchasetoken' => new external_value(PARAM_RAW, 'purchasetoken'),
            'receipt' => new external_value(PARAM_RAW, 'signature'),
            'signature' => new external_value(PARAM_RAW, 'signature'),
            'orderid' => new external_value(PARAM_RAW, 'orderid'),
            'free' => new external_value(PARAM_INT, 'free'),
            'mooduellid' => new external_value(PARAM_INT, 'mooduellid'),
            'courseid' => new external_value(PARAM_INT, 'platformid'),
            'store' => new external_value(PARAM_TEXT, 'store'),
            'ispublic' => new external_value(PARAM_INT, 'ispublic'),
        ]);

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

        $params = [
                'quizid' => $quizid,
                'gameid' => $gameid,
                'questionid' => $questionid,
                'answerids' => $answerids,
        ];

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
        return new external_function_parameters([
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'gameid' => new external_value(PARAM_INT, 'gameid id'),
                'questionid' => new external_value(PARAM_INT, 'question id'),
                'answerids' => new external_multiple_structure(new external_value(PARAM_RAW, 'answer id'),
                        'Array of answer ids'),
        ]);
    }

    /**
     * Defines the return value of answer_question.
     * @return external_single_structure
     */
    public static function answer_question_returns() {
        return new external_single_structure(
            [
                'response' => new external_multiple_structure(
                        // For numerical questions, it will contain the correct answer.
                        new external_value(PARAM_RAW, 'ids of correct answers, correct answer OR 0 if false, 1 if true')
                ),
                'iscorrect' => new external_value(PARAM_INT, '0 if false, 1 if true'),
                'generalfeedback' => new external_value(PARAM_TEXT, 'general feedback'),
                'showgeneralfeedback' => new external_value(PARAM_INT, '0 if false, 1 if true'),
                'answersfeedback' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            "answerid" => new external_value(PARAM_RAW, 'answer id'),
                            "answertext" => new external_value(PARAM_RAW, 'answer text'),
                            "feedback" => new external_value(PARAM_RAW, 'answer-specific feedback'),
                        ]
                    )
                ),
                'showanswersfeedback' => new external_value(PARAM_INT, '0 if false, 1 if true'),
            ]
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
        $returnedquizzes = [];

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
                            'timemodified' => $game->timemodified,
                    ];
                }
            } else {
                $quiz['games'] = [];
            }

            $returnedquizzes[] = $quiz;
        }

        $result = [];
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
        return new external_function_parameters([
                        'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'),
                                'Array of course ids', VALUE_DEFAULT, []),
                        'timemodified' => new external_value(PARAM_INT, 'timemodified to reduce number of returned items',
                                VALUE_DEFAULT, -1),
                ]
        );
    }

    /**
     * Describes the values returned by get_games_by_courses.
     * @return external_single_structure
     */
    public static function get_games_by_courses_returns() {
        return new external_single_structure([
                'quizzes' => new external_multiple_structure(new external_single_structure([
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
                        'challenges' => new external_multiple_structure(new external_single_structure([
                                                'id' => new external_value(PARAM_INT, 'challenge id'),
                                                'challengename' => new external_value(PARAM_TEXT, 'challenge name'),
                                                'challengetype' => new external_value(PARAM_TEXT, 'challenge type'),
                                                'actualnumber' => new external_value(PARAM_INT, 'actual number'),
                                                'status' => new external_value(PARAM_TEXT, 'challenge status'),
                                                'targetnumber' => new external_value(PARAM_INT, 'target number'),
                                                'challengepercentage' => new external_value(PARAM_INT, 'challenge percentage'),
                                                'targetdate' => new external_value(PARAM_INT,
                                                    'unix timestamp of expected completion'),
                                                'challengerank' => new external_value(PARAM_INT,
                                                    'a user\'s ranking within a challenge'),
                                                'localizedstrings' => new external_multiple_structure(
                                                    new external_single_structure([
                                                        'lang' => new external_value(PARAM_TEXT, 'language identifier'),
                                                        'stringkey' => new external_value(PARAM_TEXT, 'string identifier'),
                                                        'stringval' => new external_value(PARAM_TEXT, 'string value'),
                                                    ])
                                                ),
                                        ]
                                )
                        ),
                        'games' => new external_multiple_structure(new external_single_structure([
                                'gameid' => new external_value(PARAM_INT, 'id of game'),
                                'playeraid' => new external_value(PARAM_INT, 'id of player A'),
                                'playerbid' => new external_value(PARAM_INT, 'id of player B'),
                                'playeratime' => new external_value(PARAM_INT, 'time of player B'),
                                'playerbtime' => new external_value(PARAM_INT, 'time of player B'),
                                'status' => new external_value(PARAM_INT,
                                        'status, NULL is open game, 1 is player A\'s turn, 2 is player B\'s turn, 3 is finished'),
                                'winnerid' => new external_value(PARAM_INT, 'id of winner, 0 is not yet finished'),
                                'timemodified' => new external_value(PARAM_INT, 'time modified'),
                        ])),
                ])),
        ]);
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
        $warnings = [];
        $returnedquizzes = [];

        $params = [
                'courseids' => $courseids,
                'timemodified' => $timemodfied,
        ];
        $params = self::validate_parameters(self::get_quizzes_by_courses_parameters(), $params);

        $mycourses = [];
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
                $quizdetails = [];
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
        $result = [];
        $result['quizzes'] = $returnedquizzes;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Defines the parameters for get_quizzes_by_courses.
     * @return external_function_parameters
     */
    public static function get_quizzes_by_courses_parameters() {
        return new external_function_parameters([
                        'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'),
                                'Array of course ids', VALUE_DEFAULT, []),
                        'timemodified' => new external_value(PARAM_INT, 'timemodified to reduce number of returned items',
                                VALUE_DEFAULT, -1),
                ]
        );
    }

    /**
     * Defines the return value of get_quizzes_by_courses.
     * @return external_single_structure
     */
    public static function get_quizzes_by_courses_returns() {
        return new external_single_structure([
                'quizzes' => new external_multiple_structure(new external_single_structure([
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
                ])),
        ]);
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
        $params = [
                'courseid' => $courseid,
                'quizid' => $quizid,
                'gameid' => $gameid,
        ];

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

        // On every call, we see if completion is already done.

        $course = get_course($params['courseid']);
        $completion = new completion_info($course);

        if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
            // This calculates the completion and saves the state.
            // Which will also trigger the completion_update event, which triggers badges.
            $completion->update_state($cm);
        }

        return $gamedata;
    }

    /**
     * Describes the parameters for get_game_data.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_game_data_parameters() {
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
                'gameid' => new external_value(PARAM_INT, 'gameid id'),
        ]);
    }

    /**
     * Describes the return values for get_game_data.
     * @return external_single_structure
     */
    public static function get_game_data_returns() {
        return new external_single_structure([
            'mooduellid' => new external_value(PARAM_INT, 'mooduellid'),
            'gameid' => new external_value(PARAM_INT, 'gameid'),
            'playeraid' => new external_value(PARAM_INT, 'player A id'),
            'playerbid' => new external_value(PARAM_INT, 'player B id'),
            'winnerid' => new external_value(PARAM_INT, 'winner id'),
            'timemodified' => new external_value(PARAM_INT, 'time modified'),
            'status' => new external_value(PARAM_INT, 'status'),
            'challenges' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'challenge id'),
                'challengename' => new external_value(PARAM_TEXT, 'challenge name'),
                'challengetype' => new external_value(PARAM_TEXT, 'challenge type'),
                'actualnumber' => new external_value(PARAM_INT, 'actual number'),
                'status' => new external_value(PARAM_TEXT, 'challenge status'),
                'targetnumber' => new external_value(PARAM_INT, 'target number'),
                'challengepercentage' => new external_value(PARAM_INT, 'challenge percentage'),
                'targetdate' => new external_value(PARAM_INT,
                    'unix timestamp of expected completion date'),
                'challengerank' => new external_value(PARAM_INT,
                    'a user\'s ranking within a challenge'),
                'localizedstrings' => new external_multiple_structure(
                    new external_single_structure([
                        'lang' => new external_value(PARAM_TEXT, 'language identifier'),
                        'stringkey' => new external_value(PARAM_TEXT, 'string identifier'),
                        'stringval' => new external_value(PARAM_TEXT, 'string value'),
                    ])
                ),
            ])),
            'questions' => new external_multiple_structure(new external_single_structure([
                'questionid' => new external_value(PARAM_INT, 'questionid'),
                'questiontext' => new external_value(PARAM_RAW, 'question text'),
                'questiontype' => new external_value(PARAM_RAW, 'qtype'),
                'category' => new external_value(PARAM_INT, 'category'),
                'playeraanswered' => new external_value(PARAM_INT, 'answer player a'),
                'playerbanswered' => new external_value(PARAM_INT, 'answer player a'),
                'imageurl' => new external_value(PARAM_RAW, 'image URL'),
                'imagetext' => new external_value(PARAM_RAW, 'image Text'),
                'answers' => new external_multiple_structure(new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'answerid'),
                    'answertext' => new external_value(PARAM_RAW,
                            'answer text'),
                ])),
                'combinedfeedback' => new external_single_structure([
                    'correctfeedback' => new external_value(PARAM_TEXT, 'correct feedback'),
                    'partiallycorrectfeedback' => new external_value(PARAM_TEXT,
                        'partially correct feedback'),
                    'incorrectfeedback' => new external_value(PARAM_TEXT, 'incorrect feedback'),
                ]),
            ])),
        ]);
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
        $params = [
                'courseid' => $courseid,
                'quizid' => $quizid,
        ];

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
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'quizid' => new external_value(PARAM_INT, 'quizid id'),
        ]);
    }

    /**
     * Describes the return values for get_quiz_users.
     * @return external_multiple_structure
     */
    public static function get_quiz_users_returns() {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'userid'),
            'firstname' => new external_value(PARAM_RAW, 'firstname'),
            'lastname' => new external_value(PARAM_RAW, 'lastname'),
            'username' => new external_value(PARAM_RAW, 'username'),
            'alternatename' => new external_value(PARAM_RAW,
                    'nickname, stored as custom profile filed mooduell_alias'),
            'lang' => new external_value(PARAM_RAW, 'language'),
            'profileimageurl' => new external_value(PARAM_RAW, 'profileimageurl'),
        ]));
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

        $params = [
                'userid' => $userid,
                'alternatename' => $alternatename,
        ];

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

        return ['status' => 1];
    }


    /**
     * Describes the parameters for set_alternatename.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function set_alternatename_parameters() {
        return new external_function_parameters([
                'userid' => new external_value(PARAM_INT, 'user id'),
                'alternatename' => new external_value(PARAM_RAW, 'alternate name'),
        ]);
    }

    /**
     * Describes the return value for set_alternatename.
     * @return external_single_structure
     */
    public static function set_alternatename_returns() {
        return new external_single_structure([
                        'status' => new external_value(PARAM_INT, 'status'),
                ]
        );
    }

    /**
     * Retrieves some stats about the user.
     * @param int $userid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function get_user_stats(int $userid) {
        $params = [
                'userid' => $userid,
        ];

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
        return new external_function_parameters([
                'userid' => new external_value(PARAM_INT, 'user id'),
        ]);
    }

    /**
     * Describes the return values for get_user_stats.
     * @return external_single_structure
     */
    public static function get_user_stats_returns() {
        return new external_single_structure([
                        'playedgames' => new external_value(PARAM_INT, 'playedgames'),
                        'wongames' => new external_value(PARAM_INT, 'wongames'),
                        'lostgames' => new external_value(PARAM_INT, 'lostgames'),
                        'correctlyanswered' => new external_value(PARAM_INT, 'correctlyanswered'),
                        'playedquestions' => new external_value(PARAM_INT, 'playedquestions'),
                ]
        );
    }

    /**
     * Retrieves the highscore list for a given quiz.
     * @param int $quizid
     * @return mixed
     * @throws invalid_parameter_exception
     */
    public static function get_highscores(int $quizid) {

        $params = [
                'quizid' => $quizid,
        ];

        $params = self::validate_parameters(self::get_highscores_parameters(), $params);

        return mooduell::get_highscores(null, $params['quizid']);
    }

    /**
     * Describes the parameters of get_highscores.
     * @return external_function_parameters
     */
    public static function get_highscores_parameters() {
        return new external_function_parameters([
                'quizid' => new external_value(PARAM_INT, 'quiz id'),
        ]);
    }

    /**
     * Describes the return values of get_highscores.
     * @return external_multiple_structure
     */
    public static function get_highscores_returns() {
        return new external_multiple_structure(new external_single_structure([
                                'quizid' => new external_value(PARAM_INT, 'quizid'),
                                'userid' => new external_value(PARAM_INT, 'userid'),
                                'won' => new external_value(PARAM_INT, 'won'), // Games won.
                                'lost' => new external_value(PARAM_INT, 'lost'), // Games lost.
                                'score' => new external_value(PARAM_INT, 'firstname'),
                                'played' => new external_value(PARAM_INT, 'played'), // Games played.
                        ]
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

        $params = [
                'userid' => $userid,
        ];

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
        return new external_function_parameters([
                'userid' => new external_value(PARAM_INT, 'user id'),
        ]);
    }

    /**
     * Describes the return value of get_pushtokens.
     * @return external_multiple_structure
     */
    public static function get_pushtokens_returns() {
        return new external_single_structure([
                        'userid' => new external_value(PARAM_INT, 'quizid'),
                        'pushtokens' => new external_multiple_structure(new external_single_structure([
                                                'identifier' => new external_value(PARAM_RAW, 'identifier'),
                                                'model' => new external_value(PARAM_RAW, 'model'),
                                                'pushtoken' => new external_value(PARAM_RAW, 'pushtoken'),
                                                ]
                                )
                        ),
                ]
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

        $params = [
                'userid' => $userid,
                'identifier' => $identifier,
                'model' => $model,
                'pushtoken' => $pushtoken,
        ];

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
        return new external_function_parameters([
                        'userid' => new external_value(PARAM_INT, 'user id'),
                        'model' => new external_value(PARAM_RAW, 'identifier'),
                        'identifier' => new external_value(PARAM_RAW, 'model'),
                        'pushtoken' => new external_value(PARAM_RAW, 'pushtoken'),
                ]
        );
    }

    /**
     * Describes the return value for set_pushtokens.
     * @return external_multiple_structure
     */
    public static function set_pushtokens_returns() {
        return new external_single_structure([
                        'status' => new external_value(PARAM_INT, 'status'),
                ]
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

        $params = [
                'gameid' => $gameid,
        ];

            $params = self::validate_parameters(self::giveup_game_parameters(), $params);

        $entry = $DB->get_record('mooduell_games', ['id' => $params['gameid']]);

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
        return new external_function_parameters([
                        'gameid' => new external_value(PARAM_INT, 'game id'),
                ]
        );
    }

    /**
     * Describes the return value for giveup_game.
     * @return external_single_structure
     */
    public static function giveup_game_returns() {
        return new external_single_structure([
                        'status' => new external_value(PARAM_INT, 'status'),
                ]
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

        $fileinfo = self::validate_parameters(self::update_profile_picture_parameters(), [
                'filename' => $filename,
                'filecontent' => $filecontent,
            ]);

        if (!isset($fileinfo['filecontent'])) {
            throw new moodle_exception('nofile');
        }

        list($w, $h) = getimagesizefromstring(base64_decode($filecontent));
        // Somehow 2 large Image made it to here, caancel upload.
        if ($w > 500 || $h > 1000) {

            return ['filename' => 'TOOLARGE'];
        }

        $context = context_system::instance();
        $fs = get_file_storage();

        // Prepare file record object.
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_mooduell',
            'filearea' => 'aliasavatar',
            'itemid' => $USER->id,
            'filepath' => '/',
            'filename' => $filename.time().'.jpg',
        ];

        $files = $fs->get_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
        foreach ($files as $f) {
            $f->delete();
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

        $fileinfo['filecontent'] = strtr($filecontent, '._-', '+/=');

        file_put_contents($savedfilepath, base64_decode($fileinfo['filecontent']));

        require_once( $CFG->libdir . '/gdlib.php' );

        @chmod($savedfilepath, $CFG->filepermissions);
        unset($fileinfo['filecontent']);
        $fs->create_file_from_pathname($fileinfo, $savedfilepath);

        // Delete temporary files.
        unset( $savedfilepath );

        cache_helper::purge_by_event('setbackuserscache');

        return ['filename' => $fileinfo['filename']];
    }

    /**
     * Describes the paramters for update_profile_picture.
     * @return external_function_parameters
     */
    public static function update_profile_picture_parameters() {
        return new external_function_parameters([
                        'filename'  => new external_value(PARAM_FILE, 'file name'),
                        'filecontent' => new external_value(PARAM_TEXT, 'file content'),
                ]
        );
    }


    /**
     * Describes the return value for update_profile_picture.
     * @return external_single_structure
     */
    public static function update_profile_picture_returns() {
        return new external_single_structure([
                        'filename' => new external_value(PARAM_TEXT, 'image url'),
                ]
        );
    }
}
