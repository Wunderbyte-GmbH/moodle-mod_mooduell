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
 * Contains the class for fetching the important dates in mod_mooduell for a given module instance and a user.
 *
 * @package   mod_mooduell
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use cache;
use curl; // Use Moodle's curl class

/**
 * Handles Firebase Cloud Message Logic.
 */
class fcm_client {

    /** @var cache Application cache for storing tokens */
    private $cache;

    /** @var int Token expiry time */
    private $tokenexpirytime;

    /**
     * Class constructor.
     * Initializes cache for Firebase Cloud Messaging tokens.
     */
    public function __construct() {
        // Initialize a cache instance
        $this->cache = cache::make('mod_mooduell', 'fcmtoken'); // Make sure this cache is defined in your plugin's db/caches.php
    }

    /**
     * Sends a push notification using Firebase Cloud Messaging.
     *
     * @param string $messagetype The type of message to be sent.
     * @return mixed Response from FCM or null on failure.
     */
    public function send_push_notification(string $messagetype) {
        $pushenabled = get_config('mooduell', 'enablepush');
        if ($pushenabled) {
            // $gc = new game_control()
            // $fields = $this->gather_notifcation_data($messagetype);

            // if (!$fields) {
            //     return null;
            // }

            $accesstoken = $this->get_access_token();
            if (!$accesstoken) {
                error_log('Error obtaining access token');
                return null;
            }

            $message = [
                'message' => [
                    'token' => '',  // Set the recipient's device token here
                    'notification' => [
                        'title' => $fields['notification']['title'],
                        'body'  => $fields['notification']['body'],
                    ],
                    'data' => $fields['data'] ?? [],
                ],
            ];

            $curl = new curl();
            $response = $curl->post('https://fcm.googleapis.com/v1/projects/mooduellapp/messages:send', json_encode($message), [
                'CURLOPT_HTTPHEADER' => [
                    'Authorization: Bearer ' . $accesstoken,
                    'Content-Type: application/json',
                ],
            ]);        
            if ($response === FALSE) {
                error_log('Curl failed: ' . $curl->error);
            }
            return $response;
        }
        return null;
    }

    /**
     * Retrieves an access token for Firebase Cloud Messaging.
     *
     * Checks for a valid cached token first.
     * Generates and caches a new token if required.
     *
     * @return string|null The access token or null on failure.
     */
    private function get_access_token() {
        global $CFG;
        $now = time();

        // Check cache first
        if ($cached = $this->cache->get('fcmtoken')) {
            $data = json_decode($cached, true);
            if ($data['expiry'] > $now) {
                return $data['token']; // Return cached token if valid
            }
        }
        $serviceaccountfile = $CFG->dirroot . '/mod/mooduell/files/firebase.json';
        $credentials = json_decode(file_get_contents($serviceaccountfile), true);
        $privatekey = $credentials['private_key'];

        $token = [
            "iss" => $credentials['client_email'],
            "scope" => "https://www.googleapis.com/auth/firebase.messaging",
            "aud" => "https://oauth2.googleapis.com/token",
            "exp" => $now + 3600,  // 1 hour expiration
            "iat" => $now,
        ];

        $jwt = $this->jwt_encode($token, $privatekey);

        // Use Moodle's curl class
        $curl = new curl();
        $response = $curl->post('https://oauth2.googleapis.com/token', http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]), [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            // Cache the token
            $this->cache->set('fcmtoken', json_encode([
                'token' => $result['access_token'],
                'expiry' => $now + 3600
            ]));

            return $result['access_token'];
        }

        return null;
    }

    /**
     * Encodes data into a JWT using RS256.
     *
     * @param array $payload The payload to encode.
     * @param string $privatekey The private key for signing.
     * @return string The resulting JWT.
     */
    private function jwt_encode(array $payload, string $privatekey) {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $segments = [
            $this->base64url_encode(json_encode($header)),
            $this->base64url_encode(json_encode($payload))
        ];

        openssl_sign(implode('.', $segments), $signature, $privatekey, 'sha256');
        $segments[] = $this->base64url_encode($signature);

        return implode('.', $segments);
    }

    /**
     * Encodes string data using Base64URL.
     *
     * @param string $data The data to encode.
     * @return string The Base64URL encoded string.
     */
    private function base64url_encode(string $data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}