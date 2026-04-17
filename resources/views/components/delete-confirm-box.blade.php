$(".{{$class}}").on("click", function() {
    var url = $(this).attr('data-url');
    var redirect = typeof $(this).attr('data-redirect') == 'undefined' ? "" : $(this).attr('data-redirect');
    var datatable = typeof $(this).attr('data-datatable') == 'undefined' ? "" : $(this).attr('data-datatable');
    console.log(url);
    Swal.fire({
      title: "Are you sure?",
      text: "You won't be able to revert this!",
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, delete it!",
      confirmButtonClass: "btn btn-primary",
      cancelButtonClass: "btn btn-danger ml-1",
      buttonsStyling: false
    }).then(function(result) {
    if (result.value) {
      $.ajax({
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        url: url,
        method: "POST",
        //data: new FormData(document.getElementById(form)),
        //processData: false,
        //contentType: false,
        beforeSend: function () {
        },
        success: function (response) {
            if (response.success === false) {
                Swal.fire({
                    type: "succes",
                    title: "Deleted!",
                    text: response.payload,
                    confirmButtonClass: "btn btn-success"
                });
            } else if (response.success === true) {
                Swal.fire({
                    type: "danger",
                    title: "Exception!",
                    text: response.payload,
                    confirmButtonClass: "btn btn-danger"
                });
            }else{
                Swal.fire({
                    type: "warning",
                    title: "Error!",
                    text: "Please Try Later!.",
                    confirmButtonClass: "btn btn-danger"
                });
            }
        },
        error: function (xhr) {
            Swal.fire({
                type: "warning",
                title: "Error!",
                text: "Please Try Later!.",
                confirmButtonClass: "btn btn-danger"
            });
        }
        }); 
    }
   });
});