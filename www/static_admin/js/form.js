$(function () {
    $(document).on('form-removed', function (e, $element, data) {
        if ($element.data('all')) {
            window.location.href = $element.data('all');
        }
    });
});