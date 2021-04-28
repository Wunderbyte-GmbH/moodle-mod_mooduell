

define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax) {

    return {
        init: function (role='undefined') {
            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = window.location.search.substring(1),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;
                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');
                    if (sParameterName[0] === sParam) {
                        return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                    }
                }
                return false;
            };

            function loadHighScores(role='undefined') {
                var id = getUrlParameter('id');
                var role = role;

                $('#spinner div').removeClass('hidden');
                $('#highscorestable').addClass('hidden');

                var downloadHighscores = '<div style="margin-top: 20px;"><a class="btn btn-primary" href="view.php?id=' +
                    id + '&action=downloadhighscores">Download Highscores</a></div>';

                $('#spinner div').removeClass('hidden');
                ajax.call([{
                    methodname: "mod_mooduell_load_highscore_data",
                    args: {
                        'quizid': id
                    },
                    done: function (res) {
                        //$('#continue').attr("href", link);
                        var tablebody = '';

                        res.forEach(item => {
                            tablebody += '<tr>' +
                                '<td class="text-right">' + item.rank + '</td>' +
                                '<td class="text-left" >' + item.username + '</td>' +
                                '<td class="text-right">' + item.gamesplayed + '</td>' +
                                '<td class="text-right">' + item.gameswon + '</td>' +
                                '<td class="text-right">' + item.gameslost + '</td>' +
                                '<td class="text-right">' + item.score + '</td>' +
                                '<td class="text-right">' + item.correct + '</td>' +
                                '<td class="text-right">' + item.correctpercentage + '%</td>' +
                                '</tr>';
                        });
                        $('#highscorestable').html(tablebody);

                        // only show the Download button in teacher view
                        if (role === 'teacher'){
                            $('#hsdownloadbutton').html(downloadHighscores);
                        }

                        $('#spinner div').addClass('hidden');
                        $('#highscorestable').removeClass('hidden');
                    },
                    fail: function () {
                        $('#spinner div').addClass('hidden');
                        $('#highscorestable').removeClass('hidden');
                    }
                }]);
            }

            function loadQuestions() {
                var id = getUrlParameter('id');

                $('#spinner div').removeClass('hidden');
                $('#questionstable').addClass('hidden');

                $('#spinner div').removeClass('hidden');
                ajax.call([{
                    methodname: "mod_mooduell_load_questions_data",
                    args: {
                        'quizid': id
                    },
                    done: function (res) {
                        //$('#continue').attr("href", link);
                        var tablebody = '';

                        res.forEach(item => {
                            var image = 'no image';
                            if (item.imageurl && item.imageurl.length > 0) {
                                image = '<a href="' + item.imageurl +
                                    '"><img src="' + item.imageurl + '" alt="' +
                                    item.imagetext + '" width="100px"></img></a>';
                            }
                            var strlength = item.length;
                            if (!strlength || strlength.length == 0) {
                                strlength = item.questiontext.length;
                            }

                            var warnings = '<ul>';
                            item.warnings.forEach(item => {
                                warnings += '<li><span style="color:red;">' + item.message + '</span></li>';
                            });
                            warnings += '</ul>';

                            var answers = '<ul>';
                            item.answers.forEach(item => {

                                var style = item.fraction > 0 ? ' style="color: green;"' : '';

                                answers += '<li><span' + style + '>' + item.answertext + '</span></li>';
                            });
                            answers += '</ul>';

                            tablebody += '<tr>' +
                                '<td><a href="../../question/question.php?returnurl=/question/edit.php?courseid='+
                                item.courseid + '&courseid='+ item.courseid + '&id='+ item.questionid + '">' +
                               item.questionid + '</a></td>' +
                                '<td>' + image + '</td>' +
                                '<td class="text-left">' + item.questiontext + answers + '</td>' +
                                '<td class="text-right">' + item.questiontype + '</td>' +
                                '<td class="text-right">' + strlength + '</td>' +
                                '<td class="text-right">' + item.category + '</td>' +
                                '<td class="text-right">' + warnings + '</td>' +
                                '<td class="text-right">' + item.status + '</td>' +
                                '</tr>';
                        });
                        $('#questionstable tbody').html(tablebody);
                        $('#spinner div').addClass('hidden');
                        $('#questionstable').removeClass('hidden');
                    },
                    fail: function () {
                        $('#spinner div').addClass('hidden');
                        $('#questionstable').removeClass('hidden');
                    }
                }]);
            }

            function loadOpengames(role='undefined') {
                var id = getUrlParameter('id');
                var role = role;

                $('#spinner div').removeClass('hidden');
                $('#opengamestable').addClass('hidden');

                $('#spinner div').removeClass('hidden');
                ajax.call([{
                    methodname: "mod_mooduell_load_opengames_data",
                    args: {
                        'quizid': id
                    },
                    done: function (res) {
                        //$('#continue').attr("href", link);
                        var tablebody = '';

                        res.forEach(item => {
                            tablebody += '<tr>' +
                                '<td>' + item.playera + '</td>' +
                                '<td>' + item.playeraresults + '</td>' +
                                '<td>' + item.playerb + '</td>' +
                                '<td>' + item.playerbresults + '</td>';
                            if (role === 'teacher'){
                                tablebody +=
                                    '<td class="text-right"><a href="view.php?action=viewquestions&id=' +
                                    id + '&gameid=' + item.gameid +
                                    '" data-id="' + item.gameid +
                                    '" data-role="viewfield">view</a>\n' +
                                    '                <a href="view.php?action=delete&id=' + id + '&gameid=' + item.gameid +
                                    '" data-id="' + item.gameid + '" data-role="deletefield">delete</a></td>';
                            }
                            tablebody += '</tr>';
                        });
                        $('#opengamestable tbody').html(tablebody);
                        $('#spinner div').addClass('hidden');
                        $('#opengamestable').removeClass('hidden');
                    },
                    fail: function () {
                        $('#spinner div').addClass('hidden');
                        $('#opengamestable').removeClass('hidden');
                    }
                }]);
            }

            function loadFinishedgames(role='undefined') {
                var id = getUrlParameter('id');
                var role = role;

                $('#spinner div').removeClass('hidden');
                $('#finishedgamestable').addClass('hidden');

                $('#spinner div').removeClass('hidden');
                ajax.call([{
                    methodname: "mod_mooduell_load_finishedgames_data",
                    args: {
                        'quizid': id
                    },
                    done: function (res) {
                        //$('#continue').attr("href", link);
                        var tablebody = '';

                        res.forEach(item => {
                            tablebody += '<tr>' +
                                '<td>' + item.playera + '</td>' +
                                '<td>' + item.playeraresults + '</td>' +
                                '<td>' + item.playerb + '</td>' +
                                '<td>' + item.playerbresults + '</td>';
                            if (role === 'teacher') {
                                tablebody +=
                                    '<td class="text-right"><a href="view.php?action=viewquestions&id=' +
                                    id + '&gameid=' + item.gameid +
                                    '" data-id="' + item.gameid + '" data-role="viewfield">view</a>\n' +
                                    '                <a href="view.php?action=delete&id=' + id + '&gameid=' + item.gameid +
                                    '" data-id="' + item.gameid + '" data-role="deletefield">delete</a></td>';
                            }
                            tablebody += '</tr>';
                        });
                        $('#finishedgamestable tbody').html(tablebody);
                        $('#spinner div').addClass('hidden');
                        $('#finishedgamestable').removeClass('hidden');
                    },
                    fail: function () {
                        $('#spinner div').addClass('hidden');
                        $('#finishedgamestable').removeClass('hidden');
                    }
                }]);
            }

            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {

                var tabname = e.target.toString();
                tabname = tabname.split('#');
                if (tabname.length == 0) {
                    return;
                }
                switch (tabname[1]) {
                    case 'highscores':
                        loadHighScores(role);
                        break;
                    case 'questions':
                        loadQuestions();
                        break;
                    case 'opengames':
                        loadOpengames(role);
                        break;
                    case 'finishedgames':
                        loadFinishedgames(role);
                        break;
                }
            });

            // If we have a gameid param, we can unhide the table
            var gameid = getUrlParameter('gameid');
            if (gameid) {
                $('#spinner div').addClass('hidden');
                $('#questionstable').removeClass('hidden');
            }

        },
    };
});
