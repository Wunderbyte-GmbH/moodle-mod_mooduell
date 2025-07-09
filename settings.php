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

use mod_mooduell\utils\wb_payment;
defined('MOODLE_INTERNAL') || die();

global $ADMIN;

if ($ADMIN->fulltree) {
    // Collecting necessary settings.
    $settingscheck = [
        'webservices' => get_config('core', 'enablewebservices'),
        'mobileservices' => get_config('core', 'enablemobilewebservice'),
    ];

    // Prepare data for Mustache template.
    $templatecontext = (object)[
        'settings_check' => $settingscheck,
    ];

    // Add a custom setting type to display the rendered template.
    $settings->add(new admin_setting_heading(
        'integrationcheckheading',
        get_string('integrationcheck_integrationcheck', 'mod_mooduell'),
        $OUTPUT->render_from_template('mod_mooduell/integration_check', $templatecontext)
    ));

    // Has PRO version been activated?
    $proversion = wb_payment::pro_version_is_activated();

    $settings->add(
        new admin_setting_heading(
            'licensekeycfgheading',
            get_string('licensekeycfg', 'mod_mooduell'),
            get_string('licensekeycfgdesc', 'mod_mooduell')
        )
    );
    // Dynamically change the license info text.
    $licensekeydesc = get_string('licensekeydesc', 'mod_mooduell');

    // Get license key which has been set in text field.
    $pluginconfig = get_config('mooduell');
    if (!empty($pluginconfig->licensekey)) {
        $licensekey = $pluginconfig->licensekey;
        $decryptedlicensekey = wb_payment::decryptlicensekey($licensekey);
        if (array_key_exists('exptime', $decryptedlicensekey)) {
            $expirationdate = $decryptedlicensekey['exptime'];

            if (wb_payment::pro_version_is_activated()) {
                $licensekeydesc = "<p style='color: green; font-weight: bold'>"
                    . get_string('license_activated', 'mod_mooduell')
                    . $expirationdate
                    . ")</p>";
            }
        } else {
            $licensekeydesc = "<p style='color: red; font-weight: bold'>"
                . get_string('license_invalid', 'mod_mooduell')
                . "</p>";
        }
    }

    $settings->add(
        new admin_setting_configtext(
            'mooduell/licensekey',
            get_string('licensekey', 'mod_mooduell'),
            $licensekeydesc,
            ''
        )
    );

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
            'https://apps.apple.com/at/app/mooduell/id1598911543',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'mooduell/playstoreurl',
            get_string('playstoreurl', 'mod_mooduell'),
            '',
            'https://play.google.com/store/apps/details?id=at.wunderbyte.mooduellapp',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configcheckbox(
            'mooduell/enablepush',
            get_string('enablepush', 'mod_mooduell'),
            '',
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'mooduell/disablebadges',
            get_string('disablesbadges', 'mod_mooduell'),
            "",
            0
        ));

    if ($proversion) {
                $settings->add(new admin_setting_configtextarea(
                    'mod_mooduell/themejsonarea',
                    get_string('theme', 'mod_mooduell'),
                    get_string('themedesc', 'mod_mooduell'),
                    '',
                    PARAM_TEXT
                ));

                $settings->add(new admin_setting_configstoredfile(
                    'mod_mooduell/companylogo',
                    get_string('companylogo', 'mod_mooduell'),
                    get_string('companylogodesc', 'mod_mooduell'),
                    'themepicture',
                    0
                ));
                $settings->add(new admin_setting_configstoredfile(
                    'mod_mooduell/companylogoalternative',
                    get_string('alternativelogo', 'mod_mooduell'),
                    get_string('alternativelogodesc', 'mod_mooduell'),
                    'themepicturealternative',
                    0
                ));
    }
}
