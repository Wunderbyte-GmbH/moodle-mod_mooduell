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
class get_highscores extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                'quizid' => new \external_value(PARAM_INT, 'quiz id'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute(int $quizid) {
        $params = [
                'quizid' => $quizid,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        return \mod_mooduell\mooduell::get_highscores(null, $params['quizid']);
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_multiple_structure(new \external_single_structure([
                                'quizid' => new \external_value(PARAM_INT, 'quizid'),
                                'userid' => new \external_value(PARAM_INT, 'userid'),
                                'won' => new \external_value(PARAM_INT, 'won'),
                                'lost' => new \external_value(PARAM_INT, 'lost'),
                                'score' => new \external_value(PARAM_INT, 'firstname'),
                                'played' => new \external_value(PARAM_INT, 'played'),
                        ]));
    }
}
