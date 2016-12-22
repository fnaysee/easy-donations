<?php

/**
 * Plugin Main Class
 * 
 * @class Easy_Donations
 * @version 1.0
 */
class Easy_Donations_Session {
        
    /**
     * Holds class version
     * 
     * @var string
     */
    const version = '1.0';

    /**
     * Class Constructor
     */
    public function __construct() {
        $this->register_session();
        
    }
    
    public function register_session() {
        if( session_id() == '' ) {
            session_start();
        }
    }
    
    /**
     * Summary of set_session
     * 
     * @param string $name 
     * @param mixed $value 
     */
    public function set_session( $name, $value ) {
        $_SESSION[ $name ] = $value;
    }
    
    public function get_session( $name ) {
        if( isset( $_SESSION[ $name ] ) ) {
            return $_SESSION[ $name ];
        }
        
        return false;
    }
    
    public function remove_session( $name ) {
        unset( $_SESSION[ $name ] );
    }
}