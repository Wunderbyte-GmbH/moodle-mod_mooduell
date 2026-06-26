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
 * Scheduled task that warns admins when the PRO license key has expired.
 *
 * @package    mod_mooduell
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\task;

use core\message\message;
use core_user;
use mod_mooduell\utils\wb_payment;
use moodle_url;

/**
 * Checks the configured PRO license key once a day and notifies site admins when it has lapsed.
 *
 * Admins are warned exactly once per lapse: a config flag ('licenseexpirynotified') records that
 * the current invalid state has already been reported, and it is cleared again as soon as the
 * license is valid (or removed), so a fresh expiry in the future triggers a new notification.
 */
class check_license_task extends \core\task\scheduled_task {
    /** @var string Config flag remembering that the current lapse was already reported. */
    private const NOTIFIED_FLAG = 'licenseexpirynotified';

    /**
     * Returns the task name shown in the scheduled tasks admin page.
     *
     * @return string
     */
    public function get_name() {
        return get_string('checklicensetask', 'mod_mooduell');
    }

    /**
     * Checks the license state and notifies admins once if it is no longer valid.
     *
     * @return void
     */
    public function execute() {

        $state = wb_payment::get_license_expiry_state();

        // States 'valid' and 'nolicense' mean there is nothing to warn about. Re-arm the one-time
        // notification so that a future expiry (e.g. after a renewal) is reported again.
        if ($state['state'] === 'valid' || $state['state'] === 'nolicense') {
            unset_config(self::NOTIFIED_FLAG, 'mooduell');
            return;
        }

        // From here the license is 'expired' or 'invalid'. Only notify once per lapse.
        if (get_config('mooduell', self::NOTIFIED_FLAG)) {
            mtrace('mod_mooduell: license is not valid but admins were already notified, skipping.');
            return;
        }

        $this->notify_admins($state);

        // Remember that we have reported this lapse so we don't notify again tomorrow.
        set_config(self::NOTIFIED_FLAG, 1, 'mooduell');
    }

    /**
     * Sends an admin notification (popup) and a direct e-mail to every site admin.
     *
     * @param array $state the result of {@see wb_payment::get_license_expiry_state()}
     * @return void
     */
    private function notify_admins(array $state) {

        $admins = get_admins();
        if (empty($admins)) {
            mtrace('mod_mooduell: license is not valid but no site admins were found to notify.');
            return;
        }

        $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'modsettingmooduell']);

        // Distinguish an expired-but-readable key from one we could not validate at all.
        if ($state['state'] === 'expired') {
            $a = (object)[
                'product' => $state['product'],
                'exptime' => $state['exptime'],
                'url' => $settingsurl->out(false),
            ];
            $subject = get_string('licenseexpired_subject', 'mod_mooduell');
            $messagetext = get_string('licenseexpired_message', 'mod_mooduell', $a);
        } else {
            $a = (object)[
                'url' => $settingsurl->out(false),
            ];
            $subject = get_string('licenseinvalid_subject', 'mod_mooduell');
            $messagetext = get_string('licenseinvalid_message', 'mod_mooduell', $a);
        }

        $messagehtml = text_to_html($messagetext, false, false, true);
        $supportuser = core_user::get_support_user();

        foreach ($admins as $admin) {
            // 1) Admin notification (the bell / popup) via the message API.
            $message = new message();
            $message->component = 'mod_mooduell';
            $message->name = 'licenseexpired';
            $message->userfrom = $supportuser;
            $message->userto = $admin;
            $message->subject = $subject;
            $message->fullmessage = $messagetext;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = $messagehtml;
            $message->smallmessage = $subject;
            $message->notification = 1;
            $message->contexturl = $settingsurl->out(false);
            $message->contexturlname = get_string('licensekeycfg', 'mod_mooduell');
            message_send($message);

            // 2) A guaranteed e-mail, independent of the admin's message preferences.
            email_to_user($admin, $supportuser, $subject, $messagetext, $messagehtml);
        }

        mtrace('mod_mooduell: notified ' . count($admins) . ' admin(s) about the expired/invalid license.');
    }
}
