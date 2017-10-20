$(function() {
    $(document).on('click', '.smart-tabs li a', function (e){
        e.preventDefault();
        var $this = $(this);
        var id = $this.attr('href');
        var $tab = $(id);

        if ($tab.length) {
            $tab.parent().find('.smart-content').removeClass('active');
            $tab.addClass('active');
        }
        $this.closest('.smart-tabs').find('li').removeClass('active');
        $this.closest('li').addClass('active');

        $(document).trigger('smart-tabs-handle', [$this, $tab]);
        return false;
    });
});