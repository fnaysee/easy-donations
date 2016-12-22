<?php
/**
 * Database Provider
 * 
 * @version 1.0
 */
abstract class Easy_Donations_DB{
    
    /**
     * Class version
     */
    const version = '1.0';
    
    /**
     * Tables Character Set
     * 
     * @var string
     */
    protected $charset = null;
    
    /**
     * Tables prefix
     * 
     * @var mixed
     */
    protected $prefix = null;
    
    
    protected $dns_table = null;
    
    /**
     * Class constructor
     */
    public function __construct() {
        $this->set_defaults();
    }
    
    /**
     * Set defaults
     */
    private function set_defaults(){
        global $wpdb;
        
        $this->charset = $wpdb->get_charset_collate();
        $this->prefix = $wpdb->prefix;
        $this->dns_table = $this->fix_table_name( 'edt_payments' );
    }
    
    /**
     * Check to find if table exists
     * 
     * @param string $table_name 
     * @return bool
     */
    protected function table_exist( $table_name ) {
        global $wpdb;
		return $wpdb->get_var("SHOW TABLES LIKE '{$this->fix_table_name( $table_name )}'") == $this->fix_table_name( $table_name );
    }
    
    /**
     * Create table if not exists
     * 
     * @param string $table_name 
     * @param string $query 
     * @return bool
     */
    protected function create_table( $table_name, $query ) {
        if( ! self::table_exist( $table_name ) ) {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $query );
			return true;
		}
		else{
			return false;
		}
    }
    
    public function fix_table_name( $table_name ) {
        return $this->prefix . $table_name;
    }
    

}