<?php
/**
 * Plugin Name: Woocommerce Buy Protected Page
 * Plugin URI: https://1daywebsite.ch
 * Description: Create a product and add link to protected content (page) that shows up after purchase in the thankyou page and email confirmation
 * Version: 1.0.0
 * Author: AFB
 * Author URI: https://1daywebsite.ch
 * Tested up to: 5.6
 * WC requires at least: 2.6
 * WC tested up to: 4.8
 * Text Domain: wc-buy-protected-page
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    if ( ! class_exists( 'AFBBuyProtectedPage' ) ) :
	class AFBBuyProtectedPage {
	    public function __construct(){
			add_action( 'init', array ($this, 'afbbuyprotect_load_textdomain' ));
			add_filter( 'woocommerce_product_data_tabs', array ($this, 'afbbuyprotect_product_settings_tabs' ));
			add_action( 'woocommerce_product_data_panels', array ($this, 'afbbuyprotect_product_panels' ));
			add_action( 'woocommerce_admin_process_product_object', array ($this, 'afbbuyprotect_save_fields' ));
			add_action( 'admin_head', array($this, 'afbbuyprotect_css_icon'));
			//add_action( 'woocommerce_view_order', array ($this, 'afbbuyprotect_action_woocommerce_view_order'), 20 ); 
			add_action( 'woocommerce_thankyou', array ($this, 'afbbuyprotect_action_woocommerce_thankyou'), 20 ); 
			//add_action( 'woocommerce_email_order_meta', array ($this, 'afbbuyprotect_action_woocommerce_email_order_meta'), 10, 4 ); 

			
	    }
		/**
		 * Load plugin textdomain.
		 */
		function afbbuyprotect_load_textdomain() {
			load_plugin_textdomain( 'wc-buy-protected-page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
		}

		/*
		 * Tab
		 */

		function afbbuyprotect_product_settings_tabs( $tabs ){
			$tabs['afbbuyprotect'] = array(
				'label'    => 'Protected Content Page',
				'target'   => 'afbbuyprotect_product_data',
				'class'    => array('show_if_virtual'),
				'priority' => 21,
			);
			return $tabs;
		}
 
		/*
		 * Tab content
		 */
		function afbbuyprotect_product_panels(){
			global $post;
			echo '<div id="afbbuyprotect_product_data" class="panel woocommerce_options_panel hidden">';
			//$afbbuyprotect_rand_pw = $this->createRandomPassword();
			woocommerce_wp_text_input( array(
				'id'                => '_afbbuyprotect_password_gen',
				'value'             => $this->createRandomPassword(),
				'label'             => __('Random Password','wc-buy-protected-page'),
				'description'       => __('You can use this password for the password field below', 'wc-buy-protected-page')
			) );
			woocommerce_wp_text_input( array(
				'id'                => '_afbbuyprotect_password',
				'value'             => get_post_meta( $post->ID, '_afbbuyprotect_password', true ),
				'label'             => __('Insert Password for the protected page', 'wc-buy-protected-page'),
				'desc_tip'    		=> true,
				'description'       => __('Insert the password for the protected page that only the buyer of this product should have.','wc-buy-protected-page')
			) );			
			woocommerce_wp_text_input( array(
				'id'                => '_afbbuyprotect_page_link',
				'value'             => get_post_meta( $post->ID, '_afbbuyprotect_page_link', true ),
				'label'             => __('Insert the <b>link</b> to the page or post with the protected content.','wc-buy-protected-page'),
				'description'       => __('The post/page at this link will be protected with the password set above.','wc-buy-protected-page')
			) );
			echo '</div>';
		 
		}
		function afbbuyprotect_save_fields( $product ){
			if( isset($_POST['_afbbuyprotect_password']) ) {
				$product->update_meta_data( '_afbbuyprotect_password', sanitize_text_field($_POST['_afbbuyprotect_password']) );
			}
			if( isset($_POST['_afbbuyprotect_page_link']) ) {
				$product->update_meta_data( '_afbbuyprotect_page_link', esc_url_raw ($_POST['_afbbuyprotect_page_link']) );
				$protected_post_id = url_to_postid( $_POST['_afbbuyprotect_page_link'] );
				wp_update_post( array( 
					'ID' => $protected_post_id,
					'post_status' => 'publish',
					'post_password' => $_POST['_afbbuyprotect_password'] ) 
				);
			}		
		}
		function afbbuyprotect_css_icon(){
			echo '<style>
			#woocommerce-product-data ul.wc-tabs li.afbbuyprotect_options.afbbuyprotect_tab a:before {
				content: "\f112";
			}
			</style>';
		}
		function createRandomPassword($length=8,$chars="") { 
			if ( $chars=="" ) {
				$chars = "abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ0123456789"; 
			}	
			srand((double)microtime()*1000000); 
			$i = 0; 
			$pass = '' ; 
			while ($i < $length) { 
				$num = rand() % strlen($chars); 
				$tmp = substr($chars, $num, 1); 
				$pass = $pass . $tmp; 
				$i++; 
			} 
			return $pass; 
		}
		// Add extra order info to thank you page
	    function afbbuyprotect_action_woocommerce_thankyou( $order_id ) { 
			$order = wc_get_order( $order_id );
			foreach ($order->get_items() as $item_id => $item ) {   
				$afbbuyprotect_password = get_post_meta( $item->get_product_id(), '_afbbuyprotect_password', true);
				$afbbuyprotect_page_link = esc_url ( get_post_meta( $item->get_product_id(), '_afbbuyprotect_page_link', true) );
				if( !empty( trim( $afbbuyprotect_password ) ) && !empty ( trim ( $afbbuyprotect_page_link ) ) ) {
					echo '<h3 style="text-align:center;">Access Your Content</h3><p>' . __('You may access the content that you just purchased as soon as your order is completed. You will receive an email confirmation. Thank you!','wc-buy-protected-page') . '</p>';
				}
			}
		}		
		// Add tracking info to myaccount order page
	    function afbbuyprotect_action_woocommerce_view_order( $order_id ) { 
			$order = wc_get_order( $order_id );
			if($order->get_status()=="completed") {
				foreach ($order->get_items() as $item_id => $item ) {   
					$afbbuyprotect_password = get_post_meta( $item->get_product_id(), '_afbbuyprotect_password', true);
					$afbbuyprotect_page_link = esc_url ( get_post_meta( $item->get_product_id(), '_afbbuyprotect_page_link', true) );
					if( !empty( trim( $afbbuyprotect_password ) ) && !empty ( trim ( $afbbuyprotect_page_link ) ) ) {
						echo '<h3 style="text-align:center;">Access Your Content</h3><p>' . __('You may access the content that you just purchased at the following link','wc-buy-protected-page') . ':</p><p style="display:block;text-align:center;"><a href="'. $afbbuyprotect_page_link .'" class="btn" target="_blank">' . __('Access Content','wc-buy-protected-page') . '</a></p><p>' . __('To access your content, please use the following password','wc-buy-protected-page') . ': <b>' . $afbbuyprotect_password .'</b></p>';
					}
				}
			}
		}	
	    // Add tracking info to order complete email
	    function afbbuyprotect_action_woocommerce_email_order_meta( $order, $sent_to_admin, $plain_text, $email ) { 
			if($order->get_status()=="completed") {
				foreach ($order->get_items() as $item_id => $item ) {   
					$afbbuyprotect_password = get_post_meta( $item->get_product_id(), '_afbbuyprotect_password', true);
					$afbbuyprotect_page_link = esc_url ( get_post_meta( $item->get_product_id(), '_afbbuyprotect_page_link', true) );
					if( !empty( trim( $afbbuyprotect_password ) ) && !empty ( trim ( $afbbuyprotect_page_link ) ) ) {
						echo '<h3 style="text-align:center;">Access Your Content</h3><p>' . __('You may access the content that you just purchased at the following link','wc-buy-protected-page') . ':</p><p style="display:block;text-align:center;"><a href="'. $afbbuyprotect_page_link .'" class="btn" target="_blank">' . __('Access Content','wc-buy-protected-page') . '</a></p><p>' . __('To access your content, please use the following password','wc-buy-protected-page') . ': <b>' . $afbbuyprotect_password .'</b></p>';
					}
				}
			}
		}	
	}
    $AFBBuyProtectedPage = new AFBBuyProtectedPage();
    endif;
}
