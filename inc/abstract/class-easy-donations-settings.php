<?php

/**
 * Plugin Settings API
 * 
 * @class Easy_Donations_Settings
 * @version 1.0
 */
class Easy_Donations_Settings extends Easy_Donations_Settings_API {
    
    /**
     * Holds class version
     * @var string
     */
    const version = '1.0';
    
    /**
     * Settings group name
     * 
     * @var string
     */
    protected $settings_group = null;
    
    /**
     * Settings option name
     * 
     * @var string
     */
    public $settings_name = null;
    
    /**
     * Validator function to validate current settings group
     * 
     * @var callback
     */
    protected $validator_function = null;
      
    protected $messages = null;
    
    protected $msg_type = null;
    
    /**
     * Class constructor
     */
    public function __construct() {
    }
    
    /**
     * Setup menus
     */
    public function set_menu(){
        add_action( 'admin_menu', array( $this, 'add_menus' ) );
    }
    
    /**
     * Add menus
     */
    public function add_menus() {
        do_action( 'easy_donations_menu_items' );
    }
    
    /**
     * create_settings
     * 
     * @param string $setting_name 
     * @param string $errors_name 
     * @param callback $validator_function 
     */
    public function create_setting( $settings_group, $settings_name, $validator_function ) {
        $this->set_values( $settings_group, $settings_name, $validator_function );
        $this->register_setting();
    }
    
    /**
     * Set value of class vars
     * 
     * @param string $settings_group 
     * @param string $settings_name 
     * @param callback $validator_function 
     */
    public function set_values( $settings_group, $settings_name, $validator_function ) {
        $this->settings_group = $settings_group;
        $this->settings_name = $settings_name;
        $this->validator_function = $validator_function;
    }
    
    /**
     * Register a wp settings api setting
     * 
     */
    public function register_setting() {
        register_setting( $this->settings_group, $this->settings_name, $this->validator_function );
    }
    
    /**
     * Add a section to settings group
     * 
     * @param string $id 
     * @param string $title 
     * @param callback $callback 
     */
    public function add_section( $id, $title, $callback ) {
        add_settings_section( $id, $title, $callback, $this->settings_group );
    }
    
    /**
     * Add a field to settings group
     * 
     * @param string $id 
     * @param string $title 
     * @param callback $callback 
     * @param string $section 
     */
    public function add_field( $id, $title, $callback, $section, $args = null ) {
        add_settings_field( $id, $title, $callback, $this->settings_group, $section, $args );
    }
    
    /**
     * Generate a field for admin settings form
     * 
     * @param mixed $args 
     * @return mixed
     */
    public function get_form_field( $args ) {
        return $this->get_a_form_field( $args );
    }
    
}