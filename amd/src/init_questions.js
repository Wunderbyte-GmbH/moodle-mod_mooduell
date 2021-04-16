

define(['jquery'], function ($) {

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

            // If we have a gameid param, we can unhide the table
            var gameid = getUrlParameter('gameid');
            if (gameid) {
                $('#spinner div').addClass('hidden');
                $('#questionstable').removeClass('hidden');
            }

        },
    };
});