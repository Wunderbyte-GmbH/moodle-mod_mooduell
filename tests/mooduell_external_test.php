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
 * Tests for mooduell external functions.
 *
 * @package mod_mooduell
 * @category test
 * @copyright 2023 Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use advanced_testcase;
use coding_exception;
use context_course;
use moodle_exception;
use stdClass;
use mod_mooduell_generator;
use mod_mooduell_external;
use mod_quiz\question\bank\qbank_helper;

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->libdir.'/questionlib.php');

/**
 * Test class for mooduell external functions.
 *
 * @package mod_mooduell
 * @category test
 * @copyright 2023 Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mooduell_external_test extends advanced_testcase {

    /**
     * Tests set up.
     */
    public function setUp():void {
        $this->resetAfterTest();
    }

    /**
     * Test start game attempt.
     * @runInSeparateProcess
     * @covers ::start_attempt
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_start_game_attempt() {
        global $DB, $CFG;

        $CFG->enablecompletion = 1;

        $this->setAdminUser();

        // Setup test course.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $coursectx = context_course::instance($course->id);

        // Create categoru in question bank.
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(['contextid' => $coursectx->id]);

        // Import questions into that category.
        $path = 'mod/mooduell/tests/fixtures/testquestions.xml';
        /** @var mod_mooduell_generator $plugingenerator */
        $plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_mooduell');
        $plugingenerator->create_mooduell_questions(['filepath' => $path, 'courseid' => $course->id, 'category' => $category]);

        // Create mooduell instance.
        $ddata = [
            'name' => 'Mooduell Test', 'intro' => 'Mooduell Test', 'usefullnames' => 1, 'idnumber' => 'mooduell1',
            'course' => $course->id, 'categoriesgroup0' => ['category' => $category->id, 'weight' => 100],

        ];
        $duel1 = $this->getDataGenerator()->create_module('mooduell', $ddata);
        $cmd1 = get_coursemodule_from_instance('mooduell', $duel1->id);

        // Create users and enroll into course.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Game will be started in behalf of user1.
        $this->setUser($user1);
        $attempt = mod_mooduell_external::start_attempt($course->id, $cmd1->id, $user2->id);

        // Check attempt.
        $this->assertEquals($user1->id, $attempt->playeraid);
        $this->assertEquals($user2->id, $attempt->playerbid);
        // Status: NULL is open game, 1 is player A\'s turn, 2 is player B\'s turn, 3 is finished!
        $this->assertEquals(1, $attempt->status);
    }
}
