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
 * The mod_mooduell course module viewed event.
 *
 * @package mod_mooduell
 * @since Moodle 2.7
 * @copyright 2013 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\event;

/**
 * The mod_mooduell course module viewed event class.
 *
 * @package mod_mooduell
 * @since Moodle 3.5
 * @copyright 2020 onwards Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * get_objectid_mapping function
     * @return string[]
     */
    public static function get_objectid_mapping() {
        return [
                'db' => 'mooduell',
                'restore' => 'mooduell',
        ];
    }

    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'lesson';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }
}
