<?php

/**
 * Plugin Settings API
 * 
 * @class Easy_Donations_Options
 * @version 1.0
 */
class Easy_Donations_Options {
    
    /**
     * Holds class version
     * 
     * @var string
     */
    const version = '1.0';
    
    private static $options = null;
    
    private static $instance = null;
    
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    public function __construct() {
        if( $opts = get_option( 'easy_donations_options' ) )
            self::$options = $opts;
        else {
            update_option( 'easy_donations_options', array() );
            self::$options = array();
        }
    }
    
    public function add_option( $key, $val ) {
        self::$options[ $key ] = $val;
    }
    
    public function update_options() {
        update_option( 'easy_donations_options', self::$options );
    }
    
    public function get_option( $key = null ) {
        if( ! is_null( $key ) ) {
            if( isset( self::$options[ $key ] ) )
                return self::$options[ $key ];
            else
                return '';
        }
        else {
            return self::$options;
        }
    }
    
    public function get_options() {
        return $this->get_option();
    }

}