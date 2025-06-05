<!-- New Post Form -->
<form id="new_post" name="new_post" method="post" action="" enctype="multipart/form-data">
<?php 
$current_user = wp_get_current_user();
date_default_timezone_set("America/Chicago");
?>

<!-- Employee Name -->
<label for="net_employee_name">Employee Name</label><br />
<input type="text" value="" tabindex="1" size="20" name="net_employee_name" required/>
<br />
<!-- --------- -->

<!-- Title -->
<label for="net_title">Title</label><br />
<input type="text" value="" tabindex="1" size="20" name="net_title" required/>
<br />
<!-- --------- -->

<!-- Beat Team -->
<label for="net_beat_team">Beat Team</label><br />
<select name="net_beat_team">
  <option value="A3IM">A3IM</option>
  <option value="ACE">ACE</option>
  <option value="AWC">AWC</option>
  <option value="Beat Emerson">Beat Emerson</option>
  <option value="Beat Rockwell">Beat Rockwell</option>
  <option value="NSCC">NSCC</option>
</select>
<!-- --------- -->

<!-- >Region -->
<br /><label for="net_region">Region</label><br />
<input type="text" value="" tabindex="1" size="20" name="net_region" required/>
<br />
<!-- --------- -->

<!-- Photo -->
<label for="net_image">Photo</label><br />
<input type="file"
       id="net_image" name="net_image"
       accept=".png, .jpg, .jpeg" required>
<!-- --------- -->

<!-- >Caption-->
<br /><label for="net_caption">Please write a short description of the photo and include the full names of those pictured so they may be credited.</label><br />
<textarea cols="40" rows="10" name="net_caption" required>
</textarea>
<!-- --------- -->

<!-- Hidden inputs -->
<input type="hidden" name="action" value="form_post_new_net_photo_submission_ajax" />
<!-- --------- -->
<input type="submit" value="Submit" tabindex="6" id="submit" name="submit" />
<?php wp_nonce_field( 'new-post' ); ?>
</form>
<script>

jQuery(document).ready(function($) {

var uploadField = document.getElementById("net_image");

uploadField.onchange = function() {
    if(this.files[0].size > 8388608){
       alert("File is too big!");
       this.value = "";
    };
};

});

</script>