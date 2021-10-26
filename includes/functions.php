<?php
/**
 * Main functions files
 *
 * Load all common functions in this files. Those functions used for both
 * frontend and backend related task.
 *
 * @package Advance\CustomFee
 * @since 1.0.0
 */

/**
 * Fee types
 *
 * @return array
 */
function wcafm_fee_types(): array {
	$types = [
		'fixed'      => __( 'Fixed', 'custom-fee' ),
		'percentage' => __( 'Percentage', 'custom-fee' ),
	];

	return apply_filters( 'wcafm_fee_types', $types );
}

/**
 * Matches each symbol of PHP date format standard
 * with jQuery equivalent codeword
 *
 * @author Tristan Jahier
 */
function wcafm_wp_date_format_to_js() {
	$symbols_matching = [
		'd' => 'dd',
		'D' => 'D',
		'j' => 'd',
		'l' => 'DD',
		'N' => '',
		'S' => '',
		'w' => '',
		'z' => 'o',
		// Week.
		'W' => '',
		// Month.
		'F' => 'MM',
		'm' => 'mm',
		'M' => 'M',
		'n' => 'm',
		't' => '',
		// Year.
		'L' => '',
		'o' => '',
		'Y' => 'yy',
		'y' => 'y',
		// Time.
		'a' => '',
		'A' => '',
		'B' => '',
		'g' => '',
		'G' => '',
		'h' => '',
		'H' => '',
		'i' => '',
		's' => '',
		'u' => '',
	];

	$jqueryui_format   = '';
	$escaping          = false;
	$wp_date_format    = get_option( 'date_format' );
	$length_php_format = strlen( $wp_date_format );

	for ( $i = 0; $i < $length_php_format; $i++ ) {
		$char = $wp_date_format[ $i ];

		// PHP date format escaping character.
		if ( '\\' === $char ) {
			$i++;

			if ( $escaping ) {
				$jqueryui_format .= $wp_date_format[ $i ];
			} else {
				$jqueryui_format .= '\'' . $wp_date_format[ $i ];
			}

			$escaping = true;
		} else {
			if ( $escaping ) {
				$escaping         = false;
				$jqueryui_format .= "'";
			}

			if ( isset( $symbols_matching[ $char ] ) ) {
				$jqueryui_format .= $symbols_matching[ $char ];
			} else {
				$jqueryui_format .= $char;
			}
		}
	}

	return $jqueryui_format;
}

/**
 * Generate html depends on types.
 *
 * @param array $field Field array.
 *
 * @return string
 */
function wcafm_generate_html( $field ): string {
	$html  = '';
	$value = isset( $field['value'] ) ? $field['value'] : '';

	switch ( $field['type'] ) {

		case 'text':
		case 'number':
			$html .= '<input type="' . $field['type'] . '" class="fee_condition_value" name="fee_condition[value][]" id="fee_condition[value][]" value="' . esc_attr( $value ) . '">';
			break;

		case 'country_state':
			$html .= '<select multiple="multiple" data-attribute="country-state" id="fee_condition[value][]" name="fee_condition[value][]" data-placeholder="' . esc_attr__( 'Select contry and state', 'custom-fee' ) . '" class="wc-country-state chosen_select fee_condition_value">'; // phpcs:ignore.
			foreach ( WC()->countries->get_countries() as $country_code => $country ) {
				$html .= '<option value="' . esc_attr( $country_code ) . '"' . wc_selected( "$country_code", $value ) . '>' . esc_html( $country ) . '</option>';

				$states = WC()->countries->get_states( $country_code );

				if ( $states ) {
					foreach ( $states as $state_code => $state_name ) {
						$html .= '<option value="' . esc_attr( $country_code . ':' . $state_code ) . '"' . wc_selected( "$country_code:$state_code", $value ) . '>' . esc_html( '&nbsp;&nbsp;&nbsp;&nbsp; ' . $state_name . ' &mdash; ' . $country ) . '</option>'; // phpcs:ignore.
					}
				}
			}
			$html .= '</select>';
			break;

		case 'payment_gateway':
			$html .= '<select multiple="multiple" data-attribute="payment_gateway" id="fee_condition[value][]" name="fee_condition[value][]" data-placeholder="' . esc_attr__( 'Select payment gateway', 'custom-fee' ) . '" class="wc-country-state chosen_select fee_condition_value">'; // phpcs:ignore.

			$installed_payment_methods = WC()->payment_gateways->payment_gateways();

			foreach ( $installed_payment_methods as $key => $method ) {

				$html .= '<option value="' . esc_attr( $method->id ) . '"' . wc_selected( $method->id, $value ) . '>' . esc_html( $method->title ) . '</option>';
			}
			$html .= '</select>';
			break;

		case 'user_role':
			$html .= '<select multiple="multiple" data-attribute="user_role" id="fee_condition[value][]" name="fee_condition[value][]" data-placeholder="' . esc_attr__( 'Select user role', 'custom-fee' ) . '" class="wc-country-state chosen_select fee_condition_value">'; // phpcs:ignore.

			global $wp_roles;
			$roles = $wp_roles->get_names();

			foreach ( $roles as $key => $role ) {
				$html .= '<option value="' . esc_attr( $key ) . '"' . wc_selected( $key, $value ) . '>' . esc_html( $role ) . '</option>';
			}
			$html .= '</select>';
			break;

		default:
			do_action( 'wcafm_get_generated_html', $field );
			break;
	}

	return $html;
}

/**
 * Generate condition dropdown.
 *
 * @param array  $conditions Condtions array.
 * @param string $value Condtions pre set value.
 *
 * @return string
 */
function wcafm_generate_condtion_dropdown( array $conditions, string $value = '' ): string {
	ob_start();
	?>
	<select name="fee_condition[condition][]" id="fee_condition[condition][]" class="fee_condition_input">
		<?php foreach ( $conditions as $key => $condition ) : ?>
			<option <?php selected( $value, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $condition ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
	return ob_get_clean();
}


/**
 * Get fee conditions
 *
 * @param string $id Field ID.
 * @param string $section Section ID.
 *
 * @return array;
 */
function wcafm_get_fee_conditions( $id = '', $section = '' ): array {
	$conditions = [
		'billing specific'  => [
			'billing_post_code' => [
				'id'        => 'billing_post_code',
				'name'      => __( 'Post Code', 'custom-fee' ),
				'type'      => 'text',
				'condition' => [
					'equal'     => __( 'Equal', 'custom-fee' ),
					'not-equal' => __( 'Not Equal', 'custom-fee' ),
				],
			],
			'billing_country_state'     => [
				'id'        => 'billing_country_state',
				'name'      => __( 'Country/State', 'custom-fee' ),
				'type'      => 'country_state',
				'condition' => [
					'contain'     => __( 'from', 'custom-fee' ),
					'not-contain' => __( 'not from', 'custom-fee' ),
				],
			],
		],

		'shipping specific' => [
			'shipping_post_code'     => [
				'id'        => 'shipping_post_code',
				'name'      => __( 'Post Code', 'custom-fee' ),
				'type'      => 'text',
				'condition' => [
					'equal'     => __( 'Equal', 'custom-fee' ),
					'not-equal' => __( 'Not Equal', 'custom-fee' ),
				],
			],
			'shipping_country_state' => [
				'id'        => 'shipping_country_state',
				'name'      => __( 'Country/State', 'custom-fee' ),
				'type'      => 'country_state',
				'condition' => [
					'contain'     => __( 'from', 'custom-fee' ),
					'not-contain' => __( 'not from', 'custom-fee' ),
				],
			],
		],

		'user specific'     => [
			'user_role' => [
				'id'        => 'user_role',
				'name'      => __( 'User Role', 'custom-fee' ),
				'type'      => 'user_role',
				'condition' => [
					'equal'     => __( 'Equal', 'custom-fee' ),
					'not-equal' => __( 'Not Equal', 'custom-fee' ),
				],
			],
		],

		'cart specific'     => [
			'cart_subtotal' => [
				'id'        => 'cart_subtotal',
				'name'      => __( 'Cart Subtotal', 'custom-fee' ),
				'type'      => 'text',
				'condition' => [
					'equal'              => __( 'Equal', 'custom-fee' ),
					'not-equal'          => __( 'Not Equal', 'custom-fee' ),
					'greater-than'       => __( 'Greater Than', 'custom-fee' ),
					'less-than'          => __( 'Less than', 'custom-fee' ),
					'greater-than-equal' => __( 'Greater Than or Equal to', 'custom-fee' ),
					'less-than-equal'    => __( 'Less than or equal to', 'custom-fee' ),

				],
			],
			'quantity'      => [
				'id'        => 'quantity',
				'name'      => __( 'Cart Quantity', 'custom-fee' ),
				'type'      => 'text',
				'condition' => [
					'equal'              => __( 'Equal', 'custom-fee' ),
					'not-equal'          => __( 'Not Equal', 'custom-fee' ),
					'greater-than'       => __( 'Greater Than', 'custom-fee' ),
					'less-than'          => __( 'Less than', 'custom-fee' ),
					'greater-than-equal' => __( 'Greater Than or Equal to', 'custom-fee' ),
					'less-than-equal'    => __( 'Less than or equal to', 'custom-fee' ),
				],
			],
		],

		'payment specific'  => [
			'payment_gateway' => [
				'id'        => 'payment_gateway',
				'name'      => __( 'Payment Gateway', 'custom-fee' ),
				'type'      => 'payment_gateway',
				'condition' => [
					'equal'     => __( 'Equal', 'custom-fee' ),
					'not-equal' => __( 'Not Equal', 'custom-fee' ),
				],
			],
		],
	];

	if ( ! empty( $section ) ) {
		$condition_section = isset( $conditions[ $section ] ) ? $conditions[ $section ] : [];

		if ( ! empty( $id ) ) {
			return isset( $condition_section[ $id ] ) ? $condition_section[ $id ] : [];
		}

		return $condition_section;
	}

	if ( ! empty( $id ) ) {
		$condition_array = [];
		foreach ( $conditions as $key => $array ) {
			if ( isset( $array[ $id ] ) ) {
				$condition_array = $array[ $id ];
				continue;
			}
		}

		return $condition_array;
	}

	return $conditions;
}
