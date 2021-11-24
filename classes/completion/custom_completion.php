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
use mod_mooduell\completion\completion_utils;

/**
 * Activity custom completion subclass for the MooDuell activity.
 *
 * @package   mod_mooduell
 * @copyright 2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion
{

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array
    {
        return [
            'completiongamesplayed',
            'completiongameswon',
            'completionrightanswers',
            'completionrightanswersperc'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array
    {
        global $DB;

        $gamesplayed = $this->cm->customdata['customcompletionrules']['completiongamesplayed'] ?? 0;
        $gameswon = $this->cm->customdata['customcompletionrules']['completiongameswon'] ?? 0;
        $rightanswers = $this->cm->customdata['customcompletionrules']['completionrightanswers'] ?? 0;
        $rightanswersperc = $this->cm->customdata['customcompletionrules']['completionrightanswersperc'] ?? 0;

        return [
            'completiongamesplayed' => get_string('completiondetail:gamesplayed', 'mod_mooduell', $gamesplayed),
            'completiongameswon' => get_string('completiondetail:gameswon', 'mod_mooduell', $gameswon),
            'completionrightanswers' => get_string('completiondetail:rightanswers', 'mod_mooduell', $rightanswers),
            'completionrightanswersperc' => get_string('completiondetail:rightanswersperc', 'mod_mooduell', $rightanswersperc)
        ];
    }

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int
    {
        global $DB;
        // Make sure to validate the custom completion rule first.
        $this->validate_rule($rule);

        // If completion option is enabled, evaluate it and return true/false.
        $mooduellid = $this->cm->instance;
        $mooduell = $DB->get_record('mooduell', ['id' => $mooduellid], '*', MUST_EXIST);

        $mooduellinstance = mooduell::get_mooduell_by_instance((int) $mooduellid);
        $studentstatistics = $mooduellinstance->return_list_of_statistics_student();

        // List of completion modes and the according fields in table $studentstatistics.
        $completionmodes = completion_utils::mooduell_get_completion_modes();

        $status = COMPLETION_INCOMPLETE;

        // Now, get the completion status of the custom completion rule.
        if ($targetnumber = $DB->get_field(
            'mooduell_challenges',
            'targetnumber',
            ['mooduellid' => $mooduellid, 'challengetype' => $rule]
        )) {

            // Check the actual number against the target number.
            if ($studentstatistics[$completionmodes[$rule]] >= $targetnumber) {
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
    public function get_sort_order(): array
    {
        return [
            'completiongamesplayed',
            'completiongameswon',
            'completionrightanswers',
            'completionrightanswersperc'
        ];
    }
}
