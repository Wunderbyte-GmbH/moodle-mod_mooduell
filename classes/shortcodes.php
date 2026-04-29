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
 * Shortcode handlers for mod_mooduell.
 *
 * Register shortcodes here for use with Moodle's shortcode filter
 * (filter_shortcodes or Moodle 4.x core shortcodes).
 *
 * Basic usage:
 *   [mooduell securitytoken=ABCDEFGH]
 *
 * Log in as the least-recently-used webservice user from a given course:
 *   [mooduell randomuserfromcourse=12 securitytoken=ABCDEFGH]
 *
 * @package    mod_mooduell
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

/**
 * Shortcode handler class for mod_mooduell.
 */
class shortcodes {
    /**
     * Render a warning box for shortcode validation failures.
     *
     * @param string $stringidentifier
     * @param mixed $a Optional string placeholder data.
     * @return string
     */
    private static function render_shortcode_warning(string $stringidentifier, $a = null): string {
        return '<div class="alert alert-warning mooduell-shortcode-warning" role="alert">'
            . \s(\get_string($stringidentifier, 'mod_mooduell', $a))
            . '</div>';
    }

    /**
     * Returns whether the given course contains at least one MooDuell activity.
     *
     * @param int $courseid
     * @return bool
     */
    private static function course_has_mooduell_instance(int $courseid): bool {
        $instances = \get_coursemodules_in_course('mooduell', $courseid);
        return !empty($instances);
    }

    /**
     * Returns the ID of the least-recently-used webservice user in a course.
     *
     * Uses external_tokens.lastaccess for mod_mooduell_external so the ordering
     * reflects actual app/webservice activity instead of course page visits.
     * Deleted and suspended users are excluded.
     *
     * @param int $courseid
     * @return int|null User ID, or null if no eligible users found.
     */
    private static function get_least_recently_active_user_in_course(int $courseid): ?int {
        global $DB;

        $sql = 'SELECT u.id,
            COALESCE(MAX(et.lastaccess), 0) AS ws_lastaccess
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e            ON e.id = ue.enrolid AND e.courseid = :courseid
            LEFT JOIN {external_services} es ON es.shortname = :servicename AND es.enabled = 1
            LEFT JOIN {external_tokens} et ON et.userid = u.id
                                        AND et.externalserviceid = es.id
                                        AND et.tokentype = :tokentype
                                        AND (et.validuntil = 0 OR et.validuntil > :now)
                WHERE u.deleted   = 0
                AND u.suspended = 0
                AND ue.status   = 0
            GROUP BY u.id
            ORDER BY COALESCE(MAX(et.lastaccess), 0) ASC, u.id ASC';

        $record = $DB->get_record_sql($sql, [
            'courseid'    => $courseid,
            'servicename' => 'mod_mooduell_external',
            'tokentype'   => EXTERNAL_TOKEN_PERMANENT,
            'now'         => time(),
        ]);

        return $record ? (int) $record->id : null;
    }

    /**
     * Renders an authenticated MooDuell web-app iframe for the current user.
     *
     * Works anywhere in Moodle (course pages, dashboard, site homepage, blocks,
     * custom pages) as long as the viewer is a logged-in, non-guest user.
     * No cmid or course context is required — a short-lived autologin token is
     * minted for whoever is currently viewing the page.
     *
     * Usage:
     *   [mooduell securitytoken=ABCDEFGH]
     *
     * Log in as the least-recently-used webservice user from a given course:
     *   [mooduell randomuserfromcourse=12 securitytoken=ABCDEFGH]
     *
     * @param string        $shortcode  The shortcode tag name ("mooduell").
     * @param array         $args       Shortcode attributes. securitytoken is required for all usages.
     *                                  randomuserfromcourse (int course ID) is optional.
     * @param string|null   $content    Inner content between tags (unused).
     * @param object        $env        Rendering environment from the filter.
     * @param \Closure|null $next       Next handler in the filter chain.
     * @return string Rendered HTML, or empty string for guests / no eligible users.
     */
    public static function mooduell(
        string $shortcode,
        array $args,
        ?string $content,
        object $env,
        ?\Closure $next
    ): string {
        global $CFG, $PAGE;

        $configuredtoken = (string) \get_config('mooduell', 'shortcodetoken');
        $providedtoken = isset($args['securitytoken']) ? trim((string) $args['securitytoken']) : '';

        if ($configuredtoken === '' || $providedtoken === '') {
            return self::render_shortcode_warning('shortcode_warning_missing_securitytoken');
        }

        if (!hash_equals($configuredtoken, $providedtoken)) {
            return self::render_shortcode_warning('shortcode_warning_invalid_securitytoken');
        }

        // Only render for authenticated, non-guest users.
        // if (!isloggedin() || isguestuser()) {
        // return '';
        // }.

        // Resolve target user: randomuserfromcourse picks the least-recently-used
        // webservice user from the given course; otherwise default to current user.
        $targetuserid = null;
        $randomcourseid = !empty($args['randomuserfromcourse']) ? (int) $args['randomuserfromcourse'] : 0;
        if (!empty($randomcourseid)) {
            if (!self::course_has_mooduell_instance($randomcourseid)) {
                return self::render_shortcode_warning('shortcode_warning_missing_mooduell_instance', $randomcourseid);
            }

            $targetuserid = self::get_least_recently_active_user_in_course($randomcourseid);
            if (empty($targetuserid)) {
                return self::render_shortcode_warning('shortcode_warning_no_eligible_user', $randomcourseid);
            }
        }

        // Build the same data the student view uses for the phone-frame block.
        // Pass $targetuserid (null = current user) to the URL generators.
        $qrcode = new qr_code();

        $data = [];
        $data['webapppreviewurl'] = $qrcode->generate_web_app_launch_url($targetuserid);
        $data['webloginurl']      = $qrcode->generate_web_launch_url($targetuserid);
        $data['qrimage']          = $qrcode->generate_qr_code($targetuserid);
        $data['launchlogourl']    = $CFG->wwwroot . '/mod/mooduell/app/assets/images/Logo-full-whiteweb.png';

        $appstorelink  = \get_config('mooduell', 'appstoreurl');
        $playstorelink = \get_config('mooduell', 'playstoreurl');

        if (!empty($appstorelink)) {
            $data['appstorelink']    = $appstorelink;
            $data['appstoreqrimage'] = $qrcode->generate_url_qr_code($appstorelink);
        }
        if (!empty($playstorelink)) {
            $data['playstorelink']    = $playstorelink;
            $data['playstoreqrimage'] = $qrcode->generate_url_qr_code($playstorelink);
        }

        $output = $PAGE->get_renderer('mod_mooduell');
        $html = $output->render_from_template('mod_mooduell/launch_preview', $data);

        // Boot the QR expiry timer (same AMD module the student view uses).
        $PAGE->requires->js_call_amd('mod_mooduell/qrrefresh', 'init');

        return $html;
    }
}
