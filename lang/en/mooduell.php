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
 * Plugin strings are defined here.
 *
 * @package     mod_mooduell
 * @category    string
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['messageprovider:status'] = 'Game status changes';
$string['mooduell:managemooduellsettings'] = 'Manage the settings of a MooDuell Game';
$string['mooduell:viewstatistics'] = 'View game statistics';
$string['mooduell:viewstudentnames'] = 'View full names of participants';
$string['mooduell:viewinstance'] = 'View an instance of mooduell';
$string['mooduell:editgames'] = 'Edit games';
$string['pluginname'] = 'MooDuell';
$string['modulenameplural'] = 'MooDuells';
$string['mooduellname'] = 'Name of the MooDuell game';
$string['mooduellname_help'] = 'Choose a name for your game';
$string['mooduellsettings'] = 'MooDuell settings';
$string['mooduellfieldset'] = 'MooDuell settings';
$string['usefullnames'] = 'Use full names';
$string['usefullnames_help'] = 'Full names of users will be shown. If not, users have to define a MooDuell alias in their profile.';
$string['modulename_help'] = 'The MooDuell module helps you to create multiplayer quiz activities which then can be played using the MooDuell App.';
$string['modulename'] = 'MooDuell';
$string['addInstance'] = 'Add instance';
$string['mooduell:addinstance'] = 'Add instance';

$string['enablepush'] = 'enable push';
$string['pushtoken'] = 'Push API Access Key';
$string['countdown'] = 'Countdown';
$string['countdown_help'] = 'How much time will players have to answer a question';
$string['nocountdown'] = 'No Countdown';
$string['xseconds'] = '{$a} seconds';

$string['viewgame'] = 'View game';
$string['deletegame'] = 'Delete game';

$string['showcontinuebutton'] = 'Show "Continue"-button';
$string['showcontinuebutton_help'] = 'Even when there is only one correct answer, users have to confirm their choice by clicking on the "Continue"-button.';
$string['showcorrectanswer'] = 'Show correct answer';
$string['showcorrectanswer_help'] = 'After wrongly answering a question, the right answer will be shown.';
$string['showgeneralfeedback'] = 'Show general feedback';
$string['showgeneralfeedback_help'] = 'If you activate this, the text from a question\'s "General feedback" will be shown after answering a question.';
$string['showanswersfeedback'] = 'Show answer-specific feedbacks';
$string['showanswersfeedback_help'] = 'If you activate this, the feedback texts set for each individual answer will be shown after answering a question.';
$string['waitfornextquestion'] = 'Time to next question';
$string['waitfornextquestion_help'] = 'How long should the right answer should be visible after the user has answered a question.';
$string['clicktomoveon'] = 'Click to move on';

$string['pluginadministration'] = 'Plugin Administration';
$string['questionscategory'] = 'Category for random question';
$string['categoryweight'] = 'Weight of the category';
$string['categoriesexplanation'] = 'If you choose more than one category, please add a weight. Weight DOES NOT have to sum up to 100';
$string['addanothercategory'] = 'Add another category';
$string['questionscategorygroup'] = 'Categories for random questions';
$string['nocategories'] = 'There are not categories added yet';
$string['important'] = 'Important!';
$string['nomessage'] = 'There is nothing to say';
$string['foundthesegames'] = 'We found these games:';
$string['noopengames'] = 'There are no open games yet.';
$string['nostatistics'] = 'Sorry, statistics could\'nt be loaded.';

$string['listofquestions'] = 'List of questions:';
$string['playera'] = 'Player A';
$string['playerb'] = 'Player B';
$string['playeraresults'] = 'Result A';
$string['playerbresults'] = 'Result B';
$string['playerbresults'] = 'Result B';
$string['action'] = 'Action';
$string['noquestions'] = 'No questions';
$string['question'] = 'question';
$string['warnings'] = 'Warnings';
$string['status'] = 'Status';
$string['category'] = 'Category';

$string['lastplayed'] = 'Last time played';

$string['statistics'] = 'Statistics';
$string['opengames'] = 'Open games';
$string['finishedgames'] = 'Finished games';
$string['highscores'] = 'Highscores';
$string['students'] = 'Students';
$string['questions'] = 'Questions';

$string['stat_active_users'] = 'Active users';
$string['stat_games_started'] = 'Games started';
$string['stat_games_finished'] = 'Games finished';
$string['stat_questions_answered'] = 'Questions answered';
$string['stat_answered_correctly'] = 'Correct answers';
$string['stat_easiest_question'] = 'Easiest question';
$string['stat_hardest_question'] = 'Hardest question';
$string['stat_question_undefined'] = 'Cannot be determined yet.';
$string['stat_edit_question'] = 'Edit question';
$string['stat_student_opponents'] = 'Opponents';
$string['stat_student_open_games'] = 'Open games';
$string['stat_student_finished_games'] = 'Finished games';
$string['stat_student_games_won'] = 'Games won';
$string['stat_student_correct_answers'] = 'Correct answers';
$string['stat_student_correct_percentage'] = 'Correctly answered';

$string['stat_active_users_desc'] = 'That\'s the number of different users who have started at least one game yet.';
$string['stat_games_started_desc'] = 'That\'s how many games have been started yet.';
$string['stat_games_finished_desc'] = 'That\'s how many games have been finished yet.';
$string['stat_questions_answered_desc'] = 'That\'s how many responses have already been submitted in total.';
$string['stat_answered_correctly_desc'] = 'This is the percentage of correct answers given.';
$string['stat_easiest_question_desc1'] = 'This question was answered correctly <b>';
$string['stat_easiest_question_desc2'] = ' times</b>.';
$string['stat_hardest_question_desc1'] = 'This question was answered incorrectly <b>';
$string['stat_hardest_question_desc2'] = ' times</b>.';
$string['stat_student_opponents_desc'] = 'That\'s the number of different users you have already played against.';
$string['stat_student_open_games_desc'] = 'That\'s the number of games you\'re currently playing.';
$string['stat_student_finished_games_desc'] = 'That\'s the number of games you have already finished.';
$string['stat_student_games_won_desc'] = 'That\'s the number of games you have won.';
$string['stat_student_correct_answers_desc'] = 'That\'s the number of questions you have answered correctly.';
$string['stat_student_correct_percentage_desc'] = 'That\'s the percentage of questions you have answered correctly.';

$string['questionid'] = 'ID';
$string['questiontext'] = 'Text';
$string['questiontype'] = 'Type';
$string['questionimage'] = 'Image';
$string['questiontextlength'] = 'Text length';
$string['questionstatus'] = 'Status';
$string['noquestions'] = 'There are no questions in this quiz.';
$string['noimage'] = 'No image';

$string['univiemooduell'] = 'Univie Mooduell';
$string['downloaduniviemoduell'] = 'Dowload your app and start playing now!';
$string['univiemooduellappstore'] = 'Get it on Apple App Store:';
$string['univiemooduellplaystore'] = 'Get it on Google Play Store:';

$string['username'] = 'Name';
$string['gamesplayed'] = 'Games played';
$string['gameswon'] = 'Games won';
$string['gameslost'] = 'Games lost';
$string['rank'] = 'Ranking';
$string['score'] = 'Score';
$string['correctlyanswered'] = 'Correctly answered';
$string['correctlyansweredpercentage'] = '% correctly answered';
$string['questions_played'] = 'Questions answered';
$string['timecreated'] = 'Creation date';
$string['timemodified'] = 'Last change';

$string['downloadhighscores'] = 'Download highscores as csv file';

$string['notenoughquestions'] = 'There are not enough questions in the chosen category. Choose another category or add more questions to the existing one.';
$string['nowarnings'] = 'Your game seems fine, there are currently no warnings';
$string['questiontexttooshort'] = 'ID {$a}: Questiontext is too short';
$string['questiontexttoolong'] = 'ID {$a}: Questiontext is too long';
$string['wrongquestiontype'] = 'ID {$a}: Question has the wrong type';
$string['questionhasnocorrectanswers'] = 'ID {$a}: Question has no correct answers';

$string['addquestionstocategory'] = 'Add questions to category {$a}';

$string['notok'] = 'Not OK';
$string['ok'] = 'OK';

$string['youwin'] = 'You won against {$a}';
$string['youlose'] = 'You lost against {$a}';
$string['draw'] = 'You played draw against {$a}';
$string['yourturn'] = 'It\'s your turn against {$a}';
$string['challenged'] = '{$a} has challenged you';
$string['userhasnonickname'] = 'User has no nickname';

$string['mooduell:play'] = 'Play Mooduell';

// Privacy API.
$string['privacy:metadata:mooduell_games'] = 'Stores the mooduell game progress.';
$string['privacy:metadata:mooduell_games:mooduellid'] = 'Id of the mooduell instance.';
$string['privacy:metadata:mooduell_games:playeraid'] = 'User Id of the player who initiated the game.';
$string['privacy:metadata:mooduell_games:playerbid'] = 'User Id of the player who was challenged to this game.';
$string['privacy:metadata:mooduell_games:playeracorrect'] = 'Number of correctly answered questions by player A.';
$string['privacy:metadata:mooduell_games:playerbcorrect'] = 'Number of correctly answered questions by player B.';
$string['privacy:metadata:mooduell_games:playeraqplayed'] = 'Number of player As played questions.';
$string['privacy:metadata:mooduell_games:playerbqplayed'] = 'Number of player Bs played questions.';
$string['privacy:metadata:mooduell_games:winnerid'] = 'User Id of the games winner.';
$string['privacy:metadata:mooduell_games:status'] = 'Status of the game.';
$string['privacy:metadata:mooduell_games:victorycoefficient'] = 'Victories correlated to the strength of adversary.';
$string['privacy:metadata:mooduell_games:timemodified'] = 'Timestamp of when the instance was last modified.';
$string['privacy:metadata:mooduell_games:timecreated'] = 'Timestamp of when the instance was created.';
$string['privacy:metadata:mooduell_highscores'] = 'Stores the highscores and is updated constantly.';
$string['privacy:metadata:mooduell_highscores:mooduellid'] = 'Id of the mooduell instance.';
$string['privacy:metadata:mooduell_highscores:userid'] = 'The unique user id.';
$string['privacy:metadata:mooduell_highscores:ranking'] = 'The users rank in the highscores table of the MooDuell instance.';
$string['privacy:metadata:mooduell_highscores:gamesplayed'] = 'Number of games played (by the user on this instance).';
$string['privacy:metadata:mooduell_highscores:gameswon'] = 'Number of games won (by the user on this instance).';
$string['privacy:metadata:mooduell_highscores:gameslost'] = 'Number of games lost (by the user on this instance).';
$string['privacy:metadata:mooduell_highscores:gamesstarted'] = 'Number of games started (by the user on this instance).';
$string['privacy:metadata:mooduell_highscores:gamesfinished'] = 'Number of games finished (by the user on this instance).';
$string['privacy:metadata:mooduell_highscores:score'] = 'The users score on the MooDuell instance.';
$string['privacy:metadata:mooduell_highscores:qcorrect'] = 'The number of correctly answered questions (by the user, on the MooDuell instance).';
$string['privacy:metadata:mooduell_highscores:qplayed'] = 'Number of played questions (by the user on this MooDuell instance).';
$string['privacy:metadata:mooduell_highscores:qcpercentage'] = 'Percentage of correctly answered questions (by user on this MooDuell instance).';
$string['privacy:metadata:mooduell_highscores:timecreated'] = 'Timestamp of when the record was created.';
$string['privacy:metadata:mooduell_highscores:timemodified'] = 'Timestamp of when the record was last modified.';
$string['privacy:metadata:mooduell_pushtokens'] = 'Stores all the pushtokens for all the users.';
$string['privacy:metadata:mooduell_pushtokens:userid'] = 'The unique user id.';
$string['privacy:metadata:mooduell_pushtokens:identifier'] = 'The unique device identifier.';
$string['privacy:metadata:mooduell_pushtokens:model'] = 'Information about the device like model and screen size.';
$string['privacy:metadata:mooduell_pushtokens:pushtoken'] = 'The pushtoken.';
$string['privacy:metadata:mooduell_pushtokens:numberofnotifications'] = 'The number of notifications.';

$string['pincode'] = "Your PinCode: ";
$string['qrtitle'] = "QR Login - How to:";
$string['qrdesc'] = "To login on your Smartphone, scan this personal QR-Code with the MooDuell App and verify the process with your unique 4-digit pincode.";

/*completion strings */
$string['completiongamesplayed'] = 'User has to play number of games';
$string['completiongamesplayedlabel'] = 'Played games';
$string['completionrightanswers'] = 'User has to answer number of question correctly';
$string['completionrightanswerslabel'] = 'Number of Questions';
$string['completiongameswon'] = 'User has to win number of games';
$string['completiongameswonlabel'] = 'Won games';
