$(function() {
    $('table').checkboxes('range', true);

    $("#table-main.sortingColumn").find("tbody").sortable({
        axis: 'y',
        placeholder: "highlight",
        helper: function (e, ui) {
            ui.children().each(function () {
                var $this = $(this);
                $this.width($this.width());
            });
            return ui;
        },
        update: function (event, ui) {
            var $to = $(ui.item),
                $prev = $to.prev(),
                $next = $to.next();

            var data = $(this).sortable('toArray', {
                attribute: 'data-pk'
            });

            $.ajax({
                data: {
                    models: data,
                    pk: $to.data('pk'),
                    insertAfter: $prev.data('pk'),
                    insertBefore: $next.data('pk'),
                    action: 'sorting'
                },
                type: 'POST',
                url: '',
                success: function (data) {
                    $('#main-form').replaceWith(data);
                }
            });
        }
    }).disableSelection();
});

$(document).on('click', function(e) {
    if ($(e.target).closest('.search').length == 0) {
        $('.toolbar').removeClass('search');
    }
});

$(document).on('click', 'table thead th.check.all [type="checkbox"]', function (e) {
    var $this = $(this);
    $this.prop('checked', !$this.prop('checked')).closest('table').checkboxes('toggle');
});

$(document).on('click', '.toolbar .search', function (e) {
    e.preventDefault();
    $('.toolbar').addClass('search');
    $('.page-size').addClass('search');
    $('.toolbar .search-toolbar input').focus();
    return false;
});

$(document).on('click', '.toolbar .exit-search', function (e) {
    e.preventDefault();
    $('.toolbar').removeClass('search');
    $('.page-size').removeClass('search');
    $('.toolbar .search-toolbar input').val('');

    var searchVar = 'search';
    var $list = $('#list');
    var url = $list.data('path');

    url = url.replace(new RegExp("(&|\\?)" + searchVar + "=.*?(&|$)", 'g'), function (str, p1, p2, offset, s) {
        if (p1 == '?') {
            return '?';
        } else if (p2 == '') {
            return '';
        }
        return '&';
    });

    var data = {};
    data[searchVar] = '';
    $.ajax({
        url: url,
        data: data,
        success: function (html) {
            $list.replaceWith($(html).find('#list'));
        }
    });
    return false;
});

$(document).on('keydown', '.toolbar .toolbar-search-td input', function (e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        return false;
    }
});

var updateTimer;
$(document).on('keyup', '.toolbar .toolbar-search-td input', function (e) {
    e.preventDefault();

    var searchVar = 'search';
    var $this = $(this);
    if (e.keyCode == 27) {
        $('.toolbar').removeClass('search');
        $('.page-size').removeClass('search');
    } else {
        clearTimeout(updateTimer);
        updateTimer = setTimeout(function () {
            var $list = $('#list');
            var url = $list.data('path');
            url = url.replace(new RegExp("(&|\\?)" + searchVar + "=.*?(&|$)", 'g'), function (str, p1, p2, offset, s) {
                if (p1 == '?') {
                    return '?';
                } else if (p2 == '') {
                    return '';
                }
                return '&';
            });

            var data = {};
            data[searchVar] = $this.val();
            $.ajax({
                url: url,
                data: data,
                success: function (html) {
                    $list.replaceWith($(html).find('#list'));
                }
            });
        }, 300);
    }

    return false;
});
