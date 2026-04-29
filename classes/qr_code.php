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
 * Plugin event observers are registered here.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use mod_mooduell\manage_tokens;

defined('MOODLE_INTERNAL') || die();


require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/mooduell/thirdparty/vendor/autoload.php');

/**
 * This class handles the QR Code creation
 */
class qr_code {
    /**
     * Creates QR Code with pin for current User and returns QRImage that can be displayed.
     *
     * @param int|null $userid  Target user ID. Defaults to the currently logged-in user.
     */
    public function generate_qr_code(?int $userid = null) {
        global $CFG, $DB, $USER;

        $userid = $userid ?? $USER->id;

        // The autologin URL generators always force-create their own separate tokens,
        // so this QR token is never shared with or consumed by the autologin flow.
        $tokenobject = manage_tokens::generate_token_for_user($userid, 'mod_mooduell_tokens', 300, true);

        $url = $CFG->wwwroot;

        $token = $tokenobject->token;

        $qrstring = $url . ';' . $token;
        // Base64 encode the qr code.
        $basestring = base64_encode($qrstring);
        // Create QR code.
        // Create a basic QR code.
        $qrcode = new QrCode($basestring);
        $qrcode
            ->setSize(300)
            ->setWriterByName('png')
            ->setMargin(10)
            ->setEncoding('UTF-8')
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
            ->setLogoWidth(230)
            ->setValidateResult(false);

        $datauri = $qrcode->writeDataUri();
        return $datauri;
    }

    /**
     * Creates a data URI QR code image for an arbitrary URL.
     *
     * @param string $url
     * @return string
     */
    public function generate_url_qr_code(string $url): string {
        $qrcode = new QrCode($url);
        $qrcode
            ->setSize(300)
            ->setWriterByName('png')
            ->setMargin(10)
            ->setEncoding('UTF-8')
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
            ->setValidateResult(false);

        return $qrcode->writeDataUri();
    }

    /**
     * Creates a one-click web launch URL.
     *
     * @param int|null $userid  Target user ID. Defaults to the currently logged-in user.
     * @return string
     */
    public function generate_web_launch_url(?int $userid = null): string {
        global $CFG, $USER;

        $userid = $userid ?? $USER->id;

        // Force-create a unique token so this autologin URL token is always separate
        // from the QR login token and can be deleted independently after use.
        $tokenobject = manage_tokens::generate_token_for_user($userid, 'mod_mooduell_tokens', 300, true);
        $baseurl = $CFG->wwwroot . '/mod/mooduell/app/frame.html';

        $launchurl = new \moodle_url($baseurl, [
            'source' => 'moodle',
            'moodleurl' => $CFG->wwwroot,
            'token' => $tokenobject->token,
        ]);

        return $launchurl->out(false);
    }

    /**
     * Creates a one-click web app URL that points directly to index.html for iframe embedding.
     *
     * @param int|null $userid  Target user ID. Defaults to the currently logged-in user.
     * @return string
     */
    public function generate_web_app_launch_url(?int $userid = null): string {
        global $CFG, $USER;

        $userid = $userid ?? $USER->id;

        // Force-create a unique token so this autologin URL token is always separate
        // from the QR login token and can be deleted independently after use.
        $tokenobject = manage_tokens::generate_token_for_user($userid, 'mod_mooduell_tokens', 300, true);
        $baseurl = $CFG->wwwroot . '/mod/mooduell/app/index.html';

        $launchurl = new \moodle_url($baseurl, [
            'source' => 'moodle',
            'moodleurl' => $CFG->wwwroot,
            'token' => $tokenobject->token,
        ]);

        return $launchurl->out(false);
    }
}
