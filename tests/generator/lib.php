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
 * mod_mooduell data generator.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2021 Georg Maisser <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * mod_lesson data generator class.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2021 Georg Maisser <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mooduell_generator extends testing_module_generator {

    /**
     * @var int keep track of how many games have been created.
     */
    protected $gamescount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->gamescount = 0;
        parent::reset();
    }

    /**
     * To create a new instance.
     * @param null $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;

        return parent::create_instance($record, (array)$options);
    }
}
