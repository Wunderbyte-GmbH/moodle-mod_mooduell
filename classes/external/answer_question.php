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
class answer_question extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                'quizid' => new \external_value(PARAM_INT, 'quizid id'),
                'gameid' => new \external_value(PARAM_INT, 'gameid id'),
                'questionid' => new \external_value(PARAM_INT, 'question id'),
                'answerids' => new \external_multiple_structure(
                    new \external_value(PARAM_RAW, 'answer id'),
                    'Array of answer ids'
                ),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @param int $quizid
     * @param int $gameid
     * @param int $questionid
     * @param array $answerids
     * @return mixed
     */
    public static function execute(int $quizid, int $gameid, int $questionid, array $answerids = []) {
        global $DB;

        $params = [
                'quizid' => $quizid,
                'gameid' => $gameid,
                'questionid' => $questionid,
                'answerids' => $answerids,
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

        [$response, $iscorrect, $answersfeedback] =
            $gamecontroller->validate_question($params['questionid'], $params['answerids']);
        $result['response'] = $response;
        $result['iscorrect'] = $iscorrect;

        if (!empty($answersfeedback)) {
            $result['showanswersfeedback'] = $mooduell->settings->showanswersfeedback;

            if ($mooduell->settings->showanswersfeedback == 1) {
                $result['answersfeedback'] = $answersfeedback;
            } else {
                $result['answersfeedback'] = [];
            }
        } else {
            $result['answersfeedback'] = [];
            $result['showanswersfeedback'] = 0;
        }

        if ($generalfeedback = $DB->get_field('question', 'generalfeedback', ['id' => $questionid])) {
            $result['generalfeedback'] = strip_tags($generalfeedback);
            $result['showgeneralfeedback'] = $mooduell->settings->showgeneralfeedback;
        } else {
            $result['generalfeedback'] = '';
            $result['showgeneralfeedback'] = 0;
        }

        $result['showgeneralfeedback'] = $mooduell->settings->showgeneralfeedback;

        return $result;
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure(
            [
                'response' => new \external_multiple_structure(
                    new \external_value(PARAM_RAW, 'ids of correct answers, correct answer OR 0 if false, 1 if true')
                ),
                'iscorrect' => new \external_value(PARAM_INT, '0 if false, 1 if true'),
                'generalfeedback' => new \external_value(PARAM_TEXT, 'general feedback'),
                'showgeneralfeedback' => new \external_value(PARAM_INT, '0 if false, 1 if true'),
                'answersfeedback' => new \external_multiple_structure(
                    new \external_single_structure(
                        [
                            'answerid' => new \external_value(PARAM_RAW, 'answer id'),
                            'answertext' => new \external_value(PARAM_RAW, 'answer text'),
                            'feedback' => new \external_value(PARAM_RAW, 'answer-specific feedback'),
                        ]
                    )
                ),
                'showanswersfeedback' => new \external_value(PARAM_INT, '0 if false, 1 if true'),
            ]
        );
    }
}
