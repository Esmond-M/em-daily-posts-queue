jQuery(document).ready(function ($) {

jQuery( ".up-btn" ).click(function(event) {
    event.preventDefault();
	jQuery('#awc-send-new-queue-list .delete-btn').fadeOut(500, function() {
	  	jQuery('#awc-send-new-queue-list .delete-btn').remove().fadeIn(500);
	});
    jQuery("#awc-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons

	var getBtnRowNumber = jQuery(this).attr('class').split(' ')[1];
    var BtnRowNumber = getBtnRowNumber.match(/\d+$/)[0];
	var getBtnID = jQuery(this).attr('class').split(' ')[2];
    var BtnID = getBtnID.match(/\d+$/)[0];
    var CurrentRowInputIDValue = jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val();
    var CurrentRowNumber = parseInt(BtnRowNumber);
    var upperRowsNumber = String(CurrentRowNumber - 1);
    var currentRowTitle = jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).text();
    var upperRowTitle = jQuery('.awc-row-' + upperRowsNumber + ' .awc-title-' + upperRowsNumber).text();
    if( jQuery('.awc-row-' + upperRowsNumber + ' .up-btn-queue-number-' + upperRowsNumber ).length ){
	   var getUpperRowInputIDValue = jQuery('.awc-row-' + upperRowsNumber + ' .up-btn-queue-number-' + upperRowsNumber ).attr('class').split(' ')[2];
       var UpperRowInputIDValue = getUpperRowInputIDValue.match(/\d+$/)[0];
    }
	if (typeof UpperRowInputIDValue !== 'undefined') {
	 // I have all id and row numbers I need so now to switch the values of inputs
    jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(UpperRowInputIDValue);
    jQuery('.awc-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').val(CurrentRowInputIDValue);

 // I have all id and row numbers I need so now to switch the name of inputs
    jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ UpperRowInputIDValue );
    jQuery('.awc-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

 // I now need to change title for the Visual aspect of telling the user that things changed.
    jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).fadeOut(500, function() {
        jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).text(upperRowTitle).fadeIn(500);
    });
	   jQuery('.awc-row-' + upperRowsNumber + ' .awc-title-' + upperRowsNumber).fadeOut(500, function() {
			jQuery('.awc-row-' + upperRowsNumber + ' .awc-title-' + upperRowsNumber).text(currentRowTitle).fadeIn(500);
	   });

 // I now need to change the classes so the buttons can be reusable
    jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ UpperRowInputIDValue);

    jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
    jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
    jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

    jQuery('.awc-row-' + upperRowsNumber + ' .up-btn-ID-'+ UpperRowInputIDValue).addClass('up-btn-ID-'+ BtnID);
    jQuery('.awc-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).addClass('dwn-btn-ID-'+ BtnID);
    jQuery('.awc-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

    jQuery('.awc-row-' + upperRowsNumber + ' .up-btn-ID-'+ UpperRowInputIDValue).removeClass('up-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.awc-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).removeClass('dwn-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.awc-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).removeClass('delete-btn-ID-'+ UpperRowInputIDValue);

    jQuery("#awc-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
    jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons	

	}


	if (typeof UpperRowInputIDValue == 'undefined') {
	   var getUpperRowInputIDValue = jQuery('.awc-row-' + upperRowsNumber + ' .dwn-btn-queue-number-' + upperRowsNumber ).attr('class').split(' ')[2];
       var UpperRowInputIDValue = getUpperRowInputIDValue.match(/\d+$/)[0];
	   // I have all id and row numbers I need so now to switch the values of inputs
       jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(UpperRowInputIDValue);
       jQuery('.awc-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').val(CurrentRowInputIDValue);

       // I have all id and row numbers I need so now to switch the name of inputs
       jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ UpperRowInputIDValue );
       jQuery('.awc-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

       // I now need to change title for the Visual aspect of telling the user that things changed.
	   jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).fadeOut(500, function() {
	     	jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).text(upperRowTitle).fadeIn(500);
	   });
	   jQuery('.awc-row-' + upperRowsNumber + ' .awc-title-' + upperRowsNumber).fadeOut(500, function() {
			jQuery('.awc-row-' + upperRowsNumber + ' .awc-title-' + upperRowsNumber).text(currentRowTitle).fadeIn(500);
	   });

       // I now need to change the classes so the buttons can be reusable
      jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ UpperRowInputIDValue); 
      jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ UpperRowInputIDValue);
      jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ UpperRowInputIDValue);

      jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);  
      jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
      jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

      jQuery('.awc-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).addClass('dwn-btn-ID-'+ BtnID);
      jQuery('.awc-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

      jQuery('.awc-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).removeClass('dwn-btn-ID-'+ UpperRowInputIDValue);
      jQuery('.awc-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).removeClass('delete-btn-ID-'+ UpperRowInputIDValue);

      jQuery("#awc-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
      jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons
	
	}



});
jQuery( ".dwn-btn" ).click(function(event) {
    event.preventDefault();
	jQuery('#awc-send-new-queue-list .delete-btn').fadeOut(500, function() {
	  	jQuery('#awc-send-new-queue-list .delete-btn').remove().fadeIn(500);
	});
    jQuery("#awc-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons

	var getBtnRowNumber = jQuery(this).attr('class').split(' ')[1];
    var BtnRowNumber = getBtnRowNumber.match(/\d+$/)[0];
	var getBtnID = jQuery(this).attr('class').split(' ')[2];
    var BtnID = getBtnID.match(/\d+$/)[0];
    var CurrentRowInputIDValue = jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val();
    var CurrentRowNumber = parseInt(BtnRowNumber);
    var lowerRowsNumber = String(CurrentRowNumber + 1);
    var currentRowTitle = jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).text();
    var lowerRowTitle = jQuery('.awc-row-' + lowerRowsNumber + ' .awc-title-' + lowerRowsNumber).text();
     
    if( jQuery('.awc-row-' + lowerRowsNumber + ' .up-btn-queue-number-' + lowerRowsNumber ).length ){
	   var getlowerRowInputIDValue = jQuery('.awc-row-' + lowerRowsNumber + ' .up-btn-queue-number-' + lowerRowsNumber ).attr('class').split(' ')[2];
       var lowerRowInputIDValue = getlowerRowInputIDValue.match(/\d+$/)[0];
    }
	if (typeof lowerRowInputIDValue !== 'undefined') {
	   // I have all id and row numbers I need so now to switch the values of inputs
      jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(lowerRowInputIDValue);
      jQuery('.awc-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').val(CurrentRowInputIDValue);

     // I have all id and row numbers I need so now to switch the name of inputs
      jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ lowerRowInputIDValue );
      jQuery('.awc-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

     // I now need to change title for the Visual aspect of telling the user that things changed.
	  jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).fadeOut(500, function() {
	     	jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).text(lowerRowTitle).fadeIn(500);
	   });
	  jQuery('.awc-row-' + lowerRowsNumber + ' .awc-title-' + lowerRowsNumber).fadeOut(500, function() {
			jQuery('.awc-row-' + lowerRowsNumber + ' .awc-title-' + lowerRowsNumber).text(currentRowTitle).fadeIn(500);
	   });

     // I now need to change the classes so the buttons can be reusable
     jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ lowerRowInputIDValue);

     jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
     jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
     jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

     jQuery('.awc-row-' + lowerRowsNumber + ' .up-btn-ID-'+ lowerRowInputIDValue).addClass('up-btn-ID-'+ BtnID);
     jQuery('.awc-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).addClass('dwn-btn-ID-'+ BtnID);
     jQuery('.awc-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

     jQuery('.awc-row-' + lowerRowsNumber + ' .up-btn-ID-'+ lowerRowInputIDValue).removeClass('up-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.awc-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).removeClass('dwn-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.awc-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).removeClass('delete-btn-ID-'+ lowerRowInputIDValue);

     jQuery("#awc-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
     jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons	

	}


	if (typeof lowerRowInputIDValue == 'undefined') {
	   var getlowerRowInputIDValue = jQuery('.awc-row-' + lowerRowsNumber + ' .up-btn-queue-number-' + lowerRowsNumber ).attr('class').split(' ')[2];
       var lowerRowInputIDValue = getlowerRowInputIDValue.match(/\d+$/)[0];
	   // I have all id and row numbers I need so now to switch the values of inputs
       jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(lowerRowInputIDValue);
       jQuery('.awc-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').val(CurrentRowInputIDValue);

       // I have all id and row numbers I need so now to switch the name of inputs
       jQuery('.awc-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ lowerRowInputIDValue );
       jQuery('.awc-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

       // I now need to change title for the Visual aspect of telling the user that things changed.
	   jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).fadeOut(500, function() {
	     	jQuery('.awc-row-' + BtnRowNumber + ' .awc-title-' + BtnRowNumber).text(lowerRowTitle).fadeIn(500);
	   });
	   jQuery('.awc-row-' + lowerRowsNumber + ' .awc-title-' + lowerRowsNumber).fadeOut(500, function() {
			jQuery('.awc-row-' + lowerRowsNumber + ' .awc-title-' + lowerRowsNumber).text(currentRowTitle).fadeIn(500);
	    });

       // I now need to change the classes so the buttons can be reusable
       jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ lowerRowInputIDValue);
       jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ lowerRowInputIDValue);
       jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ lowerRowInputIDValue);

       jQuery('.awc-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
       jQuery('.awc-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
       jQuery('.awc-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

       jQuery('.awc-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).addClass('up-btn-ID-'+ BtnID);
       jQuery('.awc-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

       jQuery('.awc-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).removeClass('up-btn-ID-'+ lowerRowInputIDValue);
       jQuery('.awc-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).removeClass('delete-btn-ID-'+ lowerRowInputIDValue);

       jQuery("#awc-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
       jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons

	
	}

});
jQuery( ".delete-btn" ).click(function(event) {
    event.preventDefault();
    if (confirm("Are you sure you want to delete?") == true){
    jQuery("#awc-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons

	var getBtnID = jQuery(this).attr('class').split(' ')[2];
    var BtnID = getBtnID.match(/\d+$/)[0];
	var getBtnRowNumber = jQuery(this).attr('class').split(' ')[1];
    var BtnRowNumber = getBtnRowNumber.match(/\d+$/)[0];
    jQuery('.awc-row-' + BtnRowNumber + '').hide(); 
    jQuery('input[value="net_photo_deletion_info_ajax"]').append('<input type="hidden" name="remove_postid" value="'+ BtnID + '">'); 
    jQuery('input[value="net_photo_deletion_info_ajax"]').append('<input type="hidden" name="remove_queue" value="'+ BtnRowNumber + '">'); 
   jQuery( "#awc-send-new-queue-list" ).submit();
    }

});


jQuery( "#awc-send-new-queue-list" ).submit(function( event ) {
    event.preventDefault();
    jQuery("#awc-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons
    jQuery('#awc-send-new-queue-list').append('<div class="awc-ajax-loader"></div>'); // initial loader icon for all users request

    // Serialize the data in the form
    var serializedData = jQuery('#awc-send-new-queue-list' ).serialize()
    //console.log(serializedData);
        jQuery.ajax({
            type: "POST",
            url: ajax_net_photo_deletion_info.ajaxurl_net_photo_deletion_info,
            dataType: "html",
            data: serializedData,
            success: function(responseText){ 
               setTimeout( // timeout function to transition from loader icon to content less abruptly
                    function() {
                            jQuery(".awc-ajax-loader").remove();
                            jQuery('#awc-send-new-queue-list').append(responseText); // initial loader icon for all users request
                    },
                    0
                );
				setTimeout(function () {
					window.location.href= 'https://wp.awc-inc.com/wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions'; // the redirect goes here

				},3000); // 5 seconds


          },
            error: function(jqXHR, textStatus, errorThrown) {
                jQuery(".awc-ajax-loader").remove();
				jQuery("#awc-send-new-queue-list button").prop( "disabled", false ); // enable all form buttons
				jQuery("#awc-send-new-queue-list input[type='submit']").prop( "disabled", false ); // enable all form buttons
                jQuery("#awc-send-new-queue-list").append('<div id="awc-connect-error">Connection Error</div>');

                console.log(JSON.stringify(jqXHR) + " :: " + textStatus + " :: " + errorThrown);
            }

        });// ajax end
});

});