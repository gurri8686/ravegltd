(function(window, undefined) {
    'use strict';

    /*
    NOTE:
    ------
    PLACE HERE YOUR OWN JAVASCRIPT CODE IF NEEDED
    WE WILL RELEASE FUTURE UPDATES SO IN ORDER TO NOT OVERWRITE YOUR JAVASCRIPT CODE PLEASE CONSIDER WRITING YOUR SCRIPT HERE.  */


  })(window);



  $(document).ready(function(){

  });
  $(document).ready(function(){

        $('.moduleID').each(function (i, row) {
            var rowID = $(this).attr('module-id');
        var checkboxChecked = $('.'+rowID).filter(':checked').length;
        if(checkboxChecked>0)
        {
            $('.parent'+rowID).prop('checked', true);
            $('.parent'+rowID).val('');
        }
    });

    //------------------------------------------//
    if($('.is_24_hours').filter(':checked').length==1)
    {
        $( "#start_time" ).prop( "readonly", true );
        $( "#end_time" ).prop( "readonly", true );
    }
    else{
        $( "#start_time" ).prop( "readonly", false );
        $( "#end_time" ).prop( "readonly", false );
    }

    //------------------------------------------//
    if($('.currentDate').filter(':checked').length==1)
    {
        $( "#start_date" ).prop( "readonly", true );
        $( "#end_date" ).prop( "readonly", true );
    }
    else{
        $( "#start_date" ).prop( "readonly", false );
        $( "#end_date" ).prop( "readonly", false );
    }
  });
  $(document).on("click", ".browse", function() {
      var file = $(this).parents().find(".file");
      file.trigger("click");
    });
    $('input[type="file"]').change(function(e) {
      var fileName = e.target.files[0].name;
      $("#file").val(fileName);

      var reader = new FileReader();
      reader.onload = function(e) {
        // get loaded data and render thumbnail.
        var preview = document.getElementById("preview");
        if (preview) preview.src = e.target.result;
      };
      // read the image file as a data URL.
      reader.readAsDataURL(this.files[0]);
    });



    function selectall1(source,checkboxID) {
       ParentValue = $('.parent'+checkboxID).filter(':checked').length;
      if(ParentValue==1)
      {
           text = "Confirmation!\nTo check all click OK or Cancel.";
      }else{
           text = "Confirmation!\nTo uncheck all click OK or Cancel.";
      }
      if (confirm(text) == true) {
          //checkboxes = document.getElementsByClassName('child'+checkboxID);
          checkboxes = $('.child'+checkboxID);
          for(var i=0, n=checkboxes.length;i<n;i++) {
              checkboxes[i].checked = source.checked;
          }
      } else {
          if(ParentValue==1)
          {
              $('.parent'+checkboxID).prop('checked', false); // Unchecks it

          }else{
              $('.parent'+checkboxID).prop('checked', true); // Checks it
          }
      }
  }

  function selectallParent(source,checkboxID) {


        $('.parent'+checkboxID).prop('checked', true);
        $('.subMenu'+checkboxID).prop('checked', true);
  }

  function classActiveLeftMenu(className)
  {
      //alert('Hello');
    $("."+className).addClass("active");
  }

function is_twentyFour_Hour(checkBox,startTime,endTime){
    var ifchecked = $('.'+checkBox).filter(':checked').length;
    if(ifchecked==1)
    {
        $('#start_time').val('00:00');
        $( "#start_time" ).prop( "readonly", true );
        $('#end_time').val('23:59');
        $( "#end_time" ).prop( "readonly", true );
    }
    else
    {
        $('#start_time').val(startTime);
        $( "#start_time" ).prop( "readonly", false );
        $('#end_time').val(endTime);
        $( "#end_time" ).prop( "readonly", false );
    }
}

function is_current_date(checkBox,startTime,endTime){
    var ifchecked = $('.'+checkBox).filter(':checked').length;
    if(ifchecked==1)
    {
        let now = new Date();
        var d =  now.getDate();
        var m = (now.getMonth() + 1);
        if(d<10)
        {
            d = '0'+d;
        }
        if(m<10)
        {
            m = '0'+m;
        }
        let today = now.getFullYear()  + '-' + m + '-' + d;
        $('#start_date').val(today);
        $( "#start_date" ).prop( "readonly", true );
        $('#end_date').val(today);
        $( "#end_date" ).prop( "readonly", true );
    }
    else
    {
        $('#start_date').val(startTime);
        $( "#start_date" ).prop( "readonly", false );
        $('#end_date').val(endTime);
        $( "#end_date" ).prop( "readonly", false );
    }
}

  function selectall(source,checkboxID) {
    var ParentValue = $('.'+checkboxID).filter(':checked').length;
	
    /*if(ParentValue==1)
    {
        text = "Confirmation!\nTo check all click OK or Cancel.";
    }else{
            text = "Confirmation!\nTo uncheck all click OK or Cancel.";
    }
    if (confirm(text) == true){
        //checkboxes = document.getElementsByClassName('child'+checkboxID);
        var  checkboxes = $('.'+checkboxID);
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }else{
        if(ParentValue==1)
        {
            $('.'+checkboxID).prop('checked', false); // Unchecks it

        }else{
            $('.'+checkboxID).prop('checked', true); // Checks it
        }
    }*/
	if(source.checked === false)
        {	
            $('.'+checkboxID).prop('checked', false); // Unchecks it
        }else{
            $('.'+checkboxID).prop('checked', true); // Checks it
        }
}
