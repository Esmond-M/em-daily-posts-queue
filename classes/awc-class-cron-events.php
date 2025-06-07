<?php
declare(strict_types=1);
namespace EmDailyPostsQueue\init_plugin\Classes;

if (!class_exists('initCronEvents')) {

    class initCronEvents
    {

        /**
        Declaring constructor
         */
        public function __construct()
        {

		add_action( 'eg_3_weekdays_log',[$this, 'eg_action_net_submission_weekly_update' ]  );

        }


		/**
		 * Get jobs info for careers page.
		 */
		public function eg_action_net_submission_weekly_update() {
	
			// Use correct database Credentials
			$servername = "localhost";
			$username = "root";
			$password = "";
			$dbname = "em-site";
	
			 $conn = mysqli_connect($servername, $username, $password, $dbname);

			 if (!$conn)
			 { 
			 die("Connection to database failed with error#: " . mysqli_connect_error()); 
			 }   

			 $sql = "SELECT list FROM awc_net_photos_queue_order WHERE id='1';"; //----- get current queue list

			 $result = mysqli_query($conn, $sql);
			 $row = mysqli_fetch_assoc($result);
			 $conn->close();
			 if( isset($row['list']) && !empty($row['list']) ){ // if current queue list exists in database
			 $stored_queue_list_arr = unserialize(base64_decode($row['list']));
				 
			 $old_stored_queue_list_arr = $stored_queue_list_arr; /* using this to verify before updating database. This will be different to my other code because i am unsetting the first aray in the code below.*/		
				 
             //inserting code to see if three items or stored in queue or less than removing them for the week.
              if( count($stored_queue_list_arr) >=3  ){
				 unset($stored_queue_list_arr[0]); // remove first entry from queue 
                 unset($stored_queue_list_arr[1]); // remove second entry from queue   
                 unset($stored_queue_list_arr[2]); // remove third entry from queue 
				  // remove post itself from WordPress fully
				$idToRemove = $stored_queue_list_arr[0]['postid'];
				$idToRemove2 = $stored_queue_list_arr[1]['postid'];
				$idToRemove3 = $stored_queue_list_arr[2]['postid'];
			  }
              if( count($stored_queue_list_arr) == 2  ){
				 unset($stored_queue_list_arr[0]); // remove first entry from queue  
                 unset($stored_queue_list_arr[1]); // remove second entry from queue 
				
			    $idToRemove = $stored_queue_list_arr[0]['postid'];
				$idToRemove2 = $stored_queue_list_arr[1]['postid'];
			  }
              if( count($stored_queue_list_arr) == 1  ){
				 unset($stored_queue_list_arr[0]); // remove first entry from queue    
			     $idToRemove = $stored_queue_list_arr[0]['postid'];
			  }				  
			
			 }

			$reNumberBeforeSubmit = array_values($stored_queue_list_arr); // re-number an array
			// loop and subtract all by one to move list up
			if( count($stored_queue_list_arr) >=3  ){ //  will check how many posts I am removing from queue
							for($i = 0; $i < count($reNumberBeforeSubmit); $i++) {
				$reNumberBeforeSubmit[$i]['queueNumber'] = $reNumberBeforeSubmit[$i]['queueNumber'] - 3;
			}
			}
			if( count($stored_queue_list_arr) == 2  ){ //  will check how many posts I am removing from queue note: I do not think these last two ifs apply
							for($i = 0; $i < count($reNumberBeforeSubmit); $i++) {
				$reNumberBeforeSubmit[$i]['queueNumber'] = $reNumberBeforeSubmit[$i]['queueNumber'] - 2;
			}
			}
			if( count($stored_queue_list_arr) == 1  ){ //  will check how many posts I am removing from queue note: I do not think this last if applys
							for($i = 0; $i < count($reNumberBeforeSubmit); $i++) {
				$reNumberBeforeSubmit[$i]['queueNumber'] = $reNumberBeforeSubmit[$i]['queueNumber'] - 1;
			}
			}


			 $SubmissionConn = mysqli_connect($servername, $username, $password, $dbname);

			 if (!$SubmissionConn)
			 { 
			 die("Connection to database failed with error#: " . mysqli_connect_error()); 
			 }   

			 $SubmissionConnsql = "SELECT list FROM awc_net_photos_queue_order WHERE id='1';";
			  //----- check queue list again to see if multiple tabs open or someoneelse made a request.

			 $Second_result = mysqli_query($SubmissionConn, $SubmissionConnsql);
			 $Second_row = mysqli_fetch_assoc($Second_result);

			 if( isset($Second_row['list']) && !empty($Second_row['list']) ){
				$Second_stored_queue_list_arr = unserialize(base64_decode($Second_row['list']));
				$isWindowOutdated = AWCcompareMultiDimensional($Second_stored_queue_list_arr, $old_stored_queue_list_arr);
			   /* 
			  ----- This will check if array is the same. if yes then it will be empty.----
			  I am doing this incase another user is updating the same page on another device or if the current user is updating the page in multiple tabs.
				*/

				if( empty($isWindowOutdated) ){
					$serialize_queueListArray = base64_encode(serialize($reNumberBeforeSubmit)); 
					$SubmissionConnsql = "UPDATE awc_net_photos_queue_order SET list='" . $serialize_queueListArray . "' WHERE id=1";
					if ($SubmissionConn->query($SubmissionConnsql) === TRUE) {
                    if(isset($idToRemove) ){
						wp_delete_post( $idToRemove, true); // Set to False if you want to send them to Trash.
					}
                    if(isset($idToRemove2) ){
						wp_delete_post( $idToRemove2, true); // Set to False if you want to send them to Trash.
					}						
                    if(isset($idToRemove3) ){
						wp_delete_post( $idToRemove3, true); // Set to False if you want to send them to Trash.
					}							
						echo 'Queue List updated. Item has been removed. Next weekly post active.';
					// send email of queue having run
					$emailto = 'esmondmccain@gmail.com';
					// Email subject, "New {post_type_label}"
					$subject = 'Next submission Live: Check Status - '. ' ' . date("m-d-y");
					// Email body
					$message = '<a href="' . site_url() . '/wp-admin/tools.php?page=action-scheduler&status=pending">View it</a>';
					wp_mail( $emailto, $subject, $message );                   
					} 
					if ($SubmissionConn->query($SubmissionConnsql) !== TRUE) {
					echo "SQL Error: " . $SubmissionConnsql . "<br>" . $SubmissionConn->error;
					}
				}

			   else{ // If this form window is outdated do not let them submit to datbase new list.
					 echo 'This window is out of date. Weekly update failed.';
						// send email of new post
						// Recipient, in this case the administrator email
						$emailto = 'esmondmccain@gmail.com';

						// Email subject, "New {post_type_label}"
						$subject = 'Weekly update failed: Someone was editing ' . ' ' . date("m-d-y");

						// Email body
						$message = 'Window was out of date when cron event ran.';

						wp_mail( $emailto, $subject, $message );
				 }	


			 }


			 else{ //  if row is not there do not upate		
					echo 'Database row does not exist.';
			 }

				$SubmissionConn->close();		
		} // end of function

	

	} // Closing bracket for classes

}

new initCronEvents;