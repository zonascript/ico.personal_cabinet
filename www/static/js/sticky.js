(function ($) {

    "use strict";

    /**
     * Описание объекта
     */
    var msticky = function () {
        return msticky.init.apply(this, arguments);
    };

    /**
     * Расширение объекта
     */
    $.extend(msticky, {
        /**
         * Настройки по умолчанию
         */
        options: {
            top: undefined,
            bottom: undefined,
            parent: 'parent',
            width: undefined,
            dimension: 'top',
            offset: false
        },
        /**
         * Элемент, над которым выполняются действия
         */
        element: undefined,
        initOffset: 0,
        /**
         * Инициализация
         * @param element
         * @param options
         */
        init: function (element, options) {
            if (element === undefined || !element.length) return;
            this.element = $(element);
            this.options = $.extend(this.options, options, this.element.data());
            this.options.width = this.options.width ? this.options.width : this.element.width();
            this.parent = this.options.parent == 'parent' ? this.element.parent() : this.element.closest(this.options.parent);
            this.element.css({width: this.options.width});
            this.element.addClass("msticky-dimension-" + this.options.dimension);
            this.initOffset = this.element.offset().top - this.parent.offset().top;
            this.bind();
            $(window).trigger('scroll');
            return this;
        },
        /**
         * "Навешиваем" события
         */
        bind: function () {
            var me = this;

            var stickyBottom = this.element.offset().top + this.element.height();
            var parentBottom = this.parent.offset().top + this.parent.height();

            if (stickyBottom < parentBottom) {
                $(window).on('scroll', function(e){
                    me.handle();
                });
            }
        },
        handle: function(){
            var top = $(window).scrollTop();
            var height = $(window).height();
            var offset = this.parent.offset().top;
            var parentHeight = this.parent.height();
            var elementHeight = this.element.height();
            var elementOffset = this.options.top;
            var totalOffset = offset - elementOffset;
            if (this.options.offset) {
                totalOffset += this.initOffset;
            }

            if (this.options.dimension == 'top') {
                if (top > totalOffset) {
                    if (offset + parentHeight > top + elementOffset + elementHeight) {
                        // Sticky
                        this.setStickyT();
                    } else {
                        // bottom
                        this.setBottomT();
                    }
                } else {
                    this.setTopT();
                }
            } else {
                if (top + height < offset + parentHeight) {
                    if (top + height > offset + elementHeight) {
                        this.setStickyB();
                    } else {
                        this.setTopB();
                    }
                } else {
                    this.setBottomB()
                }
            }
        },
        setStickyT: function(){
            if (!this.element.hasClass('msticky-sticky')) {
                this.clean();
                this.element.addClass('msticky-sticky');
                this.element.css({
                    'top': this.options.top
                });
            }
        },
        setTopT: function(){
            if (!this.element.hasClass('msticky-top')) {
                this.clean();
                this.element.addClass('msticky-top');
            }
        },
        setBottomT: function(){
            if (!this.element.hasClass('msticky-bottom')) {
                this.clean();
                this.element.addClass('msticky-bottom');
            }
        },
        setStickyB: function(){
            if (!this.element.hasClass('msticky-sticky')) {
                this.clean();
                this.element.addClass('msticky-sticky');
                this.element.css({
                    'bottom': this.options.bottom
                });
            }
        },
        setTopB: function(){
            if (!this.element.hasClass('msticky-top')) {
                this.clean();
                this.element.addClass('msticky-top');
            }
        },
        setBottomB: function(){
            if (!this.element.hasClass('msticky-bottom')) {
                this.clean();
                this.element.addClass('msticky-bottom');
            }
        },
        clean: function(){
            this.element.removeClass("msticky-sticky msticky-bottom msticky-top");
            this.element.css({
                'position': '',
                'top': '',
                'bottom': ''
            })
        }
    });

    /**
     * Инициализация функции объекта для jQuery
     */
    return $.fn.msticky = function (options) {
        return msticky.init(this, options);
    };
})($);

//$(function(){
//	$('body').msticky();
//});