<?php

/**
 * Plugin Settings API
 * 
 * @class Easy_Donations_Settings
 * @version 1.0
 */
class Easy_Donations_Admin_Payments extends Easy_Donations_Settings {
    
    /**
     * Holds class version
     * @var string
     */
    const version = '1.0';
    
    private $payments_list = null;
    
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
        if( is_null( self::$instance ) )
            self::$instance = new self;
        return self::$instance;
    }
    
    public function __construct() {
        add_action( 'easy_donations_menu_items', array( $this, 'register_menus' ), 2 );
        //$this->payments_info_page = new Easy_Donationns_Admin_Payments_Info();
    }
    
    public function register_menus() {
        $hook = add_submenu_page( 'edt_plugin_options', __( 'Payments', EDT_TEXT_DOMAIN ),  __( 'Payments List', EDT_TEXT_DOMAIN ), 'manage_options', 'edt_payments_page', array( $this, 'edt_payments_page_content' ) );
        add_action( "load-{$hook}", array( $this, 'set_default_payments_table_options' ) );
        $hook = add_submenu_page( null, __( 'Payments', EDT_TEXT_DOMAIN ),  __( 'Payments List', EDT_TEXT_DOMAIN ), 'manage_options', 'edt_payments_info', array( $this, 'edt_payments_page_content' ) );

    }
    
    public function edt_payments_page_content() {
        $this->choose_content();
    }
    
    public function choose_content() {
        $goto_info_page = false;
        $payment = null;
        if( isset( $_GET['payment_id'] ) ) {
            $payment = edt_ins()->payment->get_payment_by_id( $_GET['payment_id'] );
            if( $payment ){
                $goto_info_page = true;
            }
        }
        
        if( ! $goto_info_page )
            $this->print_the_list();
        else {
            $this->print_info_page( $payment );
        }
    }
    
    public function print_the_list() {
        if( isset( $_POST['s'] ) ){
            $this->payments_list->prepare_items( $_POST['s'] );
        }
        else{
            $this->payments_list->prepare_items();
        }
        ?>
        <div class="edt-wrap">
		<form method="post">
			<input type="hidden" name="page" value="afc_manageselectors">
		<?php
        $this->payments_list->search_box('Search', 'search_id');
        

        $this->payments_list->display(); 
        echo '</form></div>';
    }
    
    public function print_info_page( $payment ) {
        $payment = $payment[0]['details'];
        ?>
        <div class="edt-wrap">
            <table class="form-table" >
                <?php if( is_array( $payment ) && ! empty( $payment ) ) : 
                    foreach( $payment as $field ) :
                    ?>
                <tr>
                    <th>
                    <?php echo $field['title']; ?>
                    </th>
                    <td>
                    <?php echo $field['value']; ?>
                    </td>
                </tr>
                <?php  
                    endforeach;
                      endif;
                ?>
            </table>
        </div>
        <?php
    }
    
    public function set_default_payments_table_options() {
        $option = 'per_page';
        $args = array(
            'label' => 'Payments',
            'default' => 10,
            'option' => 'payments_per_page'
            );
        add_screen_option( $option, $args );
        
        $this->payments_list = new Easy_Donationns_Admin_Payments_List();
        return $this->payments_list;
    }
}

Easy_Donations_Admin_Payments::instance();