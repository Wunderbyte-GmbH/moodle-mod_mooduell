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

declare(strict_types=1);

namespace mod_mooduell\completion;

use core_completion\activity_custom_completion;
use mod_mooduell\mooduell;
use stdClass;

/**
 * Activity custom completion subclass for the MooDuell activity.
 *
 * Class for defining mod_quiz's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given quiz instance and a user.
 *
 * @package   mod_quiz
 * @copyright 2021 Shamim Rezaie <shamim@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completiongamesplayed',
            'completiongameswon',
            'completionrightanswers'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        global $DB;

        $gamesplayed = $this->cm->customdata['customcompletionrules']['completiongamesplayed'] ?? 0;
        $gameswon = $this->cm->customdata['customcompletionrules']['completiongameswon'] ?? 0;
        $rightanswers = $this->cm->customdata['customcompletionrules']['completionrightanswers'] ?? 0;

        return [
            'completiongamesplayed' => get_string('completiondetail:gamesplayed', 'mod_mooduell', $gamesplayed),
            'completiongameswon' => get_string('completiondetail:gameswon', 'mod_mooduell', $gameswon),
            'completionrightanswers' => get_string('completiondetail:rightanswers', 'mod_mooduell', $rightanswers)
        ];
    }

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;
        // Make sure to validate the custom completion rule first.
        $this->validate_rule($rule);

        // If completion option is enabled, evaluate it and return true/false.
        $mooduell = $DB->get_record('mooduell', ['id' => $this->cm->instance], '*', MUST_EXIST);

        $mooduellinstance = mooduell::get_mooduell_by_instance((int) $this->cm->instance);
        $studentstatistics = $mooduellinstance->return_list_of_statistics_student();

        // List of completion modes and the according fields in table $studentstatistics.
        $completionmodes = self::mooduell_get_completion_modes();

        $status = COMPLETION_INCOMPLETE;

        // Now, get the completion status of the custom completion rule.
        if (!empty($mooduell->{$rule})) {
            // Check the actual number against the target number.
            if ($studentstatistics[$completionmodes[$rule]] >= $mooduell->{$rule}) {
                $status = COMPLETION_COMPLETE;
            } else {
                $status = COMPLETION_INCOMPLETE;
            }
        }

        return $status;
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completiongamesplayed',
            'completiongameswon',
            'completionrightanswers'
        ];
    }

    /**
     * Helper function to retrieve a list of all completion modes ...
     * ... and their associated field names in student statistics.
     * @return array $completionmodes
     */
    public static function mooduell_get_completion_modes() {
        // List of completion modes and the according fields in table $studentstatistics.
        $completionmodes = [
            'completiongamesplayed' => 'number_of_games_finished',
            'completiongameswon' => 'number_of_games_won',
            'completionrightanswers' => 'number_of_correct_answers'
        ];

        return $completionmodes;
    }

    /**
     * Helper function to create the challenges JSON needed for activity completion.
     * @param mooduell $mooduellinstance A MooDuell instance.
     * @return string An encoded JSON string containing all challenges.
     */
    public static function get_completion_challenges_json_string($mooduellinstance) {
        $completionmodes = self::mooduell_get_completion_modes();
        $studentstatistics = $mooduellinstance->return_list_of_statistics_student();

        $challengesarray = [];

        foreach ($completionmodes as $completionmode => $statsfield) {
            if (!empty($mooduellinstance->{$completionmode})) {
                $challenge = new stdClass();
                $challenge->challengetype = $completionmode;
                // TODO: challenge->challengename
                $challenge->actualnumber = $studentstatistics[$statsfield];
                $challenge->targetnumber = $mooduellinstance->{$completionmode};
                // TODO: challenge->targetdate
                // TODO: challenge->challengerank

                $challengesarray[] = $challenge;
            }
        }

        return json_encode($challengesarray);
    }
}
