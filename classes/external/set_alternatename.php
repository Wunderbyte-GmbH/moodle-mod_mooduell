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
class set_alternatename extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                'userid' => new \external_value(PARAM_INT, 'user id'),
                'alternatename' => new \external_value(PARAM_RAW, 'alternate name'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute(int $userid, string $alternatename) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $params = [
                'userid' => $userid,
                'alternatename' => $alternatename,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        if ($params['userid'] != $USER->id) {
            throw new \moodle_exception(
                'norighttosetnameofthisuser ' . $params['userid'],
                'mooduell',
                null,
                null,
                'Course module id:' . $params['quizid']
            );
        }

        $newuser = $USER;
        $newuser->profile_field_mooduell_alias = \core_user::clean_field($params['alternatename'], 'alternatename');

        profile_save_data($newuser);

        // Purge the users cache so the next get_quiz_users call returns fresh data
        // with the updated alternatename instead of the stale cached value.
        $cache = \cache::make('mod_mooduell', 'userscache');
        $cache->purge();

        return ['status' => 1];
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
                        'status' => new \external_value(PARAM_INT, 'status'),
                ]);
    }
}
