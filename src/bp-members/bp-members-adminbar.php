<?php
/**
 * BuddyPress Members Toolbar.
 *
 * Handles the member functions related to the WordPress Toolbar.
 *
 * @package BuddyPress
 * @subpackage MembersAdminBar
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Add the "My Account" menu and all submenus.
 *
 * @since 1.6.0
 *
 * @global WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
 */
function bp_members_admin_bar_my_account_menu() {
	global $wp_admin_bar;

	// Bail if this is an ajax request.
	if ( wp_doing_ajax() ) {
		return;
	}

	// Logged in user.
	if ( is_user_logged_in() ) {

		$bp = buddypress();

		// Stored in the global so we can add menus easily later on.
		$bp->my_account_menu_id = 'my-account-buddypress';

		// Create the main 'My Account' menu.
		$wp_admin_bar->add_node( array(
			'id'     => $bp->my_account_menu_id,
			'group'  => true,
			'title'  => __( 'Edit My Profile', 'buddypress' ),
			'href'   => bp_loggedin_user_url(),
			'meta'   => array(
			'class'  => 'ab-sub-secondary'
		) ) );

		// Show login and sign-up links.
	} elseif ( !empty( $wp_admin_bar ) ) {

		add_filter( 'show_admin_bar', '__return_true' );

		// Create the main 'My Account' menu.
		$wp_admin_bar->add_node( array(
			'id'    => 'bp-login',
			'title' => __( 'Log In', 'buddypress' ),
			'href'  => wp_login_url( bp_get_requested_url() )
		) );

		// Sign up.
		if ( bp_get_signup_allowed() ) {
			$wp_admin_bar->add_node( array(
				'id'    => 'bp-register',
				'title' => __( 'Register', 'buddypress' ),
				'href'  => bp_get_signup_page()
			) );
		}
	}
}
add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_my_account_menu', 4 );

/**
 * Add the User Admin top-level menu to user pages.
 *
 * @since 1.5.0
 *
 * @global WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
 */
function bp_members_admin_bar_user_admin_menu() {
	global $wp_admin_bar;

	// Only show if viewing a user.
	if ( ! bp_is_user() ) {
		return false;
	}

	// Don't show this menu to non site admins or if you're viewing your own profile.
	if ( ! current_user_can( 'edit_users' ) || bp_is_my_profile() ) {
		return false;
	}

	$bp = buddypress();

	// Unique ID for the 'My Account' menu.
	$bp->user_admin_menu_id = 'user-admin';

	// Add the top-level User Admin button.
	$wp_admin_bar->add_node(
		array(
			'id'    => $bp->user_admin_menu_id,
			'title' => __( 'Edit Member', 'buddypress' ),
			'href'  => bp_displayed_user_url()
		)
	);

	if ( bp_is_active( 'xprofile' ) ) {
		// User Admin > Edit this user's profile.
		$wp_admin_bar->add_node(
			array(
				'parent' => $bp->user_admin_menu_id,
				'id'     => $bp->user_admin_menu_id . '-edit-profile',
				'title'  => __( "Edit Profile", 'buddypress' ),
				'href'   => bp_get_members_component_link( $bp->profile->id, 'edit' ),
			)
		);

		// User Admin > Edit this user's avatar.
		if ( buddypress()->avatar->show_avatars ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => $bp->user_admin_menu_id,
					'id'     => $bp->user_admin_menu_id . '-change-avatar',
					'title'  => __( "Edit Profile Photo", 'buddypress' ),
					'href'   => bp_get_members_component_link( $bp->profile->id, 'change-avatar' ),
				)
			);
		}

		// User Admin > Edit this user's cover image.
		if ( bp_displayed_user_use_cover_image_header() ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => $bp->user_admin_menu_id,
					'id'     => $bp->user_admin_menu_id . '-change-cover-image',
					'title'  => __( 'Edit Cover Image', 'buddypress' ),
					'href'   => bp_get_members_component_link( $bp->profile->id, 'change-cover-image' ),
				)
			);
		}

	}

	if ( bp_is_active( 'settings' ) ) {
		// User Admin > Spam/unspam.
		$wp_admin_bar->add_node(
			array(
				'parent' => $bp->user_admin_menu_id,
				'id'     => $bp->user_admin_menu_id . '-user-capabilities',
				'title'  => __( 'User Capabilities', 'buddypress' ),
				'href'   => bp_get_members_component_link( $bp->settings->id, 'capabilities' ),
			)
		);

		// User Admin > Delete Account.
		$wp_admin_bar->add_node(
			array(
				'parent' => $bp->user_admin_menu_id,
				'id'     => $bp->user_admin_menu_id . '-delete-user',
				'title'  => __( 'Delete Account', 'buddypress' ),
				'href'   => bp_get_members_component_link( $bp->settings->id, 'delete-account' ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_user_admin_menu', 99 );

/**
 * Build the "Notifications" dropdown.
 *
 * @since 1.5.0
 *
 * @return bool
 */
function bp_members_admin_bar_notifications_menu() {

	// Bail if notifications is not active.
	if ( ! bp_is_active( 'notifications' ) ) {
		return false;
	}

	bp_notifications_toolbar_menu();
}
add_action( 'admin_bar_menu', 'bp_members_admin_bar_notifications_menu', 90 );

/**
 * Remove rogue WP core Edit menu when viewing a single user.
 *
 * @since 1.6.0
 */
function bp_members_remove_edit_page_menu() {
	if ( bp_is_user() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
	}
}
add_action( 'add_admin_bar_menus', 'bp_members_remove_edit_page_menu' );

/**
 * Add the "Invitations" menu and submenus.
 *
 * @since 8.0.0
 *
 * @global WP_Admin_Bar $wp_admin_bar WordPress object implementing a Toolbar API.
 */
function bp_members_admin_bar_add_invitations_menu() {
	global $wp_admin_bar;

	// Bail if this is an ajax request.
	if ( wp_doing_ajax() ) {
		return;
	}

	if ( bp_current_user_can( 'bp_members_invitations_view_screens' ) ) {
		$bp          = buddypress();
		$invite_slug = bp_get_members_invitations_slug();

		$wp_admin_bar->add_node(
			array(
				'id'     => $bp->my_account_menu_id . '-invitations',
				'parent' => $bp->my_account_menu_id,
				'title'  => __( 'Invitations', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $invite_slug ) ) ),
				'meta'   => array(
					'class'  => 'ab-sub-secondary'
				)
			)
		);

		if ( bp_current_user_can( 'bp_members_invitations_view_send_screen' ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => $bp->my_account_menu_id . '-invitations-send',
					'parent' => $bp->my_account_menu_id . '-invitations',
					'title'  => __( 'Send Invites', 'buddypress' ),
					'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $invite_slug, 'send-invites' ) ) ),
					'meta'   => array(
						'class'  => 'ab-sub-secondary'
					)
				)
			);
		}

		$wp_admin_bar->add_node(
			array(
				'id'     => $bp->my_account_menu_id . '-invitations-list',
				'parent' => $bp->my_account_menu_id . '-invitations',
				'title'  => __( 'Pending Invites', 'buddypress' ),
				'href'   => bp_loggedin_user_url( bp_members_get_path_chunks( array( $invite_slug, 'list-invites' ) ) ),
				'meta'   => array(
					'class'  => 'ab-sub-secondary'
				)
			)
		);
	}
}
add_action( 'bp_setup_admin_bar', 'bp_members_admin_bar_add_invitations_menu', 90 );
