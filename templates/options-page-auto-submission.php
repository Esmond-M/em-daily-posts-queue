<form id="edpq-send-new-queue-list" method="POST" action="">
<table class="queue-list-table">
  <thead>
    <tr>
      <th>Order</th>
      <th>Title</th>
      <th>Moveup</th>
      <th>Movedown</th>
      <th>Remove</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($queue_list) && is_array($queue_list)): ?>
      <?php foreach ($queue_list as $i => $item):
        $table_postID = $item['postid'];
        $table_postQueueNumber = $item['queueNumber'];
      ?>
      <tr class="edpq-row-<?php echo $table_postQueueNumber; ?>">
        <td>
          <?php echo $table_postQueueNumber; ?>
          <input type="hidden" value="<?php echo $table_postQueueNumber; ?>" name="queue-value-<?php echo $table_postQueueNumber; ?>" required/>
          <input type="hidden" value="<?php echo $table_postID; ?>" name="queue-postID-<?php echo $table_postID; ?>" required/>
        </td>
        <td class="edpq-title-<?php echo $table_postQueueNumber; ?>"><?php echo get_the_title($table_postID); ?></td>
        <td>
          <?php if ($i > 1): ?>
            <button class="up-btn up-btn-queue-number-<?php echo $table_postQueueNumber; ?> up-btn-ID-<?php echo $table_postID; ?>">up</button>
          <?php endif; ?>
        </td>
        <td>
          <?php if (count($queue_list) > 1 && $i !== array_key_last($queue_list) && $i !== array_key_first($queue_list)): ?>
            <button class="dwn-btn dwn-btn-queue-number-<?php echo $table_postQueueNumber; ?> dwn-btn-ID-<?php echo $table_postID; ?>">down</button>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($i !== array_key_first($queue_list)): ?>
            <button class="delete-btn delete-btn-queue-number-<?php echo $table_postQueueNumber; ?> delete-btn-ID-<?php echo $table_postID; ?>">delete</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="5">No photo submissions to display in the queue list.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>
<?php wp_nonce_field('update-photo-queue-list'); ?>
<?php if (!empty($queue_list) && is_array($queue_list)): ?>
  <input type="hidden" name="checkWindowAge" value='<?php echo json_encode($queue_list); ?>' />
  <input type="hidden" name="action" value="net_photo_deletion_info_ajax" />
  <input type="submit" value="submit">
<?php endif; ?>
</form>