<?php

/**
 * Plugin Installer
 * 
 * @version 1.0
 * 
 */
final class Easy_Donations_Install extends Easy_Donations_DB {
    
    /**
     * Class version
     */
    const version = '1.0';
    
    /**
     * An instance of this class
     * 
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Easy_Donations_install Instance
     *
     * @static
     * @return object
     */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
    
    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();
        $this->create_tables();
        $this->add_default_fields();
    }
    
    /**
     * Create plugin table if not exist
     */
    private function create_tables() {
        $this->create_table(
                $this->dns_table,
                "CREATE TABLE {$this->dns_table} (
					  id bigint(11) NOT NULL auto_increment,
                      details text NOT NULL,
					  status varchar(20) NOT NULL,
					  txn_id varchar(50) NOT NULL,
                      amount int(9) NOT NULL,
                      date varchar(40),
					  UNIQUE KEY id (id)
				) {$this->charset};"
            );
    }
    
    private function add_default_fields() {
        $fields = array(
                'donate_form_fields' => array(
                    array(
                        'id'        => 'edt_default_field_name',
                        'name'      => 'donator-name',
                        'title'     => __( 'Name', EDT_TEXT_DOMAIN ),

                    ),
                    array(
                        'id'        => 'edt_default_field_family',
                        'name'      => 'donator-family',
                        'title'     => __( 'Family', EDT_TEXT_DOMAIN ),

                    ),
                    array(
                        'id'        => 'edt_default_field_web',
                        'name'      => 'donator-web',
                        'title'     => __( 'Website', EDT_TEXT_DOMAIN ),

                    ),                
                    array(
                        'id'        => 'edt_default_field_email',
                        'name'      => 'donator-email',
                        'title'     => __( 'Email', EDT_TEXT_DOMAIN ),
                    ),                
                    array(
                        'id'        => 'edt_default_field_phone',
                        'name'      => 'donator-phone',
                        'title'     => __( 'Phone', EDT_TEXT_DOMAIN ),
                    ),                
                    array(
                        'id'        => 'edt_default_field_comment',
                        'name'      => 'donator-comment',
                        'title'     => __( 'Comment', EDT_TEXT_DOMAIN ),
                        'type'      => 'textarea'
                    )
                ),
                'donate_form_active_fields' => array( 
                    'donator-name', 
                    'donator-family', 
                    'donator-web', 
                    'donator-email', 
                    'donator-phone', 
                    'donator-comment' 
                ),
                'donate_form_required_fields' => array( 
                    'donator-name', 
                    'donator-email'
                ),
                'form_background_type' => 'parallax',
                'donate_form_amount_field' => array(
                    'type' => 'user-input',
                    'fixed' => array(
                        'donate-amount-1' => array(
                            'id'        => 'donate-amount-1',
                            'name'      => 'donate-amount-1',
                            'title'     => '',
                            'type'      => 'radio',
                            'value'     => '2000'
                        )
                    )
                ),
                'donate_form_active_currency' => array(
                    'Code' => 'IRT',
                    'Title' => __( 'تومان', EDT_TEXT_DOMAIN )
                )
            );
        
        $has_change= false;
        if( edt_ins()->options->get_option( 'donate_form_fields' ) == '' ) {
            edt_ins()->options->add_option( 'donate_form_fields', $fields['donate_form_fields'] );
            $has_change = true;
        }
        if( edt_ins()->options->get_option( 'donate_form_active_fields' ) == '' ) {
            edt_ins()->options->add_option( 'donate_form_active_fields', $fields['donate_form_active_fields'] );
            $has_change = true;
        }
        if( edt_ins()->options->get_option( 'donate_form_required_fields' ) == '' ) {
            edt_ins()->options->add_option( 'donate_form_required_fields', $fields['donate_form_required_fields'] );
            $has_change = true;
        }
        if( edt_ins()->options->get_option( 'form_background_type' ) == '' ) {
            edt_ins()->options->add_option( 'form_background_type', $fields['form_background_type'] );
            $has_change = true;
        }
        if( edt_ins()->options->get_option( 'donate_form_amount_field' ) == '' ) {
            edt_ins()->options->add_option( 'donate_form_amount_field', $fields['donate_form_amount_field'] );
            $has_change = true;
        }
        if( edt_ins()->options->get_option( 'donate_form_active_currency' ) == '' ) {
            edt_ins()->options->add_option( 'donate_form_active_currency', $fields['donate_form_active_currency'] );
            $has_change = true;
        }
        
        if( $has_change ) {
            edt_ins()->options->update_options();
        }
    }
}