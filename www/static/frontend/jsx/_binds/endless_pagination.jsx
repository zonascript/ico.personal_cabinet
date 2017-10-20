
$(document).on('click', '.front-endless-pager a.show-more', function(e){
    e.preventDefault();

    let $this = $(this);
    let $parent = $(this).parent();
    let $container = $('.product-items');
    let text_loading = $this.data('text-loading');
    let text_default = $this.data('text-default');


    window.loader.load(
        $.ajax($this.attr('href'), {
            'success' : (data)=>{
                $container.append(data.content);

                if (data.href) {
                    $this.find('.text').html(text_default);
                    $this.attr('href', data.href);
                    $this.removeAttr('disabled')
                }
                else {
                    $this.remove();
                }

                $('.page_count').html(data.page_count);

                $(window).trigger('resize');
            }
        })
    );


    $this.attr('disabled', 'disabled');
    $this.find('.text').html(text_loading);

});