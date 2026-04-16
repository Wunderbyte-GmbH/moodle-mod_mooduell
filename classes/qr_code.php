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
     */
    public function generate_qr_code() {
        global $CFG, $DB, $USER;

        $tokenobject = manage_tokens::generate_token_for_user($USER->id, 'mod_mooduell_tokens', 300);

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
     * Creates a one-click web launch URL for the currently logged in Moodle user.
     *
     * @return string
     */
    public function generate_web_launch_url(): string {
        global $CFG, $USER;

        $tokenobject = manage_tokens::generate_token_for_user($USER->id, 'mod_mooduell_tokens', 300);
        $baseurl = get_config('mooduell', 'webappurl');

        if (empty($baseurl) || strpos($baseurl, 'mooduellapp.wunderbyte.at/frame.html') !== false) {
            $baseurl = $CFG->wwwroot . '/mod/mooduell/app/frame.html';
        }

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
     * @return string
     */
    public function generate_web_app_launch_url(): string {
        global $CFG, $USER;

        $tokenobject = manage_tokens::generate_token_for_user($USER->id, 'mod_mooduell_tokens', 300);
        $baseurl = get_config('mooduell', 'webappurl');

        if (empty($baseurl) || strpos($baseurl, 'mooduellapp.wunderbyte.at/frame.html') !== false) {
            $baseurl = $CFG->wwwroot . '/mod/mooduell/app/frame.html';
        }

        if (strpos($baseurl, '/frame.html') !== false) {
            $baseurl = str_replace('/frame.html', '/index.html', $baseurl);
        }

        $launchurl = new \moodle_url($baseurl, [
            'source' => 'moodle',
            'moodleurl' => $CFG->wwwroot,
            'token' => $tokenobject->token,
        ]);

        return $launchurl->out(false);
    }
}
