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
 * Display information about all the mod_mooduell modules in the requested course.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mooduell_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        global $DB;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('mooduellname', 'mod_mooduell'), array(
                'size' => '64'
        ));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'mooduellname', 'mod_mooduell');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Adding the rest of mod_mooduell settings, spreading all them into this fieldset.
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('static', 'label1', 'mooduellsettings', get_string('mooduellsettings', 'mod_mooduell'));
        $mform->addElement('header', 'mooduellfieldset', get_string('mooduellfieldset', 'mod_mooduell'));

        $mform->addElement('checkbox', 'usefullnames', get_string('usefullnames', 'mod_mooduell'));
        $mform->addElement('checkbox', 'showcontinuebutton', get_string('showcontinuebutton', 'mod_mooduell'));
        $mform->addElement('checkbox', 'showcorrectanswer', get_string('showcorrectanswer', 'mod_mooduell'));

        $options = [
                "0" => get_string('nocountdown', 'mod_mooduell'),
                "10" => get_string('xseconds', 'mod_mooduell', 10),
                "20" => get_string('xseconds', 'mod_mooduell', 20),
                "30" => get_string('xseconds', 'mod_mooduell', 30),
                "60" => get_string('xseconds', 'mod_mooduell', 60),
                "90" => get_string('xseconds', 'mod_mooduell', 90),
                "120" => get_string('xseconds', 'mod_mooduell', 120)
        ];
        $mform->addElement('select', 'countdown', get_string('countdown', 'mod_mooduell'), $options);

        // We add the categories for the random question.
        // Right now, there is only one category supported but as a preparation, we already use the formgroup.
        $listofcategories = $DB->get_records('question_categories');
        if (count($listofcategories) > 0) {
            $categoryoptions = $this->return_list_of_category_options($this->generate_sorted_list($listofcategories));
            $formgroup = array();
            $formgroup[] = &
                    $mform->createElement('select', 'category', get_string('questionscategory', 'mod_mooduell'), $categoryoptions);
            $mform->addGroup($formgroup, 'categoriesgroup', get_string('questionscategorygroup', 'mod_mooduell'));
        } else {
            $mform->addElement('static', 'warning', get_string('nocategories', 'mod_mooduell'));
        }
        // Add standard buttons.
        $this->add_action_buttons();
    }

    private function return_list_of_category_options($list) {
        $names = array();
        $spaces = "";
        $previousitem = null;

        foreach ($list as $item) {
            if ($item->parent == 0) {
                $spaces = "";
            } else if ($previousitem && $previousitem->id == $item->parent) {
                $spaces .= "-> ";
            } else {
                $spaces = "-> ";
                $parent = $this->return_parent_for_item_in_list($list, $item);

                while ($parent->parent != 0) {
                    $parent = $this->return_parent_for_item_in_list($list, $item);
                    $spaces .= "-> ";
                }
            }
            if ($item->parent != 0) {

                $idkey = (string) $item->id;
                $names[$idkey] = $spaces . " " . $item->name;
            }
            $previousitem = $item;
        }
        return $names;
    }

    private function return_parent_for_item_in_list($list, $item) {
        foreach ($list as $parentitem) {
            if ($item->parent == $parentitem->id) {
                $parent = $parentitem;
                break;
            }
        }
        return $parent;
    }

    private function generate_sorted_list($listofcategories) {
        $i = 1;
        $sortedcategories = array();

        foreach ($listofcategories as $category) {
            if ($category->parent == 0) {
                $sortedcategories[] = $category;
                foreach ($this->return_children_in_list($category, $listofcategories) as $child) {
                    if ($child) {
                        $sortedcategories[] = $child;
                    }
                }
            }
        }

        return $sortedcategories;
    }

    private function return_children_in_list($parent, $list) {
        $i = 1;
        $children = array();

        foreach ($list as $child) {

            if ($parent->id == $child->parent) {
                $children[] = $child;
                foreach ($this->return_children_in_list($child, $list) as $grandchild) {
                    if ($grandchild) {
                        $children[] = $grandchild;
                    }
                }
            }
        }
        return $children;
    }
}
