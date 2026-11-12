<?php
//Adjust image urls in sitemap
function wpseo_cdn_filter( $uri ) {
  return str_replace( 'https://www.westwoodinstruments.com/wp-content/uploads/', 'https://www.westwoodinstruments.com/media/', $uri );
}
add_filter( 'wpseo_xml_sitemap_img_src', 'wpseo_cdn_filter' );


//Show £0 as free
add_filter( 'woocommerce_cart_item_price', 'change_cart_item_price_html', 10, 3 );
function change_cart_item_price_html( $price_html, $cart_item, $cart_item_key ) {
    if( $cart_item['data']->get_price() == 0 ) {
        return '<span class="woocommerce-Price-amount amount">'.__("FREE", "woocommerce").'</span>';
    }
    return $price_html;
}

// Cart and Checkout
add_filter( 'woocommerce_cart_item_subtotal', 'change_checkout_item_subtotal_html', 10, 3 );
function change_checkout_item_subtotal_html( $subtotal_html, $cart_item, $cart_item_key ) {
    if( $cart_item['data']->get_price() == 0 ) {
        return '<span class="woocommerce-Price-amount amount">'.__("FREE", "woocommerce").'</span>';
    }
    return $subtotal_html;
}


//Delivery details on checkout page
add_filter( 'gettext', 'bbloomer_translate_shippingtodiffaddr', 9999, 3 );  
function bbloomer_translate_shippingtodiffaddr( $translated, $untranslated, $domain ) {
   if ( ! is_admin() && 'woocommerce' === $domain ) {
      switch ( $untranslated ) {
         case 'Ship to a different address?':
            $translated = 'Enter your delivery address below for Merch orders (T-shirts, prints etc). Please note we are currently only shipping to the UK.';
            break;
      }
   }   
   return $translated;
}

//Hide screen options
function wpb_remove_screen_options() { 
if(!current_user_can('manage_options')) {
return false;
}
return true; 
}
add_filter('screen_options_show_screen', 'wpb_remove_screen_options');

//Show product images at checkout
add_filter( 'woocommerce_cart_item_name', 'bbloomer_product_image_review_order_checkout', 9999, 3 );
  
function bbloomer_product_image_review_order_checkout( $name, $cart_item, $cart_item_key ) {
    if ( ! is_checkout() ) return $name;
    $product = $cart_item['data'];
    $thumbnail = $product->get_image( array( '125' ), array( 'class' => 'alignleft' ) );
    return $thumbnail . $name;
}


//Hide product removed from cart undo message
add_filter('woocommerce_cart_item_removed_notice_type', '__return_null');

//Hide product added to cart message
add_filter( 'wc_add_to_cart_message_html', '__return_false' );

//No decimals on product prices
add_filter( 'formatted_woocommerce_price', 'dcwd_remove_zero_decimals', 10, 5 );
function dcwd_remove_zero_decimals( $formatted_price, $price, $decimal_places, $decimal_separator, $thousand_separator ) {
/*
	// Leave decimals in on single product page.
	if ( is_product() ) {
		return $formatted_price;
	}
*/
if ( $price - intval( $price ) == 0 ) {
// Format units, including thousands separator if necessary.
return $unit = number_format( intval( $price ), 0, $decimal_separator, $thousand_separator );
	}
else {
return $formatted_price;
	}
}

// Change add to cart text on single product page
add_filter( 'woocommerce_product_single_add_to_cart_text', 'woocommerce_add_to_cart_button_text_single' ); 
function woocommerce_add_to_cart_button_text_single() {
return __( 'Add to Cart', 'woocommerce' ); 
}

//Move email field to top of checkout
add_filter( 'woocommerce_billing_fields', 'move_checkout_email_field' );
function move_checkout_email_field( $address_fields ) {
    $address_fields['billing_email']['priority'] = 1;
    return $address_fields;
}

//Remove phone number field from checkout
function devpress_remove_checkout_phone_field( $fields ) {
	unset( $fields['billing']['billing_phone'] );
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'devpress_remove_checkout_phone_field' );