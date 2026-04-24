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
class get_game_data extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                'courseid' => new \external_value(PARAM_INT, 'course id'),
                'quizid' => new \external_value(PARAM_INT, 'quizid id'),
                'gameid' => new \external_value(PARAM_INT, 'gameid id'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute(int $courseid, int $quizid, int $gameid) {
        $params = [
                'courseid' => $courseid,
                'quizid' => $quizid,
                'gameid' => $gameid,
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
        $gamecontroller = new \mod_mooduell\game_control($mooduell, $params['gameid']);

        $gamedata = $gamecontroller->get_questions();
        $gamedata->challenges = \mod_mooduell\completion\completion_utils::get_completion_challenges_array($mooduell);

        $course = get_course($params['courseid']);
        $completion = new \completion_info($course);

        if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
            $completion->update_state($cm);
        }

        return $gamedata;
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'mooduellid' => new \external_value(PARAM_INT, 'mooduellid'),
            'gameid' => new \external_value(PARAM_INT, 'gameid'),
            'playeraid' => new \external_value(PARAM_INT, 'player A id'),
            'playerbid' => new \external_value(PARAM_INT, 'player B id'),
            'winnerid' => new \external_value(PARAM_INT, 'winner id'),
            'timemodified' => new \external_value(PARAM_INT, 'time modified'),
            'status' => new \external_value(PARAM_INT, 'status'),
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
                    'unix timestamp of expected completion date'
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
            'questions' => new \external_multiple_structure(new \external_single_structure([
                'questionid' => new \external_value(PARAM_INT, 'questionid'),
                'questiontext' => new \external_value(PARAM_RAW, 'question text'),
                'questiontype' => new \external_value(PARAM_RAW, 'qtype'),
                'category' => new \external_value(PARAM_INT, 'category'),
                'playeraanswered' => new \external_value(PARAM_INT, 'answer player a'),
                'playerbanswered' => new \external_value(PARAM_INT, 'answer player a'),
                'imageurl' => new \external_value(PARAM_RAW, 'image URL'),
                'imagetext' => new \external_value(PARAM_RAW, 'image Text'),
                'answers' => new \external_multiple_structure(new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'answerid'),
                    'answertext' => new \external_value(
                        PARAM_RAW,
                        'answer text'
                    ),
                ])),
                'combinedfeedback' => new \external_single_structure([
                    'correctfeedback' => new \external_value(PARAM_TEXT, 'correct feedback'),
                    'partiallycorrectfeedback' => new \external_value(
                        PARAM_TEXT,
                        'partially correct feedback'
                    ),
                    'incorrectfeedback' => new \external_value(PARAM_TEXT, 'incorrect feedback'),
                ]),
            ])),
        ]);
    }
}
