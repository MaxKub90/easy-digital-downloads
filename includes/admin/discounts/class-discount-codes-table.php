<?php
/**
 * Discount Codes Table Class
 *
 * @package     EDD
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use EDD\Admin\List_Table;

/**
 * EDD_Discount_Codes_Table Class
 *
 * Renders the Discount Codes table on the Discount Codes page
 *
 * @since 1.4
 * @since 3.0 Updated to work with the discount code migration to custom tables.
 */
class EDD_Discount_Codes_Table extends List_Table {

	/**
	 * Get things started
	 *
	 * @since 1.4
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'discount',
			'plural'   => 'discounts',
			'ajax'     => false,
		) );

		$this->process_bulk_action();
		$this->get_counts();
	}

	/**
	 * Get the base URL for the discount list table
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_base_url() {

		// Remove some query arguments
		$base = remove_query_arg( edd_admin_removable_query_args(), edd_get_admin_base_url() );

		// Add base query args
		return edd_get_admin_url( array(
			'page' => 'edd-discounts'
		), $base );
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.4
	 *
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => __( 'Name',       'easy-digital-downloads' ),
			'code'       => __( 'Code',       'easy-digital-downloads' ),
			'amount'     => __( 'Amount',     'easy-digital-downloads' ),
			'use_count'  => __( 'Uses',       'easy-digital-downloads' ),
			'start_date' => __( 'Start Date', 'easy-digital-downloads' ),
			'end_date'   => __( 'End Date',   'easy-digital-downloads' )
		);
	}

	/**
	 * Retrieve the sortable columns
	 *
	 * @since 1.4
	 *
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'name'       => array( 'name',       false ),
			'code'       => array( 'code',       false ),
			'use_count'  => array( 'use_count',  false ),
			'start_date' => array( 'start_date', false ),
			'end_date'   => array( 'end_date',   false )
		);
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 2.5
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'name';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.4
	 *
	 * @param EDD_Discount $discount Discount object.
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $discount, $column_name ) {
		return property_exists( $discount, $column_name ) ? $discount->$column_name : '';
	}

	/**
	 * This function renders the amount column.
	 *
	 * @since 3.0
	 *
	 * @param EDD_Discount $discount Data for the discount code.
	 * @return string Formatted amount.
	 */
	public function column_amount( $discount ) {
		return edd_format_discount_rate( $discount->type, $discount->amount );
	}

	/**
	 * This function renders the start column.
	 *
	 * @since 3.0
	 *
	 * @param EDD_Discount $discount Discount object.
	 * @return string Start  date
	 */
	public function column_start_date( $discount ) {
		$start_date = $discount->start_date;

		if ( $start_date ) {
			$display = edd_date_i18n( $start_date, 'M. d, Y' ) . '<br>' . edd_date_i18n( $start_date, 'H:i' );
		} else {
			$display = '&mdash;';
		}

		return $display;
	}

	/**
	 * Render the Expiration column.
	 *
	 * @since 3.0
	 *
	 * @param EDD_Discount $discount Discount object.
	 * @return string Expiration date.
	 */
	public function column_end_date( $discount ) {
		$expiration = $discount->end_date;

		if ( $expiration ) {
			$display = edd_date_i18n( $expiration, 'M. d, Y' ) . '<br>' . edd_date_i18n( $expiration, 'H:i' );
		} else {
			$display = '&mdash;';
		}

		return $display;
	}

	/**
	 * Render the Name column.
	 *
	 * @since 1.4
	 *
	 * @param EDD_Discount $discount Discount object.
	 * @return string Data shown in the Name column
	 */
	public function column_name( $discount ) {
		$base        = $this->get_base_url();
		$state       = '';
		$row_actions = array();
		$status      = $this->get_status();

		// Bail if current user cannot manage discounts
		if ( ! current_user_can( 'manage_shop_discounts' ) ) {
			return;
		}

		// State
		if ( ( ! empty( $status ) && ( $status !== $discount->status ) ) || ( 'active' !== $discount->status ) ) {
			$state = ' &mdash; ' . edd_get_discount_status_label( $discount->id );
		}

		// Edit
		$row_actions['edit'] = '<a href="' . add_query_arg( array(
			'edd-action' => 'edit_discount',
			'discount'   => $discount->id,
		), $base ) . '">' . __( 'Edit', 'easy-digital-downloads' ) . '</a>';

		// Active, so add "deactivate" action
		if ( 'active' === strtolower( $discount->status ) ) {
			$row_actions['cancel'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'edd-action' => 'deactivate_discount',
				'discount'   => $discount->id,
			), $base ), 'edd_discount_nonce' ) ) . '">' . __( 'Deactivate', 'easy-digital-downloads' ) . '</a>';

		// Inactive, so add "activate" action
		} elseif ( 'inactive' === strtolower( $discount->status ) ) {
			$row_actions['activate'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'edd-action' => 'activate_discount',
				'discount'   => $discount->id,
			), $base ), 'edd_discount_nonce' ) ) . '">' . __( 'Activate', 'easy-digital-downloads' ) . '</a>';
		}

		// Delete
		if ( 0 === (int) $discount->use_count ) {
			$row_actions['delete'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
				'edd-action' => 'delete_discount',
				'discount'   => $discount->id,
			), $base ), 'edd_discount_nonce' ) ) . '">' . __( 'Delete', 'easy-digital-downloads' ) . '</a>';
		}

		// Filter all discount row actions
		$row_actions = apply_filters( 'edd_discount_row_actions', $row_actions, $discount );

		// Wrap discount title in strong anchor
		$discount_title = '<strong><a class="row-title" href="' . add_query_arg( array(
			'edd-action' => 'edit_discount',
			'discount'   => $discount->id,
		), $base ) . '">' . stripslashes( $discount->name ) . '</a>' . esc_html( $state ) . '</strong>';

		// Return discount title & row actions
		return $discount_title . $this->row_actions( $row_actions );
	}

	/**
	 * Render the checkbox column.
	 *
	 * @since 1.4
	 *
	 * @param EDD_Discount $discount Discount object.
	 * @return string Checkbox HTML.
	 */
	public function column_cb( $discount ) {
		return 0 < $discount->use_count
			? '<input type="checkbox" title="' . esc_html__( 'This discount code cannot be deleted as it has already been used.', 'easy-digital-downloads' ) . '" disabled="disabled" />'
			: sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*$1%s*/ 'discount',
				/*$2%s*/ $discount->id
			);
	}

	/**
	 * Return discount code wrapped in a `<code>` tag.
	 *
	 * @since 3.0
	 *
	 * @param EDD_Discount $discount Discount object.
	 * @return string Discount code HTML.
	 */
	public function column_code( $discount ) {
		return '<code class="edd-discount-code">' . $discount->code . '</code>';
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since 1.7.2
	 */
	public function no_items() {
		esc_html_e( 'No discounts found.', 'easy-digital-downloads' );
	}

	/**
	 * Retrieve the bulk actions
	 *
	 * @since 1.4
	 * @return array $actions Array of the bulk actions
	 */
	public function get_bulk_actions() {
		return array(
			'activate'   => __( 'Activate',   'easy-digital-downloads' ),
			'deactivate' => __( 'Deactivate', 'easy-digital-downloads' ),
			'delete'     => __( 'Delete',     'easy-digital-downloads' )
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 1.4
	 */
	public function process_bulk_action() {

		// Bail if a nonce was not supplied.
		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-discounts' ) ) {
			return;
		}

		$ids = wp_parse_id_list( (array) $this->get_request_var( 'discount', false ) );

		// Bail if no IDs
		if ( empty( $ids ) ) {
			return;
		}

		foreach ( $ids as $id ) {
			switch ( $this->current_action() ) {
				case 'delete':
					edd_delete_discount( $id );
					break;

				case 'cancel':
					edd_update_discount_status( $id, 'cancelled' );
					break;

				case 'activate':
					edd_update_discount_status( $id, 'active' );
					break;

				case 'deactivate':
					edd_update_discount_status( $id, 'inactive' );
					break;
			}
		}
	}

	/**
	 * Retrieve the discount code counts.
	 *
	 * @since 1.4
	 */
	public function get_counts() {
		$this->counts = edd_get_discount_counts();
	}

	/**
	 * Retrieves all the data for all the discount codes.
	 *
	 * @since 1.4
	 * @deprecated 3.0 Use get_data()
	 *
	 * @return array Discount codes.
	 */
	public function discount_codes_data() {
		_edd_deprecated_function( __METHOD__, '3.0', 'EDD_Discount_Codes_Table::get_data()' );

		return $this->get_data();
	}

	/**
	 * Retrieves all of the table data for the discount codes.
	 *
	 * @since 3.0
	 *
	 * @return array Discount codes table data.
	 */
	public function get_data() {
		return edd_get_discounts( array(
			'number'  => $this->per_page,
			'paged'   => $this->get_paged(),
			'orderby' => sanitize_text_field( $this->get_request_var( 'orderby', 'id'   ) ),
			'order'   => sanitize_text_field( $this->get_request_var( 'order',   'DESC' ) ),
			'status'  => $this->get_status(),
			'search'  => $this->get_search()
		) );
	}

	/**
	 * Setup the final data for the table
	 *
	 * @since 1.4
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->get_data();

		$status = $this->get_status( 'total' );

		// Setup pagination
		$this->set_pagination_args( array(
			'total_items' => $this->counts[ $status ],
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->counts[ $status ] / $this->per_page )
		) );
	}
}