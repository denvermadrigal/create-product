<?php
/*
Plugin Name: Create Product
Description: Creates a WooCommerce product and force add to cart
Author: Denver Madrigal
Author URI: mailto:denvermadrigal@gmail.com
Version: 1.0
License: N/A
*/

if(!defined('ABSPATH')) return;
define('CO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CO_ROLE', 'customer');

global $current_user;
add_action('init', function() {
    if(is_user_logged_in()) {
        $current_user = wp_get_current_user();
    }
});

// import css/js
add_action('wp_enqueue_scripts', 'co_enqueue_scripts');
function co_enqueue_scripts() {
    wp_register_style('co-css', CO_PLUGIN_URL.'style.css');
    wp_enqueue_style('co-css');

    wp_register_script('co-fa', 'https://kit.fontawesome.com/4744c9f2f4.js');
    wp_enqueue_script('co-fa');
    
    wp_register_script('co-js', CO_PLUGIN_URL.'script.js', '', '', true);
    wp_enqueue_script('co-js');
}

add_shortcode('co_render_form', 'co_render_form');
function co_render_form() {
    global $current_user;
    ob_start();
    ?>
    <form id="create-order" action="" method="post">
		<ul>
			<li>
				<input type="text" name="order-weight" placeholder="Enter weight in kg." />
			</li>
			<li>
				<input type="submit" value="Create Order" />
                <input type="hidden" name="action" value="co-create-order" />
                <input type="hidden" name="user_id" value="<?php echo $current_user->data->ID; ?>" />
			</li>
		</ul>
		<div class="co-decimal-reminder"><i class="fa-solid fa-asterisk"></i> Accepts numbers w/ or w/o decimal values.</div>
        <div class="co-no-weight"><i class="fa-solid fa-asterisk"></i> Please enter the weight in kg.</div>
	</form>
    <?php
    return ob_get_clean();
}

/**
 * Create a product
 */
add_action('init', function(){
    if(isset($_POST['action']) && $_POST['action'] == 'co-create-order') {
        $product_weight = (float)$_POST['order-weight'];
        if(is_numeric($product_weight) && $product_weight > 0) {
            // get price per kg
            $price_per_kg = 20;
            $unit = 'kg';
            $total_amount = $product_weight * $price_per_kg;
            // get customer details
            $user_id = (int)$_POST['user_id'];

            global $wpdb;
        
            $result = $wpdb->get_results('
                SELECT '.$wpdb->prefix.'users.ID
                FROM '.$wpdb->prefix.'users INNER JOIN '.$wpdb->prefix.'usermeta 
                ON '.$wpdb->prefix.'users.ID = '.$wpdb->prefix.'usermeta.user_id 
                WHERE '.$wpdb->prefix.'usermeta.meta_key = \''.$wpdb->prefix.'capabilities\'
                AND (
                    '.$wpdb->prefix.'usermeta.meta_value LIKE \'%customer%\' 
                    OR 
                    '.$wpdb->prefix.'usermeta.meta_value LIKE \'%administrator%\'
                )
                AND '.$wpdb->prefix.'users.ID = '.$user_id
            );

            // check if user has a customer role
            if(count($result)) {
                // cid[USER_ID]-w[PRODUCT_WEIGHT]
                //$product_name = "cid{$user_id}-w{$product_weight}";
                $product_name = 'Weight: '.$product_weight.$unit;
                $product_desc = '<p>Customer ID: <strong>'.$user_id.'</strong><br />Weight: <strong>'.$product_weight.'</strong></p>';
                // create the product
                $product_id = wp_insert_post(array(
                    'post_author' => $user_id,
                    'post_title' => $product_name,
                    'post_content' => $product_desc,
                    'post_status' => 'publish',
                    'post_type' => 'product'
                ));
                // update custom fields
                wp_set_object_terms($product_id, 'simple', 'product_type');
                wp_set_object_terms($product_id, 17, 'product_cat');
                update_post_meta($product_id, '_visibility', 'visible');
                update_post_meta($product_id, '_stock_status', 'instock');
                update_post_meta($product_id, '_manage_stock', 'no');
                update_post_meta($product_id, '_downloadable', 'no');
                update_post_meta($product_id, '_virtual', 'yes');
                update_post_meta($product_id, '_regular_price', $total_amount);
                update_post_meta($product_id, '_price', $total_amount);
                update_post_meta($product_id, '_featured', 'no');
                set_post_thumbnail($product_id, 22);
                // create an order
                //$order = wc_create_order();
                //$order->add_product(wc_get_product($product_id), 1);
                
                wp_redirect(home_url('checkout/?add-to-cart='.$product_id));
                exit;

                // auto checkout
            }
        }
    }
});