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
 * backup stepslib class.
 * @package   mod_mooduell
 * @category  backup
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_mooduell_activity_task
 */

/**
 * Define the complete mooduell structure for backup, with file and id annotations
 */
class backup_mooduell_activity_structure_step extends backup_activity_structure_step {
    /**
     * define structure.
     * @return backup_nested_element
     * @throws base_element_struct_exception
     * @throws base_step_exception
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $mooduell = new backup_nested_element('mooduell', ['id'], [
            'name', 'intro', 'introformat', 'quizid', 'usefullnames',
            'showcontinuebutton', 'showcorrectanswer', 'showgeneralfeedback', 'showanswersfeedback',
            'countdown', 'waitfornextquestion', 'timecreated', 'timemodified',
        ]);

        $categories = new backup_nested_element('categories');

        $category = new backup_nested_element('category', ['id'], [
                'mooduellid', 'category', 'weight',
        ]);

        $challenges = new backup_nested_element('challenges');

        $challenge = new backup_nested_element('challenge', ['id'], [
                'mooduellid', 'challengetype', 'targetnumber', 'challengename',
        ]);

        $challengeresults = new backup_nested_element('challenge_results');

        $challengeresult = new backup_nested_element('challenge_result', ['id'], [
                'mooduellid', 'challengeid', 'userid', 'result', 'timecreated', 'timemodified',
        ]);

        $games = new backup_nested_element('games');

        $game = new backup_nested_element('game', ['id'], [
                'mooduellid', 'playeraid', 'playerbid',
                'playeratime', 'playerbtime', 'playeracorrect',
                'playerbcorrect', 'winnerid', 'status',
                'victorycoefficient', 'timemodified',
                'timecreated', 'playeraresults', 'playerbresults',
                'playeraqplayed', 'playerbqplayed',
        ]);

        $pushtokens = new backup_nested_element('pushtokens');

        $pushtoken = new backup_nested_element('pushtoken', ['id'], [
                'userid', 'identifier', 'model', 'pushtoken', 'numberofnotifications',
        ]);

        $questions = new backup_nested_element('questions');

        $question = new backup_nested_element('question', ['id'], [
                'mooduellid', 'gameid', 'questionid', 'playeraanswered', 'playerbanswered',
        ]);

        // Build the tree.
        $mooduell->add_child($categories);
        $categories->add_child($category);

        $mooduell->add_child($challenges);
        $challenges->add_child($challenge);

        $mooduell->add_child($challengeresults);
        $challengeresults->add_child($challengeresult);

        $mooduell->add_child($games);
        $games->add_child($game);

        $mooduell->add_child($pushtokens);
        $pushtokens->add_child($pushtoken);

        $mooduell->add_child($questions);
        $questions->add_child($question);

        // Define sources.
        $mooduell->set_source_table('mooduell', ['id' => backup::VAR_ACTIVITYID]);
        $category->set_source_table('mooduell_categories', ['mooduellid' => backup::VAR_PARENTID]);
        $challenge->set_source_table('mooduell_challenges', ['mooduellid' => backup::VAR_PARENTID]);

        // Only if we include userinfo, we also include games, questions, challenge results & pushtokens.
        if ($userinfo) {
            $game->set_source_table('mooduell_games', ['mooduellid' => backup::VAR_PARENTID]);
            $question->set_source_table('mooduell_questions', ['mooduellid' => backup::VAR_PARENTID]);
            $challengeresult->set_source_table('mooduell_challenge_results', ['mooduellid' => backup::VAR_PARENTID]);

            // Define id annotations.
            $game->annotate_ids('user', 'playeraid');
            $game->annotate_ids('user', 'playerbid');
        }

        $category->annotate_ids('question_categories', 'category');
        $question->annotate_ids('question', 'questionid');

        // Define file annotations.
        // This file areas haven't itemid.
        $mooduell->annotate_files('mod_mooduell', 'intro', null);

        // Return the root element (mooduell), wrapped into standard activity structure.
        return $this->prepare_activity_structure($mooduell);
    }
}
