$(function() {
    $('[name*="metaCustom"]').on('change', function (e) {
        console.log(123);
        $('[name*="metaTitle"], [name*="metaKeywords"], [name*="metaDescription"]').attr('disabled', $(this).prop('checked') ? false : 'disabled');
    });
});
