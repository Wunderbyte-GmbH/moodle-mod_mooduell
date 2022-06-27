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
}
