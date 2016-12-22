<?php

/**
 * This class is extended from a local copy of WP_List_Table. It is for generating a table of available selectors.
 * Please see wp_list_table docs in wordpress.org for more information.
 */
class Easy_Donationns_Admin_Payments_List extends EDT_WP_List_Table {
	protected $message = null;
	protected $type = null;

	/**
     * Class constructor
     */
	function __construct(){
		global $status, $page;
		parent::__construct( array(
			'singular'  => __( 'Donation', EDT_TEXT_DOMAIN ),     //singular name of the listed records
			'plural'    => __( 'Donations', EDT_TEXT_DOMAIN ),   //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
			)
		);

		add_action( 'admin_head', array( $this, 'admin_header' ) ); 

    }

	/**
     * Adds styles to admin head
     */
	function admin_header() {
		$page = ( isset( $_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		if( 'edt_' != $page )
			return;
		echo '<style type="text/css">';
		echo '.wp-list-table .column-details { width: 16.6%; }';
		echo '.wp-list-table .column-status { width: 16.6%; }';
		echo '.wp-list-table .column-txn_id { width: 16.6%; }';
        echo '.wp-list-table .column-amount { width: 16.6%; }';
        echo '.wp-list-table .column-gateway { width: 16.6%; }';
        echo '.wp-list-table .column-date { width: 16.6%; }';
		echo '</style>';
	}
	
	/**
     * Message to be shown when no selectors exists
     */
	function no_items() {
		_e( 'Nothing Found.', EDT_TEXT_DOMAIN );
	}
	
	/**
     * Column default
     * 
     * @param array $item 
     * @param string $column_name 
     * @return mixed
     */
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
			case 'details':
			case 'status':
			case 'txn_id':
            case 'amount':
            case 'gateway':
            case 'date':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}
	
	/**
     * Gets columns names
     * 
     * @return array
     */
	function get_columns(){
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'details'   => __( 'Details', EDT_TEXT_DOMAIN ),
			'status'    => __( 'Status', EDT_TEXT_DOMAIN ),
			'txn_id'    => __( 'Transaction ID', EDT_TEXT_DOMAIN ),
			'amount'    => __( 'Amount', EDT_TEXT_DOMAIN ),
            'gateway'    => __( 'Gateway', EDT_TEXT_DOMAIN ),
			'date'      => __( 'Date', EDT_TEXT_DOMAIN )
		);
		return $columns;
	}
	
	/**
     * Sorts table items
     * 
     * @param array $a 
     * @param array $b 
     * @return int or double
     */
	function usort_reorder( $a, $b ) {
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'id';
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'dec';
		$result = strcmp( $a[$orderby], $b[$orderby] );
		return ( $order === 'dec' ) ? $result : -$result;
	}
	
    /**
     * Column details special contents
     * 
     * @param array $item 
     * @return string
     */
	function column_details($item){
        //$actions = array(
        //    'edit' => sprintf('<a href="?page=%s&action=%s&id=%d">Edit</a>',$_REQUEST['page'], 'edit', $item['id'] )
        //);

        return sprintf( '%1$s', $item['details'] );//, $this->row_actions($actions) );
	}
    
	
	/**
     * Column checkbox contents
     * 
     * @param array $item 
     * @return string
     */
	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="payments[]" value="%s" />',  $item['id']
		);    
	}
	
	/**
     * Table bulk actions
     * 
     * @return array
     */
	function get_bulk_actions() {
		$actions = array(
		'delete'    => __('Delete', EDT_TEXT_DOMAIN )
		);
		return $actions;
	}
	
	/**
     * Processes table bulk actions
     */
	function process_bulk_action() {

		if( 'delete' === $this->current_action() ) {
			// security check
			if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
				$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
				$action = 'bulk-' . $this->_args['plural'];
				if ( ! wp_verify_nonce( $nonce, $action ) )
					wp_die( 'Security problem occured.' );
			}

			//TODO: must improved
			if( isset( $_POST['payments'] ) && is_array( $_POST['payments'] ) ){
				$payments_for_delete = edt_ins()->payment->get_payments_by_id_arr( $_POST['payments'] );
                edt_ins()->payment->delete_payments( $payments_for_delete );
				//$afcSelectors->updateElems( 'remove', $delSelectors );
				$this->type = 'updated';
				$this->message = count( $_POST['payments'] ) . __( ' Payment Log\'s has been deleted. ', EDT_TEXT_DOMAIN );
			}
			else{
				$this->type = 'error';
				$this->message =  __( ' Unable to access payments array. ', EDT_TEXT_DOMAIN );
			}
		}

    }
	
	/**
     * Prepares items
     */
	function prepare_items( $search = NULL ) {
		$this->process_bulk_action();
		$this->show_message();
		$perPage = 15;
		$currentPage = $this->get_pagenum();
        if( $search != NULL && trim($search) != '' ){
            $totalItems = edt_ins()->payment->get_peyments_count( true, $search );
            $result = edt_ins()->payment->get_limited_number_of_searched_payments( $search, $perPage, ( $currentPage-1 )* $perPage );
        }
        else{
            $totalItems = edt_ins()->payment->get_peyments_count();
            $result = edt_ins()->payment->get_limited_payments( $perPage, ( $currentPage-1 )* $perPage );
        }
		$this->found_data = $this->beautify_results( $result );

		$this->set_pagination_args( array(
			'total_items' => $totalItems,                  //Calculate the total number of items
			'per_page'    => $perPage                      //Determine how many items to show on a page
			)
		);
		$this->items = $this->found_data;
	}
    
    function beautify_results( $arr ) {
        $temp = array();
        if( is_array( $arr ) ) {
            foreach( $arr as $key ) {
                $key['gateway'] = $key['details']['gateway']['value'];
                $key['details'] = sprintf('<a href="?page=%s&action=%s&payment_id=%d">'. __( 'View Details', EDT_TEXT_DOMAIN ) . '</a>', $_REQUEST['page'], 'show_info', $key['id'] );
                $temp[] = $key;
            }
        }

        return $temp;
    }
	
	/**
     * Shows list of errors
     */
	function show_message() {
		if($this->message != null && $this->type != null )
			echo '<div id="edt-setting-error" class="' . $this->type . ' settings-error"><p><strong>' . $this->message . '</strong></p></div>';
	}
}