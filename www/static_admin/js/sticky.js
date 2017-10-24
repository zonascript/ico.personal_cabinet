$(function(){
    var $stickyElements = $('.sticky');

    $stickyElements.each(function(){
        $(this).data('initialTop', $(this).offset().top);
    });

    $(window).on('scroll', function(){
        var topOffset = $(window).scrollTop();
        $stickyElements.each(function(){
            var $element = $(this);
            var initialTop = $element.data('initialTop');

            if ($element.hasClass('stuck')){
                if (topOffset <= initialTop){
                    $element.removeClass('stuck')
                }
            }else{
                if (topOffset > initialTop){
                    $element.addClass('stuck')
                }
            }
        });
    });
});