<?php
/**
 * Standalone tests for Clerk basket param guard + email resolution.
 * Run: php tests/test-basket-guard.php
 */

$passed = 0;
$failed = 0;

function assert_true( $condition, $label ) {
	global $passed, $failed;
	if ( $condition ) {
		echo "PASS  $label\n";
		$passed++;
	} else {
		echo "FAIL  $label\n";
		$failed++;
	}
}

/**
 * OLD buggy guard: required ALL three params to be present (non-false).
 * Returns true if tracking would proceed.
 */
function old_guard_should_track( $add_to_cart, $removed_item, $product_id ) {
	if ( false === $add_to_cart || false === $removed_item || false === $product_id ) {
		return false;
	}
	if ( empty( $add_to_cart ) || ! is_numeric( $add_to_cart ) ) {
		if ( empty( $removed_item ) || ! is_numeric( $removed_item ) ) {
			if ( empty( $product_id ) || ! is_numeric( $product_id ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * NEW fixed guard: skip only when NONE are numeric.
 * Returns true if tracking would proceed.
 */
function new_guard_should_track( $add_to_cart, $removed_item, $product_id ) {
	if ( ! is_numeric( $add_to_cart ) && ! is_numeric( $removed_item ) && ! is_numeric( $product_id ) ) {
		return false;
	}
	return true;
}

/**
 * Resolve basket email like the fixed plugin code.
 */
function resolve_basket_email( $user_email, $billing_email ) {
	$email = (string) $user_email;
	if ( empty( $email ) ) {
		if ( ! empty( $billing_email ) && filter_var( $billing_email, FILTER_VALIDATE_EMAIL ) ) {
			$email = (string) $billing_email;
		}
	}
	return $email;
}

echo "=== Basket param guard ===\n\n";

$cases = array(
	'AJAX add-to-cart (only product_id)' => array( false, false, '123' ),
	'Classic add-to-cart (only add-to-cart)' => array( '123', false, false ),
	'Remove item (only removed_item)' => array( false, '1', false ),
	'No basket params' => array( false, false, false ),
	'Non-numeric junk params' => array( 'abc', 'xyz', 'nope' ),
	'All three present' => array( '10', '1', '10' ),
);

foreach ( $cases as $label => $params ) {
	list( $a, $r, $p ) = $params;
	$old = old_guard_should_track( $a, $r, $p );
	$new = new_guard_should_track( $a, $r, $p );
	echo "-- $label\n";
	echo "   params: add-to-cart=" . var_export( $a, true ) . ", removed_item=" . var_export( $r, true ) . ", product_id=" . var_export( $p, true ) . "\n";
	echo "   old guard tracks: " . ( $old ? 'yes' : 'no' ) . "\n";
	echo "   new guard tracks: " . ( $new ? 'yes' : 'no' ) . "\n";
}

echo "\n=== Assertions (expected behavior with NEW guard) ===\n\n";

assert_true(
	new_guard_should_track( false, false, '123' ) === true,
	'AJAX add-to-cart with only product_id SHOULD track'
);
assert_true(
	old_guard_should_track( false, false, '123' ) === false,
	'OLD guard wrongly skipped AJAX add-to-cart (reproduces bug)'
);
assert_true(
	new_guard_should_track( '456', false, false ) === true,
	'Classic add-to-cart with only add-to-cart SHOULD track'
);
assert_true(
	new_guard_should_track( false, '2', false ) === true,
	'Remove with only removed_item SHOULD track'
);
assert_true(
	new_guard_should_track( false, false, false ) === false,
	'No params SHOULD skip'
);
assert_true(
	new_guard_should_track( 'foo', 'bar', 'baz' ) === false,
	'Non-numeric params SHOULD skip'
);
assert_true(
	new_guard_should_track( '10', '1', '10' ) === true,
	'All three numeric params SHOULD track'
);

echo "\n=== Email resolution ===\n\n";

assert_true(
	resolve_basket_email( 'user@shop.com', '' ) === 'user@shop.com',
	'Logged-in user email is used'
);
assert_true(
	resolve_basket_email( '', 'guest@shop.com' ) === 'guest@shop.com',
	'Guest falls back to billing email'
);
assert_true(
	resolve_basket_email( 'user@shop.com', 'guest@shop.com' ) === 'user@shop.com',
	'Logged-in email wins over billing email'
);
assert_true(
	resolve_basket_email( '', 'not-an-email' ) === '',
	'Invalid billing email is ignored'
);
assert_true(
	resolve_basket_email( '', '' ) === '',
	'No email means no basket/set call'
);

echo "\n=== Result: $passed passed, $failed failed ===\n";
exit( $failed > 0 ? 1 : 0 );
