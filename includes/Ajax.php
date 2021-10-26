<?php
/**
 * Ajax class.
 *
 * Manage all admin related functionality.
 *
 * @package Advance\CustomFee
 */

declare(strict_types=1);

namespace Advance\CustomFee;

/**
 * Ajax handler class.
 *
 * @package Advance\CustomFee\Ajax
 */
class Ajax {

	/**
	 * Load automatically when class initiate
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_wcafm_get_condtion_fields', [ $this, 'get_condition_fields' ] );
	}

	/**
	 * Get appropiate files htmls for rendering on js
	 *
	 * @return void
	 */
	public function get_condition_fields(): void {
		if ( isset( $_GET['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_GET['nonce'] ), 'wcafm-nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce', 'custom-fee' ) );
		}

		$key     = ! empty( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] )    : 0;
		$field   = ! empty( $_GET['field'] ) ? sanitize_text_field( $_GET['field'] ): '';
		$section = ! empty( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

		if ( empty( $field ) ) {
			wp_send_json_error( __( 'No field data found', 'custom-fee' ) );
		}

		$condition = wcafm_get_fee_conditions( $field, $section );

		$condition_array = ! empty( $condition['condition'] ) ? $condition['condition'] : [];

		if ( empty( $condition_array ) ) {
			wp_send_json_error( __( 'No condition found for render field', 'custom-fee' ) );
		}

		$html = wcafm_generate_html( $condition, $key );

		if ( empty( $html ) ) {
			wp_send_json_error( __( 'No html field found for render field', 'custom-fee' ) );
		}

		$data = [
			'condition' => wcafm_generate_condtion_dropdown( $condition_array ),
			'html'      => $html,
		];

		wp_send_json_success( $data );
	}

}
