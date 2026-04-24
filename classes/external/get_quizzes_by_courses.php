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
class get_quizzes_by_courses extends external_api {
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
     * @return mixed
     */
    public static function execute(array $courseids, int $timemodified) {
        $warnings = [];
        $returnedquizzes = [];

        $params = [
                'courseids' => $courseids,
                'timemodified' => $timemodified,
        ];
        $params = self::validate_parameters(self::execute_parameters(), $params);

        $mycourses = [];
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        if (!empty($params['courseids'])) {
            [$courses, $warnings] = \external_util::validate_courses($params['courseids'], $mycourses, false, true);

            $quizzes = get_all_instances_in_courses('mooduell', $courses);

            if (count($quizzes) == 0) {
                throw new \moodle_exception(
                    'nomooduellincourses ',
                    'mooduell',
                    null,
                    null,
                    'There are no MooDuell instances in the courses you were looking in'
                );
            }

            foreach ($quizzes as $quiz) {
                $context = \context_module::instance($quiz->coursemodule);
                $course = get_course($quiz->course);

                $quizdetails = [];
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
                    if (has_capability('moodle/course:manageactivities', $context)) {
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
            ])),
        ]);
    }
}
