<?php 

function rcp_add_user_columns( $columns ) {
	$columns['rcp_subscription'] 	= 'Subscription';
    $columns['rcp_status'] 			= 'Status';
	$columns['rcp_links'] 			= 'Actions';
    return $columns;
}
add_filter( 'manage_users_columns', 'rcp_add_user_columns' );
 
function rcp_show_user_columns( $value, $column_name, $user_id ) {
	if ( 'rcp_status' == $column_name )
		return rcp_get_status( $user_id );
	if ( 'rcp_subscription' == $column_name ) {
		return rcp_get_subscription( $user_id );
	}
	if ( 'rcp_links' == $column_name ) {
		$page = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=rcp-members';
		if( rcp_is_active( $user_id ) ) {
			$links = '<a href="' . $page . '&edit_member=' . $user_id . '">' . __( 'Edit Subscription', 'rcp' ) . '</a>';
			$links .= ' | <a href="' . $page . '&view_member=' . $user_id . '">' . __( 'View Details','rcp' ) . '</a>';
		} else {
			$links = '<a href="' . $page . '&edit_member=' . $user_id . '">' . __( 'Add Subscription', 'rcp' ) . '</a>';
		}
		return $links;
	}
	return $value;
}
add_filter( 'manage_users_custom_column',  'rcp_show_user_columns', 100, 3 );
