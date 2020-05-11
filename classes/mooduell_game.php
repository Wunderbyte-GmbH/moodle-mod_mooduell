<?php
// This file is part of mod_datalynx for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_mooduell;

use stdClass;
use DateTime;
use moodle_exception;

class mooduell_game
{

    /**
     * @var mooduell MooDuell instance
     */
    public $mooduell;

    /**
     * @var questions
     */
    private $questions;

    /**
     * @var gamedata
     */
    private $gamedata;

    /**
     * game_control constructor.
     * 
     * we set all the data we have at this moment and make it available to the instance of this class
     *
     * @param mooduell $mooduell
     */
    public function __construct(mooduell $mooduell)
    {

        global $USER;

        $now = new DateTime();
        $nowtime = $now->getTimestamp();

        $this->mooduell = $mooduell;

        $data = new stdClass();
        $data->playeraid = $USER->id;
        $data->timemodified = $nowtime;
        $data->timecreated = $nowtime;

        $this->gamedata = $data;
    }

    /**
     * Create new game, set random question sequence and write to DB
     *
     * @return integer quizid or 0 when no quizid is set
     */
    public function create_new_game($playerbid)
    {

        global $USER;
        global $DB;


        $data = $this->gamedata;
        $data->playerbid = $playerbid;
        $data->mooduellid = $this->mooduell->cm->instance;

        //we collect all the data to safe to mooduell_games table

        $gameid = $DB->insert_record('mooduell_games', $data);

        //we retrieve exactly nine questions from the right categories

        $questions = self::get_available_questions();


        //write all our questions to our DB and link it to our gameID
        foreach ($questions as $question) {

            //we set data back
            $data = null;
            $data->questionid = $question->id;
            $data->mooduellid = $this->mooduell->cm->instance;
            $data->gameid = $gameid;

            $DB->insert_record('mooduell_questions', $data);
        }

        return $gameid;
    }


    /**
     * Retrieve all available questions from the question bank, filtered by category
     */
    private function get_available_questions()
    {

        global $DB;
        $questions = array();

        $categories = $DB->get_records('mooduell_categories', ['mooduellid' => $this->mooduell->cm->instance]);


        //first we calculate the number of question every category gets
        $fixednumberofquestions = 9;
        $sum = 0;
        foreach ($categories as $category) {
            $sum += $category->weight;
        }

        $numberofaddedquestions = 0;

        //now we add the numbersofquestions key to each category
        $calculatednumberofquestions = 0;
        foreach ($categories as $category) {

            $categories[$category->id]->numberofquestions = round(($category->weight / $sum) * $fixednumberofquestions);
            $calculatednumberofquestions += $categories[$category->id]->numberofquestions;
        }


        //first we lookup all the categories linked to this Mooduell instance. In our first version, this will return only one record
        foreach ($categories as $category) {


            //we need a correction of the calculated values to make sure we always add exactly nine questions
            //(there could be a problem when we have to categories with weight 100, we would only add two times 4)
            //TODO make this random and linked to overall weight. Right now we only add it to the first category
            if ($calculatednumberofquestions != $fixednumberofquestions) {
                $difference = $fixednumberofquestions - $calculatednumberofquestions;
                $categories[$category->id]->numberofquestions += $difference;
                $calculatednumberofquestions += $difference;

                if ($calculatednumberofquestions != $fixednumberofquestions) {
                    throw new moodle_exception(
                        'wrongnumberofquestions ',
                        null,
                        null,
                        "We have the wrong number of questions"
                    );
                }
            }

            //we retrieve all the available questions
            $allavailalbequestions = $DB->get_records('question', ['category' => $category->category]);


            //we have to be sure that the number of available questions for this category is bigger than the number of questions we want from this category

            if (count($allavailalbequestions) < $category->numberofquestions) {
                throw new moodle_exception(
                    'wrongnumberofquestions ',
                    null,
                    null,
                    "There are not enough questions in this category"
                );
            }


            $i = 0;
            $emergencybreak = 0;
            while ($i < $category->numberofquestions) {
                $key = array_rand($allavailalbequestions);

                $question = $allavailalbequestions[$key];

                if ($question != null && !in_array($question, $questions)) {
                    array_push($questions, $question);
                    $i++;
                }
                $emergencybreak++;
                //we have an emergency break here to avoid looping
                //it could kick in if we have less then nine different questions overall (three random querstions with three times the same category, which all in all only has four questions. So enough questions for each category individually, but not together)

                if ($emergencybreak > 500) {
                    throw new moodle_exception(
                        'onlyduplicatequestionsfound ',
                        null,
                        null,
                        "Apparently we have only duplicate questions, we had to abort our search for unqiue questions"
                    );
                }
            }
        }


        //TODO: here we should have a warning/error if we don't have count(questions) > 9

        if (count($questions) != $fixednumberofquestions) {
            throw new moodle_exception(
                'wrongnumberofquestions ',
                null,
                null,
                "For some unknown reason we didn't receive the right number of questions"
            );
        }




        return $questions;
    }
}
