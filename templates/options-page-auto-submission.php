<!--
  Photo Submission Queue List Template
  Allows assigned users to reorder, view, and delete items from the queue list.
  Users can change the order of submissions and remove items as needed.
-->
<!-- Photo Submission Queue List Form -->
<form id="edpq-send-new-queue-list" method="POST" action="">
  <!-- Queue List Table -->
<table class="queue-list-table">
  <thead>
    <!-- Table Headers -->
    <tr>
      <th>Order</th> <!-- Queue position -->
      <th>Title</th> <!-- Post title -->
      <th>Moveup</th> <!-- Move item up -->
      <th>Movedown</th> <!-- Move item down -->
      <th>Remove</th> <!-- Remove item from queue -->
    </tr>
  </thead>
  <tbody>
    <!-- Table Body: List each photo submission in the queue -->
  <?php if (!empty($queue_list) && is_array($queue_list)): ?>
      <?php foreach ($queue_list as $i => $item):
        // Extract post ID and queue number for each item
        $table_postID = $item['postid'];
        $table_postQueueNumber = $item['queueNumber'];
      ?>
  <tr class="edpq-row-<?php echo $table_postQueueNumber; ?>">
        <td>
          <!-- Queue number and hidden fields for form submission -->
          <?php echo $table_postQueueNumber; ?>
          <input type="hidden" value="<?php echo $table_postQueueNumber; ?>" name="queue-value-<?php echo $table_postQueueNumber; ?>" required/>
          <input type="hidden" value="<?php echo $table_postID; ?>" name="queue-postID-<?php echo $table_postID; ?>" required/>
        </td>
  <td class="edpq-title-<?php echo $table_postQueueNumber; ?>"><?php echo get_the_title($table_postID); ?></td>
        <td>
          <!-- Move up button (not for first item) -->
          <?php if ($i > 1): ?>
            <button class="up-btn up-btn-queue-number-<?php echo $table_postQueueNumber; ?> up-btn-ID-<?php echo $table_postID; ?>">up</button>
          <?php endif; ?>
        </td>
        <td>
          <!-- Move down button (not for last or first item) -->
          <?php if (count($queue_list) > 1 && $i !== array_key_last($queue_list) && $i !== array_key_first($queue_list)): ?>
            <button class="dwn-btn dwn-btn-queue-number-<?php echo $table_postQueueNumber; ?> dwn-btn-ID-<?php echo $table_postID; ?>">down</button>
          <?php endif; ?>
        </td>
        <td>
          <!-- Delete button (not for first item) -->
          <?php if ($i !== array_key_first($queue_list)): ?>
            <button class="delete-btn delete-btn-queue-number-<?php echo $table_postQueueNumber; ?> delete-btn-ID-<?php echo $table_postID; ?>">delete</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
    </tbody>
</table>
<?php if (empty($queue_list) || !is_array($queue_list)): ?>
  <div class="edpq-no-submissions-message">No photo submissions to display in the queue list.</div>
<?php endif; ?>
<!-- Security nonce and hidden fields for AJAX -->
<?php wp_nonce_field('update-photo-queue-list'); ?>
<?php if (!empty($queue_list) && is_array($queue_list)): ?>
  <input type="hidden" name="checkWindowAge" value='<?php echo json_encode($queue_list); ?>' />
  <input type="hidden" name="action" value="net_photo_deletion_info_ajax" />
  <input type="submit" value="submit">
<?php endif; ?>
</form>
</form>