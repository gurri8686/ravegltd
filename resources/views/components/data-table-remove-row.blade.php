$(document).on('click', '.remove-record', function () {
    let url = $(this).data('url');
    ajaxRemoveData(url,{"_token": "{{ csrf_token() }}"}, {{$datatable}});
});