<!--
  Admin Queue List Template
  Displays an admin view of the photo submission queue list with specific post IDs and their order.
  This view does not allow editing or reordering the queue.
-->
<!-- Queue List Table (Admin View Only) -->
<table>
  <thead>
    <!-- Table Headers -->
    <tr>
      <th>Post ID</th> <!-- Unique post identifier -->
      <th>Order #</th> <!-- Queue position -->
    </tr>
  </thead>
  <tbody>
    <!-- Table Body: List each photo submission in the queue -->
  <?php if (isset($queue_list['error'])): ?>
      <!-- Display error message if queue retrieval failed -->
      <tr>
        <td colspan="2"><?php echo htmlspecialchars($queue_list['error']); ?></td>
      </tr>
    <?php elseif (!empty($queue_list)): ?>
      <?php foreach ($queue_list as $item): ?>
        <!-- Display each post ID and its queue order -->
        <tr>
          <td><?php echo htmlspecialchars($item['postid']); ?></td>
          <td><?php echo htmlspecialchars($item['queueNumber']); ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- No submissions message -->
      <tr>
        <td colspan="2">No photo submissions to display in the queue list.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>