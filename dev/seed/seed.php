<?php
/**
 * Seeds the dev sandbox with FAKE, deterministic data. Run through setup.ps1:
 *   wp eval-file /opt/dev-seed/seed.php --user=admin
 *
 * Fixtures that evals write to come in A/B pairs so two runs can work side by side.
 */

if ( get_option( 'wpcli_dev_seeded' ) ) {
	WP_CLI::success( 'Already seeded.' );
	return;
}
if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

// Seeding must not send ~40 order emails.
add_filter( 'pre_wp_mail', '__return_true' );

// ---- Store settings -------------------------------------------------------
$settings = array(
	'timezone_string'                => 'UTC',
	'woocommerce_store_address'      => '1 Example Street',
	'woocommerce_store_city'         => 'Exampleville',
	'woocommerce_default_country'    => 'US:CA',
	'woocommerce_store_postcode'     => '90001',
	'woocommerce_currency'           => 'USD',
	'woocommerce_calc_taxes'         => 'no',
	'woocommerce_manage_stock'       => 'yes',
	'woocommerce_coming_soon'        => 'no',
	'woocommerce_onboarding_profile' => array( 'skipped' => true ),
	'woocommerce_bacs_settings'      => array( 'enabled' => 'yes', 'title' => 'Direct bank transfer' ),
	'woocommerce_cod_settings'       => array( 'enabled' => 'yes', 'title' => 'Cash on delivery' ),
);
foreach ( $settings as $key => $value ) {
	update_option( $key, $value );
}

// ---- Users ----------------------------------------------------------------
$make_user = function ( $login, $role, $first, $last ) {
	return wp_insert_user(
		array(
			'user_login' => $login,
			'user_email' => $login . '@example.com',
			'user_pass'  => wp_generate_password( 24 ),
			'role'       => $role,
			'first_name' => $first,
			'last_name'  => $last,
		)
	);
};
$keeper   = $make_user( 'site-editor', 'editor', 'Sam', 'Keeper' );
$tmp      = array(
	'a' => $make_user( 'tmp-editor-a', 'editor', 'Tara', 'Temp' ),
	'b' => $make_user( 'tmp-editor-b', 'editor', 'Toby', 'Temp' ),
);
$make_user( 'shop-manager', 'shop_manager', 'Morgan', 'Manager' );

$customers = array();
foreach ( array( 'alice' => 'Archer', 'bob' => 'Baker', 'carol' => 'Carter', 'dave' => 'Dyer', 'erin' => 'Ellis', 'frank' => 'Fisher' ) as $first => $last ) {
	$id               = $make_user( $first, 'customer', ucfirst( $first ), $last );
	$customers[]      = $id;
	$customer         = new WC_Customer( $id );
	$customer->set_billing_first_name( ucfirst( $first ) );
	$customer->set_billing_last_name( $last );
	$customer->set_billing_email( $first . '@example.com' );
	$customer->set_billing_address_1( '10 Sample Road' );
	$customer->set_billing_city( 'Exampleville' );
	$customer->set_billing_state( 'CA' );
	$customer->set_billing_postcode( '90001' );
	$customer->set_billing_country( 'US' );
	$customer->save();
}

// ---- Content --------------------------------------------------------------
$post = function ( $title, $status, $author, $type = 'post', $content = '' ) {
	return wp_insert_post(
		array(
			'post_title'   => $title,
			'post_status'  => $status,
			'post_author'  => $author,
			'post_type'    => $type,
			'post_content' => $content ? $content : "<p>$title body text.</p>",
		)
	);
};
foreach ( $tmp as $key => $author ) {
	$tag = strtoupper( $key );
	foreach ( array( 'Spring lookbook', 'Care guide', 'Store opening hours' ) as $t ) {
		$post( "$t ($tag)", 'publish', $author );
	}
	foreach ( array( 'Unfinished gift ideas', 'Half-written returns FAQ' ) as $t ) {
		$post( "$t ($tag)", 'draft', $author );
	}
	// Legacy-domain references for search-replace practice: content, meta, and a serialized option.
	$old = "https://old-shop-$key.example";
	foreach ( array( 'Our story', 'Wholesale', 'Press' ) as $t ) {
		$id = $post( "$t ($tag)", 'publish', 1, 'post', "<p>Read more at <a href=\"$old/about\">$old/about</a> or see <img src=\"$old/cdn/banner.jpg\" />.</p>" );
		update_post_meta( $id, '_legacy_source_url', "$old/?p=$id" );
	}
	update_option(
		"legacy_shop_settings_$key",
		array(
			'home'   => $old,
			'cdn'    => "$old/cdn",
			'label'  => "Old shop $tag",
			'nested' => array( 'feeds' => array( "$old/feed", "$old/sitemap.xml" ) ),
		)
	);
}
$post( 'Newsletter ideas', 'draft', 1 );
$post( 'Holiday schedule', 'draft', $keeper );
foreach ( array( 'About us', 'Shipping policy', 'Returns policy', 'Contact' ) as $t ) {
	$post( $t, 'publish', 1, 'page' );
}
foreach ( array( 'Wholesale terms', 'Size chart' ) as $t ) {
	$post( $t, 'draft', 1, 'page' );
}

// ---- Products -------------------------------------------------------------
$products = array();
$simple   = array(
	// sku            => name, regular, sale, stock (null = not managed)
	'MUG-BLUE-01'  => array( 'Blue Mug', '12.50', '', null ),
	'MUG-RED-01'   => array( 'Red Mug', '12.50', '9.99', null ),
	'NOTE-A5-01'   => array( 'A5 Notebook', '7.00', '', 40 ),
	'LAMP-DSK-01'  => array( 'Desk Lamp', '49.99', '', 20 ),
	'BAG-TOTE-01'  => array( 'Tote Bag', '19.00', '', 0 ),
	'HDPH-200-A'   => array( 'Headphones 200 (A)', '129.00', '', null ),
	'HDPH-200-B'   => array( 'Headphones 200 (B)', '129.00', '', null ),
);
foreach ( $simple as $sku => $row ) {
	$p = new WC_Product_Simple();
	$p->set_name( $row[0] );
	$p->set_sku( $sku );
	$p->set_regular_price( $row[1] );
	if ( '' !== $row[2] ) {
		$p->set_sale_price( $row[2] );
	}
	if ( null !== $row[3] ) {
		$p->set_manage_stock( true );
		$p->set_stock_quantity( $row[3] );
	}
	$p->set_status( 'publish' );
	$products[ $sku ] = $p->save();
}
$variable = array();
foreach ( array( 'A', 'B' ) as $tag ) {
	$p = new WC_Product_Variable();
	$p->set_name( "T-Shirt $tag" );
	$p->set_sku( "TEE-$tag" );
	$attribute = new WC_Product_Attribute();
	$attribute->set_name( 'Size' );
	$attribute->set_options( array( 'S', 'M', 'L' ) );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	$p->set_attributes( array( $attribute ) );
	$p->set_status( 'publish' );
	$pid = $p->save();
	foreach ( array( 'S', 'M', 'L' ) as $size ) {
		$v = new WC_Product_Variation();
		$v->set_parent_id( $pid );
		$v->set_attributes( array( 'size' => $size ) );
		$v->set_sku( "TEE-$tag-$size" );
		$v->set_regular_price( '25.00' );
		$v->set_manage_stock( true );
		$v->set_stock_quantity( 5 );
		$v->save();
	}
	WC_Product_Variable::sync( $pid );
	$variable[ $tag ] = $pid;
}

// ---- Coupon ---------------------------------------------------------------
$coupon = new WC_Coupon();
$coupon->set_code( 'WELCOME10' );
$coupon->set_discount_type( 'percent' );
$coupon->set_amount( 10 );
$coupon->set_usage_limit( 100 );
$coupon->set_usage_limit_per_user( 1 );
$coupon->save();

// ---- Orders ---------------------------------------------------------------
$make_order = function ( $date, $status, $customer_id, $sku, $qty, $guest_no = 0 ) use ( $products ) {
	$order = wc_create_order( array( 'customer_id' => $customer_id ) );
	$order->add_product( wc_get_product( $products[ $sku ] ), $qty );
	if ( $customer_id ) {
		$c = new WC_Customer( $customer_id );
		$order->set_address( $c->get_billing(), 'billing' );
	} else {
		$order->set_address(
			array(
				'first_name' => 'Guest',
				'last_name'  => "Number$guest_no",
				'email'      => "guest$guest_no@example.com",
				'address_1'  => '20 Sample Road',
				'city'       => 'Exampleville',
				'state'      => 'CA',
				'postcode'   => '90001',
				'country'    => 'US',
			),
			'billing'
		);
	}
	$order->set_payment_method( 'bacs' );
	$order->set_payment_method_title( 'Direct bank transfer' );
	$order->set_date_created( $date );
	$order->calculate_totals();
	$order->set_status( $status );
	if ( in_array( $status, array( 'processing', 'completed' ), true ) ) {
		$order->set_date_paid( $date );
	}
	if ( 'completed' === $status ) {
		$order->set_date_completed( $date );
	}
	$order->save();
	return $order;
};

$statuses  = array( 'completed', 'completed', 'processing', 'completed', 'on-hold', 'completed', 'cancelled', 'completed', 'pending', 'completed', 'refunded', 'completed', 'failed', 'completed' );
$order_sku = array( 'MUG-BLUE-01', 'MUG-RED-01', 'NOTE-A5-01', 'LAMP-DSK-01' );
$facts     = array( 'sept_completed_count' => 0, 'sept_completed_revenue' => 0.0, 'order_total' => 0 );
$start     = strtotime( '2026-08-03 10:00:00 UTC' );
for ( $i = 0; $i < 28; $i++ ) {
	$date     = gmdate( 'Y-m-d H:i:s', $start + $i * 2 * DAY_IN_SECONDS );
	$status   = $statuses[ $i % count( $statuses ) ];
	$customer = ( 0 === $i % 5 ) ? 0 : $customers[ $i % count( $customers ) ];
	$order    = $make_order( $date, $status, $customer, $order_sku[ $i % 4 ], 1 + ( $i % 3 ), $i );
	if ( 'completed' === $status && '2026-09' === substr( $date, 0, 7 ) ) {
		++$facts['sept_completed_count'];
		$facts['sept_completed_revenue'] += (float) $order->get_total();
	}
	++$facts['order_total'];
}
// Paired fixtures for write evals and manual verification (October, outside the September window).
$facts['refund_order_a']  = $make_order( '2026-10-02 09:00:00', 'processing', $customers[0], 'LAMP-DSK-01', 1 )->get_id();
$facts['refund_order_b']  = $make_order( '2026-10-02 09:30:00', 'processing', $customers[1], 'LAMP-DSK-01', 1 )->get_id();
$facts['pending_order_a'] = $make_order( '2026-10-03 09:00:00', 'pending', $customers[2], 'NOTE-A5-01', 2 )->get_id();
$facts['pending_order_b'] = $make_order( '2026-10-03 09:30:00', 'pending', $customers[3], 'NOTE-A5-01', 2 )->get_id();
$facts['order_total']    += 4;

$facts['sept_completed_revenue'] = number_format( $facts['sept_completed_revenue'], 2, '.', '' );
$facts['variable_product_a']     = $variable['A'];
$facts['variable_product_b']     = $variable['B'];
$facts['keeper_user_id']         = $keeper;
$facts['tmp_editor_a']           = $tmp['a'];
$facts['tmp_editor_b']           = $tmp['b'];
$facts['products']               = $products;

update_option( 'wpcli_dev_seed_facts', $facts, false );
update_option( 'wpcli_dev_seeded', gmdate( 'c' ), false );
WP_CLI::success( 'Seeded: ' . wp_json_encode( $facts ) );
