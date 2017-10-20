(function ($) {

    "use strict";

    /**
     * Описание объекта
     */
    var fileapi = function () {
        return fileapi.init.apply(this, arguments);
    };

    /**
     * Расширение объекта
     */
    $.extend(fileapi, {
        /**
         * Настройки по умолчанию
         */
        options: {
            field: null,
            option: 500,
            startPath: undefined,
            listUrl: undefined,
            uploadUrl: undefined,
            filemanSelector: '.file-manager',
            manageSelector: '.manage',
            messagesSelector: '.messages',
            messagesTimeout: 10000,
            csrfName: undefined,
            csrf: undefined,
            deletePrevention: 'Do you really want to delete the file?'
        },
        /**
         * Элемент, над которым выполняются действия
         */
        element: undefined,
        $element: undefined,
        currentPath: undefined,
        manage: {},
        /**
         * Инициализация
         * @param element
         * @param options
         */
        init: function (element, options) {
            if (element === undefined) return;

            this.element = element;
            this.$element = $(element);
            this.options = $.extend(this.options, options);

            this.currentPath = this.options.startPath;

            this.initManagement();
            this.initUploader();
            this.bind();

            return this;
        },
        initManagement: function () {
            var me = this;

            me.manage['manage'] = $(this.options.manageSelector);
            me.manage['create_folder'] = {
                'button': me.manage.manage.find('.create-folder-button'),
                'input': me.manage.manage.find('.create-folder-input')
            };
        },
        initUploader: function () {
            var me = this;

            var query = {
                'path': this.currentPath
            };
            query[this.options.csrfName] = this.options.csrf;

            var flow = new Flow({
                target: this.options.uploadUrl,
                testChunks: false,
                query: query,
                allowDuplicateUploads: true
            });

            flow.assignBrowse(document.getElementById('select'));
            flow.assignDrop(document.getElementById('zone'));

            flow.on('fileAdded', function(file, event){});

            flow.on('filesSubmitted', function(){
                flow.opts['query']['path'] = me.currentPath;
                flow.upload();
            });

            flow.on('uploadStart', function(){
                $('#progress_bar').css({
                    'width': 0
                });
                me.setUploading();
            });

            flow.on('progress', function(){
                var width = flow.progress() * 100 + '%';
                $('#progress_bar').css({
                    'width': width
                });
            });

            flow.on('complete', function(){
                $('#progress_bar').css({
                    'width': 0
                });
                me.unsetUploading();
                me.updateList();
            });
        },
        /**
         * "Навешиваем" события
         */
        bind: function () {
            var me = this;

            this.$element.on('click', '.files .file-check', function (e) {
                if (!$(e.target).is('input')){
                    e.preventDefault();
                    $(this).find('input').trigger('click');
                    return false;
                }
            });

            //this.$element.on('click', '.files .file-name', function (e) {
            //    e.preventDefault();
            //    $(this).find('a').click();
            //    return false;
            //});

            this.$element.on('click', '.files .file .file-link', function (e) {
                e.preventDefault();
                me.openFile($(this).data('url'));
                return false;
            });

            this.$element.on('click', '.files .dir .file-link', function (e) {
                e.preventDefault();
                me.openFolder($(this).data('path'));
                return false;
            });

            this.$element.on('click', '.files .delete-link', function (e) {
                e.preventDefault();
                if (confirm(me.options.deletePrevention)) {
                    me.deleteFile($(this).data('path'));
                }
                return false;
            });

            this.$element.on('click', '.create-folder-button', function (e) {
                e.preventDefault();
                me.createFolder(me.manage.create_folder.input.val());
                return false;
            });

            this.$element.on('click', '.remove-selected', function(e) {
                e.preventDefault();
                if (confirm(me.options.deletePrevention)) {
                    me.deleteAll();
                }
                return false;
            });

            // var collection = $();
            // $(document).on('dragenter', function (e) {
            //     me.showDropInfo();
            //     collection = collection.add(e.target);
            // }).on('dragleave',function (e) {
            //     collection = collection.not(e.target);
            //     if (!collection.length) {
            //         me.hideDropInfo();
            //     }
            // }).on('drop', function () {
            //     collection = $();
            //     me.hideDropInfo();
            // });
        },
        showDropInfo: function() {
            var fileman = this.$element;
            if (!fileman.hasClass('drop'))
                fileman.addClass('drop');
        },
        hideDropInfo: function() {
            this.$element.removeClass('drop');
        },
        setUploading: function() {
            this.$element.addClass('uploading');
        },
        unsetUploading: function() {
            this.$element.removeClass('uploading');
        },
        openFile: function (url) {
            var me = this;
            $('#' + this.options.field).val(url);
            $('.modal-closer').trigger('click');
            console.log(url);
        },
        openFolder: function (path) {
            var me = this;
            me.updateList(path);
        },
        createFolder: function (name) {
            var me = this;
            me.api('make', {'name': name}, function (data) {
                if (data.statement == 'success') {
                    me.manage.create_folder.input.val('');
                    me.updateList();
                }
            });
        },
        deleteAll: function() {
            var me = this;
            var files = [];
            $('input.delete-checker:checked').each(function(){
                files.push($(this).val());
            });
            me.api('deleteAll', {'files': files}, function (data) {
                $('input.delete-checker').removeAttr('checked');
                me.updateList();
            });
        },
        deleteFile: function (name) {
            var me = this;
            me.api('delete', {'name': name}, function (data) {
                if (data.statement == 'success') {
                    me.updateList();
                }
            });
        },
        api: function (action, sendData, callback) {
            var me = this;
            sendData = sendData || {};
            if (!sendData.path) {
                sendData.path = me.currentPath
            }
            sendData['action'] = action;
            sendData[me.options.csrfName] = me.options.csrf;
            $.ajax({
                'type': 'post',
                'url': me.options.apiUrl,
                'data': sendData,
                'dataType': 'json',
                'success': function (data) {
                    if (data.statement && data.message) {
                        if (data.statement == 'error') {
                            me.error(data.message);
//                        }else{
//                            me.message(data.message);
                        }
                    }
                    if (callback) {
                        callback(data);
                    }
                }
            })
        },
        message: function (message, type) {
            var me = this;
            type = type || 'message';

            var $notification = $('<div/>').addClass('notification').addClass(type).html(message);
            var $messages = $(me.options.messagesSelector);
            $messages.append($notification);

            setTimeout(function () {
                $notification.remove();
            }, me.options.messagesTimeout)
        },
        error: function (message) {
            this.message(message, 'error');
        },
        updateList: function (path) {
            var me = this;
            path = path || me.currentPath;
            var sendData = {
                'path': path
            };
            $.ajax({
                'url': me.options.listUrl,
                'data': sendData,
                'dataType': 'html',
                'success': function (data) {
                    var wrapped_data = $('<div/>').append(data);
                    $(me.element).find('.files').replaceWith(wrapped_data.find('.files'));
                    me.currentPath = path;
                }
            });
        }
    });

    /**
     * Инициализация функции объекта для jQuery
     */
    return $.fn.fileapi = function (options) {
        return fileapi.init(this, options);
    };

})($);
