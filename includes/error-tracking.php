<?php

// displays error messages from form submissions
function rcp_show_error_messages() {
	if($codes = rcp_errors()->get_error_codes()) {
		echo '<div class="rcp_message error">';
		    // Loop error codes and display errors
		   foreach($codes as $code){
		        $message = rcp_errors()->get_error_message($code);
		        echo '<p class="rcp_error"><span><strong>' . __('Error', 'rcp') . '</strong>: ' . $message . '</span></p>';
		    }
		echo '</div>';
	}	
}

// used for tracking error messages
function rcp_errors(){
    static $wp_error; // Will hold global variable safely
    return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
}