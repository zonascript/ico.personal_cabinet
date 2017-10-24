var resizeButtonsBlock = function () {
    $('.buttons-block').css($('#sidebar-td').css('display') != 'none' ? {
        width: $(document).width() - 250,
        left: 250
    } : {
        width: '100%',
        left: 0
    });
};

$(function () {
    $(document).foundation();

    $('.ui.dropdown').dropdown();
    $('.ui.checkbox').checkbox();
    $('.menu .item').tab();
    $('.show-hide-sidebar, .ui.popup-item').popup();
    $('.breadcrumb-nested').popup({
        inline: true,
        hoverable: true,
        position: 'bottom left'
    });

    $('textarea').autosize();

    resizeButtonsBlock();
    $(window).on('resize', resizeButtonsBlock);

    //$(document).on('click', "[data-toggle]", function () {
    //    $(this).next().toggle();
    //});

    var searchTimer;
    $(document).on('keyup', '#search-input', function (e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    search: e.target.value
                },
                success: function (data) {
                    $('#main-form').replaceWith(data);
                    var $searchInput = $('#search-input');
                    $searchInput.focus().val($searchInput.val());
                }
            })
        }.bind(this), 550);
    });

    var selector = '',
        types = ['jpg', 'jpeg', 'png', 'gif'];
    for (var i = 0; i < types.length; i++) {
        selector += "a[href$='." + types[i].toLowerCase() + "']:not(.ignore-fancy),a[href$='." + types[i].toUpperCase() + "']:not(.ignore-fancy)";
        if (i + 1 != types.length) {
            selector += ",";
        }
    }

    var $linkWithImage = $(selector);
    $linkWithImage
        .attr('rel', 'fancybox')
        .fancybox({
            openEffect: 'elastic',
            closeEffect: 'elastic',
            helpers: {
                title: {
                    type: 'inside'
                },
                buttons: {}
            }
        });

    $.mtooltip('[rel~=tooltip]');

    if (!$.cookie('sidebar-show')) {
        resizeButtonsBlock();
    }
});

$(document).on('click', '.mmodal', function (e) {
    e.preventDefault();
    var $this = $(this);
    $this.mmodal({
        width: $this.data('width')
    });
    return false;
});

$(document).on('click', '#hide-sidebar', function (e) {
    e.preventDefault();
    var $sidebar = $('#sidebar-td');
    $sidebar.toggle();

    var isShow = $sidebar.css('display') != 'none';
    $.cookie('sidebar-show', isShow);
    var $content = $('#content-td');
    $content.css('margin-left', isShow ? 250 : 0);
    resizeButtonsBlock();
    return false;
});

$(document).on('click', '.flash-list .remove', function (e) {
    e.preventDefault();
    var $list = $(e.target).closest('.flash-list');
    if ($list.children().length == 1) {
        $list.hide();
    } else {
        $(this).closest('li').hide();
    }
    return false;
});

function popupWindow(url, title, w, h) {
    var left = (screen.width / 2) - (w / 2),
        top = (screen.height / 2) - (h / 2);
    return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
}

$(document).on('click', '.window-open', function (e) {
    e.preventDefault();
    var $this = $(this);
    popupWindow($this.attr('href'), $this.attr('title'), ($this.data('width') || 650), ($this.data('height') || 650)).print();
    return false;
});

$(document).on('click', '[data-confirm]', function (e) {
    return confirm($(this).data('confirm'));
});