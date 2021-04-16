

define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification) {

    return {
        init: function () {
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

            function loadHighScores() {
                var id = getUrlParameter('id');

                $('#hsspinner div').removeClass('hidden');
                $('#highscorestable').addClass('hidden');

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
                                '<td>' + item.username + '</td>' +
                                '<td class="text-right">' + item.gamesplayed + '</td>' +
                                '<td class="text-right">' + item.gameswon + '</td>' +
                                '<td class="text-right">' + item.gameslost + '</td>' +
                                '<td class="text-right">' + item.score + '</td>' +
                                '<td class="text-right">' + item.correct + '</td>' +
                                '<td class="text-right">' + item.correctpercentage + '%</td>' +
                                '</tr>';
                        });
                        $('#highscorestable').html(tablebody);

                        $('#hsspinner div').addClass('hidden');
                        $('#highscorestable').removeClass('hidden');
                    },
                    fail: notification.exception
                }]);
            }

            function loadQuestions() {
                var id = getUrlParameter('id');

                $('#qspinner div').removeClass('hidden');
                $('#questionstable').addClass('hidden');


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

                            tablebody += '<tr>' +
                                '<td><a href="../../question/question.php?returnurl=/question/edit.php?courseid='+
                                item.courseid + '&courseid='+ item.courseid + '&id='+ item.questionid + '">' +
                               item.questionid + '</a></td>' +
                                '<td>' + image + '</td>' +
                                '<td class="text-left">' + item.questiontext + '</td>' +
                                '<td class="text-right">' + item.questiontype + '</td>' +
                                '<td class="text-right">' + strlength + '</td>' +
                                '<td class="text-right">' + item.category + '</td>' +
                                '<td class="text-right">' + warnings + '</td>' +
                                '<td class="text-right">' + item.status + '</td>' +
                                '</tr>';
                        });
                        $('#questionstable tbody').html(tablebody);
                        $('#qspinner div').addClass('hidden');
                        $('#questionstable').removeClass('hidden');
                    },
                    fail: notification.exception
                }]);
            }

            function loadOpengames() {
                var id = getUrlParameter('id');

                $('#ogspinner div').removeClass('hidden');
                $('#opengamestable').addClass('hidden');


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
                                '<td>' + item.playerbresults + '</td>' +
                                '</tr>';
                        });
                        $('#opengamestable tbody').html(tablebody);
                        $('#ogspinner div').addClass('hidden');
                        $('#opengamestable').removeClass('hidden');
                    },
                    fail: notification.exception
                }]);
            }

            function loadFinishedgames() {
                var id = getUrlParameter('id');

                $('#fgspinner div').removeClass('hidden');
                $('#finishedgamestable').addClass('hidden');


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
                                '<td>' + item.playerbresults + '</td>' +
                                '</tr>';
                        });
                        $('#finishedgamestable tbody').html(tablebody);
                        $('#fgspinner div').addClass('hidden');
                        $('#finishedgamestable').removeClass('hidden');
                    },
                    fail: notification.exception
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
                        loadHighScores();
                        break;
                    case 'questions':
                        loadQuestions();
                        break;
                    case 'opengames':
                        loadOpengames();
                        break;
                    case 'finishedgames':
                        loadFinishedgames();
                        break;
                }
            });
        },
    };
});