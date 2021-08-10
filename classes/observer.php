<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin observer classes are defined here.
 *
 * @package     mod_mooduell
 * @category    event
 * @copyright   2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_mooduell\game_finished;
use mod_mooduell\manage_tokens;

/**
 * Event observer class.
 *
 * @package    mod_mooduell
 * @copyright  2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mooduell_observer {

    /**
     * Triggered via $event when a game has been finished.
     *
     * @param \mod_mooduell\event\game_finished $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function game_finished(\mod_mooduell\event\game_finished $event): bool
    {
        game_finished::update_highscores_table($event->objectid);

        return true;
    }

    /**
     * Triggered when a new MooDuell instance is added.
     * This will create tokens for all users of the instance.
     *
     * @param \core\event\course_module_created $event The event.
     * @return bool True on success.
     */
    public static function course_module_created(\core\event\course_module_created $event): bool
    {
        // The $event->objectid is the course_module id (cmid).
        manage_tokens::generate_tokens_for_all_instance_users($event->objectid);

        return true;
    }

    /**
     * Triggered when a new user enrolment has been created.
     * This will create a token for the new user.
     *
     * @param \core\event\user_enrolment_created $event The event.
     * @return bool True on success.
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): bool
    {
        // The $event->relateduserid stores the user for which to create the token.
        // $event->userid is the user who did the enrolment (which is irrelevant in this case).
        manage_tokens::generate_token_for_user($event->relateduserid);

        return true;
    }

}
