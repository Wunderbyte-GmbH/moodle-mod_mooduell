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
 * Plugin event observers are registered here.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class mooduell_table extends table_sql {

    /**
     * // Only reason to extend this class is to make mooduell class instance available
     * @var null
     */
    var $mooduell = null;


    function col_playeraid($game) {
        if ($game->playeraid) {

            $name = $this->mooduell->return_name_by_id($game->playeraid);

            return $name;
        }
    }

    function col_playerbid($game) {
        if ($game->playerbid) {

            $name = $this->mooduell->return_name_by_id($game->playerbid);

            return $name;
        }
    }

    function col_timemodified($game) {
        if ($game->timemodified) {

            $name = date("F j, Y, g:i a", $game->timemodified);

            return $name;
        }
    }

    function col_mooduellid($game) {
        if ($game->mooduellid) {

            $name = $game->mooduellid;

            return $name;
        }
    }

    function col_action() {

        return 'action';
    }


}