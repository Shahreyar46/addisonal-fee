<?php
/**
 * CustomFee class
 *
 * Manage all CustomFee related functionality
 *
 * @package Advance\CustomFee
 */

declare(strict_types=1);

namespace Advance\CustomFee\Frontend;

/**
 * Admin class.
 *
 * @package Advance\CustomFee\Frontend
 */
class CustomFee {

	/**
	 * Load automatically when class initiate
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'woocommerce_cart_calculate_fees', [ $this, 'woocommerce_custom_gst' ] );
	}

	/**
	 * Display Custom Fee
	 *
	 * @return void
	 */
	public function woocommerce_custom_gst() {
		$args = array(
			'post_type' => 'wc_custom_fee',
		);

		$query = new \WP_Query( $args );

		foreach ( $query->posts as $post ) {
			$post_id        = $post->ID;
			$fee_conditions = get_post_meta( $post_id, '_wcafm_fee_conditions', true );
			$fee_settings   = get_post_meta( $post_id, '_wcafm_fee_settings', true );
			$condition_type = get_post_meta( $post_id, '_wcafm_fee_condition_type', true );
			$all_conditions = count( $fee_conditions );
	
			if ( ! empty( $fee_conditions ) ) {
				global $woocommerce;
				$matches_condition = [];

				foreach ( $fee_conditions as $key => $fee_condition ) {
					$condition_name  = $fee_condition['name'];
					$condition_value = $fee_condition['value'];
					$condition       = $fee_condition['condition'];

					switch ( $condition_name ) {
						case 'shipping_country_state':
							$shipping_country = $woocommerce->customer->get_shipping_country();
							
							if ( in_array( $shipping_country, $condition_value ) && 'contain' === $condition ) {
								$matches_condition['shipping_country'] = true;

							} elseif ( ! in_array( $shipping_country, $condition_value ) && 'not-contain' === $condition ) {
								$matches_condition['shipping_country'] = true;
							}

							break;

						case 'billing_country_state':
							$billing_country = $woocommerce->customer->get_billing_country();
							
							if ( in_array( $billing_country, $condition_value ) && 'contain' === $condition ) {
								$matches_condition['billing_country'] = true;

							} elseif ( ! in_array( $billing_country, $condition_value ) && 'not-contain' === $condition ) {
								$matches_condition['billing_country'] = true;
							}

							break;

						case 'payment_gateway':
							$chosen_gateway = WC()->session->get( 'chosen_payment_method' );
							
							if ( in_array( $chosen_gateway, $condition_value ) && 'equal' === $condition ) {
								$matches_condition['payment_gateway'] = true;

							} elseif ( ! in_array( $chosen_gateway, $condition_value ) && 'not-equal' === $condition ) {
								$matches_condition['payment_gateway'] = true;
							}
															
							break;

						case 'quantity':
							$items = $woocommerce->cart->get_cart();

							foreach ( $items as $item ) {
								$item_quantity = $item['quantity'];

								if ( 'equal' === $condition && $item_quantity == $condition_value ) {
									$matches_condition['cart_quantity'] = true;
									
								} elseif ( 'greater-than' === $condition && $item_quantity > $condition_value ) {
									$matches_condition['cart_quantity'] = true;
					
								} elseif ( 'less-than' === $condition && $item_quantity < $condition_value ) {
									$matches_condition['cart_quantity'] = true;
					
								} elseif ( 'greater-than-equal' === $condition && $item_quantity >= $condition_value ) {
									$matches_condition['cart_quantity'] = true;
					
								} elseif ( 'less-than-equal' === $condition && $item_quantity <= $condition_value ) {
									$matches_condition['cart_quantity'] = true;
					
								}
							}

							break;
						
						case 'cart_subtotal':
							$cart_subtotal = WC()->cart->subtotal;
							
							if ( 'equal' === $condition && $cart_subtotal == $condition_value ) {
								$matches_condition['cart_subtotal'] = true;
								
							} elseif ( 'greater-than' === $condition && $cart_subtotal > $condition_value ) {
								$matches_condition['cart_subtotal'] = true;
				
							} elseif ( 'less-than' === $condition && $cart_subtotal < $condition_value ) {
								$matches_condition['cart_subtotal'] = true;
				
							} elseif ( 'greater-than-equal' === $condition && $cart_subtotal >= $condition_value ) {
								$matches_condition['cart_subtotal'] = true;
				
							} elseif ( 'less-than-equal' === $condition && $cart_subtotal <= $condition_value ) {
								$matches_condition['cart_subtotal'] = true;
				
							}

							break;

						case 'user_role':
							$user = wp_get_current_user();

							if ( array_intersect( $condition_value, $user->roles ) && 'equal' === $condition ) {
								$matches_condition['user_role'] = true;

							} elseif ( ! array_intersect( $condition_value, $user->roles ) && 'not-equal' === $condition ) {
								$matches_condition['user_role'] = true;
							}
		
							break;

						case 'shipping_post_code':
							$shipping_postcode = $woocommerce->customer->get_shipping_postcode();
							
							if ( 'equal' === $condition && $shipping_postcode === $condition_value ) {
								$matches_condition['shipping_post_code'] = true;
								
							} elseif ( 'not-equal' === $condition && $shipping_postcode != $condition_value ) {
								$matches_condition['shipping_post_code'] = true;
				
							}
							break;

						case 'billing_post_code':
							$billing_postcode = $woocommerce->customer->get_postcode();
							
							if ( 'equal' === $condition && $billing_postcode === $condition_value ) {
								$matches_condition['billing_post_code'] = true;
								
							} elseif ( 'not-equal' === $condition && $billing_postcode != $condition_value ) {
								$matches_condition['billing_post_code'] = true;
				
							}
							break;
				
						default:
					}
				}

				$this->custom_fee_conditions( $matches_condition, $condition_type, $fee_settings, $all_conditions );

			}
		}
	}

	/**
	 * Custom fee based on conditions
	 *
	 * @param  array  $matches_condition Matched condition.
	 * @param  string $condition_type Conditiontype.
	 * @param  array  $fee_settings Fee Settings.
	 * @param  int    $all_conditions Total Conditions.
	 *
	 * @return void
	 */
	public function custom_fee_conditions( $matches_condition, $condition_type, $fee_settings, $all_conditions ) {
		global $woocommerce;
		$flag = false;
	 
		if ( 'match_any' === $condition_type ) {
			foreach ( $matches_condition as $condition ) {
				if ( $condition ) {
					$flag = true;
					break;
				}
			}
		}

		if ( 'match_all' === $condition_type ) {
			if ( count( $matches_condition ) === $all_conditions ) {
				$flag = true;
			}
		}

		$fee_type   = $fee_settings['type'];
		$fee_amount = $fee_settings['amount'];
		$label      = 'Conditional fee:';
	
		if ( $flag ) {
			if ( 'fixed' === $fee_type ) {
					$woocommerce->cart->add_fee( $label, $fee_amount, true, '' );
	
			} elseif ( 'percentage' === $fee_type ) {
				$percentage = $fee_amount / 100;

					$surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage;
					$woocommerce->cart->add_fee( $label, $surcharge, true, '' );

			}
		} 
	}


}
