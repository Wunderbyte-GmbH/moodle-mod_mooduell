<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Mobile cache definitions.
 *
 * @package    mod_mooduell
 * @copyright  2022 Georg Maißer <info@wudnerbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$definitions = [
    'tablescache' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 5,
        'invalidationevents' => ['setbacktablescache'],
    ],
    'userscache' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => false,
        'staticaccelerationsize' => 1,
        'invalidationevents' => ['setbackuserscache'],
    ],
    'questionscache' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'staticacceleration' => false,
        'staticaccelerationsize' => 1,
        'invalidationevents' => ['setbackquestionscache'],
    ],
    'fcmtoken' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'static' => true,
    ],
];
