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
 * QR Code is generated here
 *
 * @package     mod_mooduell
 * @copyright   2020 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/mooduell/thirdparty/vendor/autoload.php');

/**
 * This class handles the QR Code creation
 */
class qr_code {

    /**
     * Creates QR Code with pin for current User and returns QRImage that can be displayed.
     */
    function generate_qr_code() {
        global $CFG, $DB;
        

        $service = $DB->get_record('external_services', array('shortname' => 'mod_mooduell_external', 'enabled' => 1));
        if (empty($service)) {
            // will throw exception if no token found
            return;
        }

        
        //setup qrcode parameters
        $tokenObject = external_generate_token_for_current_user($service);
     
        $url = $CFG->wwwroot;
     
        $token = $tokenObject->token;

        $pinCode = rand(1000,9999);

        $qrstring = $url . ';' . $token . ';' . $pinCode;

        //base64 encode the qr code
        $basestring = base64_encode($qrstring);
        $text = get_string('pincode','mod_mooduell');
        // Create QR code
        // Create a basic QR code
        $qrCode = new QrCode($basestring);
        $qrCode
        ->setSize(300)
        ->setWriterByName('png')
        ->setMargin(10)
        ->setEncoding('UTF-8')
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
        ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0])
        ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255])
        ->setLabel($text . $pinCode, 16, $CFG->dirroot . '/mod/mooduell/thirdparty/vendor/endroid/qrcode/assets//noto_sans.otf', LabelAlignment::CENTER)
        ->setLogoPath($CFG->dirroot . '/mod/mooduell/pix/wb_logo.png')
        ->setLogoWidth(230)
        ->setValidateResult(false);

        $dataUri = $qrCode->writeDataUri();
   
        return $dataUri;
        
    }


}