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
 * @package   mod_mooduell
 * @category  backup
 * @copyright 2021 Wunderbyte Gmbh <georg.maisser@wudnerbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_mooduell_activity_task
 */

/**
 * Structure step to restore one mooduell activity
 */
class restore_mooduell_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('mooduell', '/activity/mooduell');
        $paths[] = new restore_path_element('mooduell_categories', '/activity/mooduell/categories/category');

        if ($userinfo) {
            $paths[] = new restore_path_element('mooduell_games', '/activity/mooduell/games/game');
            $paths[] = new restore_path_element('mooduell_questions', '/activity/mooduell/questions/question');
        }
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_mooduell($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

        // insert the mooduell record
        $newitemid = $DB->insert_record('mooduell', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_mooduell_categories($data) {
        global $DB;

        $data = (object)$data;

        $data->mooduellid = $this->get_new_parentid('mooduell');
        $data->category = $this->get_mappingid('question_category', $data->category);

        $newitemid = $DB->insert_record('mooduell_categories', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function process_mooduell_games($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->mooduellid = $this->get_new_parentid('mooduell');

        $data->playeraid = $this->get_mappingid('user', $data->playeraid);
        $data->playerbid = $this->get_mappingid('user', $data->playerbid);

        $data->winnerid = $this->get_mappingid('user', $data->winnerid);

        $newgameitemid = $DB->insert_record('mooduell_games', $data);

        $this->set_mapping('mooduell_game', $oldid, $newgameitemid);

    }

    protected function process_mooduell_questions($data) {
        global $DB;

        $data = (object)$data;

        $data->mooduellid = $this->get_new_parentid('mooduell');
        $data->gameid = $this->get_mappingid('mooduell_game', $data->gameid);

        $data->questionid = $this->get_mappingid('question', $data->questionid);

        // Skip treating this record if there is not id to match (game has been deleted already)
        if (!$data->gameid) {
            return;
        }

        // $data->questionid = $this->get_mappingid('question', $data->questionid);

        $newitemid = $DB->insert_record('mooduell_questions', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }



    protected function after_execute() {
        // Add mooduell related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_mooduell', 'intro', null);
    }
}
