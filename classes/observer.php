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


use mod_mooduell\game_finished;
use mod_mooduell\manage_tokens;
use mod_mooduell\event\game_draw;
use mod_mooduell\event\game_lost;
use mod_mooduell\event\game_won;
use mod_mooduell\event\question_correctly_answered;
use mod_mooduell\event\question_wrongly_answered;

/**
 * Event observer class.
 *
 * @package    mod_mooduell
 * @copyright  2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mooduell_observer {

    /**
     * Will be triggered when a game has been finished.
     *
     * @param \mod_mooduell\event\game_finished $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function game_finished(\mod_mooduell\event\game_finished $event): bool {

        // Get the right context for cmid.
        $data = $event->get_data();
        $context = $event->get_context();

        $playeraid = $data['other']['playeraid'];
        $playerbid = $data['other']['playerbid'];
        $winnerid = $data['other']['winnerid'];
        $loserid = 0;

        if ($winnerid == 0) {
            $drawevent = game_draw::create([
                'context' => $context,
                'userid' => $playeraid,
                'relateduserid' => $playerbid,
            ]);
            $drawevent->trigger();
        } else {
            // Trigger the game_won event for the winner ...
            if ($winnerid == $playeraid) {
                $loserid = $playerbid;
            } else {
                $winnerid = $playerbid;
                $loserid = $playeraid;
            }

            $wonevent = game_won::create([
                'context' => $context,
                'userid' => $winnerid,
                'relateduserid' => $loserid,
            ]);
            $wonevent->trigger();

            // ... and the game_lost event for the loser.
            $lostevent = game_lost::create([
                'context' => $context,
                'userid' => $loserid,
                'relateduserid' => $winnerid,
            ]);
            $lostevent->trigger();
        }

        // Now, update highscores and statistics.
        game_finished::update_highscores_table($data['objectid']);

        return true;
    }

    /**
     * Will be triggered when a game has been won.
     *
     * @param \mod_mooduell\event\game_won $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function game_won(\mod_mooduell\event\game_won $event): bool {
        // Currently we do nothing.
        return true;
    }

    /**
     * Will be triggered when a game has been lost.
     *
     * @param \mod_mooduell\event\game_lost $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function game_lost(\mod_mooduell\event\game_lost $event): bool {
        // Currently we do nothing.
        return true;
    }

    /**
     * Will be triggered when a game has been a draw.
     *
     * @param \mod_mooduell\event\game_draw $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function game_draw(\mod_mooduell\event\game_draw $event): bool {
        // Currently we do nothing.
        return true;
    }

    /**
     * Will be triggered after a question has been answered.
     *
     * @param \mod_mooduell\event\question_answered $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function question_answered(\mod_mooduell\event\question_answered $event): bool {

        // Get the right context for cmid.
        $data = $event->get_data();
        $context = $event->get_context();
        $questionid = $data['other']['questionid'];

        if ($event->other['iscorrect'] == true) {
            // Question was answered correctly.
            $qcorrectevent = question_correctly_answered::create([
                'context' => $context,
                'other' => [
                    'questionid' => $questionid,
                ],
            ]);
            $qcorrectevent->trigger();
        } else {
            // Question was answered wrongly.
            $qwrongevent = question_wrongly_answered::create([
                'context' => $context,
                'other' => [
                    'questionid' => $questionid,
                ],
            ]);
            $qwrongevent->trigger();
        }

        self::delete_cache($event, true);

        return true;
    }

    /**
     * Will be triggered when a question has been answered correctly.
     *
     * @param \mod_mooduell\event\question_correctly_answered $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function question_correctly_answered(\mod_mooduell\event\question_correctly_answered $event): bool {
        // Currently we do nothing.
        return true;
    }

    /**
     * Will be triggered when a question has been answered wrongly.
     *
     * @param \mod_mooduell\event\question_wrongly_answered $event The event.
     * @return bool True on success.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function question_wrongly_answered(\mod_mooduell\event\question_wrongly_answered $event): bool {
        // Currently we do nothing.
        return true;
    }

    /**
     * Will be triggered when a new MooDuell instance is added.
     * This will create tokens for all users of the instance.
     *
     * @param \core\event\course_module_created $event The event.
     * @return bool True on success.
     */
    public static function course_module_created(\core\event\course_module_created $event): bool {
        // The $event->objectid is the course_module id (cmid).
        $data = $event->get_data();
        if ($data['other']['modulename'] === 'mooduell') {
            manage_tokens::generate_tokens_for_all_instance_users($event->objectid);
        }
        return true;
    }

    /**
     * Will be triggered when a new user enrolment has been created.
     * This will create a token for the new user.
     *
     * @param \core\event\user_enrolment_created $event The event.
     * @return bool True on success.
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): bool {
        // The $event->relateduserid stores the user for which to create the token.
        // $event->userid is the user who did the enrolment (which is irrelevant in this case).
        manage_tokens::generate_token_for_user($event->relateduserid);

        return true;
    }


    /**
     * Will be triggered when a new user enrolment has been created.
     * This will create a token for the new user.
     *
     * @param \core\event\badge_awarded $event
     *
     * @return bool
     */
    public static function badge_awarded(\core\event\badge_awarded $event): bool {
        // The $event->relateduserid stores the user for which to create the token.
        // $event->userid is the user who did the enrolment (which is irrelevant in this case).

        $context = $event->get_context();
        $cmid = $context->instanceid;

        return true;
    }

    /**
     * Will be triggered by a number of events regarding question manipulation.
     * We need to update our cached tables, therefore we listen and do nothing bug triggering the corresponding event.
     *
     * @param mixed|null $event
     * @param bool $onlytables
     *
     * @return bool True on success.
     */
    public static function delete_cache($event = null, $onlytables = false): bool {

        cache_helper::purge_by_event('setbacktablescache');
        if (!$onlytables) {
            cache_helper::purge_by_event('setbackquestionscache');
        }

        return true;
    }
}
