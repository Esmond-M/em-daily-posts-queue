<?php
        // Use correct database Credentials
        $servername = "localhost";

		if($_SERVER['SERVER_NAME'] == 'awc-demo-1.esmondmccain.com') {
			$username = "root";
			$password = "";
			$dbname = "em-site";
		 }
		 if($_SERVER['SERVER_NAME'] == 'webtest.awc-inc.com') {
			$username = "wpdev_user";
			$password = "4-0a9iOPhT";
			$dbname = "WPWebdev";
		 } 
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
					if( is_array($stored_queue_list_arr) && !empty($stored_queue_list_arr) ){
							for($i = 0; $i < 1; $i++) {
						   $postID = $stored_queue_list_arr[$i]['postid'];	
						   $featuredImage = get_the_post_thumbnail_url($postID,'large');
						   $netTitle = get_field("net_title",$postID);
						   $netEmployeeName = get_field("net_employee_name",$postID);
						   $netBeatTeam = get_field("net_beat_team",$postID);
						   $netRegion = get_field("net_region",$postID);
						   $netCaption = get_field("net_caption",$postID);
						   ?>	
										<div class="awc-around-awc">
								<div class="awc-content-left">
									<img src="<?php echo $featuredImage; ?>" />
								</div>
								<div class="awc-content-right">
								 <p class="heading">Around AWC</p>
							<?php
									if( $netTitle ) {
									?><p class="awc-title"><?php echo $netTitle; ?></p> <?php
									}
									if( $netEmployeeName ) {
									?><p class="awc-employee-name"><?php echo $netEmployeeName; ?></p> <?php
									}
									if( $netBeatTeam ) {
									?><p class="awc-beat-team"><?php echo $netBeatTeam; ?></p> <?php
									}
									if( $netRegion ) {
									?><p class="awc-net-region"><?php echo $netRegion; ?></p> <?php
									}
									if( $netCaption ) {
									?><p class="awc-net-caption"><?php echo $netCaption; ?></p> <?php
									}?>
								<button class="awc-submit-btn">Submit your photo!</button>
								</div>
							</div><?php
							}

					}
                    else{ // array of posts is empty
					?>	
										<div class="awc-around-awc">
								<div class="awc-content-left">
									<img src="insertplacholderhere.png" />
								</div>
								<div class="awc-content-right">
								 <p class="heading">Around AWC</p>
								 <p class="awc-net-caption">If you would like to submit an image to be used as the .NET Intranet website banner,
					please click the button below.</p>
								<button class="awc-submit-btn">Submit your photo!</button>
								</div>
							</div><?php							
					}

		 }
         else{ // no row in database
?>	
					<div class="awc-around-awc">
			<div class="awc-content-left">
				<img src="insertplacholderhere.png" />
			</div>
			<div class="awc-content-right">
			 <p class="heading">Around AWC</p>
		     <p class="awc-net-caption">If you would like to submit an image to be used as the .NET Intranet website banner,
please click the button below.</p>
            <button class="awc-submit-btn">Submit your photo!</button>
			</div>
		</div><?php			 
		 }