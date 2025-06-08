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

		add_action( 'eg_1_weekdays_log',[$this, 'eg_action_net_submission_weekly_update' ]  ); // this action "eg_1_weekdays_log" is also called in another file edpq-class-cron-events.php

        }
		/**
		 * Check if multidimensional array is the same
		 */
		public function edpqcompareMultiDimensional($array1, $array2, $strict = true){
			if (!is_array($array1)) {
				throw new \InvalidArgumentException('$array1 must be an array!');
			}

			if (!is_array($array2)) {
				return $array1;
			}

			$result = array();

			foreach ($array1 as $key => $value) {
				if (!array_key_exists($key, $array2)) {
					$result[$key] = $value;
					continue;
				}

				if (is_array($value) && count($value) > 0) {
					$recursiveArrayDiff = $this->edpqcompareMultiDimensional($value, $array2[$key], $strict);

					if (count($recursiveArrayDiff) > 0) {
						$result[$key] = $recursiveArrayDiff;
					}

					continue;
				}

				$value1 = $value;
				$value2 = $array2[$key];

				if ($strict ? is_float($value1) && is_float($value2) : is_float($value1) || is_float($value2)) {
					$value1 = (string) $value1;
					$value2 = (string) $value2;
				}

				if ($strict ? $value1 !== $value2 : $value1 != $value2) {
					$result[$key] = $value;
				}
			}

			return $result;
		} // end function

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

			 $sql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';"; //----- get current queue list

			 $result = mysqli_query($conn, $sql);
			 $row = mysqli_fetch_assoc($result);
			 $conn->close();
			 if( isset($row['list']) && !empty($row['list']) ){ // if current queue list exists in database
			 $stored_queue_list_arr = unserialize(base64_decode($row['list']));
				 
			 $old_stored_queue_list_arr = $stored_queue_list_arr; /* using this to verify before updating database. This will be different to my other code because i am unsetting the first aray in the code below.*/		
				 
		    $idToRemove = $stored_queue_list_arr[0]['postid'];	//grab id to delete post.
			unset($stored_queue_list_arr[0]); // remove first entry from queue    
		  
			
			 }

			$reNumberBeforeSubmit = array_values($stored_queue_list_arr); // re-number an array
			// loop and subtract all by one to move list up
			for($i = 0; $i < count($reNumberBeforeSubmit); $i++) {
				$reNumberBeforeSubmit[$i]['queueNumber'] = $reNumberBeforeSubmit[$i]['queueNumber'] - 1;
			}


			 $SubmissionConn = mysqli_connect($servername, $username, $password, $dbname);

			 if (!$SubmissionConn)
			 { 
			 die("Connection to database failed with error#: " . mysqli_connect_error()); 
			 }   

			 $SubmissionConnsql = "SELECT list FROM edpq_net_photos_queue_order WHERE id='1';";
			  //----- check queue list again to see if multiple tabs open or someoneelse made a request.

			 $Second_result = mysqli_query($SubmissionConn, $SubmissionConnsql);
			 $Second_row = mysqli_fetch_assoc($Second_result);

			 if( isset($Second_row['list']) && !empty($Second_row['list']) ){
				$Second_stored_queue_list_arr = unserialize(base64_decode($Second_row['list']));
				$isWindowOutdated = $this->edpqcompareMultiDimensional($Second_stored_queue_list_arr, $old_stored_queue_list_arr);
			   /* 
			  ----- This will check if array is the same. if yes then it will be empty.----
			  I am doing this incase another user is updating the same page on another device or if the current user is updating the page in multiple tabs.
				*/

				if( empty($isWindowOutdated) ){
					$serialize_queueListArray = base64_encode(serialize($reNumberBeforeSubmit)); 
					$SubmissionConnsql = "UPDATE edpq_net_photos_queue_order SET list='" . $serialize_queueListArray . "' WHERE id=1";
					if ($SubmissionConn->query($SubmissionConnsql) === TRUE) {
                    if(isset($idToRemove) ){
						wp_delete_post( $idToRemove, true); 
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