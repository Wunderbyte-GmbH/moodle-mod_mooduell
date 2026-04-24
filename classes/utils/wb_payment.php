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
 * Wunderbyte Payment Methods.
 *
 * Contains methods for license verification and more.
 *
 * @package mod_mooduell
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\utils;

use cache;
use stdClass;

/**
 * Class to handle Wunderbyte Payment Methods.
 *
 * Contains methods for license verification and more.
 *
 * @package mod_mooduell
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wb_payment {
    /**
     * Product identifiers to allowed subscribed user limits.
     * Null means unlimited users.
     */
    public const LICENSE_PRODUCT_LIMITS = [
        'mooduellpro' => 1000,
        'mooduellpremium' => 5000,
        'mooduellpremiumplus' => 20000,
        'mooduell' => null,
    ];

    /**
     * Threshold from which admin warnings should be shown.
     */
    public const LICENSE_WARNING_THRESHOLD_PERCENT = 80;

    /**
     * mod_mooduell_PUBLIC_KEY
     *
     * @var mixed
     */
    public const MOD_MOODUELL_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu8vRBnPDug2pKoGY9wQS
KNTK1SzrPuU0KC8xm22GPQZQM1XkPpvNwBp8CmXUN29r/qiPxapDNVmIH5Ectvb+
NA7EsuVSS8xV6HfjV0tNZKIfFA4b1JD7t6l4gGDLuoppvKQV9n1JP/uZhQlFZ8Dg
7qMXGsEWRcmRGSBZxIVA+EiN35ALsR78MYWEmuAtKKtskqD4cwnAQzZhU1tZRFHz
/uSfhS2tFXQ7vjvCPIozzo9Mgy4Vr4Qoc9ohg0AfK/D3IoA/mpQFpVC+hyS+rQ0d
uqjiVvh1b0cI3ZBEwWeaNKR4Z3dVb3RHOnICCJPyxxIfSDKWDmQDMCMLa5UjvSvM
pwIDAQAB
-----END PUBLIC KEY-----";

    /**
     * Decrypt a PRO license key to get the expiration date of the license
     *
     * @param string $encryptedlicensekey an object containing licensekey and signature
     * @return string the expiration date of the license key formatted as Y-m-d
     */
    public static function decryptlicensekey(string $encryptedlicensekey): array {
        global $CFG;
        // Step 1: Do base64 decoding.
        $encryptedlicensekey = base64_decode($encryptedlicensekey);

        // Step 2: Decrypt using public key.
        openssl_public_decrypt($encryptedlicensekey, $licensekey, self::MOD_MOODUELL_PUBLIC_KEY);
        if (!$licensekey) {
            return [];
        }
        // Step 3: Do another base64 decode and decrypt using wwwroot.
        $c = base64_decode($licensekey);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);

        // Bugfix when passing wrong license keys that are too short.
        if (strlen($iv) != 16) {
            return [];
        }

        $sha2len = 32;
        $ciphertextraw = substr($c, $ivlen + $sha2len);
        $decryptedcontent = openssl_decrypt($ciphertextraw, $cipher, $CFG->wwwroot, $options = OPENSSL_RAW_DATA, $iv);
        $parts = explode(';', $decryptedcontent);
        $result = [
            'exptime' => $parts[0] ?? '',
            'product' => $parts[1] ?? '',
        ];
        return $result;
    }

    /**
     * Helper function to determine if the user has set a valid license key which has not yet expired.
     *
     * @return bool true if the license key is valid at current date
     * @throws \dml_exception
     */
    public static function pro_version_is_activated() {
        // Get license key which has been set in settings.php.
        $pluginconfig = get_config('mooduell');
        if (!empty($pluginconfig->licensekey)) {
            $licensekeyfromsettings = $pluginconfig->licensekey;
            // DEBUG: echo "License key from plugin config: $licensekey_from_settings<br>"; END.

            $data = self::decryptlicensekey($licensekeyfromsettings);
            if ($data == []) {
                return false;
            }

            if (!isset($data['product']) || !array_key_exists($data['product'], self::LICENSE_PRODUCT_LIMITS)) {
                return false;
            }

            // Return true if the current timestamp has not yet reached the expiration date.
            if (time() < strtotime($data['exptime'])) {
                if ($data['product'] === 'mooduell') {
                    return true;
                }

                $userlimit = self::LICENSE_PRODUCT_LIMITS[$data['product']];
                if ($userlimit !== null && self::count_subscribed_users_with_mooduell_access() <= $userlimit) {
                    return true;
                }
            }
        }
        // Overriding - always use PRO for testing / debugging.
        // Check if Behat OR PhpUnit tests are running.
        if ((defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
            return true;
        }
        return false;
    }

    /**
     * Return current license usage information for limited mooduell products.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_limited_license_status(): array {
        $status = [
            'islimitedproduct' => false,
            'product' => '',
            'limit' => 0,
            'currentusers' => 0,
            'percentage' => 0,
            'atwarningthreshold' => false,
            'isoverlimit' => false,
        ];

        $pluginconfig = get_config('mooduell');
        if (empty($pluginconfig->licensekey)) {
            return $status;
        }

        $data = self::decryptlicensekey($pluginconfig->licensekey);
        if ($data == [] || !isset($data['product']) || !array_key_exists($data['product'], self::LICENSE_PRODUCT_LIMITS)) {
            return $status;
        }

        if (time() >= strtotime($data['exptime'])) {
            return $status;
        }

        $productlimit = self::LICENSE_PRODUCT_LIMITS[$data['product']];
        if ($productlimit === null) {
            return $status;
        }

        $currentusers = self::count_subscribed_users_with_mooduell_access();
        $percentage = (int)floor(($currentusers / $productlimit) * 100);

        $status['islimitedproduct'] = true;
        $status['product'] = $data['product'];
        $status['limit'] = $productlimit;
        $status['currentusers'] = $currentusers;
        $status['percentage'] = $percentage;
        $status['atwarningthreshold'] = $percentage >= self::LICENSE_WARNING_THRESHOLD_PERCENT;
        $status['isoverlimit'] = $currentusers > $productlimit;

        return $status;
    }

    /**
     * Returns true when creating new games and activities must be blocked due to license limits.
     *
     * @return bool
     * @throws \dml_exception
     */
    public static function is_creation_blocked_due_to_license_limit(): bool {
        $status = self::get_limited_license_status();
        return !empty($status['isoverlimit']);
    }

    /**
     * Build warning message for admin pages when usage reaches warning threshold.
     *
     * @return string|null
     * @throws \dml_exception
     */
    public static function get_admin_limit_warning_message(): ?string {
        $status = self::get_limited_license_status();
        if (empty($status['islimitedproduct']) || empty($status['atwarningthreshold'])) {
            return null;
        }

        $stringdata = (object)[
            'current' => $status['currentusers'],
            'limit' => $status['limit'],
            'percentage' => $status['percentage'],
        ];

        if (!empty($status['isoverlimit'])) {
            return get_string('licenselimit_over_warning', 'mod_mooduell', $stringdata);
        }

        return get_string('licenselimit_threshold_warning', 'mod_mooduell', $stringdata);
    }

    /**
     * Return the current number of active users counted for mooduell licensing.
     *
     * @return int
     */
    public static function get_current_active_license_users(): int {
        return self::count_subscribed_users_with_mooduell_access();
    }

    /**
     * Counts users who are enrolled in a course containing at least one mooduell activity.
     * The value is cached in MUC to avoid frequent DB work.
     *
     * @return int
     */
    protected static function count_subscribed_users_with_mooduell_access(): int {
        global $DB;

        $cache = cache::make('mod_mooduell', 'licenseaccesscountcache');
        $cachekey = 'subscribeduserswithmooduell';
        $cachedcount = $cache->get($cachekey);

        if ($cachedcount !== false) {
            return (int) $cachedcount;
        }

        $now = time();
        $params = [
            'timestartnow' => $now,
            'timeendnow' => $now,
        ];

        $sql = "SELECT COUNT(DISTINCT ue.userid)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {mooduell} md ON md.course = c.id
                  JOIN {user} u ON u.id = ue.userid
                 WHERE e.status = 0
                   AND ue.status = 0
                   AND (ue.timestart = 0 OR ue.timestart <= :timestartnow)
                   AND (ue.timeend = 0 OR ue.timeend > :timeendnow)
                   AND u.deleted = 0
                   AND u.suspended = 0";

        $usercount = (int) $DB->count_records_sql($sql, $params);
        $cache->set($cachekey, $usercount);

        return $usercount;
    }
}
