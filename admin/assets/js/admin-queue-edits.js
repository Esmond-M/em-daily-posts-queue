jQuery(document).ready(function($) {
    // Move queue item up
    $(document).on('click', '.queue-up', function(e) {
        e.preventDefault();
        var row = $(this).closest('.queue-row');
        var prev = row.prev('.queue-row');
        if (prev.length) {
            row.insertBefore(prev);
        }
    });

    // Move queue item down
    $(document).on('click', '.queue-down', function(e) {
        e.preventDefault();
        var row = $(this).closest('.queue-row');
        var next = row.next('.queue-row');
        if (next.length) {
            row.insertAfter(next);
        }
    });

    // Delete queue item
    $(document).on('click', '.queue-delete', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this item?')) {
            $(this).closest('.queue-row').remove();
        }
    });
});
    // AJAX form submission for saving queue order
    $('#admin-queue-edit-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = $form.serialize();
        $form.find('button[type="submit"]').prop('disabled', true);
        $form.append('<div class="edpq-ajax-loader"></div>');
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'admin_queue_edit',
                form_data: formData
            },
            success: function(response) {
                $('.edpq-ajax-loader').remove();
                $form.find('button[type="submit"]').prop('disabled', false);
                if (response && response.success) {
                    alert('Queue updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating queue.');
                }
            },
            error: function() {
                $('.edpq-ajax-loader').remove();
                $form.find('button[type="submit"]').prop('disabled', false);
                alert('AJAX error.');
            }
        });
    });
