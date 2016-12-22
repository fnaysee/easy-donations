<?php

class Easy_Donations_Payments extends Easy_Donations_DB {

    const version = '1.1';
    
    public function __construct() {
        parent::__construct();
    }

    public function create_payment( $data ) {
        if( ! is_array( $data ) )
            die('payment data must be array');
        if( empty( $data ) )
            die('could not create a payment from empty array');
        
        //if( isset( $data['details'] ) ) {
            $data['details']['gateway'] = array( 'title' => __( 'Gateway', EDT_TEXT_DOMAIN ), 'value' => '' );
            $data['details']['status'] = array( 'name' => 'pending', 'title' => __( 'Status', EDT_TEXT_DOMAIN ), 'value' => __( 'Pending', EDT_TEXT_DOMAIN ) );
        //}
        //else {
            //$data['details']['gateway'] = array( 'title' => __( 'Gateway', EDT_TEXT_DOMAIN ), 'value' => '' );
            //$data['details']['status'] = array( 'name' => 'pending', 'title' => __( 'Status', EDT_TEXT_DOMAIN ), 'value' => __( 'Pending', EDT_TEXT_DOMAIN ) );
        //}

        $amount = $data['amount'];
        
        $gateway = $data['gateway'];

        $payment = array(
                'details'           => serialize( $data['details'] ),
                'status'            => __( 'Pending', EDT_TEXT_DOMAIN ),
                'txn_id'            => '',
                'amount'            => $amount,
                'date'              => $this->get_date()
            );

        $pay_id = $this->store_payment( $payment );

        $payment['id'] = $pay_id;
        $payment['pay_url'] = 'http' . ( isset( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $payment['pay_url'] = remove_query_arg( 'edt_session_key', $payment['pay_url'] );
        $payment['details'] = unserialize( $payment['details'] );
        $payment['gateway_id'] = $gateway;
        $payment['errors'] = array();
        $payment['session_creation_time'] = time();
        
        $temp = edt_ins()->session->get_session( 'edt_payment_infos' );
        
        if( is_array( $temp ) ) {
            $temp[] = $payment;
        }
        else {
            $temp = array( $payment );
        }

        edt_ins()->session->set_session( 'edt_payment_infos', $temp );

        edt_ins()->gateways->send_to_gateway( $gateway, $payment );
    }
    
    protected function store_payment( $payment ){
        global $wpdb;
        $wpdb->insert( $this->dns_table, $payment );
        return $wpdb->insert_id;
    }
    
    public function complete_payment( $payment, $status, $trans_id = '' ) {
        $status_name = ''; $status_value = '';
        switch ( $status ) {
            case 'completed':
                $status_name = 'completed';
                $status_value = __( 'Completed', EDT_TEXT_DOMAIN );
                break;
            case 'canceled':
                $status_name = 'canceled';
                $status_value = __( 'Canceled', EDT_TEXT_DOMAIN );
                break;
            default:
                $status_name = 'failed';
                $status_value = __( 'Failed', EDT_TEXT_DOMAIN );
                break;
        }
        $tmp = $payment;
        $tmp['details']['status']['name'] = $status_name;
        $tmp['details']['status']['value'] = $status_value;
        $temp = array(
                'details'           => serialize( $tmp['details'] ),
                'status'            => $status_value,
                'txn_id'            => $trans_id,
                'amount'            => $payment['amount'],
                'date'              => $this->get_date()
            );
        $this->update_payment_by_id( $payment['id'], $temp );
        
    }
    
    protected function update_payment_by_id( $id, $args = array() ) {
        global $wpdb;
        $wpdb->update( $this->dns_table, $args, array( 'id' => $id ) );
    }
    

    
    /**
     * Returns limited number of available rows in payments table
     * 
     * @param int $limit 
     * @param int $offset 
     * @return array
     */
	public function get_limited_payments( $limit, $offset ){
		global $wpdb;
		$sql = "SELECT * FROM {$this->dns_table} LIMIT {$limit} OFFSET {$offset}";
		$result = $wpdb->get_results( $sql, ARRAY_A );
        if( !empty( $result ) )
            $result = $this->unserialize_arr( $result );
        return $result;
	}
    
    /**
     * Returns limited number of available rows in payments table (based on user search)
     * 
     * @param int $limit 
     * @param int $offset 
     * @return array
     */
	public function get_limited_number_of_searched_payments( $search, $limit, $offset ){
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$this->dns_table} WHERE details LIKE '%%%s%%' OR status LIKE '%%%s%%' OR txn_id LIKE '%%%s%%' OR amount LIKE '%%%s%%' LIMIT {$limit} OFFSET {$offset}", $search, $search, $search, $search );
		$result = $wpdb->get_results( $sql, ARRAY_A );
        if( !empty( $result ) )
            $result = $this->unserialize_arr( $result );
        return $result;
	}
    
    public function get_payments_by_id_arr( $id_arr ) {
        global $wpdb;
        $count = count( $id_arr );
        $sql = "SELECT * FROM {$this->dns_table} WHERE ";
        foreach( $id_arr as $key=>$val ){
            if( $count != $key + 1  )
                $sql .= "id = '$val' OR ";
            else
                $sql .= "id = '$val'";
        }
        
        $result = $wpdb->get_results( $sql, ARRAY_A );
        if( !empty($result) )
            $result = $this->unserialize_arr( $result );
        return $result;
    }
    
    /**
     * get_payment_by_id
     * 
     * @param mixed $id 
     * @return mixed
     */
    public function get_payment_by_id( $id ) {
        global $wpdb;
		$sql = "SELECT * FROM {$this->dns_table} WHERE id='{$id}'";
        
		$result = $wpdb->get_results( $sql, ARRAY_A );
        if( ! empty( $result ) )
            $result = $this->unserialize_arr( $result );
        
        return $result;       
    }
    
    public function delete_payments( $arr ) {
        global $wpdb;
        $count = count( $arr );
        $sql = "DELETE FROM {$this->dns_table} WHERE id IN (";
        foreach( $arr as $key=>$val )
            if( $count != $key + 1 )
                $sql .= $val['id'] . ",";
            else
                $sql .= $val['id'] . ")";
        $wpdb->query( $sql );
    }
    
    /**
     * To unserialize fields that was serialized
     * 
     * @param array $arr 
     * @return array
     */
	public function unserialize( $arr ) {
        $temp = $arr;
		if( isset( $arr['details'] ) ){
			$temp['details'] = unserialize( $arr['details'] );
        }
		return $temp;
	}
    
    /**
     * To unserialize array of fields that was serialized
     * 
     * @param array $arr 
     * @return array
     */
	public function unserialize_arr( $arr ) {
		$data = array();
		foreach( $arr as $key ){
			if( isset( $key['details'] ) ){
				$key['details'] = unserialize( $key['details'] );
            }
			$data[] = $key;
		}
		return $data;
	}
    
    /**
     * Gets the count of payments
     * 
     * @return int
     */
	public function get_peyments_count( $is_search = false, $search = NULL ) {
		global $wpdb;
        if( $is_search )
            $sql = $wpdb->prepare("SELECT count(*) FROM {$this->dns_table} WHERE details LIKE '%%%s%%' OR status LIKE '%%%s%%' OR txn_id LIKE '%%%s%%' OR amount LIKE '%%%s%%'", $search, $search, $search, $search );
        else
            $sql = "SELECT count(*) FROM {$this->dns_table};";
		return $wpdb->get_var( $sql );
	}
    
    public function get_date() {
        if( function_exists( 'pdate' ) ) {
            return pdate( 'Y-m-d H:i:s' );
        }
        elseif( function_exists( 'jdate' ) ) {
            return jdate( 'Y-m-d H:i:s' );
        }
        else
            return date( 'Y-m-d H:i:s' );
    }
}