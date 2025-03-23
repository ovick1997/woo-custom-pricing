<?php
/**
 * Plugin Name: WooCommerce Custom Pricing
 * Plugin URI: https://github.com/ovick1997/woo-custom-pricing
 * Description: A WooCommerce extension to set custom product prices for individual customers with a user-friendly tabbed interface.
 * Version: 1.0.0
 * Author: Md Shorov Abedin
 * Author URI: https://shorovabedin.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woo-custom-pricing
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.5
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    // Plugin code continues below
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>WooCommerce Custom Pricing requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

// Add submenu under WooCommerce
add_action( 'admin_menu', 'wcp_register_admin_page' );
function wcp_register_admin_page() {
    add_submenu_page(
        'woocommerce',
        'Custom Pricing',
        'Custom Pricing',
        'manage_woocommerce',
        'wcp-custom-pricing',
        'wcp_render_admin_page'
    );
}

// Enqueue scripts and styles
add_action( 'admin_enqueue_scripts', 'wcp_enqueue_scripts' );
function wcp_enqueue_scripts( $hook ) {
    if ( 'woocommerce_page_wcp-custom-pricing' !== $hook ) {
        return;
    }
    wp_enqueue_script( 'wcp-admin-script', plugin_dir_url( __FILE__ ) . 'wcp-admin.js', [ 'jquery' ], '1.0.0', true );
    wp_localize_script( 'wcp-admin-script', 'wcp_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wcp_ajax_nonce' ),
    ]);
    wp_enqueue_style( 'wcp-admin-style', plugin_dir_url( __FILE__ ) . 'wcp-admin.css', [], '1.0.0' );
}

// Render the admin page with tabs
function wcp_render_admin_page() {
    $products = wc_get_products( [ 'limit' => -1, 'status' => 'publish' ] );
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'customer-list';
    $selected_user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
    $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $per_page = 15; // Changed to 15 customers per page

    // Customer query with pagination
    $args = [
        'role__in' => [ 'customer', 'subscriber' ],
        'number'   => $per_page,
        'offset'   => ( $paged - 1 ) * $per_page,
    ];
    $user_query = new WP_User_Query( $args );
    $users = $user_query->get_results();
    $total_users = $user_query->get_total();
    $total_pages = ceil( $total_users / $per_page );

    ?>
    <div class="wrap">
        <h1>Custom Pricing Manager</h1>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-list' ); ?>" class="nav-tab <?php echo $active_tab === 'customer-list' ? 'nav-tab-active' : ''; ?>">Customer List</a>
            <a href="<?php echo $selected_user_id ? admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-details&user_id=' . $selected_user_id ) : '#'; ?>" class="nav-tab <?php echo $active_tab === 'customer-details' ? 'nav-tab-active' : ''; ?>" id="customer-details-tab">Customer Details</a>
        </h2>
        <div id="wcp-tab-content">
            <!-- Customer List Tab -->
            <div class="wcp-tab-pane" id="tab-customer-list" style="display: <?php echo $active_tab === 'customer-list' ? 'block' : 'none'; ?>;">
                <div style="margin-bottom: 20px;">
                    <input type="text" id="wcp-customer-search" placeholder="Search customers..." style="width: 300px; padding: 5px;">
                </div>
                <table class="wp-list-table widefat fixed striped" id="wcp-customer-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $users as $user ) {
                            ?>
                            <tr>
                                <td><?php echo esc_html( $user->display_name ); ?></td>
                                <td><?php echo esc_html( $user->user_email ); ?></td>
                                <td>
                                    <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-details&user_id=' . $user->ID ); ?>" class="button">View Details</a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <div class="tablenav bottom" style="margin-top: 20px;">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( [
                            'base'      => add_query_arg( 'paged', '%#%' ),
                            'format'    => '',
                            'prev_text' => __( '« Previous' ),
                            'next_text' => __( 'Next »' ),
                            'total'     => $total_pages,
                            'current'   => $paged,
                        ] );
                        ?>
                        <span class="displaying-num"><?php echo esc_html( $total_users ); ?> customers</span>
                    </div>
                </div>
            </div>

            <!-- Customer Details Tab -->
            <div class="wcp-tab-pane" id="tab-customer-details" style="display: <?php echo $active_tab === 'customer-details' ? 'block' : 'none'; ?>;">
                <?php
                if ( $selected_user_id && ( $selected_user = get_user_by( 'ID', $selected_user_id ) ) ) {
                    $custom_prices = get_user_meta( $selected_user_id, 'wcp_custom_prices', true );
                    if ( ! is_array( $custom_prices ) || empty( $custom_prices ) ) {
                        $custom_prices = [ [ 'product_id' => '', 'price' => '' ] ]; // Default row
                    }
                    ?>
                    <h3><?php echo esc_html( $selected_user->display_name . ' (' . $selected_user->user_email . ')' ); ?></h3>
                    <table class="wp-list-table widefat fixed striped wcp-pricing-table" data-user-id="<?php echo esc_attr( $selected_user_id ); ?>">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Custom Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $custom_prices as $index => $entry ) {
                                $status = ( ! empty( $entry['product_id'] ) && ! empty( $entry['price'] ) ) ? '<span class="wcp-checkmark">✔</span>' : '';
                                ?>
                                <tr data-user-id="<?php echo esc_attr( $selected_user_id ); ?>" data-index="<?php echo $index; ?>">
                                    <td>
                                        <select class="wcp-product-select" name="product_id">
                                            <option value="">Select a product</option>
                                            <?php
                                            foreach ( $products as $product ) {
                                                $selected = ( $entry['product_id'] == $product->get_id() ) ? 'selected' : '';
                                                echo '<option value="' . esc_attr( $product->get_id() ) . '" ' . $selected . '>' . esc_html( $product->get_name() ) . ' (ID: ' . $product->get_id() . ')</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="wcp-price-input" name="price" value="<?php echo esc_attr( $entry['price'] ); ?>" placeholder="Custom Price" style="width: 100px;" />
                                    </td>
                                    <td class="wcp-status" style="text-align: center;"><?php echo $status; ?></td>
                                    <td>
                                        <button type="button" class="button wcp-save-row">Save</button>
                                        <button type="button" class="button wcp-delete-row">Delete</button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr class="wcp-add-row">
                                <td colspan="4">
                                    <button type="button" class="button wcp-add-price">Add New Price</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                } else {
                    echo '<p>Please select a customer from the Customer List tab.</p>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler to save custom prices
add_action( 'wp_ajax_wcp_save_price', 'wcp_save_price_ajax' );
function wcp_save_price_ajax() {
    check_ajax_referer( 'wcp_ajax_nonce', 'nonce' );
    $user_id = absint( $_POST['user_id'] );
    $product_id = absint( $_POST['product_id'] );
    $price = floatval( $_POST['price'] );

    $custom_prices = get_user_meta( $user_id, 'wcp_custom_prices', true );
    if ( ! is_array( $custom_prices ) ) {
        $custom_prices = [];
    }

    $index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : count( $custom_prices );
    if ( $product_id && $price ) {
        $custom_prices[ $index ] = [ 'product_id' => $product_id, 'price' => $price ];
    } elseif ( isset( $custom_prices[ $index ] ) ) {
        unset( $custom_prices[ $index ] );
        $custom_prices = array_values( $custom_prices ); // Reindex array
    }

    update_user_meta( $user_id, 'wcp_custom_prices', $custom_prices );
    wp_send_json_success( [ 'message' => 'Price updated successfully!' ] );
}

// AJAX handler for customer search
add_action( 'wp_ajax_wcp_search_customers', 'wcp_search_customers_ajax' );
function wcp_search_customers_ajax() {
    check_ajax_referer( 'wcp_ajax_nonce', 'nonce' );
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $paged = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
    $per_page = 15;

    $args = [
        'role__in' => [ 'customer', 'subscriber' ],
        'number'   => $per_page,
        'offset'   => ( $paged - 1 ) * $per_page,
        'search'   => '*' . esc_attr( $search ) . '*',
        'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
    ];
    $user_query = new WP_User_Query( $args );
    $users = $user_query->get_results();
    $total_users = $user_query->get_total();
    $total_pages = ceil( $total_users / $per_page );

    ob_start();
    foreach ( $users as $user ) {
        ?>
        <tr>
            <td><?php echo esc_html( $user->display_name ); ?></td>
            <td><?php echo esc_html( $user->user_email ); ?></td>
            <td>
                <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-details&user_id=' . $user->ID ); ?>" class="button">View Details</a>
            </td>
        </tr>
        <?php
    }
    $table_content = ob_get_clean();

    $pagination = paginate_links( [
        'base'      => admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-list&paged=%#%' ),
        'format'    => '',
        'prev_text' => __( '« Previous' ),
        'next_text' => __( 'Next »' ),
        'total'     => $total_pages,
        'current'   => $paged,
        'type'      => 'plain',
    ] );

    wp_send_json_success( [
        'table_content' => $table_content,
        'pagination'    => $pagination,
        'total_users'   => $total_users,
    ] );
}

// Filter to modify product price
add_filter( 'woocommerce_product_get_price', 'wcp_set_custom_price', 10, 2 );
add_filter( 'woocommerce_product_get_regular_price', 'wcp_set_custom_price', 10, 2 );

function wcp_set_custom_price( $price, $product ) {
    if ( ! is_user_logged_in() ) {
        return $price;
    }

    $user_id = get_current_user_id();
    $custom_prices = get_user_meta( $user_id, 'wcp_custom_prices', true );

    if ( ! is_array( $custom_prices ) || empty( $custom_prices ) ) {
        return $price;
    }

    foreach ( $custom_prices as $entry ) {
        if ( $entry['product_id'] == $product->get_id() ) {
            return floatval( $entry['price'] );
        }
    }

    return $price;
}