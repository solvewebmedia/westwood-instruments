<?php
function ww_cart_recs_html() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
        return '';
    }

    $in_cart = array();
    $ids     = array();

    foreach ( WC()->cart->get_cart() as $item ) {
        $in_cart[] = $item['product_id'];
        $product   = wc_get_product( $item['product_id'] );
        if ( $product ) {
            $ids = array_merge( $ids, $product->get_cross_sell_ids() );
        }
    }

    if ( empty( $ids ) ) {
        foreach ( $in_cart as $pid ) {
            $ids = array_merge( $ids, wc_get_related_products( $pid, 10 ) );
        }
    }

    $output = '';
    $count  = 0;

    foreach ( array_diff( array_unique( $ids ), $in_cart ) as $id ) {
        if ( $count >= 3 ) {
            break;
        }
        $product = wc_get_product( $id );
        if ( ! $product || ! $product->is_visible() || ! $product->is_purchasable() || (float) $product->get_price() <= 0 ) {
            continue;
        }
        $output .= sprintf(
            '<div class="ww-cart-recs__item">
                <a href="%1$s" class="ww-cart-recs__img">%2$s</a>
                <div class="ww-cart-recs__info">
                    <a href="%1$s" class="ww-cart-recs__name">%3$s</a>
                    <span class="ww-cart-recs__price">%4$s</span>
                </div>
                <a href="%5$s" data-quantity="1" data-product_id="%6$d" class="ww-cart-recs__btn %7$s" aria-label="Add %3$s to basket">Add</a>
            </div>',
            esc_url( $product->get_permalink() ),
            $product->get_image( 'woocommerce_thumbnail' ),
            esc_html( $product->get_name() ),
            $product->get_price_html(),
            esc_url( add_query_arg( 'add-to-cart', $id, home_url( '/' ) ) ),
            $id,
            $product->is_type( 'simple' ) ? 'add_to_cart_button ajax_add_to_cart' : ''
        );
        $count++;
    }

    return $output ? '<div class="ww-cart-recs"><p class="ww-cart-recs__title">You might also like</p>' . $output . '</div>' : '';
}

// Standard WooCommerce mini cart template
add_action( 'woocommerce_after_mini_cart', function () {
    echo ww_cart_recs_html();
} );

// Elementor's mini cart: append to the live cart refresh
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
    $key = 'div.widget_shopping_cart_content';
    if ( isset( $fragments[ $key ] ) && strpos( $fragments[ $key ], 'ww-cart-recs' ) === false ) {
        $fragments[ $key ] = preg_replace( '#</div>\s*$#', ww_cart_recs_html() . '</div>', $fragments[ $key ], 1 );
    }
    return $fragments;
} );

// Load WooCommerce's AJAX add-to-cart script site-wide so the "Add" buttons don't navigate
add_action( 'wp_enqueue_scripts', function () {
    if ( function_exists( 'WC' ) ) {
        wp_enqueue_script( 'wc-add-to-cart' );
    }
} );