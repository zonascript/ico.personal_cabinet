(function($) {
    $.event.special.swipe = {
        setup: function () {
            $(this).bind('touchstart', $.event.special.swipe.handler);
            $(this).bind('touchend', $.event.special.swipe.handler);
        },

        teardown: function () {
            $(this).unbind('touchstart', $.event.special.swipe.handler);
        },

        handler: function (event) {
            var args = [].slice.call(arguments, 1),
                touches = event.originalEvent.touches,
                startX, startY,
                deltaX = 0, deltaY = 0,
                that = this,
                Dxy = {};


            event = $.event.fix(event);

            function cancelTouch() {
                that.removeEventListener('touchmove', onTouchMove);
                that.removeEventListener('touchend', onTouchEnd);
                startX = startY = null;
            }

            function onTouchEnd(e)
            {
                var rad = null,
                    minPath = window['swipe_min_path'] | 100,
                    Dx = Dxy.Dx,
                    Dy = Dxy.Dy;

                if (Math.abs(Dx) >= minPath || Math.abs(Dy) >= minPath) {
                    rad = Math.abs(Math.atan2(Dy, Dx)*(180/3.14));

                    if (rad > 90) {
                        rad = 180 - rad;
                    }
                }

                if (Math.abs(Dx) >= minPath) {
                    cancelTouch();
                    deltaX = (Dx > 0) ? -1 : 1;
                }
                else if (Math.abs(Dy) >= minPath) {
                    cancelTouch();
                    deltaY = (Dy > 0) ? 1 : -1;
                }

                if (deltaX !== 0) {
                    e.preventDefault();
                }

                event.type = "swipe";
                args.unshift(event, deltaX, deltaY, rad); // add back the new event to the front of the arguments with the delatas
                return ($.event.dispatch || $.event.handle).apply(that, args);
            }

            function onTouchMove(e) {
                var Dx = startX - e.touches[0].pageX,
                    Dy = startY - e.touches[0].pageY;

                Dxy = {
                    Dx: Dx,
                    Dy: Dy,
                }
            }

            if (touches.length === 1) {
                startX = touches[0].pageX;
                startY = touches[0].pageY;
                this.addEventListener('touchmove', onTouchMove, false);
                this.addEventListener('touchend', onTouchEnd, false);
            }
        }
    };
})($);