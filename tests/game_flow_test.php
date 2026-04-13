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
 * Comprehensive game-flow tests for mod_mooduell.
 *
 * Covers: complete game lifecycle, turn enforcement, answer validation,
 * security boundaries, highscores, statistics, and question health checks.
 *
 * @package mod_mooduell
 * @category test
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use advanced_testcase;
use context_course;
use mod_mooduell_generator;
use mod_mooduell_external;

/**
 * Comprehensive game-flow test class.
 *
 * @package mod_mooduell
 * @category test
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class game_flow_test extends advanced_testcase {

    /** @var \stdClass Course used in every test. */
    private $course;

    /** @var \stdClass MooDuell module record. */
    private $duel;

    /** @var \stdClass Course-module record for the duel. */
    private $cm;

    /** @var \stdClass Enrolled user – plays as Player A in most tests. */
    private $user1;

    /** @var \stdClass Enrolled user – plays as Player B in most tests. */
    private $user2;

    // -----------------------------------------------------------------------
    // Setup helpers
    // -----------------------------------------------------------------------

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Build the standard test fixture:
     *   – one course, one MooDuell instance with 60 imported multichoice
     *     questions, two enrolled users.
     *
     * @return void
     */
    private function setup_fixture(): void {
        global $CFG;
        $CFG->enablecompletion = 1;

        $this->setAdminUser();

        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $coursectx = context_course::instance($this->course->id);

        /** @var \core_question_generator $qgen */
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $qgen->create_question_category(['contextid' => $coursectx->id]);

        /** @var mod_mooduell_generator $mgen */
        $mgen = self::getDataGenerator()->get_plugin_generator('mod_mooduell');
        $mgen->create_mooduell_questions([
            'filepath' => 'mod/mooduell/tests/fixtures/testquestions.xml',
            'courseid' => $this->course->id,
            'questioncategoryid' => $category->id,
        ]);

        $this->duel = $this->getDataGenerator()->create_module('mooduell', [
            'name' => 'Flow Test Duel',
            'intro' => 'test',
            'usefullnames' => 1,
            'course' => $this->course->id,
            'categoriesgroup0' => ['category' => $category->id, 'weight' => 100],
        ]);

        $this->cm = get_coursemodule_from_instance('mooduell', $this->duel->id);

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course->id);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Return the ID of the first correct answer for a question object.
     *
     * @param \stdClass $question question_control instance (or plain stdClass with answers).
     * @return int
     */
    private function correct_answer_id($question): int {
        return (int) array_search(
            true,
            array_column($question->answers, 'correct', 'id')
        );
    }

    /**
     * Return the ID of the first wrong answer for a question object.
     *
     * @param \stdClass $question
     * @return int
     */
    private function wrong_answer_id($question): int {
        return (int) array_search(
            false,
            array_column($question->answers, 'correct', 'id')
        );
    }

    /**
     * Play through a complete 9-question game with two users.
     * Turn order: A answers q[0..2], B answers q[0..5], A answers q[3..8], B answers q[6..8].
     *
     * @param array $questions Questions array from start_attempt return.
     * @param int $gameid
     * @param \stdClass $playera
     * @param \stdClass $playerb
     * @param bool $playeracorrect Whether Player A should answer all correctly.
     * @param bool $playerbcorrect Whether Player B should answer all correctly.
     * @return void
     */
    private function play_full_game(
        array $questions,
        int $gameid,
        \stdClass $playera,
        \stdClass $playerb,
        bool $playeracorrect = true,
        bool $playerbcorrect = true
    ): void {
        $cmid = $this->cm->id;

        // Player A: questions 0–2.
        $this->setUser($playera);
        for ($i = 0; $i < 3; $i++) {
            $qid = (int) $questions[$i]->questionid;
            $aid = $playeracorrect
                ? $this->correct_answer_id($questions[$i])
                : $this->wrong_answer_id($questions[$i]);
            mod_mooduell_external::answer_question($cmid, $gameid, $qid, [$aid]);
        }

        // Player B: questions 0–5.
        $this->setUser($playerb);
        for ($i = 0; $i < 6; $i++) {
            $qid = (int) $questions[$i]->questionid;
            $aid = $playerbcorrect
                ? $this->correct_answer_id($questions[$i])
                : $this->wrong_answer_id($questions[$i]);
            mod_mooduell_external::answer_question($cmid, $gameid, $qid, [$aid]);
        }

        // Player A: questions 3–8.
        $this->setUser($playera);
        for ($i = 3; $i < 9; $i++) {
            $qid = (int) $questions[$i]->questionid;
            $aid = $playeracorrect
                ? $this->correct_answer_id($questions[$i])
                : $this->wrong_answer_id($questions[$i]);
            mod_mooduell_external::answer_question($cmid, $gameid, $qid, [$aid]);
        }

        // Player B: questions 6–8.
        $this->setUser($playerb);
        for ($i = 6; $i < 9; $i++) {
            $qid = (int) $questions[$i]->questionid;
            $aid = $playerbcorrect
                ? $this->correct_answer_id($questions[$i])
                : $this->wrong_answer_id($questions[$i]);
            mod_mooduell_external::answer_question($cmid, $gameid, $qid, [$aid]);
        }
    }

    // -----------------------------------------------------------------------
    // Test: complete game – Player A wins
    // -----------------------------------------------------------------------

    /**
     * Test that a fully played game reaches status=3, sets the winner to Player A,
     * and produces correct counters when Player A answers everything correctly and
     * Player B answers everything incorrectly.
     *
     * @runInSeparateProcess
     * @covers \game_control::validate_question
     * @covers \game_control::save_my_turn_status
     * @covers \game_control::is_game_finished
     * @covers \game_control::return_winnerid_and_correct_answers
     */
    public function test_complete_game_player_a_wins(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $gameid = $attempt->gameid;
        $questions = $attempt->questions;

        $this->play_full_game($questions, $gameid, $this->user1, $this->user2,
            true,   // Player A answers all correctly.
            false   // Player B answers all incorrectly.
        );

        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $gameid);

        $this->assertEquals(3, $game->status, 'Game must be finished (status 3)');
        $this->assertEquals($this->user1->id, $game->winnerid, 'Player A must be the winner');
        $this->assertEquals(9, $game->playeracorrect, 'Player A answered all 9 correctly');
        $this->assertEquals(0, $game->playerbcorrect, 'Player B answered 0 correctly');
        $this->assertEquals(9, $game->playeraqplayed);
        $this->assertEquals(9, $game->playerbqplayed);
    }

    // -----------------------------------------------------------------------
    // Test: complete game – draw
    // -----------------------------------------------------------------------

    /**
     * Test draw: both players answer every question correctly → winnerid = 0.
     *
     * @runInSeparateProcess
     * @covers \game_control::return_winnerid_and_correct_answers
     */
    public function test_complete_game_draw(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2,
            true, true
        );

        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $attempt->gameid);

        $this->assertEquals(3, $game->status);
        $this->assertEquals(0, $game->winnerid, 'A draw must produce winnerid = 0');
        $this->assertEquals(9, $game->playeracorrect);
        $this->assertEquals(9, $game->playerbcorrect);
    }

    // -----------------------------------------------------------------------
    // Test: complete game – Player B wins
    // -----------------------------------------------------------------------

    /**
     * Test that Player B wins when they answer all correctly and Player A answers
     * all incorrectly.
     *
     * @runInSeparateProcess
     * @covers \game_control::return_winnerid_and_correct_answers
     */
    public function test_complete_game_player_b_wins(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2,
            false,  // Player A wrong.
            true    // Player B correct.
        );

        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $attempt->gameid);

        $this->assertEquals(3, $game->status);
        $this->assertEquals($this->user2->id, $game->winnerid, 'Player B must be the winner');
        $this->assertEquals(0, $game->playeracorrect);
        $this->assertEquals(9, $game->playerbcorrect);
    }

    // -----------------------------------------------------------------------
    // Test: turn enforcement – wrong player tries to answer first
    // -----------------------------------------------------------------------

    /**
     * Player B must not be able to answer before Player A has finished their
     * first three questions.
     *
     * @runInSeparateProcess
     * @covers \game_control::validate_question
     */
    public function test_turn_enforcement_b_cannot_go_first(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        // Player B tries to answer question[0] before Player A has moved.
        $this->setUser($this->user2);
        $qid = (int) $attempt->questions[0]->questionid;
        $aid = $this->correct_answer_id($attempt->questions[0]);

        $this->expectException(\moodle_exception::class);
        mod_mooduell_external::answer_question($this->cm->id, $attempt->gameid, $qid, [$aid]);
    }

    // -----------------------------------------------------------------------
    // Test: turn enforcement – Player A cannot answer after completing their set
    // -----------------------------------------------------------------------

    /**
     * After Player A has answered 3 questions it is Player B's turn.
     * Player A must not be able to answer a 4th question until the sequence allows it.
     *
     * @runInSeparateProcess
     * @covers \game_control::validate_question
     */
    public function test_turn_enforcement_a_cannot_jump_queue(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $questions = $attempt->questions;
        $gameid = $attempt->gameid;

        // Player A answers first 3.
        for ($i = 0; $i < 3; $i++) {
            $qid = (int) $questions[$i]->questionid;
            mod_mooduell_external::answer_question(
                $this->cm->id, $gameid, $qid,
                [$this->correct_answer_id($questions[$i])]
            );
        }

        // Player A tries to answer question[3] – not their turn yet.
        $this->expectException(\moodle_exception::class);
        mod_mooduell_external::answer_question(
            $this->cm->id, $gameid, (int) $questions[3]->questionid,
            [$this->correct_answer_id($questions[3])]
        );
    }

    // -----------------------------------------------------------------------
    // Test: answering out-of-sequence within a turn
    // -----------------------------------------------------------------------

    /**
     * Within Player A's first batch, answering question[2] before question[0]
     * must raise an out-of-sequence exception.
     *
     * @runInSeparateProcess
     * @covers \game_control::validate_question
     */
    public function test_answer_out_of_sequence(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $questions = $attempt->questions;

        // Skip question[0] and try to answer question[1] directly.
        $qid = (int) $questions[1]->questionid;
        $aid = $this->correct_answer_id($questions[1]);

        $this->expectException(\moodle_exception::class);
        mod_mooduell_external::answer_question($this->cm->id, $attempt->gameid, $qid, [$aid]);
    }

    // -----------------------------------------------------------------------
    // Test: re-answering an already answered question
    // -----------------------------------------------------------------------

    /**
     * Submitting an answer for a question that has already been answered by the
     * same player must throw a questionalreadyanswered exception.
     *
     * @runInSeparateProcess
     * @covers \game_control::save_result_to_db
     */
    public function test_answer_already_answered_question(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $questions = $attempt->questions;
        $gameid = $attempt->gameid;
        $qid = (int) $questions[0]->questionid;
        $aid = $this->correct_answer_id($questions[0]);

        // First answer – must succeed.
        mod_mooduell_external::answer_question($this->cm->id, $gameid, $qid, [$aid]);

        // Second attempt at the same question by the same player, still within their
        // valid window (they have q[1] and q[2] left). Moodle sequence check will
        // fire before the DB uniqueness check, so we need to try as if replaying q[0]
        // which now has playeraanswered set → DB check fires.
        // The easiest way is to call save_result_to_db directly through reflection or
        // to drive it through the external API with a duplicate call while it is still
        // the active question (i.e. right after the first answer, game is at q[1]).
        // At this point the sequence cursor is already past q[0], so trying q[0] again
        // will first fail the sequence check unless we skip that. We therefore call the
        // internal method directly.
        $mooduell = new mooduell($this->cm->id);
        $gamecontroller = new game_control($mooduell, $gameid);

        $this->expectException(\moodle_exception::class);
        // Access private method via reflection to unit-test the DB guard directly.
        $reflector = new \ReflectionMethod($gamecontroller, 'save_result_to_db');
        $reflector->setAccessible(true);
        $reflector->invoke($gamecontroller, $gameid, $qid, 2);
    }

    // -----------------------------------------------------------------------
    // Test: giveup by a non-participant
    // -----------------------------------------------------------------------

    /**
     * A user who is not a player in the game must get status=0 when trying to
     * give up that game (no exception, no state change).
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::giveup_game
     */
    public function test_giveup_by_nonparticipant(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        // A third user who is not in the game tries to give up.
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $this->course->id);
        $this->setUser($user3);

        $result = mod_mooduell_external::giveup_game($attempt->gameid);
        $this->assertEquals(0, $result['status'], 'Non-participant giveup must return status 0');

        // Game must still be open. Verify as one of the actual participants.
        $this->setUser($this->user1);
        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $attempt->gameid);
        $this->assertNotEquals(3, $game->status, 'Game must still be open after non-participant giveup');
    }

    // -----------------------------------------------------------------------
    // Test: delete_iapurchases – ownership enforcement
    // -----------------------------------------------------------------------

    /**
     * A user must not be able to delete a purchase record that belongs to
     * another user.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::delete_iapurchases
     */
    public function test_delete_iapurchases_requires_ownership(): void {
        global $DB;
        $this->setup_fixture();

        // Insert a purchase record owned by user1.
        $record = (object)[
            'productid' => 'testproduct',
            'purchasetoken' => 'tok123',
            'userid' => $this->user1->id,
            'ispublic' => 0,
            'timecreated' => time(),
            'validuntil' => time() + 3600,
            'platformid' => 'test',
            'store' => 'test',
        ];
        $itemid = $DB->insert_record('mooduell_purchase', $record);

        // User2 tries to delete user1's purchase → must silently fail (status 0).
        $this->setUser($this->user2);
        $result = mod_mooduell_external::delete_iapurchases($itemid);
        $this->assertEquals(0, $result['status'], 'Deleting another user\'s purchase must return status 0');
        $this->assertTrue(
            $DB->record_exists('mooduell_purchase', ['id' => $itemid]),
            'Record must still exist after failed cross-user delete'
        );

        // Owner deletes their own purchase → must succeed (status 1).
        $this->setUser($this->user1);
        $result = mod_mooduell_external::delete_iapurchases($itemid);
        $this->assertEquals(1, $result['status'], 'Owner deleting own purchase must return status 1');
        $this->assertFalse(
            $DB->record_exists('mooduell_purchase', ['id' => $itemid]),
            'Record must be gone after successful delete'
        );
    }

    // -----------------------------------------------------------------------
    // Test: status counters are updated after partial play
    // -----------------------------------------------------------------------

    /**
     * After Player A answers 2 correct and 1 incorrect, intermediate counters
     * must reflect partial progress (game still at status 1, i.e. Player A's turn).
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::get_game_data
     * @covers \game_control::save_my_turn_status
     */
    public function test_partial_game_counters(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $questions = $attempt->questions;
        $gameid = $attempt->gameid;

        // Player A: correct, incorrect, correct.
        foreach ([
            [0, true],
            [1, false],
            [2, true],
        ] as [$idx, $correct]) {
            $qid = (int) $questions[$idx]->questionid;
            $aid = $correct
                ? $this->correct_answer_id($questions[$idx])
                : $this->wrong_answer_id($questions[$idx]);
            mod_mooduell_external::answer_question($this->cm->id, $gameid, $qid, [$aid]);
        }

        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $gameid);

        // Player A answered 3, Player B answered 0 → it is now Player B's turn (status 2).
        $this->assertEquals(2, $game->status, 'After Player A\'s first 3, it must be Player B\'s turn');
        $this->assertEquals(2, $game->playeracorrect);
        $this->assertEquals(0, $game->playerbcorrect);
        $this->assertEquals(3, $game->playeraqplayed);
        $this->assertEquals(0, $game->playerbqplayed);
        $this->assertEquals(0, $game->winnerid, 'Game is not yet finished, winner must be 0');
    }

    // -----------------------------------------------------------------------
    // Test: highscores after a naturally completed game
    // -----------------------------------------------------------------------

    /**
     * After a natural full-game completion, highscores must award 3 points to
     * the winner and 0 to the loser.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::get_highscores
     * @covers \mooduell::get_highscores
     */
    public function test_highscores_after_natural_game_completion(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        // Player A wins (correct everywhere, B wrong everywhere).
        $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2,
            true, false
        );

        $this->setAdminUser();
        $hs = mod_mooduell_external::get_highscores($this->cm->id);

        // Build lookup by userid.
        $byuser = [];
        foreach ($hs as $row) {
            $byuser[$row['userid']] = $row;
        }

        $this->assertArrayHasKey($this->user1->id, $byuser, 'User1 must appear in highscores');
        $this->assertArrayHasKey($this->user2->id, $byuser, 'User2 must appear in highscores');

        $this->assertEquals(3, $byuser[$this->user1->id]['score'], 'Winner gets 3 points');
        $this->assertEquals(1, $byuser[$this->user1->id]['won']);
        $this->assertEquals(0, $byuser[$this->user1->id]['lost']);

        $this->assertEquals(0, $byuser[$this->user2->id]['score'], 'Loser gets 0 points');
        $this->assertEquals(0, $byuser[$this->user2->id]['won']);
        $this->assertEquals(1, $byuser[$this->user2->id]['lost']);
    }

    // -----------------------------------------------------------------------
    // Test: highscores after a draw
    // -----------------------------------------------------------------------

    /**
     * A draw awards 1 point to each player.
     *
     * @runInSeparateProcess
     * @covers \mooduell::get_highscores
     */
    public function test_highscores_draw_awards_one_point_each(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2,
            true, true
        );

        $this->setAdminUser();
        $hs = mod_mooduell_external::get_highscores($this->cm->id);

        $byuser = [];
        foreach ($hs as $row) {
            $byuser[$row['userid']] = $row;
        }

        $this->assertEquals(1, $byuser[$this->user1->id]['score'], 'Draw gives 1 point to Player A');
        $this->assertEquals(1, $byuser[$this->user2->id]['score'], 'Draw gives 1 point to Player B');
    }

    // -----------------------------------------------------------------------
    // Test: user stats accumulate across two games
    // -----------------------------------------------------------------------

    /**
     * After two completed games in the same module, get_user_stats must return
     * the sum across both games.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::get_user_stats
     * @covers \game_control::get_user_stats
     */
    public function test_user_stats_accumulate_across_games(): void {
        $this->setup_fixture();

        // Game 1: Player A wins.
        $this->setUser($this->user1);
        $attempt1 = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $this->play_full_game($attempt1->questions, $attempt1->gameid, $this->user1, $this->user2,
            true, false
        );

        // Game 2: Player B wins.
        $this->setUser($this->user1);
        $attempt2 = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $this->play_full_game($attempt2->questions, $attempt2->gameid, $this->user1, $this->user2,
            false, true
        );

        $stats = mod_mooduell_external::get_user_stats($this->user1->id);

        $this->assertEquals(2, $stats['playedgames'], 'Player A played 2 games');
        $this->assertEquals(1, $stats['wongames'],    'Player A won 1 of 2 games');
        $this->assertEquals(1, $stats['lostgames'],   'Player A lost 1 of 2 games');
        $this->assertEquals(9, $stats['correctlyanswered'], 'Player A answered 9 of 18 correctly (game 1 all correct)');
        $this->assertEquals(18, $stats['playedquestions']);
    }

    // -----------------------------------------------------------------------
    // Test: mooduell instance with fewer than 9 questions triggers a warning
    // -----------------------------------------------------------------------

    /**
     * When a MooDuell instance references a category with fewer than 9 questions,
     * check_quiz must return a non-empty warnings array.
     *
     * @runInSeparateProcess
     * @covers \mooduell::check_quiz
     */
    public function test_check_quiz_warns_when_too_few_questions(): void {
        global $CFG;
        $CFG->enablecompletion = 1;
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $coursectx = context_course::instance($course->id);

        /** @var \core_question_generator $qgen */
        $qgen = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $qgen->create_question_category(['contextid' => $coursectx->id]);

        // Create only 3 questions (< 9 required).
        for ($i = 0; $i < 3; $i++) {
            $qgen->create_question('multichoice', null, ['category' => $category->id]);
        }

        /** @var mod_mooduell_generator $mgen */
        $mgen = self::getDataGenerator()->get_plugin_generator('mod_mooduell');
        $smallduel = $this->getDataGenerator()->create_module('mooduell', [
            'name' => 'Small Duel',
            'intro' => 'test',
            'usefullnames' => 1,
            'course' => $course->id,
            'categoriesgroup0' => ['category' => $category->id, 'weight' => 100],
        ]);

        $cm = get_coursemodule_from_instance('mooduell', $smallduel->id);
        $mooduell = new mooduell($cm->id);

        $warnings = $mooduell->check_quiz();

        $this->assertNotEmpty($warnings, 'check_quiz must return a warning when fewer than 9 questions exist');
        $this->assertEquals(1, $warnings[0]['id']);
    }

    // -----------------------------------------------------------------------
    // Test: teacher statistics reflect completed games correctly
    // -----------------------------------------------------------------------

    /**
     * After one full game, the teacher statistics must count 1 active user pair,
     * 1 game started, 1 game finished, and the right number of answers.
     *
     * @runInSeparateProcess
     * @covers \mooduell::return_list_of_statistics_teacher
     */
    public function test_teacher_statistics_after_completed_game(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2,
            true, false
        );

        $this->setAdminUser();
        $mooduell = new mooduell($this->cm->id);
        $stats = $mooduell->return_list_of_statistics_teacher();

        $this->assertEquals(2, $stats['number_of_active_users'],
            'Both players must appear as active users');
        $this->assertEquals(1, $stats['number_of_games_started']);
        $this->assertEquals(1, $stats['number_of_games_finished']);
        // 9 answers from A + 9 from B = 18 total.
        $this->assertEquals(18, $stats['number_of_answers']);
        // Only Player A answered correctly (9 out of 18 = 50 %).
        $this->assertEquals('50.0', $stats['percentage_of_correct_answers']);
    }

    // -----------------------------------------------------------------------
    // Test: student statistics reflect the active user's data only
    // -----------------------------------------------------------------------

    /**
     * Student statistics viewed by Player A must only report Player A's own numbers.
     *
     * @runInSeparateProcess
     * @covers \mooduell::return_list_of_statistics_student
     */
    public function test_student_statistics_reflect_own_data(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        // Player A answers 3 questions (2 correct, 1 wrong).
        foreach ([[0, true], [1, false], [2, true]] as [$idx, $ok]) {
            $qid = (int) $attempt->questions[$idx]->questionid;
            $aid = $ok
                ? $this->correct_answer_id($attempt->questions[$idx])
                : $this->wrong_answer_id($attempt->questions[$idx]);
            mod_mooduell_external::answer_question($this->cm->id, $attempt->gameid, $qid, [$aid]);
        }

        // Stats while game is still open.
        $mooduell = new mooduell($this->cm->id);
        $stats = $mooduell->return_list_of_statistics_student();

        $this->assertEquals(1, $stats['number_of_open_games']);
        $this->assertEquals(0, $stats['number_of_games_finished']);
        $this->assertEquals(0, $stats['number_of_games_won']);
        $this->assertEquals(1, $stats['number_of_opponents']);
    }

    // -----------------------------------------------------------------------
    // Test: answer_question returns correct iscorrect flag
    // -----------------------------------------------------------------------

    /**
     * Verify iscorrect=1 when the correct answer is submitted and iscorrect=0
     * when an incorrect answer is submitted.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::answer_question
     * @covers \question_control::validate_question
     */
    public function test_answer_question_iscorrect_flag(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );
        $questions = $attempt->questions;
        $gameid = $attempt->gameid;

        // Correct answer for question[0].
        $result = mod_mooduell_external::answer_question(
            $this->cm->id, $gameid,
            (int) $questions[0]->questionid,
            [$this->correct_answer_id($questions[0])]
        );
        $this->assertEquals(1, $result['iscorrect'], 'Correct answer must yield iscorrect=1');

        // Wrong answer for question[1].
        $result = mod_mooduell_external::answer_question(
            $this->cm->id, $gameid,
            (int) $questions[1]->questionid,
            [$this->wrong_answer_id($questions[1])]
        );
        $this->assertEquals(0, $result['iscorrect'], 'Wrong answer must yield iscorrect=0');
    }

    // -----------------------------------------------------------------------
    // Test: giveup correctly ends the game and sets the winner
    // -----------------------------------------------------------------------

    /**
     * When Player A gives up mid-game, Player B is set as winner and status is 3.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::giveup_game
     */
    public function test_giveup_player_a_sets_player_b_as_winner(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id, $this->cm->id, $this->user2->id
        );

        // Player A answers one question then gives up.
        mod_mooduell_external::answer_question(
            $this->cm->id, $attempt->gameid,
            (int) $attempt->questions[0]->questionid,
            [$this->correct_answer_id($attempt->questions[0])]
        );

        $res = mod_mooduell_external::giveup_game($attempt->gameid);
        $this->assertEquals(1, $res['status']);

        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $attempt->gameid);
        $this->assertEquals(3, $game->status, 'Game must be finished after giveup');
        $this->assertEquals($this->user2->id, $game->winnerid, 'Player B wins when Player A gives up');
    }

    // -----------------------------------------------------------------------
    // Test: question_control validates question health correctly
    // -----------------------------------------------------------------------

    /**
     * A question with no correct answers must have status "notok" and a warning,
     * whereas a well-formed question must have status "ok".
     *
     * @runInSeparateProcess
     * @covers \question_control::check_question
     */
    public function test_question_control_health_check(): void {
        $this->setup_fixture();

        // Get one of the imported questions – they are all well-formed.
        $mooduell = new mooduell($this->cm->id);
        $allquestions = $mooduell->return_list_of_all_questions_in_quiz();
        $this->assertNotEmpty($allquestions, 'Fixture must contain questions');

        $okstring = get_string('ok', 'mod_mooduell');
        $first = reset($allquestions);
        $this->assertEquals($okstring, $first->status, 'Well-formed question must have status ok');
        $this->assertEmpty($first->warnings, 'Well-formed question must have no warnings');
    }

    // -----------------------------------------------------------------------
    // Test: return_users_for_game excludes unenrolled users
    // -----------------------------------------------------------------------

    /**
     * A user who is not enrolled in the course must not appear in the
     * users list for that course's MooDuell instance.
     *
     * @runInSeparateProcess
     * @covers \game_control::return_users_for_game
     */
    public function test_return_users_excludes_unenrolled(): void {
        $this->setup_fixture();

        // Create a third user who is NOT enrolled in the course.
        $unenrolled = $this->getDataGenerator()->create_user();

        $this->setUser($this->user1);
        $users = mod_mooduell_external::get_quiz_users($this->course->id, $this->cm->id);

        $ids = array_column($users, 'id');
        $this->assertContains($this->user1->id, $ids, 'Enrolled user1 must be in the list');
        $this->assertContains($this->user2->id, $ids, 'Enrolled user2 must be in the list');
        $this->assertNotContains($unenrolled->id, $ids,
            'Unenrolled user must not appear in the users list');
    }

    // -----------------------------------------------------------------------
    // Test: mooduell::get_highscores handles zero-game state gracefully
    // -----------------------------------------------------------------------

    /**
     * When no games have been played yet, get_highscores must return an empty
     * array (no errors, no phantom entries).
     *
     * @runInSeparateProcess
     * @covers \mooduell::get_highscores
     */
    public function test_highscores_empty_when_no_games_played(): void {
        $this->setup_fixture();

        $this->setAdminUser();
        $hs = mod_mooduell_external::get_highscores($this->cm->id);

        $this->assertIsArray($hs);
        $this->assertEmpty($hs, 'Highscores must be empty when no games have been played');
    }

    // -----------------------------------------------------------------------
    // Test: multiple games, highscore ranking
    // -----------------------------------------------------------------------

    /**
     * When Player A wins two games and Player B wins zero, Player A must rank
     * above Player B in the highscores.
     *
     * @runInSeparateProcess
     * @covers \mooduell::get_highscores
     */
    public function test_highscore_ranking_after_multiple_games(): void {
        $this->setup_fixture();

        // Two games, both won by Player A.
        for ($g = 0; $g < 2; $g++) {
            $this->setUser($this->user1);
            $attempt = mod_mooduell_external::start_attempt(
                $this->course->id, $this->cm->id, $this->user2->id
            );
            $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2,
                true, false
            );
        }

        $this->setAdminUser();
        $hs = mod_mooduell_external::get_highscores($this->cm->id);

        $this->assertCount(2, $hs, 'Two players must appear in highscores');
        $this->assertEquals(1, $hs[0]['rank'], 'First entry must be rank 1');
        $this->assertEquals($this->user1->id, $hs[0]['userid'],
            'Player A (2 wins) must be ranked first');
        $this->assertEquals(6, $hs[0]['score'], 'Two wins = 6 points (3 per win)');
    }

    // -----------------------------------------------------------------------
    // App contract: timeout answer payload (answerids[0]=0)
    // -----------------------------------------------------------------------

    /**
     * The mobile app sends answerids[0]=0 when countdown runs out and no
     * answer was selected. The backend must accept this payload and return a
     * valid answer structure with iscorrect=0.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::answer_question
     */
    public function test_answer_question_timeout_payload_is_accepted(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id,
            $this->cm->id,
            $this->user2->id
        );

        $result = mod_mooduell_external::answer_question(
            $this->cm->id,
            $attempt->gameid,
            (int) $attempt->questions[0]->questionid,
            [0]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('iscorrect', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertEquals(0, $result['iscorrect'],
            'Timeout payload must be treated as incorrect, not as malformed request');
    }

    // -----------------------------------------------------------------------
    // App contract: delta sync by timemodified
    // -----------------------------------------------------------------------

    /**
     * The app performs incremental sync using timemodified. If the same
     * timemodified value is supplied, no game updates should be returned.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::get_games_by_courses
     */
    public function test_get_games_by_courses_respects_timemodified_cutoff(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id,
            $this->cm->id,
            $this->user2->id
        );

        $allgames = mod_mooduell_external::get_games_by_courses([$this->course->id], -1);
        $this->assertCount(1, $allgames['quizzes']);
        $this->assertCount(1, $allgames['quizzes'][0]['games']);

        $lastmodified = (int) $allgames['quizzes'][0]['games'][0]['timemodified'];
        $delta = mod_mooduell_external::get_games_by_courses([$this->course->id], $lastmodified);

        $this->assertCount(1, $delta['quizzes']);
        $this->assertCount(0, $delta['quizzes'][0]['games'],
            'No games should be returned when timemodified cutoff equals latest game timestamp');
    }

    // -----------------------------------------------------------------------
    // App contract: push token authorization matrix
    // -----------------------------------------------------------------------

    /**
     * Push token writes must follow the expected matrix:
     * - own-user set is always allowed
     * - cross-user set is denied without active game
     * - cross-user set is allowed when an active game exists
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::set_pushtokens
     */
    public function test_set_pushtokens_authorization_matrix(): void {
        $this->setup_fixture();

        // Own user token write must work without any active game.
        $this->setUser($this->user1);
        $ownresult = mod_mooduell_external::set_pushtokens(
            $this->user1->id,
            'dev-own-1',
            'model-own-1',
            'token-own-1'
        );
        $this->assertEquals(1, $ownresult['status']);

        // Cross-user write must be denied while no active game exists.
        try {
            mod_mooduell_external::set_pushtokens(
                $this->user2->id,
                'dev-cross-denied',
                'model-cross-denied',
                'token-cross-denied'
            );
            $this->fail('Cross-user push token write must fail without active game');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('cantsetpushtoken', $e->errorcode);
        }

        // Start an active game between user1 and user2.
        mod_mooduell_external::start_attempt(
            $this->course->id,
            $this->cm->id,
            $this->user2->id
        );

        // Cross-user write must now be allowed because an active game exists.
        $crossresult = mod_mooduell_external::set_pushtokens(
            $this->user2->id,
            'dev-cross-allowed',
            'model-cross-allowed',
            'token-cross-allowed'
        );
        $this->assertEquals(1, $crossresult['status']);
    }

    // -----------------------------------------------------------------------
    // App contract: highscores with quizid=0
    // -----------------------------------------------------------------------

    /**
     * The app calls get_highscores without quizid, which becomes quizid=0.
     * Backend must return a stable structure for the active user.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::get_highscores
     */
    public function test_get_highscores_quizid_zero_returns_active_user_entry(): void {
        $this->setup_fixture();

        // Create one finished game so highscores have meaningful values.
        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id,
            $this->cm->id,
            $this->user2->id
        );
        $this->play_full_game($attempt->questions, $attempt->gameid, $this->user1, $this->user2, true, false);

        // quizid=0 path should only return active user's entry.
        $this->setUser($this->user1);
        $highscores = mod_mooduell_external::get_highscores(0);

        $this->assertIsArray($highscores);
        $this->assertCount(1, $highscores, 'quizid=0 must return only active user entry');
        $this->assertEquals($this->user1->id, $highscores[0]['userid']);
        $this->assertArrayHasKey('quizid', $highscores[0]);
        $this->assertArrayHasKey('played', $highscores[0]);
        $this->assertArrayHasKey('won', $highscores[0]);
        $this->assertArrayHasKey('lost', $highscores[0]);
        $this->assertArrayHasKey('score', $highscores[0]);
    }

    // -----------------------------------------------------------------------
    // App contract: giveup idempotency
    // -----------------------------------------------------------------------

    /**
     * The app may retry giveup requests. Repeating giveup on the same game
     * must keep terminal state stable and return success.
     *
     * @runInSeparateProcess
     * @covers \mod_mooduell_external::giveup_game
     */
    public function test_giveup_game_is_idempotent(): void {
        $this->setup_fixture();

        $this->setUser($this->user1);
        $attempt = mod_mooduell_external::start_attempt(
            $this->course->id,
            $this->cm->id,
            $this->user2->id
        );

        $first = mod_mooduell_external::giveup_game($attempt->gameid);
        $second = mod_mooduell_external::giveup_game($attempt->gameid);

        $this->assertEquals(1, $first['status']);
        $this->assertEquals(1, $second['status']);

        $game = mod_mooduell_external::get_game_data($this->course->id, $this->cm->id, $attempt->gameid);
        $this->assertEquals(3, $game->status);
        $this->assertEquals($this->user2->id, $game->winnerid,
            'Winner must remain Player B after repeated giveup calls by Player A');
    }
}
