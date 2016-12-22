<?php
defined( 'ABSPATH' ) OR exit;

/**
 * Donate Form
 * 
 * @class Easy_Donations
 * @version 1.0
 */
class Easy_Donations_Form extends Easy_Donations_Settings_API {
    
    /**
     * Holds plugin version
     * @var string
     */
    const version = '1.0';
    
    const messages_session = 'edt_form_messages';
    
    private static $form_fields = null;
    
    private static $active_fields = null;
    
    private static $required_fields = null;
    
    private static $fields_html = null;
    
    protected $messages = null;
    
    public function __construct(){

        add_action( 'init', array( $this, 'set_donation_fields' ), 9 );
        //$this->set_donation_fields();
        
        add_shortcode( 'EasyDonations', array( $this, 'do_short_code' ) );

        add_action( 'init', array( $this, 'flush_form_messages' ), 9 );
        
        if( isset( $_POST['easy_donations_form'] ) && $_POST['easy_donations_form'] == '1' ) {
            add_action( 'init', array( $this, 'validate_form' ), 10 );
        }
    }
    
    public function add_message( $message, $type, $from = null ) {
        $messages = $this->get_messages();
        $messages[] = array(
                    'message'   => $message,
                    'type'      => $type,
                    'from'      => ( is_null( $from ) ) ? 'form' : $from
                );
        
        edt_ins()->session->set_session( self::messages_session, $messages );
    }
    
    private function get_messages() {
        $messages_session = edt_ins()->session->get_session( self::messages_session );
        return ( $messages_session ) ? $messages_session : array();
    }
    
    public function flush_messages( $from = null ) {
        if( ! is_null( $from ) ) {
            $messages = $this->get_messages();
            foreach( $messages as $msg => $val ) {
                if( $from == $val['from'] ) {
                    unset( $messages[ $msg ] );
                }
            }
            edt_ins()->session->set_session( self::messages_session, $messages );
        }
        else {
            edt_ins()->session->remove_session( self::messages_session );
        }
    }
    
    //public function flush_gateway_messages() {
    //    $this->flush_messages( 'gateway' );
    //}
    
    public function flush_form_messages() {
        $this->flush_messages( 'form' );
    }
    
    /**
     * Still needs work
     * 
     * @return mixed
     */
    public function set_donation_fields() {
        $form_fields = edt_ins()->options->get_option('donate_form_fields');
        $active_fields = edt_ins()->options->get_option('donate_form_active_fields');
        $required_fields = edt_ins()->options->get_option('donate_form_required_fields');
        self::$active_fields = ( is_array( $active_fields ) ) ? $active_fields : array();
        self::$required_fields = ( is_array( $required_fields ) ) ? $required_fields : array();
        self::$fields_html = array();

        $temp = array();
        foreach( $form_fields as $val ) {
            if( array_key_exists( $val['name'], self::$active_fields ) ) {
                $temp[] = array(
                    'title' => $val['title'],
                    'field' => array(
                        'id'        => $val['id'],
                        'name'      => "form-fields[{$val['name']}]",
                        'real_name' => $val['name'],
                        'type'      => ( isset( $val['type'] ) ) ? $val['type'] : '',
                        'value'     => ''
                    )
                );
            }
        }
        self::$form_fields = $temp;
        foreach( $temp as $item ) {
            self::$fields_html[] = "<div><label class='edt-title' for='{$item['field']['id']}'>{$item['title']}</label><span class='the-fields'>" . $this->get_a_form_field( $item['field'] ) . "</span></div>";
        }
        
        return apply_filters( "edt_donate_form_fields", self::$fields_html );
    }
    
    public function get_form_fields(){
        return implode( '', self::$fields_html );
    }
    
    public function get_amount_field() {
        $byuser = false;
        $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
        $active_currency = ( $active_currency != '' ) ? $active_currency : array( 'Code' => 'IRT', 'Title' => __( 'تومان', EDT_TEXT_DOMAIN ) ) ;
        if( ( $amount_field = edt_ins()->options->get_option( 'donate_form_amount_field' ) ) != '' ) {
            if( $amount_field['type'] == 'fixed' ) {
                $amounts = "<div class='amount-field'><label class='edt-title' for='donation-amount'>" . __( 'Amount', EDT_TEXT_DOMAIN ) . "&nbsp;&nbsp;</label><div class='donate-prices' >";
                foreach( $amount_field['fixed'] as $amount ) {
                    $amount['text'] = "<label for='{$amount['id']}'>" . $amount['value'] . '<span class="currency">' . $active_currency['Title'] . "</span></label>";
                    $amount['name'] = 'donate-amount';
                    $amounts .= $this->get_a_form_field( $amount );
                }
                $amounts .= "</div></div>";
                return $amounts;
            }
            else{
                $byuser = true;
            }
        }
        else {
            $byuser = true;
        }
        
        if( $byuser ) {
            return "<div class='amount-field'><label class='edt-title' for='donate-amount'>" . __( 'Amount:', EDT_TEXT_DOMAIN ) . " </label><span class='the-fields'><input id='donate-amount' type='text' name='donate-amount' class='amount-text-field'  /> {$active_currency['Title']}</span></div>";
        }
        
        return  '';
    }
    
    protected function submit_button() {
        return apply_filters( "edt_donate_form_submit", "<div class='pay-btn'><input type='submit' name='edt-submit-btn' class='donate-form-submit' value='" . __( 'Payout', EDT_TEXT_DOMAIN ) . "'/></div>" );
    }
    
    public function get_easy_donations_form(){
        $form = "<form class='easy-donations-form' action='". remove_query_arg( 'edt_session_key', esc_url_raw( add_query_arg( array(  ) ) ) ) ."' method='post'>
                    <input type='hidden' name='easy_donations_form' value='1' />" 
            . $this->get_messages_html()
            . $this->get_form_fields()
            . $this->get_amount_field()
            . $this->gateways()
            . $this->submit_button()
            . "</form>";
        
        return apply_filters( "edt_donate_form", $form );
    }
    
    public function easy_donations_form() {
        $this->form_styles();
        
        $is_success_payment = false;
        $successfull_payment = edt_ins()->options->get_option('successfull_payment');
        if( isset( $_GET['edt_session_key'] ) ) {
            if( $successfull_payment && ! empty( $successfull_payment ) && isset( $successfull_payment['show_form_after_successfull_payment'] ) && trim( $successfull_payment['successfull_payment_message'] ) != '' ) {
                $payment = edt_ins()->session->get_session( 'edt_payment_infos' );
                if( ! $payment ){
                    return null;
                }
            
                $temp = $_GET['edt_session_key'];
                $temp2 = array();

                foreach( $payment as $key => $pay ) {
                    if( $key == $temp ) {
                        $temp2 = $pay;
                        break;
                    }
                }
            
                if( ! empty( $temp2 ) ) {
                    foreach( $temp2['errors'] as $item ) {
                        if( $item['type'] == 'updated' ) {
                            $is_success_payment = true;
                            break;
                        }
                    }
                }
            }
        }
        
        if( $is_success_payment ) {
            return '<div id="edt-form-wrap"><div class="easy-donations-form">' . $successfull_payment['successfull_payment_message'] . '</div></div>';
        }
        else {
            return '<div id="edt-form-wrap">' . $this->get_easy_donations_form() . '</div>';
        }

        
    }
    
    /**
     * Plugin shortcode handle
     * To allow users call the plugin form in wp editor
     */
    public function do_short_code() {
        return $this->easy_donations_form();
    }
    
    public function the_easy_donations_form() {
        echo $this->easy_donations_form();
    }
    
    
    public function validate_form() {
        $data = array();
        $temp = '';
        $has_error = false;
        
        if( ! empty( self::$form_fields ) && ! isset( $_POST['form-fields'] ) ) {
            $this->add_message( __( 'Please do not remove the form fields !' , EDT_TEXT_DOMAIN ), 'error' );
            return;
        }
        
        foreach( self::$form_fields as $field ) {
            if( isset( $_POST['form-fields'][ $field['field']['real_name'] ] ) ) {
                if( ( ( $temp = sanitize_text_field( $_POST['form-fields'][ $field['field']['real_name'] ] ) ) == '' ) && array_key_exists( $field['field']['real_name'], self::$required_fields ) ) {
                    $this->add_message( $field['title'] . __( " is required" , EDT_TEXT_DOMAIN ), 'error' );
                    $has_error = true;
                }
                else {
                    $data['details'][ $field['field']['real_name'] ]['value'] = $temp;
                    $data['details'][ $field['field']['real_name'] ]['title'] = $field['title'];
                }
            }
        }

        if( ! isset( $_POST['donate-amount'] ) || ( $temp = sanitize_text_field( $_POST['donate-amount'] ) ) == '' ) {
            $this->add_message( __( 'Amount is required' , EDT_TEXT_DOMAIN ), 'error' );
            $has_error = true;
        }
        else {
            if( is_numeric( $temp ) )
                $data['amount'] = $temp;
            else{
                $this->add_message( __( 'Amount is numeric !!!' , EDT_TEXT_DOMAIN ), 'error' );
                $has_error = true;
            }
        }
        
        if( ! isset( $_POST['donator-gateway'] ) || ( $temp = sanitize_text_field( $_POST['donator-gateway'] ) ) == '' ) {
            $this->add_message( __( 'Please choose a gateway' , EDT_TEXT_DOMAIN ), 'error' );
            $has_error = true;
        }
        else {
            $data['gateway'] = $temp;
        }
        
        
        do_action( 'edt_form_validation' );
        
        if( ! $has_error ) {
            edt_ins()->payment->create_payment( $data );
        }
    }
    
    public function form_styles() {
        $back_type = edt_ins()->options->get_option( 'form_background_type' );
        $back_url = edt_ins()->options->get_option( 'form-background-image' );
        $wrapper_styles = '#edt-form-wrap { display: block;';
        if( $back_url != '0' ){
            $wrapper_styles .= "background-image: url({$back_url});";
            if( $back_type == '' || $back_type == 'parallax' )
                $wrapper_styles .= "background-attachment: fixed;  background-position: top center; background-size: cover; ";
            else
                $wrapper_styles .= " background-size: cover;";
        }
        $wrapper_styles .= '}';
        
        ?>
        <style type="text/css">

            <?php echo $wrapper_styles; ?>

            #edt-form-wrap { 
                display: block;
                padding: 20px;
            }
            .easy-donations-form{
                width: 70%;
                margin: 0 auto;
                padding: 20px;
                background-color: rgba(255, 255, 255, 0.8);
                box-shadow: 0 0 4px #fff;
                border-radius: 6px;
                border: 1px solid #ccc;
            }

            #edt-form-wrap label{
                margin-bottom:10px;
            }
            
            #edt-form-wrap .edt-title{
                width: 130px;
                display:inline-block;
                color: #000;
                vertical-align: top;
                margin-top: 5px;
                font-weight: bold;
            }

            #edt-form-wrap input{
                border-radius: 6px;
            }

            #edt-form-wrap textarea{
                margin-bottom:10px;
                width: 180px;
            }

            #edt-form-wrap .the-fields{
                display: inline-block;
                width: 180px;
            }

            #edt-form-wrap .the-fields input{
                width: 130px;
                display: inline-block;
                border: 1px solid #ccc;
            }

            #edt-form-wrap .the-fields input:focus{
                box-shadow: 0px 0px 3px #C0A220;
            }

            #edt-form-wrap input, #edt-form-wrap textarea{
                background-color: rgba(255, 255, 255, 0.62);
            }

            .gateway-title{
                font-weight: bold;
            }

            #edt-form-wrap .gateway-item{
                margin-top: 5px;
                margin-right: 10px;
            }

            #edt-form-wrap .gateway-item input{
                margin-left:10px;
            }

            #edt-form-wrap .pay-btn{
                margin-top: 20px;
            }

            #edt-form-wrap input.donate-form-submit{
                margin: 0px auto;
                display: block;
                font-size: 20px;
                background-color: rgb(192, 162, 32);
                background-image: none;
                color: rgb(255, 255, 255);
                box-shadow: none;
                border: none;
            }

            #edt-form-wrap .donate-form-submit:hover{
                background: transparent -moz-linear-gradient(center top , #A8A0A0 0px, #756E6E 100%) repeat scroll 0% 0% !important;
            }

            #edt-form-wrap .edt_messages {
                margin-bottom: 10px;
            }

                #edt-form-wrap .edt_messages .error, #edt-form-wrap .edt_messages .updated {
                padding: 2px 4px;
                margin-bottom: 2px;
                background-color: rgba(255, 255, 255, 0.62);
                
                }

                <?php if( is_rtl() ): ?> 
                #edt-form-wrap .edt_messages .error {
                    border-right: 2px solid rgb(170, 73, 73);
                    display: block;
                }

                #edt-form-wrap .edt_messages .updated {
                    border-right: 2px solid rgb(71, 170, 145);
                    display: block;
                }
                <?php else:  ?>
                #edt-form-wrap .edt_messages .error {
                    border-right: 2px solid rgb(170, 73, 73);
                    display: block;
                }

                #edt-form-wrap .edt_messages .updated {
                    border-right: 2px solid rgb(71, 170, 145);
                    display: block;
                }
                <?php endif;  ?>
            
            .amount-field{
                margin: 15px 0;
            }

            #edt-form-wrap .the-fields .amount-text-field{
                width: 60px;
            }            

            .donate-prices {
                width: 100px;
                display: inline-block;
            }

            .donate-prices input {
                margin-left: 5px;
            }

            .donate-prices .currency {
                margin-right: 3px;
            }

            <?php echo edt_ins()->options->get_option( 'form_custome_styles' ); ?>
        </style>
        <?php
    }
    
    public function get_messages_html() {
        $msgs = $this->get_messages();
        $output = '<div class="edt_messages" >';
        if( ! empty( $msgs ) ) {
            foreach( $msgs as $msg ){
                $output .= '<div class="';
                if( $msg['type'] == 'error' )
                    $output .= 'error"';
                elseif( $msg['type'] == 'updated' )
                    $output .= 'updated"';
                $output .= ">{$msg['message']}</div>";
            }

        }
        
        $gtw_messages = $this->gateway_messages();
        if( ! is_null( $gtw_messages ) ) {
            foreach( $gtw_messages as $msg ){
                $output .= '<div class="';
                if( $msg['type'] == 'error' )
                    $output .= 'error"';
                elseif( $msg['type'] == 'updated' )
                    $output .= 'updated"';
                $output .= ">{$msg['message']}</div>";
            }
        }
        
        $output .= '</div>';
        
        apply_filters( 'edt_form_messages', $output );
        
        return $output;
    }
    
    public function gateways() {
        $output = "";
        $active_gateways = edt_ins()->options->get_option( 'active_gateways' );
        $active_gateways = ( is_array( $active_gateways ) ) ? $active_gateways : array();
        $output .= "<div><div class='gateway-title'> " . __( 'Gateway:', EDT_TEXT_DOMAIN ) . " </div>";
        if( empty( $active_gateways ) ){
            $output .= __( 'There is no active gateways.', EDT_TEXT_DOMAIN ) . " </div>";
        }
        else {
            if( $gateways = edt_ins()->gateways->get_gateways() ) {
                $gtws_count = count( $active_gateways );
                foreach( $gateways as $gateway ) {
                    if( array_key_exists( $gateway['id'], $active_gateways ) ) {
                        if( $gtws_count > 2 )
                            $field = "<input id='{$gateway['id']}'  type='radio' name='donator-gateway' value='{$gateway['id']}' />";
                        else
                            $field = "<input id='{$gateway['id']}'  type='hidden' name='donator-gateway' value='{$gateway['id']}' />";
                            
                        $output .= "<div class='gateway-item' >{$field}<label class='gateway-label2' for='{$gateway['id']}'>{$gateway['title']}</label></div>";
                    }
                }
                $output .= "</div>";
            }
            else {
                $output .= __( 'There is no active gateways.', EDT_TEXT_DOMAIN ) . " </div>";
            }
        }

        return $output;
    }
    
    public function gateway_messages() {
        if( ! isset( $_GET['edt_session_key'] ) ) {
            return null;
        }
  
        $payment = edt_ins()->session->get_session( 'edt_payment_infos' );
        if( ! $payment ){
            return null;
        }
        
        $temp = $_GET['edt_session_key'];
        
        $session_key = null;
        $temp2 = array();
        

        foreach( $payment as $key => $pay ) {

            if( $key == $temp ) {
                $temp2 = $pay;
                $session_key = $key;
                break;
            }
        }
        
        if( empty( $temp2 ) )
            return null;
        
        $payment[ $session_key ]['errors'] = array();
        edt_ins()->session->set_session( 'edt_payment_infos', $payment );
        return $temp2['errors'];
    }
}