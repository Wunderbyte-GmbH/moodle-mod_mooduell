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
 * Quiz answer class for mod_mooduell.
 *
 * @package mod_mooduell
 * @copyright 2021 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mooduell;

/**
 * Class for MooDuell answers.
 * @package mod_mooduell
 */
class answer_control {

    /**
     *
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $answertext;

    /**
     *
     * @var int
     */
    public $fraction;

    /**
     * The answer-specific feedback.
     * @var string
     */
    public $feedback;

    /**
     * @var bool
     */
    public $correct;

    /**
     * Constructor for answer_control class.
     * @param null $data
     */
    public function __construct($data = null) {

        // If we have $data, we automatically create all the relevant values for this answer...
        if ($data) {
            $this->id = $data->id;

            // The original answer.
            $this->answertext = $data->answer;

            // Only strip HTML-Tags and remove markdown, if it's a text format.
            if ($data->answerformat == 1) {

                // If there is still markdown in answers, we need to render it properly.
                $this->answertext = format_text($this->answertext, FORMAT_MARKDOWN);

                // Now, we will remove all HTML tags and trim whitespaces.
                $this->answertext = trim(strip_tags($this->answertext));
            }

            // The answer fraction (percentage).
            $this->fraction = $data->fraction;

            // The answer feedback.
            if ($data->feedback) {
                $this->feedback = $data->feedback;
            }

            // Is it a right or a wrong answer?
            if ($this->fraction > 0) {
                $this->correct = true;
            } else {
                $this->correct = false;
            }
        }
    }
}
