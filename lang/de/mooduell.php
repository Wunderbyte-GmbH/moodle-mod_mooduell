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

$string['messageprovider:status'] = 'Spiel Status Änderung';
$string['mooduell:managemooduellsettings'] = 'Bearbeite die Einstellungen eines MooDuell Spiels';
$string['mooduell:viewstatistics'] = 'Siehe Spiel Statistik';
$string['mooduell:viewstudentnames'] = 'Zeige die ganzen Namen der Studierenden';
$string['mooduell:editgames'] = 'Bearbeite Spiele';
$string['pluginname'] = 'MooDuell';
$string['modulenameplural'] = 'MooDuells';
$string['mooduellname'] = 'Name des MooDuell Spiels';
$string['mooduellname_help'] = 'Wähle einen Namen für Dein Spiel';
$string['mooduellsettings'] = 'MooDuell Einstellungen';
$string['mooduellfieldset'] = 'MooDuell Einstellungen';
$string['usefullnames'] = 'Verwende ganze Namen';
$string['usefullnames_help'] = 'Die richtigen Namen der UserInnen werden verwendet. Wenn nicht, müssen die UserInnen in ihrem Profil ein MooDuell Alias definieren.';
$string['modulename_help'] = 'Das MooDuell Modul hilft dir eine Multiplayer Quiz Aktivität zu erstellen, die dann das Spielen mit der MooDuell App erlaubt.';
$string['modulename'] = 'MooDuell';
$string['addInstance'] = 'Ein neues Spiel hinzufügen';
$string['mooduell:addinstance'] = 'Ein neues Spiel hinzufügen';

$string['enablepush'] = 'Push Nachrichten aktivieren';
$string['pushtoken'] = 'Push API Access Key';
$string['countdown'] = 'Countdown';
$string['countdown_help'] = 'Wie lange sollen die SpielerInnen Zeit haben, um die Fragen zu beantworten?';
$string['nocountdown'] = 'Kein Countdown';
$string['xseconds'] = '{$a} Sekunden';

$string['viewgame'] = 'Zeige Spiel';
$string['deletegame'] = 'Lösche Spiel';

$string['showcontinuebutton'] = 'Zeige "Weiter"-Button';
$string['showcontinuebutton_help'] = 'Auch wenn es nur eine richtige Antwort gibt, muss diese mit einem Klick auf einen "Weiter"-Button bestätigt werden.';
$string['showcorrectanswer'] = 'Zeige die korrekten Antworten';
$string['showcorrectanswer_help'] = 'Nach einer falschen Antwort werden die richtigen Antworten angezeigt.';
$string['waitfornextquestion'] = 'Zeit um die korrekten Antworten zu sehen.';
$string['waitfornextquestion_help'] = 'Wie lange sollen die richtigen Antworten nach der Beantwortung der Frage sichtbar bleiben.';
$string['clicktomoveon'] = 'Warte bis zum nächsten Klick';

$string['pluginadministration'] = 'Plugin Administration';
$string['questionscategory'] = 'Kategorie für Zufallsfragen';
$string['categoryweight'] = 'Gewichtung der Kategorie (0 bis 100)';
$string['categoriesexplanation'] = 'Wenn du mehr als eine Kategorie auswählst, gib bitte auch eine Gewichtung an. Gewichtungen müssen NICHT zusammen 100 ergeben.';
$string['addanothercategory'] = 'Eine weitere Kategorie hinzufügen';
$string['questionscategorygroup'] = 'Kategorien für Zufallsfragen';
$string['nocategories'] = 'Es gibt noch keine Kategorien';
$string['important'] = 'Wichtig!';
$string['nomessage'] = 'Es gibt keine Nachrichten.';
$string['foundthesegames'] = 'Diese Spiele wurden gefunden:';
$string['noopengames'] = 'Es gibt noch keine offenen Spiele.';
$string['nostatistics'] = 'Es tut uns leid, die Statistik konnte nicht geladen werden.';

$string['listofquestions'] = 'Alle Fragen im Quiz:';
$string['playera'] = 'Spieler/in A';
$string['playerb'] = 'Spieler/in B';
$string['playeraresults'] = 'Resultat A';
$string['playerbresults'] = 'Resultat B';
$string['action'] = 'Aktion';
$string['noquestions'] = 'Keine Fragen';
$string['question'] = 'Frage';
$string['warnings'] = 'Warnungen';
$string['status'] = 'Status';
$string['category'] = 'Kategorie';

$string['lastplayed'] = 'Zuletzt gespielt';

$string['statistics'] = 'Statistik';
$string['opengames'] = 'Offene Spiele';
$string['finishedgames'] = 'Beendete Spiele';
$string['highscores'] = 'Highscores';
$string['students'] = 'Studierende';
$string['questions'] = 'Fragen';

$string['stat_active_users'] = 'Aktive User*innen';
$string['stat_games_started'] = 'Spiele begonnen';
$string['stat_games_finished'] = 'Spiele beendet';
$string['stat_questions_answered'] = 'Fragen beantwortet';
$string['stat_answered_correctly'] = 'Fragen richtig';
$string['stat_easiest_question'] = 'Einfachste Frage';
$string['stat_hardest_question'] = 'Schwierigste Frage';
$string['stat_question_undefined'] = 'Kann noch nicht festgestellt werden.';
$string['stat_edit_question'] = 'Frage bearbeiten';
$string['stat_student_opponents'] = 'Gegner*innen';
$string['stat_student_open_games'] = 'Offene Spiele';
$string['stat_student_finished_games'] = 'Beendete Spiele';
$string['stat_student_games_won'] = 'Gewonnene Spiele';
$string['stat_student_correct_answers'] = 'Fragen richtig beantwortet';
$string['stat_student_correct_percentage'] = 'der Fragen richtig beantwortet';

$string['stat_active_users_desc'] = 'So viele unterschiedliche Benutzer*innen haben bereits mindestens ein Spiel gestartet.';
$string['stat_games_started_desc'] = 'So viele Spiele wurden bereits begonnen.';
$string['stat_games_finished_desc'] = 'So viele Spiele wurden bereits beendet.';
$string['stat_questions_answered_desc'] = 'So viele Antworten wurden bereits insgesamt abgegeben.';
$string['stat_answered_correctly_desc'] = 'So viel Prozent der abgegebenen Antworten waren richtig.';
$string['stat_easiest_question_desc1'] = 'Diese Frage wurde <b>';
$string['stat_easiest_question_desc2'] = ' Mal</b> richtig beantwortet.';
$string['stat_hardest_question_desc1'] = 'Diese Frage wurde <b>';
$string['stat_hardest_question_desc2'] = ' Mal</b> falsch beantwortet.';
$string['stat_student_opponents_desc'] = 'Gegen so viele unterschiedliche Spieler*innen hast du bereits gespielt.';
$string['stat_student_open_games_desc'] = 'Anzahl der Spiele, die du gerade spielst.';
$string['stat_student_finished_games_desc'] = 'Anzahl der Spiele, die du bereits beendet hast.';
$string['stat_student_games_won_desc'] = 'So viele Spiele hast du bereits gewonnen.';
$string['stat_student_correct_answers_desc'] = 'So viele von deinen Antworten waren richtig.';
$string['stat_student_correct_percentage_desc'] = 'Der Prozentsatz der von dir richtig beantworteten Fragen.';

$string['questionid'] = 'ID';
$string['questiontext'] = 'Text';
$string['questiontype'] = 'Typ';
$string['questionimage'] = 'Bild';
$string['questiontextlength'] = 'Textlänge';
$string['questionstatus'] = 'Status';
$string['noquestions'] = 'Es gibt noch keine Fragen in diesem Quiz.';
$string['noimage'] = 'Kein Bild';

$string['univiemooduell'] = 'Univie Mooduell';
$string['downloaduniviemoduell'] = 'Lade Deine App auf dein Handy und beginne sofort mit deinem Spiel!';
$string['univiemooduellappstore'] = 'Hier geht es zum Apple App Store:';
$string['univiemooduellplaystore'] = 'Hier geht es zum Google Play Store:';

$string['username'] = 'Name';
$string['gamesplayed'] = 'Gespielte Spiele';
$string['gameswon'] = 'Gewonnene Spiele';
$string['gameslost'] = 'Verlorene Spiele';
$string['rank'] = 'Platz';
$string['score'] = 'Punkte';
$string['correctlyanswered'] = 'Fragen richtig';
$string['correctlyansweredpercentage'] = '% korrekt';
$string['questions_played'] = 'Fragen beantwortet';
$string['timecreated'] = 'Erstellt';
$string['timemodified'] = 'Zuletzt geändert';

$string['downloadhighscores'] = 'Lade Highscores als csv Datei';

$string['notenoughquestions'] = 'Es gibt nicht genug Fragen in diesem Spiel. Füge der gewählten Fragenkategorie mehr Fragen hinzu oder wechsle die Kategorie.';
$string['nowarnings'] = 'Dein Spiel sieht gut aus, es gibt keine Warnungen.';
$string['questiontexttooshort'] = 'ID {$a}: Text der Frage ist zu kurz';
$string['questiontexttoolong'] = 'ID: {$a}: Text der Frage ist zu lang';
$string['wrongquestiontype'] = 'ID {$a}: Frage hat einen falschen Typ';
$string['questionhasnocorrectanswers'] = 'ID {$a}: Frage hat keine richtigen Antworten';

$string['addquestionstocategory'] = 'Fragen zur Kategorie {$a} hinzufügen';

$string['notok'] = 'Nicht OK';
$string['ok'] = 'OK';


$string['youwin'] = 'Du hast gegen {$a} gewonnen';
$string['youlose'] = 'Du hast gegen {$a} verloren';
$string['draw'] = 'Du hast gegen {$a} unentschieden gespielt';
$string['yourturn'] = 'Du bist dran gegen {$a}';
$string['challenged'] = '{$a} hat dich herausgefordert';
$string['userhasnonickname'] = 'SpielerIn ohne Alias';

$string['mooduell:play'] = 'MooDuell spielen';

$string['pincode'] = "Dein PinCode: ";
$string['qrtitle'] = "Anleitung QR-Login:";
$string['qrdesc'] = "Um dich auf deinem Smartphone anzumelden, scanne einfach diesen persönlichen QRCode in der App und bestätige den Login mit dem einmaligen Pincode.";


/*completion strings */
$string['completiongamesplayed'] = 'Teilnehmer:in muss Anzahl Spiele spielen';
$string['completiongamesplayedlabel'] = 'Gespielte Runden';
$string['completionrightanswers'] = 'Teilnehmer:in muss n Fragen richtig beantworten';
$string['completionrightanswerslabel'] = 'Richtige Antworten';
$string['completiongameswon'] = 'Teilnehmer:in muss eine bestimmte Anzahl Spiele gewinnen';
$string['completiongameswonlabel'] = 'Gewonnene Spiele';