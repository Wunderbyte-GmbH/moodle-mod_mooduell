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
namespace mod_mooduell\task;

defined('MOODLE_INTERNAL') || die();

use mod_mooduell\completion\completion_utils;
use coding_exception;
use mod_mooduell\game_control;
use mod_mooduell\mooduell;
use stdClass;

/**
 * Adhoc task to write challenge results into the table mooduell_challenge_results
 * at the specific expiration time of a challenge.
 */
class challenge_results_task extends \core\task\adhoc_task {

    /**
     * Execute the task.
     * {@inheritdoc}
     * @see \core\task\task_base::execute()
     */
    public function execute() {

        $taskdata = $this->get_custom_data();

        if ($taskdata != null) {
            echo 'Challenge results task: Started.' . PHP_EOL;

            global $DB;

            // If completionexpected timestamp has changed in the meantime, we don't do anything.
            if ($completionexpected = $DB->get_field('course_modules', 'completionexpected', ['id' => $taskdata->cmid])) {
                if ($completionexpected == $taskdata->completionexpected) {

                    // This is where the magic happens.
                    $this->store_challenge_results($taskdata);

                } else {
                    echo 'Challenge results task: Not executed beacuse expiration date has changed in the meantime.' . PHP_EOL;
                    return;
                }
            } else {
                echo 'Challenge results task: Not executed beacuse expiration date was removed in the meantime.' . PHP_EOL;
                return;
            }
        } else {
            throw new coding_exception(
                'Challenge results task: Not executed because no task data was provided.'
            );
        }

        echo 'Challenge results task: Finished.' . PHP_EOL;
    }

    /**
     * This function actually stores the challenge results to the DB.
     * @param stdClass $taskdata
     */
    private function store_challenge_results(stdClass $taskdata) {

        global $DB;

        echo 'Challenge results task: Now writing challenge results to table mooduell_challenge_results.'
                        . PHP_EOL;

        // Initialize everything we'll need.
        $mooduellid = $taskdata->mooduellid;
        $completionmodes = completion_utils::mooduell_get_completion_modes();
        $mooduellinstance = mooduell::get_mooduell_by_instance((int) $mooduellid);
        $players = game_control::return_users_for_game($mooduellinstance, false);

        foreach ($completionmodes as $completionmode => $statsfield) {

            if ($challenge = $DB->get_record('mooduell_challenges', ['mooduellid' => $mooduellid,
                                                                     'challengetype' => $completionmode])) {
                foreach ($players as $player) {

                    // Get the player's current statistics to set result values.
                    $studentstatistics = $mooduellinstance->return_list_of_statistics_student($player);

                    if ($challengeresult = $DB->get_record('mooduell_challenge_results', ['mooduellid' => $mooduellid,
                                                                                          'challengeid' => $challenge->id,
                                                                                          'userid' => $player->id])) {

                        if ($taskdata->completionexpected < $challengeresult->timemodified) {
                            echo 'Challenge results task: Could not update because a task with a later date has already run.'
                                . PHP_EOL;
                            return;
                        }

                        $challengeresult->result = (int) $studentstatistics[$statsfield];
                        $challengeresult->timemodified = time();

                        // Update, if there already is a stored challenge result.
                        $DB->update_record('mooduell_challenge_results', $challengeresult);
                    } else {
                        $challengeresult = new stdClass;
                        $challengeresult->mooduellid = $mooduellid;
                        $challengeresult->challengeid = $challenge->id;
                        $challengeresult->userid = $player->id;
                        $challengeresult->result = (int) $studentstatistics[$statsfield];
                        $challengeresult->timecreated = time();
                        $challengeresult->timemodified = time();

                        // Insert, if no record exists yet.
                        $DB->insert_record('mooduell_challenge_results', $challengeresult);
                    }
                }
            }
        }

        return;
    }
}
