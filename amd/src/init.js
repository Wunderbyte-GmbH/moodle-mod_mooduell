

define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax) {

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

            function loadOpengames() {
                var id = getUrlParameter('id');
                callLoadOpenGames(id);
            }

            function loadFinishedgames() {
                var id = getUrlParameter('id');
                callLoadFinishedGames(id);
            }

            function loadHighScores() {
                var id = getUrlParameter('id');
                callLoadHighScores(id);
            }

            function callLoadOpenGames(id,
                                       pageid = null,
                                       tsort = null,
                                       thide = null,
                                       tshow = null,
                                       tdir = null,
                                       treset = null) {
                $('#spinner div').removeClass('hidden');
                $('#opengamestable').addClass('hidden');
                $('#spinner div').removeClass('hidden');

                ajax.call([{
                    methodname: "mod_mooduell_load_opengames_data",
                    args: {
                        'quizid': id,
                        'pageid' : pageid,
                        'tsort' : tsort,
                        'thide' : thide,
                        'tshow' : tshow,
                        'tdir' : tdir,
                        'treset' : treset
                    },
                    done: function (res) {

                        var dom_nodes = $($.parseHTML(res.content));

                        if (dom_nodes.length != 1) {
                            replaceDownloadLink(id, dom_nodes, 'opengames');
                            replaceResetTableLink(id, dom_nodes, callLoadOpenGames);
                            replacePaginationLinks(id, dom_nodes, callLoadOpenGames);
                            replaceSortColumLinks(id, dom_nodes, callLoadOpenGames);
                        }

                        $('#spinner div').addClass('hidden');
                        $("#opengamestable").html(dom_nodes);
                        $('#opengamestable').removeClass('hidden');
                    },
                    fail: function () {
                        alert('fail');
                        $('#spinner div').addClass('hidden');
                        $('#opengamestable').removeClass('hidden');
                    }
                }]);
            }

            function callLoadFinishedGames(id,
                                           pageid = null,
                                           tsort = null,
                                           thide = null,
                                           tshow = null,
                                           tdir = null,
                                           treset = null) {
                $('#spinner div').removeClass('hidden');
                $('#finishedgamestable').addClass('hidden');
                $('#spinner div').removeClass('hidden');

                ajax.call([{
                    methodname: "mod_mooduell_load_finishedgames_data",
                    args: {
                        'quizid': id,
                        'pageid' : pageid,
                        'tsort' : tsort,
                        'thide' : thide,
                        'tshow' : tshow,
                        'tdir' : tdir,
                        'treset' : treset
                    },
                    done: function (res) {

                        var dom_nodes = $($.parseHTML(res.content));

                        if (dom_nodes.length != 1) {
                            replaceDownloadLink(id, dom_nodes, 'finishedgames');
                            replaceResetTableLink(id, dom_nodes, callLoadFinishedGames);
                            replacePaginationLinks(id, dom_nodes, callLoadFinishedGames);
                            replaceSortColumLinks(id, dom_nodes, callLoadFinishedGames);
                        }

                        $('#spinner div').addClass('hidden');
                        $("#finishedgamestable").html(dom_nodes);
                        $('#finishedgamestable').removeClass('hidden');
                    },
                    fail: function () {
                        alert('fail');
                        $('#spinner div').addClass('hidden');
                        $('#finishedgamestable').removeClass('hidden');
                    }
                }]);
            }

            function callLoadHighScores(id, pageid = null, tsort = null, thide = null, tshow = null, tdir = null, treset = null) {
                $('#spinner div').removeClass('hidden');
                $('#highscorestable').addClass('hidden');
                $('#spinner div').removeClass('hidden');

                ajax.call([{
                    methodname: "mod_mooduell_load_highscore_data",
                    args: {
                        'quizid': id,
                        'pageid' : pageid,
                        'tsort' : tsort,
                        'thide' : thide,
                        'tshow' : tshow,
                        'tdir' : tdir,
                        'treset' : treset
                    },
                    done: function (res) {

                        var dom_nodes = $($.parseHTML(res.content));

                        if (dom_nodes.length != 1) {
                            replaceDownloadLink(id, dom_nodes, 'highscores');
                            replaceResetTableLink(id, dom_nodes, callLoadHighScores);
                            replacePaginationLinks(id, dom_nodes, callLoadHighScores);
                            replaceSortColumLinks(id, dom_nodes, callLoadHighScores);
                        }

                        $('#spinner div').addClass('hidden');
                        $("#highscorestable").html(dom_nodes);
                        $('#highscorestable').removeClass('hidden');
                    },
                    fail: function () {
                        alert('fail');
                        $('#spinner div').addClass('hidden');
                        $('#highscorestable').removeClass('hidden');
                    }
                }]);
            }

            function replacePaginationLinks(id, dom_nodes, functionToCall) {
                var arrayOfPageItems = dom_nodes.find(".page-item");
                $.each(arrayOfPageItems, function() {
                    // First we disable all the links
                    // $(this).removeAttr('href');
                    var pageNumber = $(this).data('page-number');

                    $(this).children('a').attr('href', '#');
                    $(this).children('a').click(function () {
                        functionToCall(id, pageNumber-1);
                    });
                });
            }

            function replaceSortColumLinks(id, dom_nodes, functionToCall) {
                var arrayOfItems = dom_nodes.find("th.header a");
                $.each(arrayOfItems, function() {
                    // First we disable all the links
                    // $(this).removeAttr('href');
                    var sortid = $(this).data('sortby');
                    var sortorder = $(this).data('sortorder');
                    var thide = $(this).data('action') == 'hide' ? $(this).data('column') : null;
                    var tshow = $(this).data('action') == 'show' ? $(this).data('column') : null;

                    // make sure we only return int
                    sortorder = parseInt(sortorder);

                    $(this).attr('href', '#');
                    $(this).click(function () {
                        functionToCall(id, null, sortid, thide, tshow, sortorder);
                    });
                });
            }

            function replaceResetTableLink(id, dom_nodes, functionToCall) {
                var arrayOfItems = dom_nodes.find("div.resettable");

                // Strangely it wasn't possible to get the first div by class, we have to run all the nodes
                // So we create a fallback
                if (arrayOfItems.length == 0) {
                    arrayOfItems = dom_nodes;
                }

                $.each(dom_nodes, function() {
                    var classofelement = $(this).attr('class');
                    if (classofelement.indexOf('resettable') >= 0) {
                        $(this).children('a').attr('href', '#');
                        $(this).children('a').click(function () {
                            functionToCall(id, null, null, null, null, null, 1);
                        });
                    }
                });
            }

            function replaceDownloadLink(id, dom_nodes, action) {
                var arrayOfItems = dom_nodes.find("form");

                // Strangely it wasn't possible to get the first div by class, we have to run all the nodes
                // So we create a fallback
                if (arrayOfItems.length == 0) {
                    arrayOfItems = dom_nodes;
                }

                $.each(arrayOfItems, function() {
                    if ($(this).prop("tagName") == 'FORM') {
                        var url = $(this).attr('action');
                        var quizid = getUrlParameter('id');
                        $(this).append('<input type="hidden" name="quizid" value="' + quizid + '">');
                        $(this).append('<input type="hidden" name="action" value="' + action + '">');
                    }
                });
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

            // If we have a gameid param, we can unhide the table
            var gameid = getUrlParameter('gameid');
            if (gameid) {
                $('#spinner div').addClass('hidden');
                $('#questionstable').removeClass('hidden');
            }
        },
    };
});
