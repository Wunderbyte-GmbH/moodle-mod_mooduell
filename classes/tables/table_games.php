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
 * Table SQL class for displaying MooDuell games.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\tables;

use local_wunderbyte_table\wunderbyte_table;
use mod_mooduell\mooduell;
use mod_mooduell\output\list_action;
use mod_mooduell\output\renderer;
use stdClass;

/**
 * MooDuell Table sql class.
 */
class table_games extends wunderbyte_table {
    /**
     * Parameter to store the action (what to show in the mooduell_table)
     * @var string action ('opengames'|'finishedgames'|'questions'|'highscores')
     */
    public $action;

    /**
     * @var int mooduellid
     */
    public $mooduellid;

    /**
     * mooduell_table constructor
     *
     * @param string $action
     * @param int $mooduellid
     */
    public function __construct($action, int $mooduellid) {
        global $PAGE, $USER;

        parent::__construct($action . $USER->id . $mooduellid);

        if ($mooduellid) {
            $this->mooduellid = $mooduellid;
        }
        $this->action = $action;

        $this->define_cache('mod_mooduell', 'tablescache');
    }

    /**
     * Function to return the players name instead of id
     * @param object $game
     */
    public function col_playeraid($game) {
        if ($game->playeraid) {
            $mooduell = mooduell::get_instance($this->mooduellid);
            $name = $mooduell->return_name_by_id($game->playeraid);

            return $name;
        }
    }

    /**
     * Function to return the players name instead of id
     * @param object $game
     */
    public function col_playerbid($game) {
        if ($game->playerbid) {
            $mooduell = mooduell::get_instance($this->mooduellid);
            $name = $mooduell->return_name_by_id($game->playerbid);

            return $name;
        }
    }

    /**
     * Function to return the readable date instead of timestamp.
     * @param object $game
     */
    public function col_timemodified($game) {
        if ($game->timemodified) {
            return userdate($game->timemodified, get_string('strftimedatetime', 'core_langconfig'));
        }
        return '';
    }

    /**
     * Function to return the MooDuell id.
     * @param object $game
     */
    public function col_mooduellid($game) {
        if ($this->mooduellid) {
            $name = $this->mooduellid;

            return $name;
        }
    }

    /**
     * Function to return clickable action links.
     * @param object $game
     */
    public function col_action($game) {

        global $OUTPUT;

        $mooduell = mooduell::get_instance($this->mooduellid);
        $action = new list_action($game->id, $game, $mooduell);

        return $OUTPUT->render_from_template('mod_mooduell/list_action', $action->export_for_template($OUTPUT));
    }
}
