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

namespace mod_mooduell;

use advanced_testcase;

/**
 * Health check for drag-and-drop-into-text questions: MooDuell plays them with exactly one choice per
 * gap, so a question with distractor words (more choices than gaps) must be flagged and kept out of games.
 *
 * @package     mod_mooduell
 * @category    test
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_mooduell\question_control
 */
final class question_control_ddwtos_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        // The question_control class relies on constants (ACCEPTEDTYPES, MINLENGTH, ...) that
        // classes/mooduell.php defines when it is loaded; in the plugin it is always loaded first.
        class_exists(mooduell::class);
    }

    /**
     * Creates a question in a fresh course category and returns the record question_control expects.
     *
     * @param string $qtype
     * @param string $which test helper variant
     * @return \stdClass question record with the category id added
     */
    private function create_question(string $qtype, string $which): \stdClass {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        /** @var \core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $generator->create_question_category(['contextid' => $context->id]);
        $question = $generator->create_question($qtype, $which, ['category' => $category->id]);
        $record = $DB->get_record('question', ['id' => $question->id], '*', MUST_EXIST);
        $record->category = $category->id;
        return $record;
    }

    /**
     * The choices of a ddwtos question, in id order.
     *
     * @param int $questionid
     * @return string[]
     */
    private function choices(int $questionid): array {
        global $DB;
        return $DB->get_fieldset_select('question_answers', 'answer', 'question = :q ORDER BY id', ['q' => $questionid]);
    }

    /**
     * A drag-and-drop question with more choices than gaps is flagged and set to "Not OK".
     *
     * @runInSeparateProcess
     */
    public function test_ddwtos_with_distractor_words_is_flagged_and_not_ok(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Moodle's sample question: 3 gaps, 6 choices (quick/fox/lazy + slow/dog/assiduous).
        $record = $this->create_question('ddwtos', 'fox');
        $this->assertCount(6, $this->choices($record->id));

        $question = new question_control($record);

        $this->assertSame(get_string('notok', 'mod_mooduell'), $question->status);
        $expected = get_string('questionddwtoschoicesmismatch', 'mod_mooduell', (object) [
            'id' => $record->id, 'choices' => 6, 'gaps' => 3,
        ]);
        $this->assertContains($expected, array_column($question->warnings, 'message'));
    }

    /**
     * A drag-and-drop question with exactly one choice per gap passes the health check.
     *
     * @runInSeparateProcess
     */
    public function test_ddwtos_with_one_choice_per_gap_is_ok(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $record = $this->create_question('ddwtos', 'fox');
        // Remove the distractors so the question has exactly one choice per gap.
        $DB->delete_records_select(
            'question_answers',
            "question = :q AND answer IN ('slow', 'dog', 'assiduous')",
            ['q' => $record->id]
        );
        $this->assertSame(['quick', 'fox', 'lazy'], array_values($this->choices($record->id)));

        $question = new question_control($record);

        $this->assertSame(get_string('ok', 'mod_mooduell'), $question->status);
        $this->assertSame([], $question->warnings);
    }

    /**
     * The choice-count check does not affect other question types.
     *
     * @runInSeparateProcess
     */
    public function test_choice_count_check_leaves_other_question_types_alone(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $record = $this->create_question('multichoice', 'one_of_four');

        $question = new question_control($record);

        $this->assertSame(get_string('ok', 'mod_mooduell'), $question->status);
        $this->assertSame([], $question->warnings);
    }
}
