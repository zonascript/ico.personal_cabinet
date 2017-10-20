$(function () {
    $(document).on('click', 'a[data-prevention]', function (e) {
        e.preventDefault();
        var $this = $(this);
        var data = $this.data();
        var url = $this.attr('href');
        var type = data.type ? data.type : 'post';
        var trigger = data.trigger ? data.trigger : null;
        var text = data.text ? data.text : null;
        var title = data.title ? data.title : null;

        $.confirm({
            title: title,
            text: text,
            confirm: function () {
                $.ajax({
                    url: url,
                    type: type,
                    dataType: 'json',
                    success: function (data) {
                        if (data.success && trigger) {
                            $(document).trigger(trigger, [$this, data]);
                        }
                        $('.modal-closer').click();
                    }
                });
            }
        });
        return false;
    });
});