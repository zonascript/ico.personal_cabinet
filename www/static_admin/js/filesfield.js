(function ($) {

    "use strict";

    var filesField = {
        options: {
            listId: undefined,
            contentId: undefined,
            uploadUrl: undefined,
            sortUrl: undefined,
            deleteUrl: undefined,

            flowData: {},
            sortData: {},
            deleteData: {}
        },
        element: undefined,
        init: function (element, options) {
            if (element === undefined) return;

            this.element = element;
            this.options = $.extend(this.options, options);

            this.bind();
            this.initUploader();
            this.initList();

            return this;
        },
        bind: function()
        {
            var me = this;
            $(document).on('click', '#' + me.options.listId + ' .remove', function(e){
                e.preventDefault();
                var $item = $(this).closest('li');
                if ($item.data('pk')) {
                    me.remove($item.data('pk'));
                }
                return false;
            });
        },
        initUploader: function () {
            var me = this;

            var flow = new Flow({
                target: me.options.uploadUrl,
                testChunks: false,
                query: me.options.flowData
            });

            flow.assignBrowse(this.element);
            flow.assignDrop(this.element);

            flow.on('filesSubmitted', function(){
                flow.upload();
            });

            flow.on('uploadStart', function(){
                $(me.element).find('.progress_bar').css({
                    'width': 0
                });
            });

            flow.on('progress', function(){
                var width = flow.progress() * 100 + '%';
                $(me.element).find('.progress_bar').css({
                    'width': width
                });
            });

            flow.on('complete', function(){
                $(me.element).find('.progress_bar').css({
                    'width': '0%'
                });
                me.updateList();
            });
        },
        checkEmpty: function() {
            var $list = $('#' + this.options.listId);
            var $empty = $list.next('.empty-info');
            if ($list.find('li').length > 0) {
                $empty.addClass('hide');
            } else {
                $empty.removeClass('hide');
            }
        },
        updateList: function () {
            var me = this;
            $.ajax({
                'url': window.location.href,
                'dataType': 'html',
                'success': function (data) {
                    var wrapped_data = $('<div/>').append(data);
                    $('#' + me.options.contentId).replaceWith(wrapped_data.find('#' + me.options.contentId));
                    me.initList();
                    me.checkEmpty();
                }
            });
        },
        initList: function() {
            var me = this;
            $("#" + this.options.listId).sortable({
                placeholder: "empty",

                update: function (event, ui) {
                    me.sort();
                }
            }).disableSelection();
        },
        sort: function() {
            var pk = [];
            var me = this;
            var data = me.options.sortData;

            $("#" + this.options.listId).find('li').each(function(){
                if ($(this).data('pk')) {
                    pk.push($(this).data('pk'));
                }
            });

            data['pk'] = pk;

            $.ajax({
                'type': 'post',
                'url': me.options.sortUrl,
                'data': data
            });
        },
        remove: function(pk) {
            var me = this;
            var data = me.options.deleteData;
            data['pk'] = pk;
            $('#' + me.options.listId).find('[data-pk="'+pk+'"]').fadeOut(300, function(){
                $(this).remove();
                me.checkEmpty();
            });
            $.ajax({
                'type': 'post',
                'url': me.options.deleteUrl,
                'data': data,
                'success': function(){

                }
            });
        }
    };

    /**
     * Инициализация функции объекта для jQuery
     */
    return $.fn.filesField = function (options) {
        return $.extend(true, {}, filesField).init(this, options);
    };

})($);