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

    /** @var array $completionmodes - defined completionmodes for this form */
    private $completionmodes = ['completiongamesplayed', 'completiongameswon', 'completionrightanswers'];

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
        $mform->addElement('header', 'mooduellsettings', get_string('mooduellsettings', 'mod_mooduell'));
        $mform->setExpanded('mooduellsettings');

        $mform->addElement('checkbox', 'usefullnames', get_string('usefullnames', 'mod_mooduell'));
        $mform->setDefault('usefullnames', $config->usefullnames);
        $mform->addHelpButton('usefullnames', 'usefullnames', 'mod_mooduell');

        $mform->addElement('checkbox', 'showcontinuebutton', get_string('showcontinuebutton', 'mod_mooduell'));
        $mform->setDefault('showcontinuebutton', $config->showcontinuebutton);
        $mform->addHelpButton('showcontinuebutton', 'showcontinuebutton', 'mod_mooduell');

        $mform->addElement('checkbox', 'showcorrectanswer', get_string('showcorrectanswer', 'mod_mooduell'));
        $mform->setDefault('showcorrectanswer', $config->showcorrectanswer);
        $mform->addHelpButton('showcorrectanswer', 'showcorrectanswer', 'mod_mooduell');

        $mform->addElement('checkbox', 'showgeneralfeedback', get_string('showgeneralfeedback', 'mod_mooduell'));
        $mform->setDefault('showgeneralfeedback', $config->showgeneralfeedback);
        $mform->addHelpButton('showgeneralfeedback', 'showgeneralfeedback', 'mod_mooduell');

        $mform->addElement('checkbox', 'showanswersfeedback', get_string('showanswersfeedback', 'mod_mooduell'));
        $mform->setDefault('showanswersfeedback', $config->showanswersfeedback);
        $mform->addHelpButton('showanswersfeedback', 'showanswersfeedback', 'mod_mooduell');

        $mform->addElement('select', 'countdown', get_string('countdown', 'mod_mooduell'), $this->return_countdown_options());
        if (isset($config->countdown)) {
            $mform->setDefault('countdown', $config->countdown);
        }
        $mform->addHelpButton('countdown', 'countdown', 'mod_mooduell');

        $mform->addElement('select', 'waitfornextquestion', get_string('waitfornextquestion', 'mod_mooduell'),
                $this->return_move_on_options());
        if (isset($config->waitfornextquestion)) {
            $mform->setDefault('waitfornextquestion', $config->waitfornextquestion);
        }
        $mform->addHelpButton('waitfornextquestion', 'waitfornextquestion', 'mod_mooduell');
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
     * create array for countdown select.
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

    /**
     * Possibility to add another category.
     * @param int $counter
     * @param object|null $selectedcategory
     * @param array $listofcategories
     * @param object $mform
     * @throws coding_exception
     */
    private function add_categories_group(int $counter, $selectedcategory, array $listofcategories, object $mform) {

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

    /**
     * Build the categories list.
     * @param array $list
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    private function return_list_of_category_options(array $list) {

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

    /**
     * Returns the parent of an item in list.
     * @param array $list
     * @param object $item
     * @return mixed
     */
    private function return_parent_for_item_in_list(array $list, object $item) {
        foreach ($list as $parentitem) {
            if ($item->parent == $parentitem->id) {
                $parent = $parentitem;
                break;
            }
        }
        return $parent;
    }

    /**
     * Generate a sorted list.
     * @param array $listofcategories
     * @return array
     */
    private function generate_sorted_list(array $listofcategories) {
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

    /**
     * Returns children in list.
     * @param object $parent
     * @param array $list
     * @return array
     */
    private function return_children_in_list(object $parent, array $list) {
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

    /**
     * Returns list of category weight options.
     * @return string[]
     */
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

    /**
     * Get categories of context from db.
     * @return array
     * @throws dml_exception
     */
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

    /**
     * Set defaults and prepare data for form.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        foreach ($this->completionmodes as $mode) {
            $defaultvalues[$mode . 'enabled'] = !empty($defaultvalues[$mode]) ? 1 : 0;
            if (empty($defaultvalues[$mode])) {
                $defaultvalues[$mode] = 1;
            }
        }
    }
    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $result = [];
        foreach ($this->completionmodes as $mode) {
            $group = array();
            $group[] = $mform->createElement('checkbox', $mode . 'enabled', '', get_string($mode, 'mooduell'));
            $group[] = $mform->createElement('text', $mode, '', array('size' => 2));
            $mform->setType($mode, PARAM_INT);
            $mform->addGroup($group, $mode . 'group', get_string($mode . 'label', 'mooduell'), array(' '), false);
            $mform->disabledIf($mode, $mode . 'enabled', 'notchecked');
            $result[] = $mode . 'group';
        }
        return $result;
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        foreach ($this->completionmodes as $mode) {
            if (!empty($data[$mode . 'enabled']) && $data[$mode] !== 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the data of the form
     *
     * @return array Contains the data of the form
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked.
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completiongamesplayedenabled) || !$autocompletion) {
                $data->completiongamesplayed = 0;
            }
            if (empty($data->completiongameswonenabled) || !$autocompletion) {
                $data->completiongameswon = 0;
            }
            if (empty($data->completionrightanswersenabled) || !$autocompletion) {
                $data->completionrightanswers = 0;
            }
        }
        return $data;
    }
}
