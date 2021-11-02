

define(['jquery', 'core/ajax', 'core/notification'], function($, ajax) {

    return {
        init: function() {
            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = window.location.search.substring(1);
                 return getAdressParameter(sParam, sPageURL);
            };
            var getAdressParameter = function getAdressParameter(sParam, sPageURL) {
                var params = sPageURL.split('?');
                sPageURL = params.length > 1 ? params[1] : params[0];
                var sURLVariables = sPageURL.split('&'),
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

            /**
             * Load questions function.
             */
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
                    done: function(res) {
                        // $('#continue').attr("href", link);
                        var tablebody = '';

                        res.forEach(item => {
                            var image = ' ';
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
                                '<td><a href="../../question/question.php?id='
                                    + item.questionid
                                    + '&courseid=' + item.courseid
                                    + 'returnto=url&returnurl=%2Fmod%2Fmooduell%2Fview.php'
                                    + '%3Fid%3D' + id + '%23questions">' +
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
                    fail: function() {
                        $('#spinner div').addClass('hidden');
                        $('#questionstable').removeClass('hidden');
                    }
                }]);
            }

            /**
             * Function to load open games.
             */
            function loadOpengames() {
                var id = getUrlParameter('id');
                callLoadOpenGames(id);
            }

            /**
             * Function to load finished games.
             */
            function loadFinishedgames() {
                var id = getUrlParameter('id');
                callLoadFinishedGames(id);
            }

            /**
             * Function to load highscores.
             */
            function loadHighScores() {
                var id = getUrlParameter('id');
                callLoadHighScores(id);
            }

            /**
             * This function is called by the loadOpenGames function.
             *
             * @param {number} id
             * @param {string} pageid
             * @param {string} tsort
             * @param {string} thide
             * @param {string} tshow
             * @param {number} tdir
             * @param {number} treset
             */
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
                        'pageid': pageid,
                        'tsort': tsort,
                        'thide': thide,
                        'tshow': tshow,
                        'tdir': tdir,
                        'treset': treset
                    },
                    done: function(res) {

                        var domNodes = $($.parseHTML(res.content));

                        if (domNodes.length > 2) {
                            replaceDownloadLink(id, domNodes, 'opengames');
                            replaceResetTableLink(id, domNodes, callLoadOpenGames);
                            replacePaginationLinks(id, domNodes, callLoadOpenGames);
                            replaceSortColumnLinks(id, domNodes, callLoadOpenGames);
                        }

                        $('#spinner div').addClass('hidden');
                        $("#opengamestable").html(domNodes);
                        $('#opengamestable').removeClass('hidden');
                    },
                    fail: function() {
                        // Debug: alert('fail');
                        $('#spinner div').addClass('hidden');
                        $('#opengamestable').removeClass('hidden');
                    }
                }]);
            }

            /**
             * This function is called by the loadFinishedGames function.
             * @param {number} id
             * @param {string} pageid
             * @param {string} tsort
             * @param {string} thide
             * @param {string} tshow
             * @param {number} tdir
             * @param {number} treset
             */
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
                        'pageid': pageid,
                        'tsort': tsort,
                        'thide': thide,
                        'tshow': tshow,
                        'tdir': tdir,
                        'treset': treset
                    },
                    done: function(res) {

                        var domNodes = $($.parseHTML(res.content));

                        if (domNodes.length > 2) {
                            replaceDownloadLink(id, domNodes, 'finishedgames');
                            replaceResetTableLink(id, domNodes, callLoadFinishedGames);
                            replacePaginationLinks(id, domNodes, callLoadFinishedGames);
                            replaceSortColumnLinks(id, domNodes, callLoadFinishedGames);
                        }

                        $('#spinner div').addClass('hidden');
                        $("#finishedgamestable").html(domNodes);
                        $('#finishedgamestable').removeClass('hidden');
                    },
                    fail: function() {
                        // Debug: alert('fail');
                        $('#spinner div').addClass('hidden');
                        $('#finishedgamestable').removeClass('hidden');
                    }
                }]);
            }

            /**
             * This function is called by the loadHighScores function.
             * @param {number} id
             * @param {string} pageid
             * @param {string} tsort
             * @param {string} thide
             * @param {string} tshow
             * @param {number} tdir
             * @param {number} treset
             */
            function callLoadHighScores(id, pageid = null, tsort = null, thide = null, tshow = null, tdir = null, treset = null) {
                $('#spinner div').removeClass('hidden');
                $('#highscorestable').addClass('hidden');
                $('#spinner div').removeClass('hidden');

                ajax.call([{
                    methodname: "mod_mooduell_load_highscore_data",
                    args: {
                        'quizid': id,
                        'pageid': pageid,
                        'tsort': tsort,
                        'thide': thide,
                        'tshow': tshow,
                        'tdir': tdir,
                        'treset': treset
                    },
                    done: function(res) {

                        var domNodes = $($.parseHTML(res.content));

                        if (domNodes.length > 2) {
                            replaceDownloadLink(id, domNodes, 'highscores');
                            replaceResetTableLink(id, domNodes, callLoadHighScores);
                            replacePaginationLinks(id, domNodes, callLoadHighScores);
                            replaceSortColumnLinks(id, domNodes, callLoadHighScores);
                        }

                        $('#spinner div').addClass('hidden');
                        $("#highscorestable").html(domNodes);
                        $('#highscorestable').removeClass('hidden');
                    },
                    fail: function() {
                        // Debug: alert('fail');
                        $('#spinner div').addClass('hidden');
                        $('#highscorestable').removeClass('hidden');
                    }
                }]);
            }

            /**
             * To provide for all themes, we get the right link from the data attribute.
             *
             * @param {number} id
             * @param {Array} domNodes
             * @param {Object} functionToCall
             */
            function replacePaginationLinks(id, domNodes, functionToCall) {
                var arrayOfPageItems = domNodes.find(".page-item");
                $.each(arrayOfPageItems, function() {
                    var element = $(this).find('a');
                    var url = element.attr("href");
                    var pageNumber;
                    if (url != undefined) {

                        pageNumber = getAdressParameter('page', url);
                    } else {
                        pageNumber = +element.text();
                        --pageNumber;
                    }
                    element.attr('href', '#');
                    if (pageNumber) {
                        $(this).children('a').click(function() {
                            functionToCall(id, pageNumber);
                        });
                    }
                });
            }

            /**
             * Function to replace the sort column links.
             *
             * @param {string} id
             * @param {Array} domNodes
             * @param {Object} functionToCall
             */
            function replaceSortColumnLinks(id, domNodes, functionToCall) {
                var arrayOfItems = domNodes.find("th.header a");
                $.each(arrayOfItems, function() {
                    // First we disable all the links
                    // $(this).removeAttr('href');
                    var sortid = $(this).data('sortby');
                    var sortorder = $(this).data('sortorder');
                    var thide = $(this).data('action') == 'hide' ? $(this).data('column') : null;
                    var tshow = $(this).data('action') == 'show' ? $(this).data('column') : null;

                    // Make sure we only return int.
                    sortorder = parseInt(sortorder);

                    $(this).attr('href', '#');
                    $(this).click(function() {
                        functionToCall(id, null, sortid, thide, tshow, sortorder);
                    });
                });
            }

            /**
             * Function to replace the reset table link.
             *
             * @param {string} id
             * @param {Array} domNodes
             * @param {Object} functionToCall
             */
            function replaceResetTableLink(id, domNodes, functionToCall) {
                var arrayOfItems = domNodes.find("div.resettable");

                // Strangely it wasn't possible to get the first div by class, we have to run all the nodes
                // So we create a fallback
                if (arrayOfItems.length == 0) {
                    arrayOfItems = domNodes;
                }

                $.each(domNodes, function() {
                    var classofelement = $(this).attr('class');
                    if (classofelement.indexOf('resettable') >= 0) {
                        $(this).children('a').attr('href', '#');
                        $(this).children('a').click(function() {
                            functionToCall(id, null, null, null, null, null, 1);
                        });
                    }
                });
            }

            /**
             * Function to replace the download link.
             *
             * @param {string} id
             * @param {Array} domNodes
             * @param {string} action
             */
            function replaceDownloadLink(id, domNodes, action) {
                var arrayOfItems = domNodes.find("form");

                // Strangely it wasn't possible to get the first div by class, we have to run all the nodes
                // So we create a fallback
                if (arrayOfItems.length == 0) {
                    arrayOfItems = domNodes;
                }
                $.each(arrayOfItems, function() {
                    if ($(this).prop("tagName") == 'FORM') {
                        var quizid = getUrlParameter('id');
                        $(this).append('<input type="hidden" name="quizid" value="' + quizid + '">');
                        $(this).append('<input type="hidden" name="action" value="' + action + '">');
                    }
                });
            }

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {

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
