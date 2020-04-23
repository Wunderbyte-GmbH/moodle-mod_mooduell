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
 * The main mod_mooduell configuration form.
 *
 * @package     mod_mooduell
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_mooduell
 * @copyright  2020 David Bogner <david.bogner@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        $mform->addElement('text', 'name', get_string('mooduellname', 'mod_mooduell'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'mooduellname', 'mod_mooduell');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_mooduell settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('static', 'label1', 'mooduellsettings', get_string('mooduellsettings', 'mod_mooduell'));
        $mform->addElement('header', 'mooduellfieldset', get_string('mooduellfieldset', 'mod_mooduell'));

        $mform->addElement('checkbox', 'useFullNames', get_string('useFullNames', 'mod_mooduell'));
        $mform->addElement('checkbox', 'showContinueButton', get_string('showContinueButton', 'mod_mooduell'));
        $mform->addElement('checkbox', 'showCorrectAnswer', get_string('showCorrectAnswer', 'mod_mooduell'));
        $mform->addElement('select', 'type', get_string('countdown', 'mod_mooduell'), array(get_string('nocountdown', 'mod_mooduell'), get_string('10seconds', 'mod_mooduell'), get_string('20seconds', 'mod_mooduell'), get_string('30seconds', 'mod_mooduell'), get_string('60seconds', 'mod_mooduell'), get_string('90seconds', 'mod_mooduell'), get_string('120seconds', 'mod_mooduell')));

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
