jQuery(document).ready(function ($) {
    var $form = $("#new_post");
    var $loader = $(".edpq-ajax-loader");

    $form.submit(function (event) {
        event.preventDefault();
        $form.find("button, input[type='submit']").prop("disabled", true);
        $loader.css("display", "block");

        // Remove any previous error message
        $("#edpq-connect-error").remove();

        var formData = new FormData($form[0]);
        $.ajax({
            type: "POST",
            url: ajax_form_post_new_net_photo_submission.ajaxurl_form_post_new_net_photo_submission,
            dataType: "html",
            processData: false,
            contentType: false,
            data: formData,
            success: function (responseText) {
                setTimeout(function () {
                    $loader.css("display", "none");
                    $form.after(responseText);
                    $form.remove();
                    var $modal = $(".newpost-success");
                    if ($modal.length > 0) {
                        $([window.top.document.documentElement, window.top.document.body])
                            .animate({ scrollTop: $modal.offset().top - ($modal.width() / 2) }, 500);
                    }
                }, 0);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $loader.css("display", "none");
                $form.find("button, input[type='submit']").prop("disabled", false);
                $form.append('<div id="edpq-connect-error">Connection Error</div>');
                console.log(JSON.stringify(jqXHR) + " :: " + textStatus + " :: " + errorThrown);
            }
        });
    });
});