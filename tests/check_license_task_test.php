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
 * Tests for the daily license check task.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use advanced_testcase;
use mod_mooduell\task\check_license_task;
use mod_mooduell\utils\wb_payment;

/**
 * Test class for the check_license_task and its license-state helper.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_mooduell\task\check_license_task
 * @covers     \mod_mooduell\utils\wb_payment::get_license_expiry_state
 */
final class check_license_task_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Runs the scheduled task while swallowing its mtrace() output, so tests don't report as risky.
     *
     * @param check_license_task $task
     * @return void
     */
    private function run_task(check_license_task $task): void {
        ob_start();
        $task->execute();
        ob_get_clean();
    }

    /**
     * With no license key configured there is nothing that could expire.
     */
    public function test_state_is_nolicense_when_key_empty(): void {
        unset_config('licensekey', 'mooduell');
        $state = wb_payment::get_license_expiry_state();
        $this->assertEquals('nolicense', $state['state']);
    }

    /**
     * A key that cannot be decrypted is reported as invalid.
     */
    public function test_state_is_invalid_for_unreadable_key(): void {
        set_config('licensekey', 'this-is-not-a-real-key', 'mooduell');
        $state = wb_payment::get_license_expiry_state();
        $this->assertEquals('invalid', $state['state']);
    }

    /**
     * Admins are notified once (notification + email) and not again on subsequent runs,
     * and the one-time flag is cleared once the license is valid/removed again.
     */
    public function test_admins_are_notified_only_once(): void {
        // An unreadable key puts us into the "invalid" state, which must trigger a warning.
        set_config('licensekey', 'this-is-not-a-real-key', 'mooduell');

        $admincount = count(get_admins());
        $this->assertGreaterThan(0, $admincount);

        $task = new check_license_task();

        // First run: admins get exactly one notification and one e-mail each, flag gets set.
        $messagesink = $this->redirectMessages();
        $emailsink = $this->redirectEmails();

        $this->run_task($task);

        $this->assertCount($admincount, $messagesink->get_messages());
        $this->assertCount($admincount, $emailsink->get_messages());
        $this->assertEquals(1, get_config('mooduell', 'licenseexpirynotified'));

        $messagesink->clear();
        $emailsink->clear();

        // Second run with the same lapsed license: nothing new must be sent.
        $this->run_task($task);

        $this->assertCount(0, $messagesink->get_messages());
        $this->assertCount(0, $emailsink->get_messages());

        // Once the problem is resolved (here: key removed), the flag is re-armed for next time.
        unset_config('licensekey', 'mooduell');
        $this->run_task($task);
        $this->assertFalse(get_config('mooduell', 'licenseexpirynotified'));

        $messagesink->close();
        $emailsink->close();
    }
}
