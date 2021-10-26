import '../scss/admin.scss';

window.$ = window.$ || jQuery;

export const { domReady } = wp;

domReady( () => {
	const CustomFeeAdmin = {
		init() {
			this.initDatePicker();
			this.bindEvents();
			// this.handleRemoveButton();
			this.reArrangeConditionKey();
		},

		bindEvents() {
			$( '.wcafm-fee-condition-wrapper' ).on( 'click', 'a.add-new-condition', this.addCondtions );
			$( '.wcafm-fee-condition-wrapper' ).on( 'click', 'button.remove-condition', this.removeCondtion );
			$( 'table.fee-condition-table' ).on( 'change', 'select.fee_condition_name', this.handleCondtionSelect );
			// $( 'select.fee_condition_name' ).trigger( 'change' );
		},

		// handleRemoveButton() {
		// 	if ( 1 === $( 'table.fee-condition-table' ).find( 'tr' ).length ) {
		// 		$( 'table.fee-condition-table' ).find( 'tr' ).first().find( 'td.action' ).hide();
		// 	}
		// },

		reArrangeConditionKey() {
			const row = $( 'table.fee-condition-table' ).find( 'tr' );

			if ( row.length > 0 ) {
				row.each( ( index, elm ) => {
					$( elm ).find( '.fee_condition_name' ).attr( 'name', 'fee_condition[name][' + index + ']' );
					$( elm ).find( '.fee_condition_name' ).attr( 'id', 'fee_condition[name][' + index + ']' );
					$( elm ).find( '.fee_condition_input' ).attr( 'name', 'fee_condition[condition][' + index + ']' );
					$( elm ).find( '.fee_condition_input' ).attr( 'id', 'fee_condition[condition][' + index + ']' );

					$( elm ).find( '.fee_condition_value' ).attr( 'name', 'fee_condition[value][' + index + '][]' );
					$( elm ).find( '.fee_condition_value' ).attr( 'id', 'fee_condition[value][' + index + '][]' );
				} );
			}
		},

		initDatePicker() {
			$( '.datepicker' ).datepicker( {
				dateFormat: WCAFM.dateFormat,
			} );
		},

		handleCondtionSelect() {
			const self = $( this );
			const row = self.closest( 'tr.condition-row' );

			if ( '' === self.val() ) {
				row.find( 'td.condition' ).html( '' );
				row.find( 'td.value' ).html( '' );
				return;
			}

			const data = {
				action: 'wcafm_get_condtion_fields',
				nonce: WCAFM.nonce,
				key: self.closest( 'tr' ).attr( 'data-key' ),
				field: self.val(),
				section: self.find( ':selected' ).data( 'section' ),
			};

			$.get( WCAFM.ajaxurl, data, ( resp ) => {
				if ( resp.success ) {
					row.find( 'td.condition' ).html( resp.data.condition );
					row.find( 'td.value' ).html( resp.data.html );

					// Load wc select2.
					$( document.body ).trigger( 'wc-enhanced-select-init' );
					CustomFeeAdmin.reArrangeConditionKey();
				} else {
					alert( resp.data );
				}
			} );
		},

		addCondtions( e ) {
			e.preventDefault();
			const row = $( 'table.fee-condition-table' ).find( 'tr' ).first().clone();
			row.find( 'td.name' ).find( 'select.fee_condition_name' ).val( '' );
			row.find( 'td.condition' ).html( '' );
			row.find( 'td.value' ).html( '' );
			row.find( 'td.action' ).show();
			$( 'table.fee-condition-table' ).find( 'tr' ).find( 'td.action' ).show();
			$( 'table.fee-condition-table' ).find( 'tbody' ).append( row );
			CustomFeeAdmin.reArrangeConditionKey();
		},

		removeCondtion( e ) {
			e.preventDefault();
			const self = $( this );

			if ( 1 >= self.closest( 'tbody' ).find( 'tr' ).length ) {
				return;
			}

			self.closest( 'tr' ).remove();
			CustomFeeAdmin.reArrangeConditionKey();
		},
	};

	CustomFeeAdmin.init();
} );
