$(function () {
    var flashOutTime = 7000;

    var $flashList = $('.flash-messages-block .flash-list');

    $(document).on('click', '.close-flash', function (e) {
        e.preventDefault();
        $(this).closest('.flash-message').fadeOut(400, function () {
            $(this).remove();
        });
        return false;
    });

    window.addFlashMessage = function (message, type, time) {
        type = type ? type : 'success';
        var outTime = (time && time > flashOutTime) ? time : flashOutTime;

        var $item = $('<div class="flash-message"></div>').addClass(type);
        var $closer = $('<a class="close-flash right"><i class="icon-delete_in_filter"></i></a>');
        var $text = $('<span/>').addClass('message').text(message);

        $item.append([$closer, $text]);
        $flashList.append($item);

        setTimeout(function () {
            if ($item && $item.length > 0) {
                $item.fadeOut(400, function () {
                    $(this).remove();
                });
            }
        }, outTime);
    };

    if (window['flashStack'] && window['flashStack'].length) {
        for (var i in window['flashStack']) {
            var f = window['flashStack'][i];
            addFlashMessage(f.message, f.type);
        }
    }
});