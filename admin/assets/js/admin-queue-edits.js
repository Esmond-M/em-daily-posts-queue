jQuery(document).ready(function($) {
    // Capture queue state as it was when the page loaded — used for optimistic concurrency
    var initialQueueSnapshot = [];
    $('#queue-list .queue-row').each(function() {
        initialQueueSnapshot.push({
            postid: parseInt($(this).attr('data-postid'), 10),
            queueNumber: parseInt($(this).attr('data-queuenumber'), 10)
        });
    });
    renumberQueue();

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
        // Always assign sequential queueNumber values (1,2,3...) to all items
        $('#queue-list .queue-row').each(function(index) {
            var newNumber = index + 1;
            var $row = $(this);
            var $badge = $row.find('.edpq-display-badge');
            $row.attr('data-queuenumber', newNumber);
            $row.find('.edpq-position').first().text(newNumber);
            $row.find('input[name^="queue-postID-"]').attr('name', 'queue-postID-' + newNumber);
            $row.find('input[name^="queue-value-"]').attr('name', 'queue-value-' + newNumber).val(newNumber);
            $row.find('.queue-up').prop('disabled', index === 0);
            $row.find('.queue-down').prop('disabled', index === $('#queue-list .queue-row').length - 1);

            $badge.removeClass('edpq-display-current');
            if (index === 0) {
                $badge.addClass('edpq-display-current').text(edpq_admin_queue.showingNow);
            } else if (index === 1) {
                $badge.text(edpq_admin_queue.upNext);
            } else {
                $badge.text(edpq_admin_queue.queued);
            }
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
                nonce: edpq_admin_queue.nonce,
                form_data: formData,
                client_snapshot: JSON.stringify(initialQueueSnapshot)
            },
            success: function(response) {
                $('.edpq-ajax-loader').remove();
                $form.find('button[type="submit"]').prop('disabled', false);
                if (response && response.success) {
                    alert('Queue updated successfully!');
                    location.reload();
                } else if (response && response.data && response.data.conflict) {
                    // Conflict detected: another user has changed the queue
                    var $conflict = $('<div class="edpq-conflict-warning">Another user has updated the queue. This page will refresh in 5 seconds.</div>');
                    $form.prepend($conflict);
                    setTimeout(function() { location.reload(); }, 5000);
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
                    action: 'admin_queue_full_wipe',
                    nonce: edpq_admin_queue.nonce
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
