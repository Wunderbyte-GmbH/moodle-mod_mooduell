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
 * Plugin administration pages are defined here.
 *
 * @package     mod_mooduell
 * @category    admin
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN;

if ($ADMIN->fulltree) {

        $setting = new admin_setting_configcheckbox(
                'mooduell/usefullnames',
                get_string('usefullnames', 'mod_mooduell'),
                "",
                0
        );
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $settings->add(new admin_setting_configcheckbox(
                'mooduell/showcontinuebutton',
                get_string('showcontinuebutton', 'mod_mooduell'),
                "",
                0
        ));

        $settings->add(new admin_setting_configcheckbox(
                'mooduell/showcorrectanswer',
                get_string('showcorrectanswer', 'mod_mooduell'),
                "",
                0
        ));

        $settings->add(new admin_setting_configcheckbox(
                'mooduell/showgeneralfeedback',
                get_string('showgeneralfeedback', 'mod_mooduell'),
                "",
                0
        ));

        $settings->add(new admin_setting_configcheckbox(
                'mooduell/showanswersfeedback',
                get_string('showanswersfeedback', 'mod_mooduell'),
                "",
                0
        ));

        $name = new lang_string('countdown', 'mod_mooduell');
        $options = [
                "0" => get_string('nocountdown', 'mod_mooduell'),
                "10" => get_string('xseconds', 'mod_mooduell', 10),
                "20" => get_string('xseconds', 'mod_mooduell', 20),
                "30" => get_string('xseconds', 'mod_mooduell', 30),
                "60" => get_string('xseconds', 'mod_mooduell', 60),
                "90" => get_string('xseconds', 'mod_mooduell', 90),
                "120" => get_string('xseconds', 'mod_mooduell', 120),
        ];
        $setting = new admin_setting_configselect(
                'countdown',
                $name,
                "",
                -1,
                $options
        );

        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($setting);

        $name = new lang_string('clicktomoveon', 'mod_mooduell');
        $options = [
                "0" => get_string('clicktomoveon', 'mod_mooduell'),
                "2" => get_string('xseconds', 'mod_mooduell', 2),
                "5" => get_string('xseconds', 'mod_mooduell', 5),
                "10" => get_string('xseconds', 'mod_mooduell', 10),
                "20" => get_string('xseconds', 'mod_mooduell', 20),
                "30" => get_string('xseconds', 'mod_mooduell', 30),
        ];
        $settings->add(new admin_setting_configselect(
                'mooduell/waitfornextquestion',
                $name,
                "",
                -1,
                $options
        ));

        $settings->add(new admin_setting_configtext(
                'mooduell/cachetime',
                get_string('cachetime', 'mod_mooduell'),
                '',
                300,
                PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
                'mooduell/pushtoken',
                get_string('pushtoken', 'mod_mooduell'),
                '',
                '',
                PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
                'mooduell/supporturl',
                get_string('supporturl', 'mod_mooduell'),
                '',
                '',
                PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
                'mooduell/appstoreurl',
                get_string('appstoreurl', 'mod_mooduell'),
                '',
                'https://apps.apple.com/kw/app/u-mooduell/id1596475094',
                PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
                'mooduell/playstoreurl',
                get_string('playstoreurl', 'mod_mooduell'),
                '',
                'https://play.google.com/store/apps/details?id=at.ac.univie.uwmooduell',
                PARAM_URL
        ));

        $settings->add(new admin_setting_configcheckbox(
                'mooduell/enablepush',
                get_string('enablepush', 'mod_mooduell'),
                '',
                0
        ));

        $settings->add(new admin_setting_configcheckbox(
                'mooduell/unlockplatform',
                get_string('unlockplatform', 'mod_mooduell'),
                "",
                0
        ));

        $settings->add(new admin_setting_configstoredfile(
                'mod_mooduell/themejson',
                'Theme File',
                'Pick ThemeFile',
                'themefile',
                0
        ));


        $settings->add(new admin_setting_configstoredfile(
                'mooduell/companylogo',
                'Companylogo',
                'Pick Logo for LoginScreen',
                'themepicture',
                0
        ));
}
