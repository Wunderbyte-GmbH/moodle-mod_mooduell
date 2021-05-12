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

global $CFG;

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

        // Add mooduell elements.
        $this->mooduell_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }


    /**
     * Add Mooduell setting elements.
     * @throws coding_exception
     */
    private function mooduell_elements() {

        global $DB;

        // Get MooDuell id.
        // Get MooDuell id.
        $cm = $this->get_coursemodule();

        if ($cm && property_exists($cm, 'instance')) {
            $mooduellid = $cm->instance;
        } else {
            $mooduellid = 0;
        }

        $config = get_config('mooduell');

        $mform = $this->_form;

        // Adding the rest of mod_mooduell settings, spreading all them into this fieldset.
        $mform->addElement('static', 'label1', 'mooduellsettings', get_string('mooduellsettings', 'mod_mooduell'));
        $mform->addElement('header', 'mooduellfieldset', get_string('mooduellfieldset', 'mod_mooduell'));

        $mform->addElement('checkbox', 'usefullnames', get_string('usefullnames', 'mod_mooduell'));
        $mform->setDefault('usefullnames', $config->usefullnames);
        $mform->addElement('checkbox', 'showcontinuebutton', get_string('showcontinuebutton', 'mod_mooduell'));
        $mform->setDefault('showcontinuebutton', $config->showcontinuebutton);
        $mform->addElement('checkbox', 'showcorrectanswer', get_string('showcorrectanswer', 'mod_mooduell'));
        $mform->setDefault('showcorrectanswer', $config->showcorrectanswer);

        $mform->addElement('select', 'countdown', get_string('countdown', 'mod_mooduell'), $this->return_countdown_options());
        $mform->setDefault('countdown', $config->countdown);
        $mform->addElement('select', 'waitfornextquestion', get_string('waitfornextquestion', 'mod_mooduell'),
                $this->return_move_on_options());
        $mform->setDefault('waitfornextquestion', $config->waitfornextquestion);

        $this->apply_admin_defaults();

        // We add the categories for the random question.
        $listofcategories = $this->get_categories_of_context_from_db();

        $listofmooduellcats = $DB->get_records('mooduell_categories', array('mooduellid' => $mooduellid));
        if (count($listofcategories) > 0) {
            // First, there is the explanation.
            $mform->addElement('static', 'categoriesexplanation', get_string('important', 'mod_mooduell'),
                    get_string('categoriesexplanation', 'mod_mooduell'));

            // Between one to three categories are supported.
            $i = 0;
            $max = 3;
            while ($i < $max) {

                if ($listofmooduellcats && count($listofmooduellcats) > 0) {
                    $selectedcategory = array_shift($listofmooduellcats);
                } else {
                    $selectedcategory = null;
                }

                $this->add_categories_group($i, $selectedcategory, $listofcategories, $mform);
                if ($i < $max - 1) {
                    $j = $i + 1;
                    $mform->addElement('checkbox', 'addanothercategory' . $j, get_string('addanothercategory', 'mod_mooduell'));
                    // We check the checkbox if we have a category element saved.
                    if (count($listofmooduellcats) > 0) {
                        $mform->setDefault('addanothercategory' . $j, true);
                    }
                }
                // Hide categories depending on checkboxes add categories.
                if ($i > 0) {
                    $j = $i - 1;
                    $mform->hideIf('categoriesgroup' . $i, 'addanothercategory' . $i, 'notchecked');
                    $mform->hideIf('addanothercategory' . $i, 'addanothercategory' . $j, 'notchecked');
                }
                ++$i;
            }

        } else {
            // Warning if there are not categories.
            $mform->addElement('static', 'warning', get_string('important', 'mod_mooduell'),
                    get_string('nocategories', 'mod_mooduell'));
        }
    }

    /**
     * @return array
     * @throws coding_exception
     */
    private function return_countdown_options() {
        return [
                "0" => get_string('nocountdown', 'mod_mooduell'),
                "10" => get_string('xseconds', 'mod_mooduell', 10),
                "20" => get_string('xseconds', 'mod_mooduell', 20),
                "30" => get_string('xseconds', 'mod_mooduell', 30),
                "60" => get_string('xseconds', 'mod_mooduell', 60),
                "90" => get_string('xseconds', 'mod_mooduell', 90),
                "120" => get_string('xseconds', 'mod_mooduell', 120)
        ];
    }

    /**
     * Because of get_string, this has to be a function.
     *
     * @return array
     * @throws coding_exception
     */
    private function return_move_on_options() {
        return [
                "0" => get_string('clicktomoveon', 'mod_mooduell'),
                "2" => get_string('xseconds', 'mod_mooduell', 2),
                "5" => get_string('xseconds', 'mod_mooduell', 5),
                "10" => get_string('xseconds', 'mod_mooduell', 10),
                "20" => get_string('xseconds', 'mod_mooduell', 20),
                "30" => get_string('xseconds', 'mod_mooduell', 30)
        ];
    }

    private function add_categories_group($counter, $selectedcategory, $listofcategories, $mform) {

        $categoryoptions = $this->return_list_of_category_options($this->generate_sorted_list($listofcategories));
        $catweightoptions = $this->return_list_of_category_weight_options();

        $formgroup = array();
        $formgroup[] =&
                $mform->createElement('select', 'category', get_string('questionscategory', 'mod_mooduell'), $categoryoptions);
        if ($selectedcategory) {
            $formgroup[0]->setSelected($selectedcategory->category);
        }
        $formgroup[] =&
                $mform->createElement('select', 'weight', get_string('categoryweight', 'mod_mooduell'), $catweightoptions);
        if ($selectedcategory) {
            $formgroup[1]->setSelected($selectedcategory->weight);
        } else {
            $formgroup[1]->setSelected('100');
        }
        $mform->addGroup($formgroup, 'categoriesgroup' . $counter, get_string('questionscategorygroup', 'mod_mooduell'));
    }

    private function return_list_of_category_options($list) {

        global $DB;

        $names = array();
        $spaces = "";
        $previousitem = null;

        foreach ($list as $item) {
            if ($item->parent == 0) {
                $spaces = "";
            } else if ($previousitem && $previousitem->id == $item->parent) {
                $spaces .= "-";
            } else {
                $spaces = "-";
                $parent = $this->return_parent_for_item_in_list($list, $item);

                while ($parent->parent != 0) {
                    $parent = $this->return_parent_for_item_in_list($list, $parent);
                    $spaces .= "-";
                }
            }
            if ($item->parent != 0) {

                // Here we fetch the number of available questions from our DB.
                $numberofquestions = $DB->count_records('question', ['category' => $item->id]);

                if ($numberofquestions == 0) {
                    $questionsstring = '(' . get_string('noquestions', 'mod_mooduell') . ')';
                } else if ($numberofquestions == 1) {
                    $questionsstring = '(1 ' . get_string('question', 'mod_mooduell') . ')';
                } else {
                    $questionsstring = '(' . $numberofquestions . ' ' . get_string('questions', 'mod_mooduell') . ')';
                }

                $idkey = (string) $item->id;
                $names[$idkey] = $spaces . "> " . $item->name . ' ' . $questionsstring;
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

    /**
     * @param $listofcategories
     * @return array
     */
    private function generate_sorted_list($listofcategories) {
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

    private function return_list_of_category_weight_options() {
        return array(
                0 => '0',
                17 => '17',
                33 => '33',
                50 => '50',
                66 => '66',
                100 => '100'
        );
    }

    private function get_categories_of_context_from_db() {
        global $DB;

        $context = $this->context;
        $listofcontextids = explode('/', $context->path);

        // Then the SQL query is built from the relevant categories.
        $sql = 'SELECT * FROM {question_categories} WHERE';
        foreach ($listofcontextids as $key => $entry) {
            if ($entry != '') {
                $sql .= ' contextid = ' . $entry;
                if ($key < count($listofcontextids) - 1) {
                    $sql .= ' OR';
                }
            }
        }
        $sql .= ';';

        return $DB->get_records_sql($sql);
    }
}
