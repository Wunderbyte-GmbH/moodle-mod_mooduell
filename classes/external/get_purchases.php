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
class get_purchases extends external_api {
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
        global $COURSE, $USER;

        $context = \context_course::instance($COURSE->id);
        self::validate_context($context);
        $enrolledcourses = enrol_get_users_courses($USER->id, true);
        $quizzes = get_all_instances_in_courses('mooduell', $enrolledcourses);
        self::validate_parameters(self::execute_parameters(), []);
        return \mod_mooduell\mooduell::get_purchases($enrolledcourses, $quizzes);
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'purchases' => new \external_multiple_structure(new \external_single_structure(
                [
                    'id' => new \external_value(PARAM_INT, 'id'),
                    'productid' => new \external_value(PARAM_TEXT, 'productid'),
                    'purchasetoken' => new \external_value(PARAM_TEXT, 'purchasetoken'),
                    'receipt' => new \external_value(PARAM_TEXT, 'receipt', VALUE_OPTIONAL, ''),
                    'signature' => new \external_value(PARAM_TEXT, 'signature', VALUE_OPTIONAL, ''),
                    'orderid' => new \external_value(PARAM_TEXT, 'orderid', VALUE_OPTIONAL, ''),
                    'free' => new \external_value(PARAM_INT, 'free', VALUE_OPTIONAL, 0),
                    'userid' => new \external_value(PARAM_INT, 'userid'),
                    'mooduellid' => new \external_value(PARAM_INT, 'mooduellid', VALUE_OPTIONAL, 0),
                    'platformid' => new \external_value(PARAM_TEXT, 'platformid', VALUE_OPTIONAL, ''),
                    'courseid' => new \external_value(PARAM_INT, 'courseid', VALUE_OPTIONAL, 0),
                    'store' => new \external_value(PARAM_TEXT, 'store', VALUE_OPTIONAL, ''),
                    'ispublic' => new \external_value(PARAM_INT, 'ispublic'),
                    'timecreated' => new \external_value(PARAM_INT, 'timecreated', VALUE_OPTIONAL, 0),
                ]
            )),
            ]);
    }
}
