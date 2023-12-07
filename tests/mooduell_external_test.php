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
use mod_mooduell_generator;
use mod_mooduell_external;

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
     * Summary of returntestdata
     * @return array
     */
    private function returntestdata() {
        global $CFG;

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
        $plugingenerator->create_mooduell_questions([
            'filepath' => $path,
            'courseid' => $course->id,
            'questioncategoryid' => $category->id,
        ]);

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

        return [$duel1, $user1, $user2, $cmd1, $course];
    }

    /**
     * Test start game attempt.
     * @runInSeparateProcess
     * @covers ::start_attempt
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_start_attempt() {

        list($duel1, $user1, $user2, $cmd1, $course) = $this->returntestdata();

        // Game will be started in behalf of user1.
        $this->setUser($user1);
        $attempt = mod_mooduell_external::start_attempt($course->id, $cmd1->id, $user2->id);

        // Check attempt.
        $this->assertEquals($user1->id, $attempt->playeraid);
        $this->assertEquals($user2->id, $attempt->playerbid);
        // Status: NULL is open game, 1 is player A\'s turn, 2 is player B\'s turn, 3 is finished!
        $this->assertEquals(1, $attempt->status);
        $this->assertEquals(0, $attempt->winnerid);
        $this->assertIsArray($attempt->questions);
        $this->assertObjectNotHasAttribute('warnings', $attempt);
    }

    /**
     * Test get games by courses.
     * @runInSeparateProcess
     * @covers ::get_games_by_courses
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_games_by_courses() {

        list($duel1, $user1, $user2, $cmd1, $course) = $this->returntestdata();

        // Game will be started in behalf of user1.
        $this->setUser($user1);
        $attempt = mod_mooduell_external::start_attempt($course->id, $cmd1->id, $user2->id);

        // Get games data.
        $games = mod_mooduell_external::get_games_by_courses([$course->id], -1);

        // Check games.
        $this->assertIsArray($games["quizzes"][0]);
        $this->assertEquals(1, count($games["quizzes"]));
        $this->assertEquals($course->id, $games["quizzes"][0]["courseid"]);
        $this->assertEquals($cmd1->id, $games["quizzes"][0]["coursemodule"]);
        $this->assertEquals($duel1->course, $games["quizzes"][0]["courseid"]);
        $this->assertEquals($duel1->cmid, $games["quizzes"][0]["coursemodule"]);
        $this->assertIsArray($games["quizzes"][0]["games"]);
        $this->assertEquals(1, count($games["quizzes"][0]["games"]));
        // Check game attempt.
        $this->assertEquals($user1->id, $games["quizzes"][0]["games"][0]["playeraid"]);
        $this->assertEquals($user2->id, $games["quizzes"][0]["games"][0]["playerbid"]);
        // Status: NULL is open game, 1 is player A\'s turn, 2 is player B\'s turn, 3 is finished!
        $this->assertEquals(1, $games["quizzes"][0]["games"][0]["status"]);
        $this->assertEquals(0, $games["quizzes"][0]["games"][0]["winnerid"]);
        $this->assertEmpty($games["warnings"]);
    }

    /**
     * Test get quiz users.
     * @runInSeparateProcess
     * @covers ::get_quiz_users
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_quiz_users() {

        list($duel1, $user1, $user2, $cmd1, $course) = $this->returntestdata();

        $users = mod_mooduell_external::get_quiz_users($course->id, $cmd1->id);

        // Check users.
        $this->assertIsArray($users);
        $this->assertEquals(2, count($users));
        // TODO: no built-in methods to compare stdClass instances.
        $ids = array_map(function($item) {
            return $item->id;
        }, $users);
        $emails = array_map(function($item) {
            return $item->email;
        }, $users);
        $usernames = array_map(function($item) {
            return $item->username;
        }, $users);
        $this->assertEquals(true, in_array($user1->id, $ids));
        $this->assertEquals(true, in_array($user2->id, $ids));
        $this->assertEquals(true, in_array($user1->email, $emails));
        $this->assertEquals(true, in_array($user2->email, $emails));
        $this->assertEquals(true, in_array($user1->username, $usernames));
        $this->assertEquals(true, in_array($user2->username, $usernames));
    }

    /**
     * Test get quizzes by courses.
     * @runInSeparateProcess
     * @covers ::get_quizzes_by_courses
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_quizzes_by_courses() {

        list($duel1, $user1, $user2, $cmd1, $course) = $this->returntestdata();

        $duels = mod_mooduell_external::get_quizzes_by_courses([$course->id], -1);

        // Check no warnings.
        $this->assertEmpty($duels['warnings']);
        // Check moduel.
        $this->assertIsArray($duels['quizzes']);
        $this->assertEquals(1, count($duels['quizzes']));
        $this->assertEquals($course->id, $duels["quizzes"][0]["courseid"]);
        $this->assertEquals($cmd1->id, $duels["quizzes"][0]["coursemodule"]);
        $this->assertEquals($duel1->course, $duels["quizzes"][0]["courseid"]);
        $this->assertEquals($duel1->cmid, $duels["quizzes"][0]["coursemodule"]);
        $this->assertEquals($duel1->name, $duels["quizzes"][0]["quizname"]);
        $this->assertEquals($duel1->usefullnames, $duels["quizzes"][0]["usefullnames"]);
    }

    /**
     * Test answer_question.
     * @runInSeparateProcess
     * @covers ::answer_question
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_answer_question() {

        list($duel1, $user1, $user2, $cmd1, $course) = $this->returntestdata();

        // Game will be started in behalf of user1.
        $this->setUser($user1);
        $attempt = mod_mooduell_external::start_attempt($course->id, $cmd1->id, $user2->id);
        // Player A question 0 - submit correct answer.
        $questionid = (int) $attempt->questions[0]->questionid;
        $answerid = array_search(true, array_column($attempt->questions[0]->answers, 'correct', 'id'));
        $res = mod_mooduell_external::answer_question($cmd1->id, $attempt->gameid, $questionid, [$answerid]);
        $this->assertEquals(1, $res["iscorrect"]);
        // Player A question 1 - submit incorrect answer.
        $questionid = (int) $attempt->questions[1]->questionid;
        $answerid = array_search(false, array_column($attempt->questions[1]->answers, 'correct', 'id'));
        $res = mod_mooduell_external::answer_question($cmd1->id, $attempt->gameid, $questionid, [$answerid]);
        $this->assertEquals(0, $res["iscorrect"]);
    }

    /**
     * Test get game data.
     * @runInSeparateProcess
     * @covers ::get_game_data
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_game_data() {

        list($duel1, $user1, $user2, $cmd1, $course) = $this->returntestdata();

        // Game will be started in behalf of user1.
        $this->setUser($user1);
        $attempt = mod_mooduell_external::start_attempt($course->id, $cmd1->id, $user2->id);

        // Player A question 0 - submit correct answer.
        $questionid = (int) $attempt->questions[0]->questionid;
        $answerid = array_search(true, array_column($attempt->questions[0]->answers, 'correct', 'id'));
        $res = mod_mooduell_external::answer_question($cmd1->id, $attempt->gameid, $questionid, [$answerid]);

        // Player A question 1 - submit incorrect answer.
        $questionid = (int) $attempt->questions[1]->questionid;
        $answerid = array_search(false, array_column($attempt->questions[1]->answers, 'correct', 'id'));
        $res = mod_mooduell_external::answer_question($cmd1->id, $attempt->gameid, $questionid, [$answerid]);

        // Get games data.
        $game = mod_mooduell_external::get_game_data($course->id, $cmd1->id, $attempt->gameid);
        // Check game data.
        $this->assertEquals($user1->id, (int) $game->playeraid);
        $this->assertEquals($user2->id, (int) $game->playerbid);
        $this->assertEquals(1, $game->playeracorrect);
        $this->assertEquals(0, $game->playerbcorrect);
        $this->assertEquals(2, $game->playeraqplayed);
        $this->assertEquals(0, $game->playerbqplayed);
        $this->assertEquals(0, $game->winnerid);
        $this->assertEquals(1, $game->status);
    }
}
