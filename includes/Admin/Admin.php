<?php
/**
 * Admin class
 *
 * Manage all admin related functionality
 *
 * @package Advance\CustomFee
 */

declare(strict_types=1);

namespace Advance\CustomFee\Admin;

use function Advance\CustomFee\plugin;
use WP_Post;

/**
 * Admin class.
 *
 * @package Advance\CustomFee\Admin
 */
class Admin {

	/**
	 * Load automatically when class initiate
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'custom_fee_submenu_page' ] );
		add_filter( 'parent_file', [ $this, 'fix_admin_parent_file' ] );
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes' ] );
		add_action( 'save_post_wc_custom_fee', [ $this, 'save_fee_settings' ], 10, 2 );
		add_action( 'save_post_wc_custom_fee', [ $this, 'save_wc_conditional_fee_metabox' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hooks Define admin pages.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts( string $hooks ): void {
		global $current_screen;

		if ( ! in_array( $hooks, [ 'edit.php', 'post-new.php', 'post.php' ] ) ) {
			return;
		}

		if ( empty( $current_screen->post_type ) ) {
			return;
		}

		if ( 'wc_custom_fee' === $current_screen->post_type ) {

			wp_enqueue_style(
				'admin-css',
				plugin()->assets_dir . '/build/css/admin.css',
				[],
				time()
			);

			wp_enqueue_script( 'wc-enhanced-select' );

			wp_enqueue_script(
				'admin-custom-fee-scripts',
				plugin()->assets_dir . '/build/js/admin.build.js',
				array( 'jquery', 'jquery-ui-datepicker', 'wc-enhanced-select' ),
				time(),
				true
			);

			wp_localize_script(
				'admin-custom-fee-scripts',
				'WCAFM',
				[
					'ajaxurl'    => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'wcafm-nonce' ),
					'dateFormat' => wcafm_wp_date_format_to_js(),
				]
			);
		}
	}

	/**
	 * Added a Sub Menu on Woocommerce
	 *
	 * @return void
	 */
	public function custom_fee_submenu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Fees & Duties', 'custom-fee' ),
			__( 'Fees & Duties', 'custom-fee' ),
			'manage_woocommerce',
			'edit.php?post_type=wc_custom_fee'
		);
	}

	function fix_admin_parent_file( $parent_file ) {
		global $submenu_file, $current_screen;

		if ( 'wc_custom_fee' === $current_screen->post_type ) {
			$submenu_file = 'edit.php?post_type=wc_custom_fee';
			$parent_file  = 'woocommerce';
		}

		return $parent_file;
	}

	/**
	 * Add Metabox
	 *
	 * @param array $post_type Post type Array.
	 *
	 * @return void
	 */
	public function meta_boxes( $post_type ): void {
		if ( in_array( $post_type, [ 'wc_custom_fee' ] ) ) {
			add_meta_box(
				'wc_fee_details',
				__( 'Fee settings', 'custom-fee' ),
				[ $this, 'render_fee_settings' ],
				$post_type,
				'advanced',
				'high'
			);

			add_meta_box(
				'wc_conditional_fee',
				__( 'Conditional Fee Rules', 'custom-fee' ),
				[ $this, 'render_fee_rules' ],
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 *
	 * @return void
	 */
	public function save_fee_settings( int $post_id ): void {
		// Check nonce verfication.
		if ( isset( $_POST['wcafm_fee_settings_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['wcafm_fee_settings_nonce'] ), 'wcafm_fee_settings' ) ) {
			return;
		}

		// Return if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Return if user has no permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$postdata = isset( $_POST['fee_settings'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['fee_settings'] ) )
					: [];

		if ( empty( $postdata ) ) {
			return;
		}

		// Format date to timestamp.
		if ( ! empty( $postdata['start_date'] ) ) {
			$postdata['start_date'] = strtotime( str_replace( '/', '-', $postdata['start_date'] ) );
		}

		// Format end date to timestamp.
		if ( ! empty( $postdata['end_date'] ) ) {
			$postdata['end_date'] = strtotime( str_replace( '/', '-', $postdata['end_date'] ) );
		}

		update_post_meta( $post_id, '_wcafm_fee_settings', $postdata );
	}

	/**
	 * Save the wc_conditional_fee meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_wc_conditional_fee_metabox( $post_id ) {

		if ( isset( $_POST['wcafm_fee_condition_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['wcafm_fee_condition_nonce'] ), 'wcafm_fee_condition' ) ) {
			return;
		}

		// Return if autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Return if user has no permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$condition_array = [];
		$postdata        = wp_unslash( $_POST );

		if ( ! empty( $postdata['fee_condition_type'] ) ) {
			$condition_type = $postdata['fee_condition_type'];
			update_post_meta( $post_id, '_wcafm_fee_condition_type', $condition_type );
		}

		if ( ! empty( $postdata['fee_condition']['name'] ) ) {
			foreach ( $postdata['fee_condition']['name'] as $key => $name ) {
				if ( empty( $name ) ) {
					continue;
				}

				$value = ! empty( $postdata['fee_condition']['value'][ $key ] ) ? $postdata['fee_condition']['value'][ $key ] : '';

				$condition_array[] = [
					'name'      => $name,
					'condition' => ! empty( $postdata['fee_condition']['condition'][ $key ] ) ? $postdata['fee_condition']['condition'][ $key ] : '',
					'value'     => count( $value ) > 1 ? $value : $value[0],
				];
			}
		}

		update_post_meta( $post_id, '_wcafm_fee_conditions', $condition_array );
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function render_fee_settings( WP_Post $post ): void {
		wp_nonce_field( 'wcafm_fee_settings', 'wcafm_fee_settings_nonce' );

		$fee_settings = get_post_meta( $post->ID, '_wcafm_fee_settings', true );
		$fee_type     = $fee_settings['type'] ?? '';
		$amount       = $fee_settings['amount'] ?? '';
		$is_taxable   = $fee_settings['taxable'] ?? 'no';
		$start_date   = ! empty( $fee_settings['start_date'] ) ? date_i18n( get_option( 'date_format' ), $fee_settings['start_date'] ): '';
		$end_date     = ! empty( $fee_settings['end_date'] ) ? date_i18n( get_option( 'date_format' ), $fee_settings['end_date'] ) : '';
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<td>
						<label>
							<strong><?php esc_html_e( 'Fee type', 'custom-fee' ); ?></strong>
						</label>
					</td>
					<td>
						<select name="fee_settings[type]" id="fee_settings[type]">
							<?php foreach ( wcafm_fee_types() as $type_key => $type ) : ?>
								<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $fee_type, $type ); ?>><?php echo esc_html( $type ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<label>
							<strong><?php esc_html_e( 'Fee amount', 'custom-fee' ); ?></strong>
						</label>
					</td>
					<td>
						<input type="text" class="regular-price wc_input_price" name="fee_settings[amount]" id="fee_settings[amount]" value="<?php echo esc_attr( $amount ); ?>">
					</td>
				</tr>
				<tr>
					<td>
						<label>
							<strong><?php esc_html_e( 'Is amount taxable?', 'custom-fee' ); ?></strong>
						</label>
					</td>
					<td>
						<select name="fee_settings[taxable]" id="fee_settings[taxable]">
							<option value="yes" <?php selected( $is_taxable, 'yes' ); ?>><?php esc_html_e( 'Yes', 'custom-fee' ); ?></option>
							<option value="no" <?php selected( $is_taxable, 'yes' ); ?>><?php esc_html_e( 'No', 'custom-fee' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<label>
							<strong><?php esc_html_e( 'Start date', 'custom-fee' ); ?></strong>
						</label>
					</td>
					<td>
						<input type="text" class="datepicker" name="fee_settings[start_date]" id="fee_settings[start_date]" value="<?php echo esc_attr( $start_date ); ?>">
					</td>
				</tr>
				<tr>
					<td>
						<label>
							<strong><?php esc_html_e( 'End date', 'custom-fee' ); ?></strong>
						</label>
					</td>
					<td>
						<input type="text" class="datepicker" name="fee_settings[end_date]" id="fee_settings[end_date]" value="<?php echo esc_attr( $end_date ); ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_fee_rules( $post ) {
		wp_nonce_field( 'wcafm_fee_condition', 'wcafm_fee_condition_nonce' );

		$conditions          = wcafm_get_fee_conditions();
		$selected_conditions = get_post_meta( $post->ID, '_wcafm_fee_conditions', true );
		$condition_type      = get_post_meta( $post->ID, '_wcafm_fee_condition_type', true );

		?>
		<div class="wcafm-fee-condition-wrapper">
			<a href="#" class="button button-primary add-new-condition"><?php esc_html_e( 'Add new Condtion', 'custom-fee' ); ?></a>

			<select name="fee_condition_type" id="fee_condition_type]" class="fee_condition_type">
				<option value=""><?php esc_html_e( '--Select Condtion Type--', 'custom-fee' ); ?></option>
				<option  value="match_any" <?php 'match_any' === $condition_type ? esc_html_e( 'selected', 'custom-fee' ) : ''; ?> > <?php esc_html_e( 'Match any of this', 'custom-fee' ); ?></option>
				<option  value="match_all" <?php 'match_all' === $condition_type ? esc_html_e( 'selected', 'custom-fee' ) : ''; ?>> <?php esc_html_e( 'Meet all condition', 'custom-fee' ); ?></option>
			</select>

			<table class="form-table fee-condition-table">
				<tbody>
					<?php if ( ! empty( $selected_conditions ) ) : ?>
						<?php foreach ( $selected_conditions as $key => $condition_data ) : ?>
							<?php
								$selected_condition = wcafm_get_fee_conditions( $condition_data['name'] );
							?>
							<tr class="condition-row">
								<td class="name" width="30%">
									<select name="fee_condition[name][]" id="fee_condition[name][]" class="fee_condition_name">
										<option value=""><?php esc_html_e( '--Select a Condtion--', 'custom-fee' ); ?></option>
										<?php foreach ( $conditions as $key => $condition_array ) : ?>
											<optgroup label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
												<?php foreach ( $condition_array as $id => $condition ) : ?>
													<option <?php selected( $condition_data['name'], $id ); ?> value="<?php echo esc_attr( $id ); ?>" data-section="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $condition['name'] ); ?></option>
												<?php endforeach; ?>
											</optgroup>
										<?php endforeach; ?>
									</select>
								</td>
								<td class="condition" width="20%">
									<?php
										$selected_condition_data = $condition_data['condition'];
										echo wcafm_generate_condtion_dropdown( $selected_condition['condition'], $selected_condition_data ); // phpcs:ignore
									?>
								</td>
								<td class="value" width="40%">
									<?php
										$selected_condition['value'] = $condition_data['value'];
										echo wcafm_generate_html( $selected_condition ); // phpcs:ignore
									?>
								</td>
								<td class="action" width="10%">
									<button class="button button-default remove-condition"><?php esc_html_e( 'Remove', 'custom-fee' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr class="condition-row">
							<td class="name" width="30%">
								<select name="fee_condition[name][index]" id="fee_condition[name][index]" class="fee_condition_name">
									<option value=""><?php esc_html_e( '--Select a Condtion--', 'custom-fee' ); ?></option>
									<?php foreach ( $conditions as $key => $condition_array ) : ?>
										<optgroup label="<?php echo esc_attr( ucfirst( $key ) ); ?>">
											<?php foreach ( $condition_array as $id => $conditon ) : ?>
												<option value="<?php echo esc_attr( $id ); ?>" data-section="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $conditon['name'] ); ?></option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
							</td>
							<td class="condition" width="20%"></td>
							<td class="value" width="40%"></td>
							<td class="action" width="10%">
								<button class="button button-default remove-condition"><?php esc_html_e( 'Remove', 'custom-fee' ); ?></button>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
