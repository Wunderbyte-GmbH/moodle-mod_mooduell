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

use coding_exception;
use mod_mooduell\mooduell;
use stdClass;

/**
 * A library of completion functions used for custom completion.
 *
 * @package   mod_mooduell
 * @copyright 2021 Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_utils
{
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
            'completionrightanswers' => 'number_of_correct_answers',
            'completionrightanswersperc' => 'percentage_of_correct_answers'
        ];

        return $completionmodes;
    }

    /**
     * Helper function to create the challenges array needed for the get_game_data webservice.
     * @param mooduell $mooduellinstance A MooDuell instance.
     * @return array An array of objects. Each object contains a challenge.
     */
    public static function get_completion_challenges_array($mooduellinstance): array {
        global $DB, $USER;

        $mooduellid = $mooduellinstance->cm->instance;
        $completionexpected = $mooduellinstance->cm->completionexpected;

        $completionmodes = self::mooduell_get_completion_modes();
        $studentstatistics = $mooduellinstance->return_list_of_statistics_student();

        $challengesarray = [];

        foreach ($completionmodes as $completionmode => $statsfield) {

            if ($challenge = $DB->get_record(
                'mooduell_challenges',
                ['mooduellid' => $mooduellid, 'challengetype' => $completionmode]
            )) {

                // Remove fields not supported by webservice.
                unset($challenge->mooduellid);

                // If the challenge is already expired take the result value from the challenge results table.
                if ($mooduellinstance->cm->completion == 2 && $completionexpected != 0 && time() > $completionexpected) {
                    if (!$challenge->actualnumber = (int) $DB->get_field('mooduell_challenge_results', 'result', [
                        'mooduellid' => $mooduellid,
                        'challengeid' => $challenge->id,
                        'userid' => $USER->id
                    ])) {
                        // Error prevention.
                        $challenge->actualnumber = 0;
                    }

                    // Check the actual number against the target number.
                    if ($challenge->actualnumber >= $challenge->targetnumber) {
                        $challenge->status = 'CHALLENGE_EXPIRED_PASS'; // Challenge expired. User passed.
                    } else {
                        $challenge->status = 'CHALLENGE_EXPIRED_FAIL'; // Challenge expired. User failed.
                    }
                } else {
                    // Else retrieve the actual number from the statistics.
                    $challenge->actualnumber = (int) $studentstatistics[$statsfield];

                    // Check the actual number against the target number.
                    if ($challenge->actualnumber >= $challenge->targetnumber) {
                        $challenge->status = 'CHALLENGE_ACTIVE_COMPLETE'; // Challenge still open, but already complete.
                    } else {
                        $challenge->status = 'CHALLENGE_ACTIVE_INCOMPLETE'; // Challenge still open, but not finished yet.
                    }
                }

                // Calculate challenge percentage.
                $percentage = null;
                if (isset($challenge->actualnumber) && !empty($challenge->targetnumber)) {
                    if ($challenge->actualnumber == 0) {
                        $percentage = 0;
                    } else if (
                        $challenge->actualnumber > 0
                        && $challenge->actualnumber < $challenge->targetnumber
                    ) {
                        $percentage = ($challenge->actualnumber / $challenge->targetnumber) * 100;
                    } else if ($challenge->actualnumber >= $challenge->targetnumber) {
                        $percentage = 100;
                    } else {
                        $percentage = null;
                    }
                } else {
                    $percentage = null;
                }
                $challenge->challengepercentage = $percentage ? (int) floor($percentage) : null;

                // Date until the challenge needs to be done.
                $challenge->targetdate = $mooduellinstance->cm->completionexpected ?? null;

                // TODO: Calculate a user's rank within a challenge. - Will be done in a future release.
                $challenge->challengerank = null;

                // Add an array of objects containing localized language strings needed by the app.
                $localizedstrings = [];
                $stringman = get_string_manager();
                $languages = $stringman->get_list_of_translations();

                foreach ($languages as $langkey => $langval) {
                    $stringobj = new stdClass;
                    $stringobj->lang = $langkey;
                    $stringobj->stringkey = $completionmode;
                    $stringobj->stringval = $stringman->get_string('app:' . $completionmode, 'mooduell',
                        $challenge->targetnumber, $langkey);
                    $localizedstrings[] = $stringobj;
                }

                $challenge->localizedstrings = $localizedstrings;

                $challengesarray[] = $challenge;
            }
        }

        return $challengesarray;
    }
}
