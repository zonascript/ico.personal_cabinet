$(document).on('click', 'a.mmodal', function(e){
    e.preventDefault();
    e.stopPropagation();
    $(this).mmodal({skin: $(this).data('modal-class') || 'front'});
});
