<?php
/**
 * Plugin Name: WooCommerce Custom Pricing
 * Plugin URI: https://github.com/ovick1997/woo-custom-pricing
 * Description: A WooCommerce extension to set custom product prices via rules or individually per customer.
 * Version: 1.0.3
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
    wp_enqueue_script( 'wcp-admin-script', plugin_dir_url( __FILE__ ) . 'wcp-admin.js', [ 'jquery' ], '1.0.3', true );
    wp_localize_script( 'wcp-admin-script', 'wcp_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wcp_ajax_nonce' ),
        'admin_url' => admin_url( 'admin.php?page=wcp-custom-pricing' ), // Pass base admin URL
    ]);
    wp_enqueue_style( 'wcp-admin-style', plugin_dir_url( __FILE__ ) . 'wcp-admin.css', [], '1.0.3' );
}

// Render the admin page with tabs
function wcp_render_admin_page() {
    $products = wc_get_products( [ 'limit' => -1, 'status' => 'publish' ] );
    $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'customer-list';
    $selected_user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
    $selected_rule_id = isset( $_GET['rule_id'] ) ? sanitize_text_field( $_GET['rule_id'] ) : 0; // Changed to sanitize_text_field
    $paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $per_page = 15;

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
    $rules = get_option( 'wcp_pricing_rules', [] );

    ?>
    <div class="wrap">
        <h1>Custom Pricing Manager</h1>
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-list' ); ?>" class="nav-tab <?php echo $active_tab === 'customer-list' ? 'nav-tab-active' : ''; ?>">Customer List</a>
            <a href="<?php echo $selected_user_id ? admin_url( 'admin.php?page=wcp-custom-pricing&tab=customer-details&user_id=' . $selected_user_id ) : '#'; ?>" class="nav-tab <?php echo $active_tab === 'customer-details' ? 'nav-tab-active' : ''; ?>" id="customer-details-tab">Customer Details</a>
            <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=pricing-rules' ); ?>" class="nav-tab <?php echo $active_tab === 'pricing-rules' ? 'nav-tab-active' : ''; ?>">Pricing Rules</a>
            <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=bulk-customer' ); ?>" class="nav-tab <?php echo $active_tab === 'bulk-customer' ? 'nav-tab-active' : ''; ?>">Bulk Customer</a>
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
                            <th>Assigned Rule</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $users as $user ) {
                            $assigned_rule_id = get_user_meta( $user->ID, 'wcp_assigned_rule', true );
                            $status = $assigned_rule_id ? '<span class="wcp-checkmark">✔</span>' : '';
                            ?>
                            <tr data-user-id="<?php echo esc_attr( $user->ID ); ?>">
                                <td><?php echo esc_html( $user->display_name ); ?></td>
                                <td><?php echo esc_html( $user->user_email ); ?></td>
                                <td>
                                    <select class="wcp-rule-select" name="assigned_rule">
                                        <option value="">None</option>
                                        <?php
                                        foreach ( $rules as $rule_id => $rule ) {
                                            $selected = $assigned_rule_id == $rule_id ? 'selected' : '';
                                            echo '<option value="' . esc_attr( $rule_id ) . '" ' . $selected . '>' . esc_html( $rule['name'] ) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td class="wcp-status" style="text-align: center;"><?php echo $status; ?></td>
                                <td>
                                    <button type="button" class="button wcp-save-rule-assignment">Save</button>
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
                        $custom_prices = [ [ 'product_id' => '', 'price' => '' ] ];
                    }
                    $assigned_rule_id = get_user_meta( $selected_user_id, 'wcp_assigned_rule', true );
                    $rule_name = $assigned_rule_id && isset( $rules[$assigned_rule_id] ) ? $rules[$assigned_rule_id]['name'] : 'None';
                    ?>
                    <h3><?php echo esc_html( $selected_user->display_name . ' (' . $selected_user->user_email . ')' ); ?></h3>
                    <p>Assigned Rule: <?php echo esc_html( $rule_name ); ?></p>
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

            <!-- Pricing Rules Tab -->
            <div class="wcp-tab-pane" id="tab-pricing-rules" style="display: <?php echo $active_tab === 'pricing-rules' ? 'block' : 'none'; ?>;">
                <?php
                if ( ! $selected_rule_id ) {
                    ?>
                    <h3>Pricing Rules</h3>
                    <button type="button" class="button wcp-add-rule" style="margin-bottom: 20px;">Add New Rule</button>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Rule Name</th>
                                <th>Assigned Customers</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ( empty( $rules ) ) {
                                echo '<tr><td colspan="3">No rules created yet.</td></tr>';
                            } else {
                                foreach ( $rules as $rule_id => $rule ) {
                                    $assigned_customers = get_option( 'wcp_rule_' . $rule_id . '_customers', [] );
                                    $customer_count = count( $assigned_customers );
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $rule['name'] ); ?></td>
                                        <td><?php echo esc_html( $customer_count ); ?> customers</td>
                                        <td>
                                            <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=pricing-rules&rule_id=' . $rule_id ); ?>" class="button">Edit</a>
                                            <button type="button" class="button wcp-delete-rule" data-rule-id="<?php echo esc_attr( $rule_id ); ?>">Delete</button>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php
                } else {
                    $rule = isset( $rules[$selected_rule_id] ) ? $rules[$selected_rule_id] : [ 'name' => '', 'prices' => [] ];
                    $rule_prices = ! empty( $rule['prices'] ) ? $rule['prices'] : [ [ 'product_id' => '', 'price' => '' ] ];
                    ?>
                    <h3><?php echo $selected_rule_id && isset( $rules[$selected_rule_id] ) ? 'Edit Rule' : 'Create New Rule'; ?></h3>
                    <input type="text" id="wcp-rule-name" value="<?php echo esc_attr( $rule['name'] ); ?>" placeholder="Rule Name" style="width: 300px; margin-bottom: 20px;">
                    <table class="wp-list-table widefat fixed striped wcp-rule-pricing-table" data-rule-id="<?php echo esc_attr( $selected_rule_id ); ?>">
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
                            foreach ( $rule_prices as $index => $entry ) {
                                $status = ( ! empty( $entry['product_id'] ) && ! empty( $entry['price'] ) ) ? '<span class="wcp-checkmark">✔</span>' : '';
                                ?>
                                <tr data-rule-id="<?php echo esc_attr( $selected_rule_id ); ?>" data-index="<?php echo $index; ?>">
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
                                        <button type="button" class="button wcp-save-rule-row">Save</button>
                                        <button type="button" class="button wcp-delete-rule-row">Delete</button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr class="wcp-add-rule-row">
                                <td colspan="4">
                                    <button type="button" class="button wcp-add-rule-price">Add New Price</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <a href="<?php echo admin_url( 'admin.php?page=wcp-custom-pricing&tab=pricing-rules' ); ?>" class="button" style="margin-top: 10px;">Back to Rules</a>
                    <?php
                }
                ?>
            </div>

            <!-- Bulk Customer Tab -->
            <div class="wcp-tab-pane" id="tab-bulk-customer" style="display: <?php echo $active_tab === 'bulk-customer' ? 'block' : 'none'; ?>;">
                <h3>Bulk Customer Assignment</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Rule Name</th>
                            <th>Assigned Customers</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ( empty( $rules ) ) {
                            echo '<tr><td colspan="3">No rules created yet. Create rules in the Pricing Rules tab first.</td></tr>';
                        } else {
                            foreach ( $rules as $rule_id => $rule ) {
                                $assigned_customers = get_option( 'wcp_rule_' . $rule_id . '_customers', [] );
                                $customer_count = count( $assigned_customers );
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $rule['name'] ); ?></td>
                                    <td><?php echo esc_html( $customer_count ); ?> customers</td>
                                    <td>
                                        <button type="button" class="button wcp-assign-bulk-customers" data-rule-id="<?php echo esc_attr( $rule_id ); ?>">Add Customers</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <div id="wcp-bulk-assign-form" style="display: none; margin-top: 20px;">
                    <h4>Assign Customers to Rule: <span id="wcp-bulk-rule-name"></span></h4>
                    <select multiple id="wcp-bulk-customers" style="width: 300px; height: 150px; margin-bottom: 10px;">
                        <?php
                        $all_users = get_users( [ 'role__in' => [ 'customer', 'subscriber' ] ] );
                        foreach ( $all_users as $user ) {
                            echo '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->display_name . ' (' . $user->user_email . ')' ) . '</option>';
                        }
                        ?>
                    </select>
                    <br>
                    <button type="button" class="button wcp-save-bulk-customers">Save</button>
                    <button type="button" class="button wcp-cancel-bulk-customers" style="margin-left: 10px;">Cancel</button>
                    <input type="hidden" id="wcp-bulk-rule-id" value="">
                </div>
            </div>
        </div>
    </div>
    <?php
}

// AJAX handler to save customer prices
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
        $custom_prices[$index] = [ 'product_id' => $product_id, 'price' => $price ];
    } elseif ( isset( $custom_prices[$index] ) ) {
        unset( $custom_prices[$index] );
        $custom_prices = array_values( $custom_prices );
    }

    update_user_meta( $user_id, 'wcp_custom_prices', $custom_prices );
    wp_send_json_success( [ 'message' => 'Price saved successfully!' ] );
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
    $rules = get_option( 'wcp_pricing_rules', [] );

    ob_start();
    foreach ( $users as $user ) {
        $assigned_rule_id = get_user_meta( $user->ID, 'wcp_assigned_rule', true );
        $status = $assigned_rule_id ? '<span class="wcp-checkmark">✔</span>' : '';
        ?>
        <tr data-user-id="<?php echo esc_attr( $user->ID ); ?>">
            <td><?php echo esc_html( $user->display_name ); ?></td>
            <td><?php echo esc_html( $user->user_email ); ?></td>
            <td>
                <select class="wcp-rule-select" name="assigned_rule">
                    <option value="">None</option>
                    <?php
                    foreach ( $rules as $rule_id => $rule ) {
                        $selected = $assigned_rule_id == $rule_id ? 'selected' : '';
                        echo '<option value="' . esc_attr( $rule_id ) . '" ' . $selected . '>' . esc_html( $rule['name'] ) . '</option>';
                    }
                    ?>
                </select>
            </td>
            <td class="wcp-status" style="text-align: center;"><?php echo $status; ?></td>
            <td>
                <button type="button" class="button wcp-save-rule-assignment">Save</button>
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

// AJAX handler to save rule prices
add_action( 'wp_ajax_wcp_save_rule_price', 'wcp_save_rule_price_ajax' );
function wcp_save_rule_price_ajax() {
    check_ajax_referer( 'wcp_ajax_nonce', 'nonce' );
    $rule_id = sanitize_text_field( $_POST['rule_id'] ); // Changed to sanitize_text_field
    $product_id = absint( $_POST['product_id'] );
    $price = floatval( $_POST['price'] );
    $rule_name = sanitize_text_field( $_POST['rule_name'] );

    if ( empty( $rule_name ) ) {
        wp_send_json_error( [ 'message' => 'Rule name is required.' ] );
        return;
    }

    $rules = get_option( 'wcp_pricing_rules', [] );
    if ( ! isset( $rules[$rule_id] ) ) {
        $rules[$rule_id] = [ 'name' => $rule_name, 'prices' => [] ];
    } else {
        $rules[$rule_id]['name'] = $rule_name;
    }

    $index = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : count( $rules[$rule_id]['prices'] );
    if ( $product_id && $price ) {
        $rules[$rule_id]['prices'][$index] = [ 'product_id' => $product_id, 'price' => $price ];
    } elseif ( isset( $rules[$rule_id]['prices'][$index] ) ) {
        unset( $rules[$rule_id]['prices'][$index] );
        $rules[$rule_id]['prices'] = array_values( $rules[$rule_id]['prices'] );
    }

    update_option( 'wcp_pricing_rules', $rules );
    wp_send_json_success( [ 'message' => 'Rule price saved successfully!' ] );
}

// AJAX handler to save rule assignment for a single customer
add_action( 'wp_ajax_wcp_save_rule_assignment', 'wcp_save_rule_assignment_ajax' );
function wcp_save_rule_assignment_ajax() {
    check_ajax_referer( 'wcp_ajax_nonce', 'nonce' );
    $user_id = absint( $_POST['user_id'] );
    $rule_id = $_POST['rule_id'] ? sanitize_text_field( $_POST['rule_id'] ) : ''; // Changed to sanitize_text_field

    if ( $rule_id ) {
        update_user_meta( $user_id, 'wcp_assigned_rule', $rule_id );
        $assigned_customers = get_option( 'wcp_rule_' . $rule_id . '_customers', [] );
        if ( ! in_array( $user_id, $assigned_customers ) ) {
            $assigned_customers[] = $user_id;
            update_option( 'wcp_rule_' . $rule_id . '_customers', $assigned_customers );
        }
    } else {
        $current_rule_id = get_user_meta( $user_id, 'wcp_assigned_rule', true );
        delete_user_meta( $user_id, 'wcp_assigned_rule' );
        if ( $current_rule_id ) {
            $assigned_customers = get_option( 'wcp_rule_' . $current_rule_id . '_customers', [] );
            $assigned_customers = array_diff( $assigned_customers, [ $user_id ] );
            update_option( 'wcp_rule_' . $current_rule_id . '_customers', array_values( $assigned_customers ) );
        }
    }

    $status = $rule_id ? '✔' : '';
    wp_send_json_success( [ 'message' => 'Rule assignment saved successfully!', 'status' => $status ] );
}

// AJAX handler to save bulk customers
add_action( 'wp_ajax_wcp_save_bulk_customers', 'wcp_save_bulk_customers_ajax' );
function wcp_save_bulk_customers_ajax() {
    check_ajax_referer( 'wcp_ajax_nonce', 'nonce' );
    $rule_id = sanitize_text_field( $_POST['rule_id'] ); // Changed to sanitize_text_field
    $customer_ids = isset( $_POST['customer_ids'] ) ? array_map( 'absint', (array) $_POST['customer_ids'] ) : [];

    $current_assigned = get_option( 'wcp_rule_' . $rule_id . '_customers', [] );
    $new_assigned = array_unique( array_merge( $current_assigned, $customer_ids ) );
    update_option( 'wcp_rule_' . $rule_id . '_customers', $new_assigned );

    foreach ( $customer_ids as $user_id ) {
        update_user_meta( $user_id, 'wcp_assigned_rule', $rule_id );
    }

    wp_send_json_success( [ 'message' => 'Bulk customers assigned successfully!' ] );
}

// AJAX handler to delete rule
add_action( 'wp_ajax_wcp_delete_rule', 'wcp_delete_rule_ajax' );
function wcp_delete_rule_ajax() {
    check_ajax_referer( 'wcp_ajax_nonce', 'nonce' );
    $rule_id = sanitize_text_field( $_POST['rule_id'] ); // Changed to sanitize_text_field

    $rules = get_option( 'wcp_pricing_rules', [] );
    if ( isset( $rules[$rule_id] ) ) {
        unset( $rules[$rule_id] );
        update_option( 'wcp_pricing_rules', $rules );
        delete_option( 'wcp_rule_' . $rule_id . '_customers' );

        $all_users = get_users( [ 'role__in' => [ 'customer', 'subscriber' ] ] );
        foreach ( $all_users as $user ) {
            if ( get_user_meta( $user->ID, 'wcp_assigned_rule', true ) == $rule_id ) {
                delete_user_meta( $user->ID, 'wcp_assigned_rule' );
            }
        }
    }

    wp_send_json_success( [ 'message' => 'Rule deleted successfully!' ] );
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
    $assigned_rule_id = get_user_meta( $user_id, 'wcp_assigned_rule', true );

    if ( is_array( $custom_prices ) && ! empty( $custom_prices ) ) {
        foreach ( $custom_prices as $entry ) {
            if ( $entry['product_id'] == $product->get_id() ) {
                return floatval( $entry['price'] );
            }
        }
    }

    if ( $assigned_rule_id ) {
        $rules = get_option( 'wcp_pricing_rules', [] );
        if ( isset( $rules[$assigned_rule_id]['prices'] ) ) {
            foreach ( $rules[$assigned_rule_id]['prices'] as $entry ) {
                if ( $entry['product_id'] == $product->get_id() ) {
                    return floatval( $entry['price'] );
                }
            }
        }
    }

    return $price;
}