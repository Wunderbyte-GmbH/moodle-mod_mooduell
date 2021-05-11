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

    /**
     * Parameter to store the action (what to show in the mooduell_table)
     * @var String action ('opengames'|'finishedgames'|'questions'|'highscores')
     */
    var $action = null;

    /** TODO
     * mooduell_table constructor.
     * @param null $mooduell
     */
    public function __construct($mooduell, $action)
    {
        parent::__construct($action);
        $this->mooduell = $mooduell;
        $this->action = $action;
    }

    /* COLUMNS for OPEN GAMES and FINISHED GAMES */

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
        global $SESSION;
        if ($game->timemodified) {

            if ($SESSION->lang === 'de'){
                $name = date("d.m.Y, H:i:s", $game->timemodified);
            } else {
                $name = date("F j, Y, g:i:s a", $game->timemodified);
            }

            return $name;
        }
    }

    function col_mooduellid($game) {
        if ($game->mooduellid) {

            $name = $game->mooduellid;

            return $name;
        }
    }

    function col_action($game) {
        $cmid = $this->mooduell->cm->id;

        $link = '<a href="view.php?action=viewquestions&id=' . $cmid . '&gameid=' . $game->id .'">' .
                '<i class="fa fa-info"></i>'
                . '</a>';
        $link .= " ";
        $link .= '<a href="view.php?action=delete&id=' . $cmid . '&gameid=' . $game->id .'">' .
                '<i class="fa fa-trash"></i>'
                . '</a>';

        return $link;
    }

    /* COLUMNS for HIGHSCORES */

    function col_ranking($highscore_entry) {
        if ($highscore_entry->ranking) {

            $ranking = $highscore_entry->ranking;

            return $ranking;
        }
    }

    function col_userid($highscore_entry) {
        if ($highscore_entry->userid) {

            $username = $this->mooduell->return_name_by_id($highscore_entry->userid);

            return $username;
        }
    }

    function col_score($highscore_entry) {
        if ($highscore_entry->score !== null) {

            $score = $highscore_entry->score;

            return $score;
        }
    }

    function col_gamesplayed($highscore_entry) {
        if ($highscore_entry->gamesplayed !== null) {

            $gamesplayed = $highscore_entry->gamesplayed;

            return $gamesplayed;
        }
    }

    function col_gameswon($highscore_entry) {
        if ($highscore_entry->gameswon !== null) {

            $gameswon = $highscore_entry->gameswon;

            return $gameswon;
        }
    }

    function col_gameslost($highscore_entry) {
        if ($highscore_entry->gameslost !== null) {

            $gameslost = $highscore_entry->gameslost;

            return $gameslost;
        }
    }

    function col_qcorrect($highscore_entry) {
        if ($highscore_entry->qcorrect !== null) {

            $qcorrect = $highscore_entry->qcorrect;

            return $qcorrect;
        }
    }

    function col_qplayed($highscore_entry) {
        if ($highscore_entry->qplayed !== null) {

            $qplayed = $highscore_entry->qplayed;

            return $qplayed;
        }
    }

    function col_qcorrectpercentage($highscore_entry) {
        if ($highscore_entry->qcorrect !== null && $highscore_entry->qplayed !== null) {

            $qcorrectpercentage = number_format((($highscore_entry->qcorrect / $highscore_entry->qplayed) * 100), 1);

            return $qcorrectpercentage;
        }
    }
}