<?php
/*
Plugin Name: Delete Users with Specific Roles
Description: A professional WordPress plugin to delete users based on their roles.
Version: 2.1
Author: Awasam
Author URI: https://awasam.com
Text Domain: delete-users-roles
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add submenu under the Settings menu
add_action( 'admin_menu', 'dus_roles_menu' );

function dus_roles_menu() {
    add_options_page(
        __( 'Delete Users Settings', 'delete-users-roles' ),
        __( 'Delete Users', 'delete-users-roles' ),
        'manage_options',
        'delete-users-roles',
        'dus_settings_page'
    );
}

// Add a "Settings" link to the plugin action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'dus_add_settings_link' );

function dus_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=delete-users-roles">' . __( 'Settings', 'delete-users-roles' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// Render the settings page
function dus_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $roles = get_editable_roles();
    $selected_role = get_option( 'dus_auto_delete_role', '' );

    // Handle form submissions
    if ( isset( $_POST['dus_save_settings'] ) ) {
        check_admin_referer( 'dus_save_settings_action', 'dus_save_settings_nonce' );

        $new_role = sanitize_text_field( $_POST['role'] );
        update_option( 'dus_auto_delete_role', $new_role );

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'delete-users-roles' ) . '</p></div>';
    }

    if ( isset( $_POST['dus_delete_users'] ) ) {
        check_admin_referer( 'dus_delete_users_action', 'dus_delete_users_nonce' );

        if ( $selected_role ) {
            $deleted_count = dus_delete_users_by_role( $selected_role );
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( '%d users with the role "%s" were deleted.', 'delete-users-roles' ), $deleted_count, esc_html( $selected_role ) ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No role selected for deletion.', 'delete-users-roles' ) . '</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Delete Users Settings', 'delete-users-roles' ); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="role"><?php esc_html_e( 'Select Role for Deletion', 'delete-users-roles' ); ?></label>
                    </th>
                    <td>
                        <select name="role" id="role" class="regular-text">
                            <?php foreach ( $roles as $role_key => $role_data ) : ?>
                                <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $role_key, $selected_role ); ?>>
                                    <?php echo esc_html( $role_data['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field( 'dus_save_settings_action', 'dus_save_settings_nonce' ); ?>
            <p class="submit">
                <input type="submit" name="dus_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'delete-users-roles' ); ?>">
            </p>
        </form>
        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field( 'dus_delete_users_action', 'dus_delete_users_nonce' ); ?>
            <p class="submit">
                <input type="submit" name="dus_delete_users" class="button button-danger" value="<?php esc_attr_e( 'Delete Users with Selected Role', 'delete-users-roles' ); ?>">
            </p>
        </form>
    </div>
    <?php
}

// Delete users by role function
function dus_delete_users_by_role( $role ) {
    $users = get_users( array( 'role' => $role ) );
    $deleted_count = 0;

    foreach ( $users as $user ) {
        if ( wp_delete_user( $user->ID ) ) {
            $deleted_count++;
        }
    }

    return $deleted_count;
}

// Load plugin text domain for translations
add_action( 'plugins_loaded', 'dus_load_textdomain' );

function dus_load_textdomain() {
    load_plugin_textdomain( 'delete-users-roles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
