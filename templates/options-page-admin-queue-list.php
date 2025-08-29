<table>
  <thead>
    <tr>
      <th>Post ID</th>
      <th>Order #</th>
    </tr>
  </thead>
 <tbody>
<?php
        $conn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD,DB_NAME);     
         if (!$conn)
         { 
         die("Connection to database failed with error#: " . mysqli_connect_error()); 
         }   
         
         $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';"; //----- get current queue list
         
         $result = mysqli_query($conn, $sql);
         $row = mysqli_fetch_assoc($result);
         if( isset($row['list']) && !empty($row['list']) ){ // if current queue list exists add to array with new post that was published
		         $stored_queue_list_arr = unserialize(base64_decode($row['list']));	
         // print_r($stored_queue_list_arr); 
         $keys = array_keys($stored_queue_list_arr);
		for($i = 0; $i < count($stored_queue_list_arr); $i++) {

			?>
    <tr>
      <td><?php echo $stored_queue_list_arr[$i]['postid']  ?></td>
      <td><?php echo $stored_queue_list_arr[$i]['queueNumber'] ?> </td>
    </tr>
    <?php 
		
			
		}

		 }
?>
  </tbody>
</table>
  <?php
  if( is_array($stored_queue_list_arr) && empty($stored_queue_list_arr) ){
    ?>
      <div class="edpq-response-msg"><p>No photo submissions to display in the queue list.</p>
  <?php	
  }