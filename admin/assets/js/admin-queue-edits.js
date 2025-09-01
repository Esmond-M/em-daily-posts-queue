jQuery(document).ready(function($) {
    // Move queue item up
    $(document).on('click', '.queue-up', function(e) {
        e.preventDefault();
        var row = $(this).closest('.queue-row');
        var prev = row.prev('.queue-row');
        if (prev.length) {
            row.insertBefore(prev);
            renumberQueue();
        }
    });

    // Move queue item down
    $(document).on('click', '.queue-down', function(e) {
        e.preventDefault();
        var row = $(this).closest('.queue-row');
        var next = row.next('.queue-row');
        if (next.length) {
            row.insertAfter(next);
            renumberQueue();
        }
    });

    // Delete queue item
    $(document).on('click', '.queue-delete', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this item?')) {
            $(this).closest('.queue-row').remove();
            renumberQueue();
        }
    });

    // Renumber queue rows and update hidden inputs
    function renumberQueue() {
        $('#queue-list .queue-row').each(function(index) {
            var newNumber = index + 1;
            $(this).attr('data-queuenumber', newNumber);
            $(this).find('.queue-title').text('Photo Submission #' + newNumber + ' (Post ID: ' + $(this).data('postid') + ')');
            $(this).find('input[name^="queue-postID-"]').attr('name', 'queue-postID-' + newNumber);
            $(this).find('input[name^="queue-value-"]').attr('name', 'queue-value-' + newNumber).val(newNumber);
        });
    }

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
    
        // Full Wipe button AJAX
        $('#full-wipe-btn').on('click', function() {
            if (!confirm('Are you sure? This will delete ALL queue data and ALL net_submission posts. This cannot be undone.')) return;
            var $form = $('#admin-queue-edit-form');
            $form.append('<div class="edpq-ajax-loader"></div>');
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'admin_queue_full_wipe'
                },
                success: function(response) {
                    $('.edpq-ajax-loader').remove();
                    if (response && response.success) {
                        alert('Full wipe completed!');
                        location.reload();
                    } else {
                        alert('Error during full wipe.');
                    }
                },
                error: function() {
                    $('.edpq-ajax-loader').remove();
                    alert('AJAX error.');
                }
            });
        });

});
