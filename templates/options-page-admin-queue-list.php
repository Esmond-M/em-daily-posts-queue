<style>
* {
  box-sizing: border-box;
}

html,
body {
  padding: 0;
  margin: 0;
}

body {
  font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", "Roboto", "Oxygen", "Ubuntu", "Cantarell", "Fira Sans", "Droid Sans", "Helvetica Neue", "Helvetica", "Arial", sans-serif;
}

table {
    display: grid;
    max-width: 709px;
    border-collapse: collapse;
    width: 100%;
    grid-template-columns: minmax(150px, 0.5fr) minmax(150px, 0.5fr);
    margin: 74px auto 0 0;
}

thead,
tbody,
tr {
  display: contents;
}

th,
td {
  padding: 15px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

th {
  position: sticky;
  top: 0;
  background: #6c7ae0;
  text-align: left;
  font-weight: normal;
  font-size: 1.1rem;
  color: white;
}

th:last-child {
  border: 0;
}

td {
  padding-top: 10px;
  padding-bottom: 10px;
  color: #808080;
}

tr:nth-child(even) td {
  background: #f8f6ff;
}
</style>
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