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
class update_iap extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
            'productid' => new \external_value(PARAM_RAW, 'productid'),
            'purchasetoken' => new \external_value(PARAM_RAW, 'purchasetoken'),
            'receipt' => new \external_value(PARAM_RAW, 'signature'),
            'signature' => new \external_value(PARAM_RAW, 'signature'),
            'orderid' => new \external_value(PARAM_RAW, 'orderid'),
            'free' => new \external_value(PARAM_INT, 'free'),
            'mooduellid' => new \external_value(PARAM_INT, 'mooduellid'),
            'courseid' => new \external_value(PARAM_INT, 'platformid'),
            'store' => new \external_value(PARAM_TEXT, 'store'),
            'ispublic' => new \external_value(PARAM_INT, 'ispublic'),
        ]);
    }
    /**
     * Executes the external function.
     *
     * @param string $productid
     * @param string $purchasetoken
     * @param string|null $receipt
     * @param string|null $signature
     * @param string|null $orderid
     * @param string|null $free
     * @param int $mooduellid
     * @param int|null $courseid
     * @param string $store
     * @param int $ispublic
     * @return mixed
     */
    public static function execute(
        string $productid,
        string $purchasetoken,
        ?string $receipt = null,
        ?string $signature = null,
        ?string $orderid = null,
        ?string $free = null,
        int $mooduellid = 0,
        ?int $courseid = null,
        string $store = '',
        int $ispublic = 0
    ) {
        global $USER, $CFG;

        $params = [
            'productid' => $productid,
            'purchasetoken' => $purchasetoken,
            'receipt' => $receipt,
            'signature' => $signature,
            'orderid' => $orderid,
            'free' => $free,
            'mooduellid' => $mooduellid,
            'courseid' => $courseid,
            'store' => $store,
            'ispublic' => $ispublic,
        ];

        $params = self::validate_parameters(self::execute_parameters(), $params);

        $params['userid'] = $USER->id;

        if ($params['productid'] === 'unlockplatformsubscription') {
            $params['platformid'] = $CFG->wwwroot;
        }
        return \mod_mooduell\mooduell::purchase_item($params);
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
            'status' => new \external_value(PARAM_TEXT, 'status'),
            'itemid' => new \external_value(PARAM_INT, 'itemid'),
            'type' => new \external_value(PARAM_TEXT, 'type'),
        ]);
    }
}
