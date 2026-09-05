jQuery(function($) {
    var $queueList = $('#queue-list');
    var $form = $('#admin-queue-edit-form');
    var $saveButton = $form.find('button[type="submit"]');
    var $discardButton = $('#edpq-discard-queue-changes');
    var $fullWipeButton = $('#full-wipe-btn');
    var $status = $('#edpq-queue-status');
    var initialQueueSnapshot = readQueueSnapshot();
    var initialQueueHtml = $queueList.html();

    renumberQueue();
    setDirty(false, '');

    $(document).on('click', '.queue-up', function(e) {
        e.preventDefault();
        moveRow($(this).closest('.queue-row'), 'up');
    });

    $(document).on('click', '.queue-down', function(e) {
        e.preventDefault();
        moveRow($(this).closest('.queue-row'), 'down');
    });

    $(document).on('keydown', '.queue-row', function(e) {
        if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) {
            return;
        }

        e.preventDefault();
        moveRow($(this), e.key === 'ArrowUp' ? 'up' : 'down');
    });

    $(document).on('click', '.queue-delete', function(e) {
        e.preventDefault();
        if (window.confirm(edpq_admin_queue.confirmDelete)) {
            $(this).closest('.queue-row').remove();
            renumberQueue();
            setDirty(true, edpq_admin_queue.dirty);
        }
    });

    $discardButton.on('click', function() {
        $queueList.html(initialQueueHtml);
        renumberQueue();
        setDirty(false, edpq_admin_queue.clean);
    });

    $form.on('submit', function(e) {
        e.preventDefault();

        if (!$form.data('dirty')) {
            setStatus(edpq_admin_queue.clean, 'info');
            return;
        }

        setSaving(true);
        setStatus(edpq_admin_queue.saving, 'info');

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'admin_queue_edit',
                nonce: edpq_admin_queue.nonce,
                form_data: $form.serialize(),
                client_snapshot: JSON.stringify(initialQueueSnapshot)
            },
            success: function(response) {
                setSaving(false);
                if (response && response.success) {
                    initialQueueSnapshot = readQueueSnapshot();
                    initialQueueHtml = $queueList.html();
                    setDirty(false, edpq_admin_queue.saved);
                    return;
                }

                if (response && response.data && response.data.conflict) {
                    setStatus(edpq_admin_queue.conflict, 'warning');
                    return;
                }

                setStatus((response && response.data && response.data.message) || edpq_admin_queue.error, 'error');
            },
            error: function() {
                setSaving(false);
                setStatus(edpq_admin_queue.error, 'error');
            }
        });
    });

    $('#full-wipe-btn').on('click', function() {
        var confirmation = window.prompt(edpq_admin_queue.fullWipePrompt, '');
        if (confirmation !== 'FULL WIPE') {
            setStatus(edpq_admin_queue.fullWipeCancelled, 'info');
            return;
        }

        setFullWipeSaving(true);
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'admin_queue_full_wipe',
                nonce: edpq_admin_queue.nonce,
                confirmation: confirmation
            },
            success: function(response) {
                setFullWipeSaving(false);
                if (response && response.success) {
                    window.location.reload();
                } else {
                    setStatus((response && response.data && response.data.message) || edpq_admin_queue.error, 'error');
                }
            },
            error: function() {
                setFullWipeSaving(false);
                setStatus(edpq_admin_queue.error, 'error');
            }
        });
    });

    function moveRow($row, direction) {
        var $target = direction === 'up' ? $row.prev('.queue-row') : $row.next('.queue-row');
        if (!$target.length) {
            return;
        }

        if (direction === 'up') {
            $row.insertBefore($target);
        } else {
            $row.insertAfter($target);
        }

        renumberQueue();
        $row.trigger('focus');
        setDirty(true, edpq_admin_queue.dirty);
    }

    function readQueueSnapshot() {
        var snapshot = [];
        $queueList.find('.queue-row').each(function() {
            snapshot.push({
                postid: parseInt($(this).attr('data-postid'), 10),
                queueNumber: parseInt($(this).attr('data-queuenumber'), 10)
            });
        });
        return snapshot;
    }

    function renumberQueue() {
        var $rows = $queueList.find('.queue-row');
        $rows.each(function(index) {
            var newNumber = index + 1;
            var $row = $(this);
            var $badge = $row.find('.edpq-display-badge');
            $row.attr('data-queuenumber', newNumber);
            $row.find('.edpq-position').first().text(newNumber);
            $row.find('input[name^="queue-postID-"]').attr('name', 'queue-postID-' + newNumber);
            $row.find('input[name^="queue-value-"]').attr('name', 'queue-value-' + newNumber).val(newNumber);
            $row.find('.queue-up').prop('disabled', index === 0);
            $row.find('.queue-down').prop('disabled', index === $rows.length - 1);

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

    function setDirty(isDirty, message) {
        $form.data('dirty', isDirty);
        $saveButton.prop('disabled', !isDirty);
        $discardButton.prop('disabled', !isDirty);
        setStatus(message, isDirty ? 'warning' : 'success');
    }

    function setSaving(isSaving) {
        $form.toggleClass('edpq-is-saving', isSaving);
        $saveButton.prop('disabled', isSaving || !$form.data('dirty'));
        $discardButton.prop('disabled', isSaving || !$form.data('dirty'));
    }

    function setFullWipeSaving(isSaving) {
        $fullWipeButton.prop('disabled', isSaving);
        setSaving(isSaving);
    }

    function setStatus(message, type) {
        $status
            .removeClass('edpq-status-info edpq-status-success edpq-status-warning edpq-status-error')
            .addClass('edpq-status-' + type)
            .text(message || '');
    }
});