/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Lightweight auto-dismiss toast. type: 'success' | 'error'
 */
function showFormToast(message, type) {
    type = type || 'success';
    if (!document.getElementById('form-toast-style')) {
        var st = document.createElement('style');
        st.id = 'form-toast-style';
        st.innerHTML = ''
            + '#form-toast-wrap{position:fixed;top:78px;right:18px;z-index:999999;display:flex;flex-direction:column;gap:10px;}'
            + '.form-toast{display:flex;align-items:center;gap:10px;min-width:240px;max-width:360px;padding:13px 16px;border-radius:10px;'
            + 'font-size:13.5px;font-weight:600;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,0.18);'
            + 'opacity:0;transform:translateX(24px);transition:opacity .25s ease,transform .25s ease;}'
            + '.form-toast.show{opacity:1;transform:translateX(0);}'
            + '.form-toast--success{background:#16a34a;}'
            + '.form-toast--error{background:#dc2626;}'
            + '.form-toast svg{flex-shrink:0;}';
        document.head.appendChild(st);
    }
    var wrap = document.getElementById('form-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'form-toast-wrap';
        document.body.appendChild(wrap);
    }
    var icon = type === 'error'
        ? '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
        : '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    var toast = document.createElement('div');
    toast.className = 'form-toast form-toast--' + type;
    toast.innerHTML = icon + '<span>' + message + '</span>';
    wrap.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('show'); });
    setTimeout(function () {
        toast.classList.remove('show');
        setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
    }, 3000);
}

function submitForm(form, url, output) {

    $.ajax({
        url: url,
        method: "POST",
        data: new FormData(form),
        processData: false,
        contentType: false,
        beforeSend: function () {
            if (output != '') {
                $(output).css({opacity: '.7'});
            }
            $('button[type=submit]').attr('disabled', true);
        },
        success: function (res) {
            if (output != '') {
                $(output).html(res);
                $(output).css({opacity: 1});
            }
            $('button[type=submit]').attr('disabled', false);

        },
        error: function (xhr) {
            if (output != '') {
                $(output).css({opacity: 1});
            }
            $('button[type=submit]').attr('disabled', false);
        }
    });

    return false;
}

function submitAjaxForm(form, url) {
    $.ajax({
        url: url,
        method: "POST",
        data: new FormData(document.getElementById(form)),
        processData: false,
        contentType: false,
        beforeSend: function () {
            let elements = '#' + form + ' .invalid-feedback';
            let loader = '#' + form + ' .loader';
            $(elements).remove();
            $('button[type=submit]').attr('disabled', true);
            $(loader).removeClass('display-none');
        },
        success: function (response) {
            if (response.success === false) {
                if (typeof response.form_errors != "undefined") {
                    for (let i in response.form_errors) {
                        let key = [i];
                        let value = '<div class="invalid-feedback position-absolute">';
                        for (let k in response.form_errors[i]) {
                            value += '<span>' + response.form_errors[i][k] + '</span>';
                        }
                        value += '</div>';
                        let field = '#' + form + ' #' + key;
                        //console.log(field);
                        $(field).before(value);
                    }
                } else {
                    bootbox.alert({centerVertical: true, "message": response.payload});
                }
                $('.captcha-container').find('img').click();
            } else if (response.success === true) {
                if (typeof response.payload.redirect != "undefined") {
                    window.location.href = response.payload.redirect;
                }
            }
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');
        },
        error: function (xhr) {
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');
        }
    });
}

function submitAjaxFormData(form, url, method) {
    $.ajax({
        url: url,
        method: method,
        data: new FormData(document.getElementById(form)),
        processData: false,
        contentType: false,
        beforeSend: function () {
            let elements = '#' + form + ' .invalid-feedback';
            let loader = '#' + form + ' .loader';
            $(elements).remove();
            $('button[type=submit]').attr('disabled', true);
            $(loader).removeClass('display-none');
        },
        success: function (response) {
            if (response.success === false) {
                if (typeof response.form_errors != "undefined") {
                    for (let i in response.form_errors) {
                        let key = [i];
                        let value = '<span class="invalid-feedback">';
                        for (let k in response.form_errors[i]) {
                            value += '' + response.form_errors[i][k] + '';
                        }
                        value += '</span>';
                        //let field = '#'+form +' #'+key;
                        let field = '#' + form + ' [data-validate="' + key + '"]';
                        //console.log(field);
                        $(field).html(value);
                    }
                } else {
                    bootbox.alert({centerVertical: true, "message": response.payload});
                }
            } else if (response.success === true) {
                if (typeof response.payload.redirect != "undefined") {
                    window.location.href = response.payload.redirect;
                } else {
                    showFormToast(response.payload.message, 'success');
                    // reset form
                }
            }
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');

        },
        error: function (xhr) {
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');
        }
    });
}

function submitAjaxFormCallback(form, url, callback) {
    $.ajax({
        url: url,
        method: "POST",
        data: new FormData(document.getElementById(form)),
        processData: false,
        contentType: false,
        beforeSend: function () {
            let elements = '#' + form + ' .invalid-feedback';
            let loader = '#' + form + ' .loader';
            $(elements).remove();
            $('button[type=submit]').attr('disabled', true);
            $(loader).removeClass('display-none');
        },
        success: function (response) {
            if (response.success === false) {
                if (typeof response.form_errors != "undefined") {
                    for (let i in response.form_errors) {
                        let key = [i];
                        let value = '<span class="invalid-feedback">';
                        for (let k in response.form_errors[i]) {
                            value += '' + response.form_errors[i][k] + '';
                        }
                        value += '</span>';
                        //let field = '#'+form +' #'+key;
                        let field = '#' + form + ' [data-validate="' + key + '"]';
                        //console.log(field);
                        $(field).html(value);
                    }
                } else {
                    bootbox.alert({centerVertical: true, "message": response.payload});
                }
            } else if (response.success === true) {
                callback(response);
            }
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');

        },
        error: function (xhr) {
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');
        }
    });
}

function ajaxRemoveData_bkp(url, data, datatable) {
    bootbox.confirm({
        message: "Are you sure to remove this",
        centerVertical: true,
        buttons: {
            confirm: {
                label: 'Yes',
                className: 'btn-success'
            },
            cancel: {
                label: 'No',
                className: 'btn-danger'
            }
        },
        callback: function (result) {
            if (result === true) {
                $.ajax({
                    url: url,
                    method: "POST",
                    data: data,
                    beforeSend: function () {
                    },
                    success: function (response) {

                        if(typeof(response)=='string'){
                            response=JSON.parse(response)
                        }

                        if (response.success === true) {
                            bootbox.alert({centerVertical: true, "message": response.payload});
                        } else {
                            bootbox.alert({centerVertical: true, "message": response.payload});
                        }

                        if (datatable) {
                            datatable.draw();
                        }
                    },
                    error: function (xhr) {
                        bootbox.alert({centerVertical: true, "message": "There is some error"});
                    }
                });
            }
        }
    });
}

function ajaxRemoveData(url, data, datatable) {
    var url = url;
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
        data: data,
        beforeSend: function () {
        },
        success: function (response) {
            if(typeof(response)=='string'){
                response=JSON.parse(response)
            }

            if (response.success === true) {
                Swal.fire({
                    type: "succes",
                    title: "Deleted!",
                    text: response.payload,
                    confirmButtonClass: "btn btn-success"
                });
            } else {
                Swal.fire({
                    type: "danger",
                    title: "Exception!",
                    text: response.payload,
                    confirmButtonClass: "btn btn-danger"
                });
            }

            if (datatable) {
                datatable.draw();
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
}

function submitAjaxFormWithoutRedirect(form, url) {
    $.ajax({
        url: url,
        method: "POST",
        data: new FormData(document.getElementById(form)),
        processData: false,
        contentType: false,
        beforeSend: function () {
            let elements = '#' + form + ' .invalid-feedback';
            let loader = '#' + form + ' .loader';
            $(elements).remove();
            $('button[type=submit]').attr('disabled', true);
            $(loader).removeClass('display-none');
        },
        success: function (response) {
            if (response.success === false) {
                if (typeof response.form_errors != "undefined") {
                    for (let i in response.form_errors) {
                        let key = [i];
                        let value = '<span class="invalid-feedback">';
                        for (let k in response.form_errors[i]) {
                            value += '' + response.form_errors[i][k] + '';
                        }
                        value += '</span>';
                        //let field = '#'+form +' #'+key;
                        let field = '#' + form + ' [data-validate="' + key + '"]';
                        //console.log(field);
                        $(field).html(value);
                    }
                } else {
                    bootbox.alert({centerVertical: true, "message": response.payload});
                }
            } else if (response.success === true) {
                bootbox.alert({centerVertical: true, "message": response.payload.message});
            }
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');

        },
        error: function (xhr) {
            $('button[type=submit]').attr('disabled', false);
            let loader = '#' + form + ' .loader';
            $(loader).addClass('display-none');
        }
    });
}

function submitCodeForm(form, url, output) {
    var data = {};
    data['text'] = editor.getValue();
    $.ajax({
        url: url,
        method: "POST",
        data: $(form).serialize() + '&' + $.param(data),
        //processData: false,
        //contentType: false,
        beforeSend: function () {
            if (output != '') {
                $(output).css({opacity: '.7'});
            }
            $('button[type=submit]').attr('disabled', true);
        },
        success: function (res) {
            if (output != '') {
                $(output).html(res);
                $(output).css({opacity: 1});
            }
            $('button[type=submit]').attr('disabled', false);

        },
        error: function (xhr) {
            if (output != '') {
                $(output).css({opacity: 1});
            }
            $('button[type=submit]').attr('disabled', false);
        }
    });

    return false;
}

function submitDownloadCodeForm(form, url, output) {
    if (typeof editor != "undefined") {
        var data = {};
        data['text'] = editor.getValue();
        data['revision_name'] = $('.revision_name').val();
        $.ajax({
            url: url,
            method: "POST",
            data: $(form).serialize() + '&' + $.param(data),
            //processData: false,
            //contentType: false,
            beforeSend: function () {
                if (output != '') {
                    $(output).css({opacity: '.7'});
                }
                $('button[type=submit]').attr('disabled', true);
            },
            success: function (res) {
                if (output != '') {
                    $(output).html(res);
                    $(output).css({opacity: 1});
                }
                $('button[type=submit]').attr('disabled', false);

            },
            error: function (xhr) {
                if (output != '') {
                    $(output).css({opacity: 1});
                }
                $('button[type=submit]').attr('disabled', false);
            }
        });
    }

    return false;
}

function directoryList(url, data, output) {
    $.ajax({
        url: url,
        method: "POST",
        data: data,
        //processData: false,
        //contentType: false,
        beforeSend: function () {
            $(output).html('<center><h4>Loading...</h4></center>');
        },
        success: function (res) {
            $(output).html(res);
        },
        error: function (xhr) {
            $(output).html("There is SOme error!");
        }
    });
}

/**
 *
 * @param {type} container
 * @return {undefined}
 */
function copyToClipboard(container) {
    $(container).select();
    document.execCommand('copy');
}

function copyToCode() {
    editor.getValue().select();
    document.execCommand('copy');
}

/**
 *
 * @param {*} string
 * @param {*} target
 * @param {*} replacement
 */
function findAndReplace(string, target, replacement) {
    var i = 0,
            length = string.length;
    for (i; i < length; i++) {
        string = string.replace(target, replacement);
    }
    return string;
}

function compileToOutput() {

}
/**
 *
 */
function copyToClipboardText(val) {
    document.getElementById("dummy_copy").value = val;
    $('#dummy_copy').select();
    document.execCommand('copy');
}

function queryStringGenerator(data){
	let params = new URLSearchParams();
	Object.entries(data).forEach(([key, value]) => {
	  if (Array.isArray(value)) {
		value.forEach(v => params.append(key + '[]', v));
	  } else {
		params.append(key, value);
	  }
	});

	let queryString = params.toString();
	return queryString;
}