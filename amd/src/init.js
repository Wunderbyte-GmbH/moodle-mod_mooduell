

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
                    },
                    fail: notification.exception
                }]);
            }

            function loadQuestions() {
                var id = getUrlParameter('id');
                ajax.call([{
                    methodname: "mod_mooduell_load_questions_data",
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
                        $('#questionstable').html(tablebody);
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
                        alert('open');
                        break;
                }
            });
        },
    };
});