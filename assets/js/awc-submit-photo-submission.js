jQuery(document).ready(function ($) {
jQuery( "#new_post" ).submit(function( event ) {
    event.preventDefault();
    jQuery("#new_post button").prop( "disabled", true ); // disable all form buttons
    jQuery("#new_post button input[type='submit']").prop( "disabled", true ); // disable all form buttons
    jQuery('#new_post').append('<div class="awc-ajax-loader"></div>'); // initial loader icon for all users request

    // Serialize the data in the form
    var formData = new FormData(jQuery("#new_post")[0]); 
    //console.log(serializedData);
        jQuery.ajax({
            type: "POST",
            url: ajax_form_post_new_net_photo_submission.ajaxurl_form_post_new_net_photo_submission,
            dataType: "html",
			processData: false,
			contentType: false,
            data: formData,
            success: function(responseText){ 
               setTimeout( // timeout function to transition from loader icon to content less abruptly
                    function() {
                            jQuery(".awc-ajax-loader").remove();
                            jQuery('#new_post').after(responseText); // initial loader icon for all users request
                            jQuery("#new_post").remove();
                            var $modal = jQuery(".newpost-success");
                            jQuery([window.top.document.documentElement, window.top.document.body]).animate({scrollTop: $modal.offset().top - ($modal.width() / 2)}, 500);
                    },
                    0
                );

          },
            error: function(jqXHR, textStatus, errorThrown) {
                jQuery(".awc-ajax-loader").remove();
				jQuery("#new_post button").prop( "disabled", false ); // enable all form buttons
				jQuery("#new_post input[type='submit']").prop( "disabled", false ); // enable all form buttons
                jQuery("#new_post").append('<div id="awc-connect-error">Connection Error</div>');

                console.log(JSON.stringify(jqXHR) + " :: " + textStatus + " :: " + errorThrown);
            }

        });// ajax end
});

});