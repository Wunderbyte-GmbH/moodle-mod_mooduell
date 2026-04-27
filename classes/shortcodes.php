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
 * Basic usage — works anywhere the user is logged in:
 *   [[mooduell]]
 *
 * Step 2 – log in as the most-recently-active user from a course (not yet implemented):
 *   [[mooduell randomuserfromcourse=12]]
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
     * Renders an authenticated MooDuell web-app iframe for the current user.
     *
     * Works anywhere in Moodle (course pages, dashboard, site homepage, blocks,
     * custom pages) as long as the viewer is a logged-in, non-guest user.
     * No cmid or course context is required — a short-lived autologin token is
     * minted for whoever is currently viewing the page.
     *
     * Usage:
     *   [[mooduell]]
     *
     * Step 2 – random user from course (not yet implemented):
     *   [[mooduell randomuserfromcourse=12]]
     *
     * @param string        $shortcode  The shortcode tag name ("mooduell").
     * @param array         $args       Shortcode attributes (currently unused).
     * @param string|null   $content    Inner content between tags (unused).
     * @param object        $env        Rendering environment from the filter.
     * @param \Closure|null $next       Next handler in the filter chain.
     * @return string Rendered <iframe> HTML, or empty string for guests.
     */
    public static function mooduell(
        string $shortcode,
        array $args,
        ?string $content,
        object $env,
        ?\Closure $next
    ): string {
        // Only render for authenticated, non-guest users.
        if (!isloggedin() || isguestuser()) {
            return '';
        }

        // Generate an authenticated single-use launch URL for the current user.
        // This only needs $USER->id — no course or activity context required.
        $qrcode = new qr_code();
        $url = $qrcode->generate_web_app_launch_url();

        if (empty($url)) {
            return '';
        }

        // s() HTML-encodes the URL (& → &amp; etc.) for safe use in an attribute.
        return '<iframe class="mooduell-embed-iframe"'
            . ' src="' . s($url) . '"'
            . ' title="MooDuell"'
            . ' loading="lazy"'
            . ' allow="camera; microphone">'
            . '</iframe>';
    }
}
