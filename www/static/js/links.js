$(function(){
    $(document).on('click', '.scroll-link', function(e){
        e.preventDefault();
        var id = $(this).attr('href');
        var $target = $(id);
        var offset = $(this).data('offset');
        offset = offset ? offset : 0;
        if ($target.length) {
            $('body, html').animate({
                scrollTop: $target.offset().top - offset
            }, 500);
        }
        return false;
    });

    $(document).on('click', '.toggle-link', function(e){
        e.preventDefault();
        var $this = $(this);
        $($this.data('selector')).toggleClass($this.data('toggle'));
        return false;
    });
});