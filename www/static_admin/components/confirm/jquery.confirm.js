(function ($) {

    /**
     * Confirm a link or a button
     * @param [options] {{title, text, confirm, cancel, confirmButton, cancelButton, post, confirmButtonClass}}
     */
    $.fn.confirm = function (options) {
        if (typeof options === 'undefined') {
            options = {};
        }

        this.click(function (e) {
            e.preventDefault();

            var newOptions = $.extend({
                button: $(this)
            }, options);

            $.confirm(newOptions, e);
        });

        return this;
    };

    /**
     * Show a confirmation dialog
     * @param [options] {{title, text, confirm, cancel, confirmButton, cancelButton, post, confirmButtonClass}}
     * @param [e] {Event}
     */
    $.confirm = function (options, e) {
        // Do nothing when active confirm modal.
        if ($('.confirmation-modal').length > 0)
            return;

        // Parse options defined with "data-" attributes
        var dataOptions = {};
        if (options.button) {
            var dataOptionsMapping = {
                'title': 'title',
                'text': 'text',
                'confirm-button': 'confirmButton',
                'cancel-button': 'cancelButton',
                'confirm-button-class': 'confirmButtonClass',
                'cancel-button-class': 'cancelButtonClass',
                'dialog-class': 'dialogClass'
            };
            $.each(dataOptionsMapping, function(attributeName, optionName) {
                var value = options.button.data(attributeName);
                if (value) {
                    dataOptions[optionName] = value;
                }
            });
        }

        // Default options
        var settings = $.extend({}, $.confirm.options, {
            confirm: function () {
                var url = e && (('string' === typeof e && e) || (e.currentTarget && e.currentTarget.attributes['href'].value));
                if (url) {
                    if (options.post) {
                        var form = $('<form method="post" class="hide" action="' + url + '"></form>');
                        $("body").append(form);
                        form.submit();
                    } else {
                        window.location = url;
                    }
                }
            },
            cancel: function (o) {
                $('.modal-closer').click();
            },
            button: null
        }, dataOptions, options);

        // Modal
        var modalHeader = '';
        if (settings.title) {
            modalHeader =
                '<div class="confirm-modal-header">' +
                    '<h4 class="modal-title">' + settings.title+'</h4>' +
                '</div>';
        }
        var modalText = '';
        if (settings.text) {
            modalText = '<div class="confirm-modal-body">' + settings.text + '</div>';
        }
        var modalHTML =
                '<div class="confirmation-modal modal fade" tabindex="-1" role="dialog">' +
                    '<div class="'+ settings.dialogClass +'">' +
                        '<div class="confirm-modal-content">' +
                            modalHeader +
                            modalText +
                            '<div class="confirm-modal-footer">' +
                                '<a class="cancel btn ' + settings.cancelButtonClass + '" href="#" data-dismiss="modal">' +
                                    settings.cancelButton +
                                '</a>' +
                                '<a class="confirm btn ' + settings.confirmButtonClass + '" href="#" data-dismiss="modal">' +
                                    settings.confirmButton +
                                '</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';

        var modal = $(modalHTML);


        modal.modal({
            onAfterOpen: function () {
                var mcontent = $('.modal-content');
                mcontent.find(".confirm").on('click', function (e) {
                    e.preventDefault();
                    settings.confirm(settings.button);
                    return false;
                });
                mcontent.find(".cancel").on('click', function (e) {
                    e.preventDefault();
                    settings.cancel(settings.button);
                    return false;
                });
            }
        });
    };

    /**
     * Globally definable rules
     */
    $.confirm.options = {
        text: "Вы уверены?",
        title: "",
        confirmButton: "Да",
        cancelButton: "Нет",
        post: false,
        confirmButtonClass: "button green round",
        cancelButtonClass: "button transparent grey round",
        dialogClass: "modal-dialog"
    }
})(jQuery);
