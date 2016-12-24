<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name: Easy Donations
 * Plugin URI: http://wp-src.ir
 * Description: Using this plugin you can let your users receives donations easily in their website
 * Version: 1.4.1
 * Author: Farhan
 * Author URI: http://wp-src.ir
 */

load_plugin_textdomain( 'easy-donations', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

if( !class_exists('Easy_Donations') ){

    /**
     * Plugin Main Class
     * 
     * @class Easy_Donations
     * @version 1.0
     */
    final class Easy_Donations {
        
        /**
         * Holds plugin version
         * @var string
         */
        const version = '1.0';

        public $session = null;

        public $form = null;

        public $gateways = null;
        
        public $payment = null;

        public $options = null;

        public $general_page = null;

        /**
         * An instance of this class
         * 
         * @var object
         */
        protected static $instance = null;
        
        /**
         * Easy_Donations Instance
         *
         * @since 1.0
         * @static
         * @return Easy_Donations instance
         */
        public static function instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self;
            }
            return self::$instance;
        }
        
        /**
         * Class Constructor
         * 
         */
        public function __construct() {
            
            $this->define_constants();
            $this->includes();
            $this->init();
        }
        

        /**
         * Defines Constants
         */
        private function define_constants() {
            $name = 'EASYDONATIONS_PLUGIN';
            $this->define( "{$name}_PATH", plugin_dir_path( __FILE__ ) );
            $this->define( "{$name}_URL", plugin_dir_url( __FILE__ ) );
            $this->define( "{$name}_VERSION", self::version );
            $this->define( "EDT_TEXT_DOMAIN", 'easy-donations' );
        }
        
        /**
         * Define constant if not already set
         * @param string $name 
         * @param mixed $value 
         */
        private function define( $name, $value ) {
            if( !defined( $name ) )
                define( $name, $value );
        }
        
        /**
         * Including files
         * 
         */
        public function includes() {
            $dir = EASYDONATIONS_PLUGIN_PATH;
            include_once $dir . 'inc/abstract/class-easy-donations-db.php';
            include_once $dir . 'inc/abstract/class-easy-donations-settings-api.php';
            include_once $dir . 'inc/abstract/class-easy-donations-settings.php';
            include_once $dir . 'inc/class-easy-donations-options.php';
            include_once $dir . 'inc/class-easy-donations-session.php';
            include_once $dir . 'inc/class-easy-donations-payments.php';
            include_once $dir . 'inc/class-easy-donations-form.php';
            include_once $dir . 'inc/class-easy-donations-gateway.php';
            include_once $dir . 'inc/widgets/class-easy-donations-widgets.php';
            //Default Gateways
            include_once $dir . 'inc/gateways/class-edt-pardakhtpal-gateway.php';
			include_once $dir . 'inc/gateways/class-edt-pardakhtshahr-gateway.php';
            include_once $dir . 'inc/gateways/class-edt-perfectmoney-gateway.php';
			include_once $dir . 'inc/gateways/class-edt-bitpay-gateway.php';
            include_once $dir . 'inc/gateways/class-edt-blockchain-gateway.php';
            include_once $dir . 'inc/class-easy-donations-admin-general.php';
            //for admins
            if( is_admin() ) {
                include_once $dir . 'inc/abstract/class-wp-list-table.php';
                
                include_once $dir . 'inc/class-easy-donations-admin-payments.php';
                include_once $dir . 'inc/class-easy-donations-admin-payments-list.php';
            }
        }
        
        /**
         * Loading scripts
         */
        public function enqueue_admin_scripts(){
            
            if( is_admin() ){
                wp_enqueue_style( 'edt-admin', EASYDONATIONS_PLUGIN_URL . "assets/css/admin-styles.css" );
                
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'admin-js', EASYDONATIONS_PLUGIN_URL . "assets/js/admin-js.js" );
                
                wp_enqueue_media();
                
            }
        }
        
        /**
         * init
         */
        public function init() {
            register_activation_hook( __FILE__, array( $this, 'install' ) );
            
            $this->options = new Easy_Donations_Options;
            $this->session = new Easy_Donations_Session;
            $this->payment = new Easy_Donations_Payments;
            $this->gateways = new Easy_Donations_Gateway;
            $this->form = new Easy_Donations_Form;
            $this->general_page = new Easy_Donations_Admin_General;

            
            add_action( 'admin_enqueue_scripts' , array( $this , 'enqueue_admin_scripts' ) );
        }
        
        /**
         * Plugin Installation
         */
        public function install(){
            include_once EASYDONATIONS_PLUGIN_PATH . 'inc/class-easy-donations-install.php';
            Easy_Donations_Install::instance();
        }
    }
    
}

/**
 * Creates an instance of plugin main class
 * @return object
 */
function edt_ins() {
    return Easy_Donations::instance();
}

edt_ins();

/**
 * Prints the plugin form
 */
function the_easy_donations_form(){
    echo edt_ins()->form->get_easy_donations_form();
}