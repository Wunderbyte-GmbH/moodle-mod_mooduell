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
 * Tests for the mod_mooduell settings form.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use advanced_testcase;
use cm_info;
use ReflectionClass;

/**
 * Test class for the mod_mooduell settings form.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_mooduell_mod_form
 */
final class mod_form_test extends advanced_testcase {
    /**
     * The settings form must build its question selection across all supported Moodle versions.
     *
     * This is a regression guard for the activity settings form crashing while listing question
     * categories. On Moodle 5.0+ the categories come from shareable question banks, whose result
     * objects changed shape in 5.2 (the typed formatted_bank dropped the ->contextid property used
     * before). On Moodle 4.x the categories come from the course context instead. In every case the
     * form definition must build without throwing.
     */
    public function test_settings_form_builds_question_selection(): void {
        global $CFG, $PAGE, $COURSE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        // On Moodle 5.0+ the form lists categories from shareable question banks, so make sure the
        // course actually has one. This exercises the shared-bank code path that crashed on 5.2.
        // The API (and the formatted_bank object) does not exist on 4.x, where the course context
        // path is used instead, so we guard the setup by version.
        if ($CFG->version >= 2025041400) {
            \core_question\local\bank\question_bank_helper::create_default_open_instance($course, 'Test bank');

            // Confirm the shared-bank path the form relies on is actually populated (otherwise this
            // test would pass without ever exercising the code that crashed on 5.2), and assert the
            // exact contract the fix depends on: each bank exposes a cm_info whose context resolves.
            $banks = \core_question\local\bank\question_bank_helper::get_activity_instances_with_shareable_questions(
                [$course->id]
            );
            $this->assertNotEmpty($banks);
            foreach ($banks as $bank) {
                $this->assertInstanceOf(cm_info::class, $bank->cminfo);
                $this->assertInstanceOf(\context_module::class, $bank->cminfo->context);
            }
        }

        $mooduell = $this->getDataGenerator()->create_module('mooduell', ['course' => $course->id]);
        $cm = cm_info::create(get_coursemodule_from_instance('mooduell', $mooduell->id, $course->id, false, MUST_EXIST));

        // The form reads the active course from these globals.
        $COURSE = $course;
        $PAGE->set_course($course);

        require_once($CFG->dirroot . '/mod/mooduell/mod_form.php');

        $data = (object) [
            'instance' => $mooduell->id,
            'id' => $cm->id,
            'course' => $course->id,
        ];

        // Constructing the form runs definition() -> mooduell_questions(). Before the fix this threw
        // "Undefined property: core_question\local\bank\formatted_bank::$contextid" on Moodle 5.2.
        $form = new \mod_mooduell_mod_form($data, 0, $cm, $course);

        $mform = (new ReflectionClass(\mod_mooduell_mod_form::class))
            ->getProperty('_form')
            ->getValue($form);

        // The question-selection section was added, which means mooduell_questions() completed.
        $this->assertTrue($mform->elementExists('questionsettings'));
    }
}
