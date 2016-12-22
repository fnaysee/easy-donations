<?php

class Easy_Donations_Gateway {
    
    const version = '1.0';
    
    private static $gateways = null;
    
    protected $posted_data = null;
    
    protected $session_key = null;
    
    public function __construct() {
        if( isset( $_GET['edt_after_payment'] ) && $_GET['edt_after_payment'] == '1' ){
            if( ! isset( $_GET['pay_id'] ) || ! is_numeric( $_GET['pay_id'] ) )
                wp_die( __( 'Returned Payment ID was invalid.' , EDT_TEXT_DOMAIN ) );
            $this->posted_data = $_POST;
            add_action( 'init', array( $this, 'do_on_return' ) );       
        }
    }
    
    /**
     * Register gateway to plugin gateways list
     * $class_name is the name of your gateway class which is extended from this class
     * 
     * @param string $slug 
     * @param string $class_name 
     */
    public function register_gateway( $gateway_id, $title, $class_name, $img_url = null ) {
        self::$gateways[ $gateway_id ] = array(
                'id'    => $gateway_id,
                'title' => $title,
                'class' => $class_name
            );
        $gtw = new $class_name;
        add_action( 'init', array( $gtw, 'gateway_settings_fields' ) );

    }
    
    public function add_gtw_setting( $gateway_id, $table_title, $settings, $callback_before = null, $callback_after = null ) {
        $active_gateways = edt_ins()->options->get_option( 'active_gateways' );
        $active_gateways = ( is_array( $active_gateways ) ) ? $active_gateways : array();

        $temp = $settings;
        $settings = array();
        $settings[] = array(
                'id'      => $gateway_id,
                'name'    => edt_ins()->general_page->settings_name . "[active_gateways][{$gateway_id}]",
                'type'    => 'checkbox',
                'text'    => "<label for='{$gateway_id}'>" . __( "Enable", EDT_TEXT_DOMAIN ) . "</label>",
                'checked' => ( ( array_key_exists( $gateway_id, $active_gateways ) ) ? true : false )
            );
        
        $settings[] = array(
                'name'    => edt_ins()->general_page->settings_name . "[{$gateway_id}][rubbish]",
                'type'    => 'hidden',
                'value'   => 'rubbish'
            );
        
        foreach ( $temp as $item ) {
            $item['name'] = edt_ins()->general_page->settings_name . "[{$gateway_id}]{$item['name']}" ;
            $settings[] = $item;
        }
        
        edt_ins()->general_page->register_opts( 'gateways', $table_title, $settings, $callback_before, $callback_after );
    }
    
    public function get_gateways() {
        if( ! is_null( self::$gateways ) )
            return self::$gateways;
        else 
            return false;
    }
    
    /**
     * Any thing that must be done befor sending to gateway
     * Override it in your gateway class
     */
    protected function before_send( $payment ) {
    }
    
    /**
     * Any thing that must be done when user is returned from gateway
     * override it in your gateway class
     * us edt_ins()->payment->complete_payment( $payment ) to complete the payment
     */
    public function on_return( $payment, $post ) {
    }
    
    /**
     * Summary of run_gateway
     * 
     * @param mixed $gateway 
     * @param mixed $payment 
     */
    public function send_to_gateway( $gateway_id, $payment ) {
        if( ! isset( self::$gateways[ $gateway_id ] ) ){
            $this->add_message( __( 'This gateway is not registered' , EDT_TEXT_DOMAIN ), 'error' );
            wp_redirect( get_bloginfo('wpurl') . esc_url_raw( add_query_arg( array() ) ) );
        }
        
        $pay_session = edt_ins()->session->get_session( 'edt_payment_infos' );
        if( ! $payment )
            wp_die( __( 'Payment session is not set.' , EDT_TEXT_DOMAIN ) );
        $statuses = array( 'completed', 'canceled', 'failed' );
        foreach( $pay_session as $key => $pay ) {
            if( ( time() - $pay['session_creation_time'] ) > 900 || in_array( $pay['details']['status']['name'], $statuses ) ) {
                unset( $pay_session[ $key ] );
                continue;
            }

            if( $pay['id'] == $payment['id'] ) {
                $this->session_key = $key;
                break;
            }
        }
        
        if( is_null( $this->session_key ) )
            wp_die( __( 'This payment session is expired.' , EDT_TEXT_DOMAIN ) );
        
        $payment['pay_url'] = add_query_arg( array( 'edt_session_key' => $this->session_key ), $payment['pay_url'] );
        $payment['details']['gateway']['value'] = self::$gateways[ $gateway_id ]['title'];
        $pay_session[ $this->session_key ] = $payment;
        edt_ins()->session->set_session( 'edt_payment_infos', $pay_session );
            
        $tmpgty = new self::$gateways[ $gateway_id ]['class'];
        $tmpgty->before_send( $payment );
    }

    /**
     * Handling events after user returned from payment gateway
     */
    public function do_on_return() {
        
        $temp = edt_ins()->payment->get_payment_by_id( $_GET['pay_id'] );
        $temp = $temp[0];
        if( ! isset( $temp['id'] ) )
            wp_die( __( 'No payment with this id exists.' , EDT_TEXT_DOMAIN ) );
        
        $payment = edt_ins()->session->get_session( 'edt_payment_infos' );
        if( ! $payment )
            wp_die( __( 'Payment data is not set.' , EDT_TEXT_DOMAIN ) );
        
        $temp2 = array();
        foreach( $payment as $key => $pay ) {
            if( $pay['id'] == $temp['id'] ){
                $temp2 = $pay;
                $this->session_key = $key;
                break;
            }
        }
            
        if( empty( $temp2 ) ) {
            wp_die( __( 'There is no session with this id.' , EDT_TEXT_DOMAIN ) );
        }
            
        $gateway_id = $temp2['gateway_id'];
        if( ! isset( self::$gateways[ $gateway_id ] ) ){
            $this->add_message( __( 'This gateway is not registered' , EDT_TEXT_DOMAIN ), 'error' );
            wp_redirect( $payment['pay_url'] );
        }
            
        $tmpgty = new self::$gateways[ $gateway_id ]['class'];
        $tmpgty->on_return( $temp2, $this->posted_data );
    }
    
    /**
     * Url that we must redirect the user to, after returning from gateway
     * You can store it in a session , but befor using it you must check wether user has set a return page or not.
     * 
     * @param mixed $payment_id 
     * @return string
     */
    public function form_page_url() {
        $url = ( isset( $_SERVER['HTTPS'] ) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        return $url;
    }
    
    /**
     * Url that users go into when gateway sends them to our website
     * 
     * @param int $id //payment id
     * @return mixed
     */
    public function return_url( $id, $encode = false ) {
        $url = get_site_url();
        $url = add_query_arg( array( 'edt_after_payment' => '1', 'pay_id' => $id ), $url . '/' );
        if( $encode )
            return urlencode( $url );
        else
            return $url;
    }
    
    public function add_message( $message, $type ) {
        $session_key = edt_ins()->gateways->session_key;
        $pay_session = edt_ins()->session->get_session( 'edt_payment_infos' ); 
        $pay_session[ $session_key ]['errors'][] = array( 'message'=> $message, 'type' => $type );
        edt_ins()->session->set_session( 'edt_payment_infos', $pay_session );
    }
}