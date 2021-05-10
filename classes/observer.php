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
// namespace mod_mooduell;

defined('MOODLE_INTERNAL') || die();

use mod_mooduell\game_finished;
/**
 * Event observer class.
 *
 * @package    mod_mooduell
 * @copyright  2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mooduell_observer {

    /**
     * Triggered via $event.
     *
     * @param \mod_mooduell\event\game_finished $event The event.
     * @return bool True on success.
     */
    public static function game_finished($event) {

        // For more information about the Events API, please visit:
        // https://docs.moodle.org/dev/Event_2

        game_finished::update_highscores_table($event->objectid);

        return true;
    }
}
