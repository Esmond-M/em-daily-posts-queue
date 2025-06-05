<?php
/**
 * Template Name: Get Error
 */
get_header('test');
?>
<div class="">

<?php 
echo strtotime( 'Now' ) . '<br>';
echo strtotime( 'Now America/Chicago' );

?>
	</div><!-- #primary -->

<?php
get_footer();