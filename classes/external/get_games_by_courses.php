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
class get_games_by_courses extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                        'courseids' => new \external_multiple_structure(
                            new \external_value(PARAM_INT, 'course id'),
                            'Array of course ids',
                            VALUE_DEFAULT,
                            []
                        ),
                        'timemodified' => new \external_value(
                            PARAM_INT,
                            'timemodified to reduce number of returned items',
                            VALUE_DEFAULT,
                            -1
                        ),
                ]);
    }
    /**
     * Executes the external function.
     *
     * @param array $courseids
     * @param int $timemodified
     * @return mixed
     */
    public static function execute(array $courseids, int $timemodified) {
        $context = \context_system::instance();
        self::validate_context($context);

        $returnedquizzes = get_quizzes_by_courses::execute($courseids, $timemodified);

        $warnings = $returnedquizzes['warnings'];
        $quizzes = $returnedquizzes['quizzes'];
        $returnedquizzes = [];

        foreach ($quizzes as $quiz) {
            $instanceid = $quiz['coursemodule'];
            $mooduell = new \mod_mooduell\mooduell($instanceid);

            $games = $mooduell->return_games_for_this_instance(true, null, $timemodified);

            $quiz['challenges'] = \mod_mooduell\completion\completion_utils::get_completion_challenges_array($mooduell);

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
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
                'quizzes' => new \external_multiple_structure(new \external_single_structure([
                        'quizid' => new \external_value(PARAM_INT, 'id of coursemodule'),
                        'quizname' => new \external_value(PARAM_RAW, 'name of quiz'),
                        'courseid' => new \external_value(PARAM_INT, 'courseid'),
                        'coursename' => new \external_value(PARAM_RAW, 'coursename'),
                        'usefullnames' => new \external_value(PARAM_INT, 'usefullnames'),
                        'showcorrectanswer' => new \external_value(PARAM_INT, 'showcorrectanswer'),
                        'showcontinuebutton' => new \external_value(PARAM_INT, 'showcontinuebutton'),
                        'showgeneralfeedback' => new \external_value(PARAM_INT, 'showgeneralfeedback'),
                        'showanswersfeedback' => new \external_value(PARAM_INT, 'showanswersfeedback'),
                        'countdown' => new \external_value(PARAM_INT, 'countdown'),
                        'waitfornextquestion' => new \external_value(PARAM_INT, 'waitfornextquestion'),
                        'isteacher' => new \external_value(PARAM_INT, 'isteacher'),
                        'challenges' => new \external_multiple_structure(new \external_single_structure([
                                                'id' => new \external_value(PARAM_INT, 'challenge id'),
                                                'challengename' => new \external_value(PARAM_TEXT, 'challenge name'),
                                                'challengetype' => new \external_value(PARAM_TEXT, 'challenge type'),
                                                'actualnumber' => new \external_value(PARAM_INT, 'actual number'),
                                                'status' => new \external_value(PARAM_TEXT, 'challenge status'),
                                                'targetnumber' => new \external_value(PARAM_INT, 'target number'),
                                                'challengepercentage' => new \external_value(PARAM_INT, 'challenge percentage'),
                                                'targetdate' => new \external_value(
                                                    PARAM_INT,
                                                    'unix timestamp of expected completion'
                                                ),
                                                'challengerank' => new \external_value(
                                                    PARAM_INT,
                                                    'a user\'s ranking within a challenge'
                                                ),
                                                'localizedstrings' => new \external_multiple_structure(
                                                    new \external_single_structure([
                                                        'lang' => new \external_value(PARAM_TEXT, 'language identifier'),
                                                        'stringkey' => new \external_value(PARAM_TEXT, 'string identifier'),
                                                        'stringval' => new \external_value(PARAM_TEXT, 'string value'),
                                                    ])
                                                ),
                                        ])),
                        'games' => new \external_multiple_structure(new \external_single_structure([
                                'gameid' => new \external_value(PARAM_INT, 'id of game'),
                                'playeraid' => new \external_value(PARAM_INT, 'id of player A'),
                                'playerbid' => new \external_value(PARAM_INT, 'id of player B'),
                                'playeratime' => new \external_value(PARAM_INT, 'time of player B'),
                                'playerbtime' => new \external_value(PARAM_INT, 'time of player B'),
                                'status' => new \external_value(
                                    PARAM_INT,
                                    'status, NULL is open game, 1 is player A\'s turn, 2 is player B\'s turn, 3 is finished'
                                ),
                                'winnerid' => new \external_value(PARAM_INT, 'id of winner, 0 is not yet finished'),
                                'timemodified' => new \external_value(PARAM_INT, 'time modified'),
                        ])),
                ])),
        ]);
    }
}
