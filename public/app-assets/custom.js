$(function () {

    $.ajaxSetup({
        headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        dataType: 'json'
    });

});   

var FORM_SUBMIT_HANDLER = {

    removeErrorMessage: (form_id) => {
        $('#'+form_id).find('select, input, textarea').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    },

    printErrorMessage: (error_message) => {
        
        $.each( error_message, function( key, value ) {
            $('input[name="'+key+'"]').addClass('is-invalid').after('<div class="invalid-feedback error" id="'+key+'_error"><em>'+value+'</em></div>');
        });
    },

    submitForm: (_this, route_path) => {

        var form_id = $(_this).parents('form').attr('id');
        var form_data = $('#'+form_id).serialize();

        $.ajax({

            type: 'POST',
            url: admin_url+route_path,
            data: form_data,
            success: function(response){
                console.log(response);
                
                if(response.status == true && $.isEmptyObject(response.errors) && $.isEmptyObject(response.form_errors)){

                    $('#'+form_id).find(".print_msg").append('<div class="alert alert-success">'+response.message+'</div>').show();
                    if(typeof response.retailer_id != 'undefined' && response.retailer_id != ''){
                        $("form").find('input[name="retailer_id"]').val(response.retailer_id);
                    }

                    FORM_SUBMIT_HANDLER.removeErrorMessage(form_id);
                    
                    if(response.action == 'insert'){
                       setTimeout(function(){ window.location.href= admin_url+'/retailer/list' }, 3000);     

                    }

                    if($.isEmptyObject(response.action_type) == false && response.action_type == 'add'){
                        $('#smartwizard3').smartWizard("next");
                    }
                    
                }else{

                    if(response.error_type == "simple_error"){
                        $('#'+form_id).find(".print_msg").append('<div class="alert alert-danger">'+response.message+'</div>').show();
                    }else{

                        FORM_SUBMIT_HANDLER.removeErrorMessage(form_id);
                        FORM_SUBMIT_HANDLER.printErrorMessage(response.message);

                    }

                }
            },
            error: function(error){
                console.log("error"+error);
            }
        })


    }

}



var FORM_SUBMIT_HANDLER_PROFILE = {

    removeErrorMessage: (form_id) => {
        $('#'+form_id).find('select, input, textarea').removeClass('is-invalid');
        $('.invalid-feedback').remove();
    },

    printErrorMessage: (error_message) => {
        
        $.each( error_message, function( key, value ) {
            $('input[name="'+key+'"]').addClass('is-invalid').after('<div class="invalid-feedback error" id="'+key+'_error"><em>'+value+'</em></div>');
        });
    },

    submitForm: (_this, route_path) => {

        var form_id = $(_this).parents('form').attr('id');
        // var form_data = $('#'+form_id).serialize();
        var form_data = new FormData($('#'+form_id)[0]);
       
        $.ajax({

            type: 'POST',
            url: admin_url+route_path,
            processData: false,
            contentType: false,
            data: form_data,
            success: function(response){
                console.log(response);
                
                if(response.status == true && $.isEmptyObject(response.errors) && $.isEmptyObject(response.form_errors)){

                    $('#'+form_id).find(".print_msg").append('<div class="alert alert-success">'+response.message+'</div>').show();
                    
                    FORM_SUBMIT_HANDLER_PROFILE.removeErrorMessage(form_id);
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                }else{

                    if(response.error_type == "simple_error"){
                        FORM_SUBMIT_HANDLER_PROFILE.removeErrorMessage(form_id);
                        $('#'+form_id).find(".print_msg").append('<div class="alert alert-danger">'+response.message+'</div>').show();
                    }else{
                        FORM_SUBMIT_HANDLER_PROFILE.removeErrorMessage(form_id);
                        FORM_SUBMIT_HANDLER_PROFILE.printErrorMessage(response.message);

                    }

                }
            },
            error: function(error){
                console.log("error"+error);
            }
        })


    }

}