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
 * update subscription task for mooduell
 *
 * @package    mod_mooduell
 * @copyright  2024 Christian Badusch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\task;
use mod_mooduell\mooduell;

/**
 * update_subscription_task
 */
class update_subscription_task extends \core\task\scheduled_task {

    /**
     * Returns the taskname.
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatesubscription', 'mod_mooduell');
    }

    /**
     * Executes the task and checks the subscription.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $mooduell = $DB->get_record('modules', ['name' => 'mooduell']);
        if ($mooduell) {
            $mooduellid = $DB->get_record('course_modules', ['module' => $mooduell->id]);
            if ($mooduellid) {
                $mooduellinstance = new mooduell($mooduellid->id);
                $mooduellinstance->update_all_subscriptions();
            }
        }
    }
}
