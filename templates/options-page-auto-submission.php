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
<?php

         $conn = mysqli_connect( DB_HOST, DB_USER, DB_PASSWORD,DB_NAME);
         
         if (!$conn)
         { 
         die("Connection to database failed with error#: " . mysqli_connect_error()); 
         }   
         
         $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';"; //----- get current queue list
         
         $result = mysqli_query($conn, $sql);
         $row = mysqli_fetch_assoc($result);
	     $conn->close();
         if( isset($row['list']) && !empty($row['list']) ){ // if current queue list exists in database
		 $stored_queue_list_arr = unserialize(base64_decode($row['list']));	

		for($i = 0; $i < count($stored_queue_list_arr); $i++) {
       $table_postID = $stored_queue_list_arr[$i]['postid'];
       $table_postQueueNumber = $stored_queue_list_arr[$i]['queueNumber'];
			?>
     <tr class="edpq-row-<?php echo $table_postQueueNumber; ?>">
      <td>
        <?php echo $table_postQueueNumber; ?>
        <input type="hidden" value="<?php echo $table_postQueueNumber;  ?>" name="queue-value-<?php echo $table_postQueueNumber; ?>" required/>
        <input type="hidden" value="<?php echo $table_postID;  ?>" name="queue-postID-<?php echo $table_postID; ?>" required/>
       </td>
      <td class="edpq-title-<?php echo $table_postQueueNumber;  ?>"><?php echo get_the_title($table_postID); ?></td>
      <td>
	  <?php
		if ($i > 1) {
		?>
      <button class="up-btn up-btn-queue-number-<?php echo $table_postQueueNumber; ?> up-btn-ID-<?php echo $table_postID; ?>">up</button>
        <?php
		}
	  ?>
      </td>
      <td>
	  <?php
		if ( count($stored_queue_list_arr) > 1 && $i !== array_key_last($stored_queue_list_arr) && $i !== array_key_first($stored_queue_list_arr) )  {
		?>
    <button class="dwn-btn dwn-btn-queue-number-<?php echo $table_postQueueNumber; ?> dwn-btn-ID-<?php echo $table_postID; ?>">down</button>
        <?php
		}
	  ?>    
      </td>
      <td>
	  <?php
		if ( $i !== array_key_first($stored_queue_list_arr) )  {
		?>
    <button class="delete-btn delete-btn-queue-number-<?php echo $table_postQueueNumber; ?> delete-btn-ID-<?php echo $table_postID; ?>">delete</button>
     </td>
        <?php
		}
	  ?> 
    </tr>
    <?php 
		
			
		}

		 }

     ?>

	  </tbody>
	</table>

	<?php wp_nonce_field( 'update-photo-queue-list' ); 
		if( is_array($stored_queue_list_arr) && !empty($stored_queue_list_arr) ){
		 ?>
	<input type="hidden" name="checkWindowAge" value='<?php echo json_encode($stored_queue_list_arr) ;?>' />
	<input type="hidden" name="action" value="net_photo_deletion_info_ajax" />
    <input type="submit" value="submit">
       <?php	
		}
     ?>
	</form>
        <?php
		if( is_array($stored_queue_list_arr) && empty($stored_queue_list_arr) ){
		 ?>
				<div class="edpq-response-msg"><p>No photo submissions to display in the queue list.</p>
       <?php	
		}