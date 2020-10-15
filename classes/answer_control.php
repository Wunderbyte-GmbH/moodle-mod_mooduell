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

namespace mod_mooduell;

defined('MOODLE_INTERNAL') || die();

class answer_control {

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var string
     */
    public $answertext;

    /**
     *
     * @var int
     */
    public $fraction;

    /**
     *
     * @var string
     */
    public $feedback;

    /**
     * @var bool
     */
    public $correct;

    /**
     * question_control constructor.
     *
     * @param mooduell $mooduell
     */
    public function __construct($data = null) {

        // If we have $data, we automatically create all the relevant values for this answer...

        if ($data) {
            $this->id = $data->id;
            $this->answertext = strip_tags($data->answer);
            $this->fraction = $data->fraction;
            if ($data->feedback) {
                $this->feedback = $data->feedback;
            }
            if ($this->fraction > 0) {
                $this->correct = true;
            }
        }
    }
}