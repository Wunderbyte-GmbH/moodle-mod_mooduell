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

namespace mod_mooduell\task;

use mod_mooduell\completion\completion_utils;
use coding_exception;
use mod_mooduell\game_control;
use mod_mooduell\mooduell;
use mod_mooduell\fcm_client;
use stdClass;

/**
 * Adhoc task to send push notifications.
 * @package mod_mooduell
 * @copyright 2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_push_notification_task extends \core\task\adhoc_task {
    /**
     * Execute the task.
     * {@inheritdoc}
     * @see \core\task\task_base::execute()
     */
    public function execute() {

        $taskdata = $this->get_custom_data();

        if ($taskdata != null) {
            $mooduell = new mooduell($taskdata->cm->id);
            $gamecontroller = new game_control($mooduell, $taskdata->gameid);
            $fields = $gamecontroller->gather_notifcation_data($taskdata->message);

            if ($fields !== null) {
                $fcmclient = new fcm_client();
                $fcmclient->send_push_notification($fields);
            } else {
                debugging('No push notification sent: notification data fields are null', DEBUG_DEVELOPER);
            }
        }
    }
}
