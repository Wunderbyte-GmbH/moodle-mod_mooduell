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
class delete_iap extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'itemid' => new \external_value(PARAM_INT, 'itemid'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @return mixed
     */
    public static function execute(int $itemid) {
        global $DB;

        if ($DB->record_exists('mooduell_purchase', ['id' => $itemid])) {
            $DB->delete_records('mooduell_purchase', ['id' => $itemid]);
            $returnarray['status'] = 1;
        } else {
            $returnarray['status'] = 0;
        }
        return $returnarray;
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_TEXT, 'status'),
        ]);
    }
}
