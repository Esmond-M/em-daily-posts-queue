jQuery(document).ready(function ($) {

// Document ready: set up event handlers for queue management buttons

jQuery( ".up-btn" ).click(function(event) {
     // Handle click on 'up' button to move a queue item up
    event.preventDefault();
     // Fade out and remove delete buttons before re-enabling them
    jQuery('#edpq-send-new-queue-list .delete-btn').fadeOut(500, function() {
          jQuery('#edpq-send-new-queue-list .delete-btn').remove().fadeIn(500);
    });
     // Disable all form buttons during operation
    jQuery("#edpq-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons

     // Extract row and ID info from button classes
    var getBtnRowNumber = jQuery(this).attr('class').split(' ')[1];
    var BtnRowNumber = getBtnRowNumber.match(/\d+$/)[0];
    var getBtnID = jQuery(this).attr('class').split(' ')[2];
    var BtnID = getBtnID.match(/\d+$/)[0];
     // Get current input value and row info
    var CurrentRowInputIDValue = jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val();
    var CurrentRowNumber = parseInt(BtnRowNumber);
    var upperRowsNumber = String(CurrentRowNumber - 1);
    var currentRowTitle = jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).text();
    var upperRowTitle = jQuery('.edpq-row-' + upperRowsNumber + ' .edpq-title-' + upperRowsNumber).text();
     // Check if upper row exists and get its input ID value
    if( jQuery('.edpq-row-' + upperRowsNumber + ' .up-btn-queue-number-' + upperRowsNumber ).length ){
       var getUpperRowInputIDValue = jQuery('.edpq-row-' + upperRowsNumber + ' .up-btn-queue-number-' + upperRowsNumber ).attr('class').split(' ')[2];
       var UpperRowInputIDValue = getUpperRowInputIDValue.match(/\d+$/)[0];
    }
     // If upper row input ID is found, swap values, names, titles, and button classes
    if (typeof UpperRowInputIDValue !== 'undefined') {
     // I have all id and row numbers I need so now to switch the values of inputs
    jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(UpperRowInputIDValue);
    jQuery('.edpq-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').val(CurrentRowInputIDValue);

 // I have all id and row numbers I need so now to switch the name of inputs
    jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ UpperRowInputIDValue );
    jQuery('.edpq-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

 // I now need to change title for the Visual aspect of telling the user that things changed.
    jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).fadeOut(500, function() {
        jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).text(upperRowTitle).fadeIn(500);
    });
       jQuery('.edpq-row-' + upperRowsNumber + ' .edpq-title-' + upperRowsNumber).fadeOut(500, function() {
            jQuery('.edpq-row-' + upperRowsNumber + ' .edpq-title-' + upperRowsNumber).text(currentRowTitle).fadeIn(500);
       });

 // I now need to change the classes so the buttons can be reusable
    jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ UpperRowInputIDValue);

    jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
    jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
    jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

    jQuery('.edpq-row-' + upperRowsNumber + ' .up-btn-ID-'+ UpperRowInputIDValue).addClass('up-btn-ID-'+ BtnID);
    jQuery('.edpq-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).addClass('dwn-btn-ID-'+ BtnID);
    jQuery('.edpq-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

    jQuery('.edpq-row-' + upperRowsNumber + ' .up-btn-ID-'+ UpperRowInputIDValue).removeClass('up-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.edpq-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).removeClass('dwn-btn-ID-'+ UpperRowInputIDValue);
    jQuery('.edpq-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).removeClass('delete-btn-ID-'+ UpperRowInputIDValue);

    jQuery("#edpq-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
    jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons

    }


    if (typeof UpperRowInputIDValue == 'undefined') {
         // If upper row input ID is not found, try alternate selector and swap values, names, titles, and button classes
       var getUpperRowInputIDValue = jQuery('.edpq-row-' + upperRowsNumber + ' .dwn-btn-queue-number-' + upperRowsNumber ).attr('class').split(' ')[2];
       var UpperRowInputIDValue = getUpperRowInputIDValue.match(/\d+$/)[0];
       // I have all id and row numbers I need so now to switch the values of inputs
       jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(UpperRowInputIDValue);
       jQuery('.edpq-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').val(CurrentRowInputIDValue);

       // I have all id and row numbers I need so now to switch the name of inputs
       jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ UpperRowInputIDValue );
       jQuery('.edpq-row-' + upperRowsNumber + ' input[name="queue-postID-'+ UpperRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

       // I now need to change title for the Visual aspect of telling the user that things changed.
       jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).fadeOut(500, function() {
             jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).text(upperRowTitle).fadeIn(500);
       });
       jQuery('.edpq-row-' + upperRowsNumber + ' .edpq-title-' + upperRowsNumber).fadeOut(500, function() {
            jQuery('.edpq-row-' + upperRowsNumber + ' .edpq-title-' + upperRowsNumber).text(currentRowTitle).fadeIn(500);
       });

       // I now need to change the classes so the buttons can be reusable
      jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ UpperRowInputIDValue);
      jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ UpperRowInputIDValue);
      jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ UpperRowInputIDValue);

      jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
      jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
      jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

      jQuery('.edpq-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).addClass('dwn-btn-ID-'+ BtnID);
      jQuery('.edpq-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

      jQuery('.edpq-row-' + upperRowsNumber + ' .dwn-btn-ID-'+ UpperRowInputIDValue).removeClass('dwn-btn-ID-'+ UpperRowInputIDValue);
      jQuery('.edpq-row-' + upperRowsNumber + ' .delete-btn-ID-'+ UpperRowInputIDValue).removeClass('delete-btn-ID-'+ UpperRowInputIDValue);

      jQuery("#edpq-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
      jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons

    }



});
jQuery( ".dwn-btn" ).click(function(event) {
     // Handle click on 'down' button to move a queue item down
    event.preventDefault();
     // Fade out and remove delete buttons before re-enabling them
    jQuery('#edpq-send-new-queue-list .delete-btn').fadeOut(500, function() {
          jQuery('#edpq-send-new-queue-list .delete-btn').remove().fadeIn(500);
    });
     // Disable all form buttons during operation
    jQuery("#edpq-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons

     // Extract row and ID info from button classes
    var getBtnRowNumber = jQuery(this).attr('class').split(' ')[1];
    var BtnRowNumber = getBtnRowNumber.match(/\d+$/)[0];
    var getBtnID = jQuery(this).attr('class').split(' ')[2];
    var BtnID = getBtnID.match(/\d+$/)[0];
     // Get current input value and row info
    var CurrentRowInputIDValue = jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val();
    var CurrentRowNumber = parseInt(BtnRowNumber);
    var lowerRowsNumber = String(CurrentRowNumber + 1);
    var currentRowTitle = jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).text();
    var lowerRowTitle = jQuery('.edpq-row-' + lowerRowsNumber + ' .edpq-title-' + lowerRowsNumber).text();

     // Check if lower row exists and get its input ID value
    if( jQuery('.edpq-row-' + lowerRowsNumber + ' .up-btn-queue-number-' + lowerRowsNumber ).length ){
       var getlowerRowInputIDValue = jQuery('.edpq-row-' + lowerRowsNumber + ' .up-btn-queue-number-' + lowerRowsNumber ).attr('class').split(' ')[2];
       var lowerRowInputIDValue = getlowerRowInputIDValue.match(/\d+$/)[0];
    }
     // If lower row input ID is found, swap values, names, titles, and button classes
    if (typeof lowerRowInputIDValue !== 'undefined') {
       // I have all id and row numbers I need so now to switch the values of inputs
      jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(lowerRowInputIDValue);
      jQuery('.edpq-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').val(CurrentRowInputIDValue);

     // I have all id and row numbers I need so now to switch the name of inputs
      jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ lowerRowInputIDValue );
      jQuery('.edpq-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

     // I now need to change title for the Visual aspect of telling the user that things changed.
      jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).fadeOut(500, function() {
             jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).text(lowerRowTitle).fadeIn(500);
       });
      jQuery('.edpq-row-' + lowerRowsNumber + ' .edpq-title-' + lowerRowsNumber).fadeOut(500, function() {
            jQuery('.edpq-row-' + lowerRowsNumber + ' .edpq-title-' + lowerRowsNumber).text(currentRowTitle).fadeIn(500);
       });

     // I now need to change the classes so the buttons can be reusable
     jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ lowerRowInputIDValue);

     jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
     jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
     jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

     jQuery('.edpq-row-' + lowerRowsNumber + ' .up-btn-ID-'+ lowerRowInputIDValue).addClass('up-btn-ID-'+ BtnID);
     jQuery('.edpq-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).addClass('dwn-btn-ID-'+ BtnID);
     jQuery('.edpq-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

     jQuery('.edpq-row-' + lowerRowsNumber + ' .up-btn-ID-'+ lowerRowInputIDValue).removeClass('up-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.edpq-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).removeClass('dwn-btn-ID-'+ lowerRowInputIDValue);
     jQuery('.edpq-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).removeClass('delete-btn-ID-'+ lowerRowInputIDValue);

     jQuery("#edpq-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
     jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons

    }


    if (typeof lowerRowInputIDValue == 'undefined') {
         // If lower row input ID is not found, try alternate selector and swap values, names, titles, and button classes
       var getlowerRowInputIDValue = jQuery('.edpq-row-' + lowerRowsNumber + ' .up-btn-queue-number-' + lowerRowsNumber ).attr('class').split(' ')[2];
       var lowerRowInputIDValue = getlowerRowInputIDValue.match(/\d+$/)[0];
       // I have all id and row numbers I need so now to switch the values of inputs
       jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').val(lowerRowInputIDValue);
       jQuery('.edpq-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').val(CurrentRowInputIDValue);

       // I have all id and row numbers I need so now to switch the name of inputs
       jQuery('.edpq-row-' + BtnRowNumber + ' input[name="queue-postID-'+ BtnID +'"]').attr( 'name', 'queue-postID-'+ lowerRowInputIDValue );
       jQuery('.edpq-row-' + lowerRowsNumber + ' input[name="queue-postID-'+ lowerRowInputIDValue +'"]').attr( 'name', 'queue-postID-'+ CurrentRowInputIDValue );

       // I now need to change title for the Visual aspect of telling the user that things changed.
       jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).fadeOut(500, function() {
             jQuery('.edpq-row-' + BtnRowNumber + ' .edpq-title-' + BtnRowNumber).text(lowerRowTitle).fadeIn(500);
       });
       jQuery('.edpq-row-' + lowerRowsNumber + ' .edpq-title-' + lowerRowsNumber).fadeOut(500, function() {
            jQuery('.edpq-row-' + lowerRowsNumber + ' .edpq-title-' + lowerRowsNumber).text(currentRowTitle).fadeIn(500);
        });

       // I now need to change the classes so the buttons can be reusable
       jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).addClass('up-btn-ID-'+ lowerRowInputIDValue);
       jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).addClass('dwn-btn-ID-'+ lowerRowInputIDValue);
       jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).addClass('delete-btn-ID-'+ lowerRowInputIDValue);

       jQuery('.edpq-row-' + BtnRowNumber + ' .up-btn-ID-'+ BtnID).removeClass('up-btn-ID-'+ BtnID);
       jQuery('.edpq-row-' + BtnRowNumber + ' .dwn-btn-ID-'+ BtnID).removeClass('dwn-btn-ID-'+ BtnID);
       jQuery('.edpq-row-' + BtnRowNumber + ' .delete-btn-ID-'+ BtnID).removeClass('delete-btn-ID-'+ BtnID);

       jQuery('.edpq-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).addClass('up-btn-ID-'+ BtnID);
       jQuery('.edpq-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).addClass('delete-btn-ID-'+ BtnID);

       jQuery('.edpq-row-' + lowerRowsNumber + ' .dwn-btn-ID-'+ lowerRowInputIDValue).removeClass('up-btn-ID-'+ lowerRowInputIDValue);
       jQuery('.edpq-row-' + lowerRowsNumber + ' .delete-btn-ID-'+ lowerRowInputIDValue).removeClass('delete-btn-ID-'+ lowerRowInputIDValue);

       jQuery("#edpq-send-new-queue-list button").prop( "disabled", false ); // disable all form buttons
       jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", false ); // disable all form buttons


    }

});
jQuery( ".delete-btn" ).click(function(event) {
     // Handle click on 'delete' button to remove a queue item
    event.preventDefault();
     // Confirm deletion with user
    if (confirm("Are you sure you want to delete?") == true){
     // Disable all form buttons during operation
    jQuery("#edpq-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons

     // Extract row and ID info from button classes
    var getBtnID = jQuery(this).attr('class').split(' ')[2];
    var BtnID = getBtnID.match(/\d+$/)[0];
    var getBtnRowNumber = jQuery(this).attr('class').split(' ')[1];
    var BtnRowNumber = getBtnRowNumber.match(/\d+$/)[0];
     // Hide the row and append hidden inputs for deletion info
    jQuery('.edpq-row-' + BtnRowNumber + '').hide();
    jQuery('input[value="net_photo_deletion_info_ajax"]').append('<input type="hidden" name="remove_postid" value="'+ BtnID + '">');
    jQuery('input[value="net_photo_deletion_info_ajax"]').append('<input type="hidden" name="remove_queue" value="'+ BtnRowNumber + '">');
     // Submit the form to process deletion
   jQuery( "#edpq-send-new-queue-list" ).submit();
    }

});


jQuery( "#edpq-send-new-queue-list" ).submit(function( event ) {
     // Handle form submission for queue changes or deletions
    event.preventDefault();
     // Disable all form buttons and show loader
    jQuery("#edpq-send-new-queue-list button").prop( "disabled", true ); // disable all form buttons
    jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", true ); // disable all form buttons
    jQuery('#edpq-send-new-queue-list').append('<div class="edpq-ajax-loader"></div>'); // initial loader icon for all users request

    // Serialize the data in the form
    var serializedData = jQuery('#edpq-send-new-queue-list' ).serialize()
    //console.log(serializedData);
          // Send AJAX request to server for queue update or deletion
        jQuery.ajax({
            type: "POST",
            url: ajax_net_photo_deletion_info.ajaxurl_net_photo_deletion_info,
            dataType: "html",
            data: serializedData,
            success: function(responseText){
                     // On success, remove loader and show response, then redirect after 3 seconds
                setTimeout( // timeout function to transition from loader icon to content less abruptly
                    function() {
                        jQuery(".edpq-ajax-loader").remove();
                        jQuery('#edpq-send-new-queue-list').append(responseText);
                    },
                    0
                );
                setTimeout(function () {
                    var basePath = window.location.origin + window.location.pathname.split('/wp-content')[0];
                    if (!basePath.endsWith('/')) basePath += '/';
                    var adminUrl = basePath + 'wp-admin/edit.php?post_type=net_submission&page=edit_net_submissions';
                    window.location.href = adminUrl;
                }, 3000); // 3 seconds


          },
            error: function(jqXHR, textStatus, errorThrown) {
                     // On error, remove loader, re-enable buttons, and show error message
                jQuery(".edpq-ajax-loader").remove();
                jQuery("#edpq-send-new-queue-list button").prop( "disabled", false ); // enable all form buttons
                jQuery("#edpq-send-new-queue-list input[type='submit']").prop( "disabled", false ); // enable all form buttons
                jQuery("#edpq-send-new-queue-list").append('<div id="edpq-connect-error">Connection Error</div>');

                console.log(JSON.stringify(jqXHR) + " :: " + textStatus + " :: " + errorThrown);
            }

        });// ajax end
});

});