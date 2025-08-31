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
                $form.append(`
                    <div class="edpq-success-message edpq-error-message">
                        <svg class="edpq-success-icon" width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="16" fill="#dc3545"/>
                            <path d="M10 22L22 10" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 22L10 10" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h3>Submission Failed</h3>
                        <p>We couldn't submit your photo due to a connection error.<br>
                        <strong>Troubleshooting tips:</strong></p>
                        <ul style="margin: 0 0 1em 1.5em; color: #dc3545;">
                            <li>Check your internet connection.</li>
                            <li>Try reloading the page and submitting again.</li>
                            <li>If the problem persists, contact support or try later.</li>
                        </ul>
                        <a href="" class="edpq-success-home-btn" onclick="location.reload();return false;">Try Again</a>
                        <a href="/" class="edpq-success-home-btn" style="margin-left:10px;">Return to Homepage</a>
                        <div style="font-size:0.9em;color:#888;margin-top:1em;">Error details: <code>${textStatus}</code></div>
                    </div>
                `);
                console.log(JSON.stringify(jqXHR) + " :: " + textStatus + " :: " + errorThrown);
            }
        });
    });
});