<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function wishsimply_delete_plugin() {

	delete_option( 'wishsimply_version' );

	$posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type' => 'wishsimply',
			'post_status' => 'any',
		)
	);

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
	}
}

wishsimply_delete_plugin();
