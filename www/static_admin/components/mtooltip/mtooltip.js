(function($) {
    var Tooltip = function (selector, options) {
        this.init(selector, options);
    };

    Tooltip.prototype = {
        options: {},
        _tooltip: undefined,
        init: function (selector, options) {
            this.options = options || {};
            this._tooltip = $('<div class="tooltip"></div>');

            var self = this;
            $(document).on('mouseenter', selector, function () {
                self._mouseEnterHandler.call(self, this);
            });
        },
        initTooltip: function (target, tooltip) {
            var posLeft, posTop, offset = target.offset();

            tooltip.css('width', tooltip.width() / 2);

            posLeft = offset.left + (target.outerWidth() / 2) - (tooltip.outerWidth() / 2);
            posTop = offset.top - tooltip.outerHeight() - 25;

            if (posLeft < 0) {
                posLeft = offset.left + target.outerWidth() / 2 - 20;
                tooltip.addClass('left');
            } else {
                tooltip.removeClass('left');
            }

            if (posLeft + tooltip.outerWidth() > $(window).width()) {
                posLeft = offset.left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
                tooltip.addClass('right');
            } else {
                tooltip.removeClass('right');
            }

            var isTop = posTop < 0;
            if (posTop < 0) {
                posTop = offset.top + target.outerHeight();
                tooltip.addClass('top');
            } else {
                tooltip.removeClass('top');
            }

            if (isTop) {
                tooltip.css({left: posLeft, top: posTop + 30}).animate({top: '-=10', opacity: 1}, 50);
            } else {
                tooltip.css({left: posLeft, top: posTop}).animate({top: '+=10', opacity: 1}, 50);
            }
        },
        _mouseEnterHandler: function (element) {
            var $this = $(element),
                tip = $this.attr('title'),
                tooltip = $('<div class="tooltip"></div>');

            if (!tip || tip == '') {
                return false;
            }

            $this.removeAttr('title');
            tooltip.css('opacity', 0).html(tip).appendTo('body');

            this.initTooltip($this, tooltip);

            var self = this;
            $(window).on('resize', function () {
                self.initTooltip.call(self, $this, tooltip);
            });
            $this.bind('mouseleave', function () {
                self.removeTooltip.call(self, tooltip, $this, tip);
            });
            tooltip.bind('click', function () {
                self.removeTooltip.call(self, tooltip, $this, tip);
            });
        },
        removeTooltip: function (tooltip, $target, tip) {
            tooltip.animate({top: '-=10', opacity: 0}, 50, function () {
                $(this).remove();
            });

            $target.attr('title', tip);
        }
    };

    $.extend({
        mtooltip: function (selector, options) {
            return new Tooltip(selector, options)
        }
    });
})(jQuery);
