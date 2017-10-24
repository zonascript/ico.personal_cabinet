$(function(){
    var on_scroll = function(e){
        var margin = $(window).height() / 2;
        var scrolled = $(window).scrollTop() + $(window).height();

        $('.need-animation').each(function(){
            var $this = $(this);
            var offset = $(this).offset();
            var item_margin = margin;
            if ($(this).data('margin')){
                item_margin = $(this).data('margin');
            }
            if (offset.top && scrolled - item_margin >= offset.top){
                $this.removeClass('need-animation');
                $this.addClass('animate');
            }
        });

    };

    $(document).on('scroll', on_scroll);
    $(document).on('ontouchmove', on_scroll);
    on_scroll();
});