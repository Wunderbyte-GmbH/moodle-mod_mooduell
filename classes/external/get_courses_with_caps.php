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
class get_courses_with_caps extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute() {
        global $USER;

        $userid = $USER->id;
        self::validate_parameters(self::execute_parameters(), []);
        $allcourses = enrol_get_users_courses($userid);
        $capcourses = [];
        foreach ($allcourses as $course) {
            $context = \context_course::instance($course->id);
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
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'courses' => new \external_multiple_structure(new \external_single_structure([
                'courseid' => new \external_value(PARAM_INT, 'id of course'),
                'coursename' => new \external_value(PARAM_TEXT, 'name of course'),
            ])),
        ]);
    }
}
