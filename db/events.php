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
 * @package     mod_mooduell
 * @category    event
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(

        array(
                'eventname' => 'mod_mooduell\event\user_challenged',
                'callback' => 'mod_mooduell_observer::user_challenged'
        ),

        array(
                'eventname' => 'mod_mooduell\event\leg_finished',
                'callback' => 'mod_mooduell_observer::leg_finished'
        ),

        array(
                'eventname' => 'mod_mooduell\event\game_finished',
                'callback' => 'mod_mooduell_observer::game_finished'
        ),

        array(
                'eventname' => 'mod_mooduell\event\game_won',
                'callback' => 'mod_mooduell_observer::game_won'
        ),

        array(
                'eventname' => 'mod_mooduell\event\game_lost',
                'callback' => 'mod_mooduell_observer::game_lost'
        ),

        array(
                'eventname' => 'mod_mooduell\event\game_draw',
                'callback' => 'mod_mooduell_observer::game_draw'
        ),

        array(
                'eventname' => 'mod_mooduell\event\question_answered',
                'callback' => 'mod_mooduell_observer::question_answered'
        ),

        array(
                'eventname' => 'mod_mooduell\event\question_correctly_answered',
                'callback' => 'mod_mooduell_observer::question_correctly_answered'
        ),

        array(
                'eventname' => 'mod_mooduell\event\question_wrongly_answered',
                'callback' => 'mod_mooduell_observer::question_wrongly_answered'
        ),

        array(
                'eventname' => '\core\event\course_module_created',
                'callback' => 'mod_mooduell_observer::course_module_created'
        ),

        array(
                'eventname' => '\core\event\user_enrolment_created',
                'callback' => 'mod_mooduell_observer::user_enrolment_created'
        )
);
