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
class get_support extends external_api {
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
        $url = get_config('mooduell', 'supporturl');
        $pay = \mod_mooduell\utils\wb_payment::pro_version_is_activated(false);
        $badges = get_config('mooduell', 'disablebadges');
        $themeimg = get_config('mod_mooduell', 'companylogo');
        $themeimgalt = get_config('mod_mooduell', 'companylogoalternative');
        $themejson = get_config('mod_mooduell', 'themejsonarea');

        $versions = [
            'ios' => '1.0.0',
            'android' => '0.9.0',
        ];
        $support = [
            'url' => $url,
            'badges' => $badges,
            'unlock' => $pay,
            'versions' => $versions,
            'themeimg' => $themeimg,
            'themejson' => $themejson,
            'themeimgalt' => $themeimgalt,
        ];

        self::validate_parameters(self::execute_parameters(), []);
        return $support;
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
                    'url' => new \external_value(PARAM_TEXT, 'url'),
                    'badges' => new \external_value(PARAM_BOOL, 'badges'),
                    'unlock' => new \external_value(PARAM_BOOL, 'unlock'),
                    'versions' => new \external_single_structure(
                        [
                        'ios' => new \external_value(PARAM_RAW, 'ios app version'),
                        'android' => new \external_value(PARAM_RAW, 'android app version'),
                        ]
                    ),
                    'themeimg' => new \external_value(PARAM_TEXT, 'themeimg'),
                    'themeimgalt' => new \external_value(PARAM_TEXT, 'themeimgalt'),
                    'themejson' => new \external_value(PARAM_RAW, 'themejson'),
                ]);
    }
}
