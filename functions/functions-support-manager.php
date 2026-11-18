<?php
defined( 'ABSPATH' ) || exit;

// Support Manager (wpas_support_manager) restrictions. Administrators are never affected.
function ww_is_support_manager( $user_id ) {
	$u = get_userdata( $user_id );
	return $u && in_array( 'wpas_support_manager', $u->roles, true ) && ! in_array( 'administrator', $u->roles, true );
}

// Can only edit/delete/remove/promote Customers (or themselves).
add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, [ 'edit_user', 'delete_user', 'remove_user', 'promote_user' ], true ) || ! ww_is_support_manager( $user_id ) ) {
		return $caps;
	}
	$target = isset( $args[0] ) ? (int) $args[0] : 0;
	if ( $target === (int) $user_id ) {
		return $caps;
	}
	$t = get_userdata( $target );
	if ( ! $t || array_values( $t->roles ) !== [ 'customer' ] ) {
		$caps[] = 'do_not_allow';
	}
	return $caps;
}, 10, 4 );

// Pages hidden from Support Manager: [ parent => slugs ].
function ww_support_manager_blocked_pages() {
	return [
		'woocommerce'     => [ 'wc-settings', 'wc-status' ],
		'license-manager' => [ 'license-manager-settings' ],
		// Sales figures: Product Hub royalties (sits under Analytics) and AutomateWoo dashboard/reports.
		'wc-admin&path=/analytics/overview' => [ 'product-hub-royalties' ],
		'automatewoo'     => [ 'automatewoo-dashboard', 'automatewoo-reports' ],
	];
}

add_action( 'admin_menu', function () {
	if ( ! ww_is_support_manager( get_current_user_id() ) ) {
		return;
	}
	foreach ( ww_support_manager_blocked_pages() as $parent => $slugs ) {
		foreach ( $slugs as $slug ) {
			remove_submenu_page( $parent, $slug );
		}
	}
	// Analytics is empty once Royalties is gone; Payments only leads to the blocked wc-settings.
	remove_menu_page( 'wc-admin&path=/analytics/overview' );
	remove_menu_page( 'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM' );
}, 999 );

// Dashboard widget showing Smart Coupons revenue.
add_action( 'wp_dashboard_setup', function () {
	if ( ww_is_support_manager( get_current_user_id() ) ) {
		foreach ( [ 'normal', 'side', 'column3', 'column4' ] as $context ) {
			remove_meta_box( 'wc_sc_insights', 'dashboard', $context );
		}
	}
}, 999 );

// WooCommerce Home "New sales record!" inbox note (loaded via REST).
add_filter( 'rest_post_dispatch', function ( $response, $server, $request ) {
	if ( ! ww_is_support_manager( get_current_user_id() ) || false === strpos( $request->get_route(), '/wc-analytics/admin/notes' ) ) {
		return $response;
	}
	$data = $response->get_data();
	if ( is_array( $data ) ) {
		$response->set_data( array_values( array_filter( $data, function ( $n ) {
			return ! ( is_array( $n ) && isset( $n['name'] ) && 'wc-admin-new-sales-record' === $n['name'] );
		} ) ) );
	}
	return $response;
}, 10, 3 );

add_action( 'admin_init', function () {
	if ( ! ww_is_support_manager( get_current_user_id() ) || empty( $_GET['page'] ) ) {
		return;
	}
	$page = sanitize_key( wp_unslash( $_GET['page'] ) );
	foreach ( ww_support_manager_blocked_pages() as $slugs ) {
		if ( in_array( $page, $slugs, true ) ) {
			wp_die( 'You do not have permission to access this page.', 403 );
		}
	}
}, 1 );
