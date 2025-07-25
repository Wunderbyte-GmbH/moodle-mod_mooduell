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
 * Plugin event class for event question_correctly_answered.
 *
 * @package     mod_mooduell
 * @copyright   2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\event;

/**
 * The mod_mooduell question_correctly_answered event class.
 *
 * @package mod_mooduell
 * @since Moodle 3.5
 * @copyright 2021 onwards Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_correctly_answered extends \core\event\base {
    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns the description for event logs.
     * @return string
     */
    public function get_description() {

        $userid = $this->data['userid'];
        $questionid = $this->data['other']['questionid'];

        $message = "The user with the id {$userid} has answered question {$questionid} correctly.";

        return $message;
    }
}
