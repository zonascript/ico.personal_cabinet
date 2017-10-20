export default class DottedText
{
    constructor(elem) {
        this.init(elem);
    }

    init(elem) {
        if ($.fn.dotdotdot)
        {
            this.elements = elem || '.must-show-less';

            this.params = {
                watch:'window',
                after: 'a.show_more',
                callback: function(isTruncated, originalContent) {
                    let $this = $(this);
                    let $ml_a = $this.find('a.show_more');

                    if (isTruncated) {
                        $ml_a.css({display: 'inline-block'});
                    }
                    else {
                        $ml_a.css({display: 'none'});
                    }
                }
            };

            this._bind();
        }
    }

    _bind() {

        $(this.elements).each(function(){
            let $this = $(this);

            if (this.offsetHeight < this.scrollHeight ||
                this.offsetWidth < this.scrollWidth) {
                $this.append('<a href="#" class="show_more"></a>');

                let $ml_a = $this.find('a.show_more');
                $ml_a.html($this.data('text-more'));
            }

        });

        $(this.elements).dotdotdot(this.params);

        $(document)
            .on('click', this.elements + ' .show_more', (e) => {
                e.preventDefault();

                let $this = $(e.target).closest(this.elements);
                let isTruncated = $this.triggerHandler("isTruncated");

                if (isTruncated) {
                    $this.addClass('full');
                    $this.trigger('destroy');

                    let $ml_a = $this.find('a.show_more');
                    $ml_a.html($this.data('text-less'));
                }
                else {
                    $this.removeClass('full');

                    let $ml_a = $this.find('a.show_more');
                    $ml_a.html($this.data('text-more'));

                    $this.dotdotdot(this.params);
                }
            });
    }
}
