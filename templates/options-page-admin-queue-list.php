<table>
  <thead>
    <tr>
      <th>Post ID</th>
      <th>Order #</th>
    </tr>
  </thead>
  <tbody>
    <?php if (isset($queue_list['error'])): ?>
      <tr>
        <td colspan="2"><?php echo htmlspecialchars($queue_list['error']); ?></td>
      </tr>
    <?php elseif (!empty($queue_list)): ?>
      <?php foreach ($queue_list as $item): ?>
        <tr>
          <td><?php echo htmlspecialchars($item['postid']); ?></td>
          <td><?php echo htmlspecialchars($item['queueNumber']); ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="2">No photo submissions to display in the queue list.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>