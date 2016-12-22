<?php
/**
 * PardakhtPal Gateway for easy donations
 * 
 */

add_action( 'plugins_loaded', 'run_edt_pp_gateway' );

function run_edt_pp_gateway() {

    if( ! class_exists( 'EDT_PardakhtPal_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) ) {
        
        
        
        class EDT_PardakhtPal_Gateway extends Easy_Donations_Gateway {
            
            const version = '1.0';
            
            public function __construct() {
            }

            /**
             * (Required) This is a necessary function for all gateways, to add their gateway settings to the plugin settings
             * 
             */
            public function gateway_settings_fields() {
                $gateway_id = 'edt_pp_gateway';
                $gtw_data = edt_ins()->options->get_option( $gateway_id );
                $setting_fields = array(
                            array(
                                'id'      => 'pardakhtpal_api',
                                'name'    => '[pardakhtpal_api]',
                                'type'    => 'text',
                                'text'    => 'کد API دریافتی از پرداخت پال را وارد نمایید.',
                                'value'   => ( isset( $gtw_data['pardakhtpal_api'] ) ) ? $gtw_data['pardakhtpal_api'] : ''
                            )
                        );
                $this->add_gtw_setting( $gateway_id, 'پرداخت پال', $setting_fields );
                
            }
            
            public function before_send( $payment ) {
                
                $target_url = 'http://www.pardakhtpal.com/WebService/WebService.asmx?wsdl';

                $gtw_data = edt_ins()->options->get_option( 'edt_pp_gateway' );
                
                $API = ( isset( $gtw_data['pardakhtpal_api'] ) ) ? $gtw_data['pardakhtpal_api'] : '';
                $Amount = intval( $payment['amount'] ); // Required
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRT' )
                    $Amount = $Amount * 10; // convert value to rial, pardakhtpal only accepts rial
                
                $Description = 'پرداخت فاکتور به شماره ی' . $payment['id']; // Required 
                $CallbackURL =  edt_ins()->gateways->return_url( $payment['id'] );
                $OrderId = $payment['id']; // Required 
                
                $client = new SoapClient( $target_url ); 

                $params = array( 'API' => $API , 'Amount' => $Amount, 'CallBack' => $CallbackURL, 'OrderId' => $OrderId, 'Text' => $Description );

                $res = $client->requestpayment( $params ); 
                $Result = $res->requestpaymentResult; 
                
                if( strlen($Result) == 8 ){
                    $payment_url = 'http://www.pardakhtpal.com/payment/pay_invoice/';
                    
                    Header( "Location: $payment_url" . $Result );
                    die();
                }
                else{
                    $this->add_message( 'خطا در ارسال به پرداخت پال', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    header( "Location: {$payment['pay_url']}" );
                    return;
                }
            }
            
            public function on_return( $payment, $post ) {
                $gtw_data = edt_ins()->options->get_option( 'edt_pp_gateway' );
                
                $API = ( isset( $gtw_data['pardakhtpal_api'] ) ) ? $gtw_data['pardakhtpal_api'] : '';
                $Amount = intval( $payment['amount'] ); //  - ریال به مبلغ Required
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRT' )
                    $Amount = $Amount * 10; // convert value to rial, pardakhtpal only accepts rial
                
                $Authority = ( isset( $_POST['au'] ) ) ? $_POST['au'] : '';
                if( strlen( $Authority ) > 4 ){ 
                    $target_url = 'http://www.pardakhtpal.com/WebService/WebService.asmx?wsdl';
                        
                    $client = new SoapClient( $target_url ); 

                    $params = array('API' => $API , 'Amount' => $Amount, 'InvoiceId' => $Authority); 

                    $res = $client->verifypayment($params); 
                    $Result = $res->verifypaymentResult; 
                        
                    if( $Result == 1 ){
                        $this->add_message( 'پرداخت شما با موفقیت دریافت شد.', 'updated' );
                        edt_ins()->payment->complete_payment( $payment, 'completed', $Authority );
                        header( "Location: {$payment['pay_url']}" );
                    }
                    else{
                        $this->add_message( 'خطایی به هنگام پرداخت پیش آمده. کد خطا عبارت است از :' . $Result . ' . برای آگاهی از دلیل خطا کد آن را به پرداخت پال ارائه نمایید.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        header( "Location: {$payment['pay_url']}" );
                    }
                }
                else{
                    $this->add_message( 'به نظر می رسد عملیات پرداخت توسط شما لغو گردیده، اگر چنین نیست مجددا اقدام به پرداخت فاکتور نمایید.', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'canceled' );
                    header( "Location: {$payment['pay_url']}" );
                }
            }
        }
        
        edt_ins()->gateways->register_gateway( 'edt_pp_gateway', 'پرداخت پال', 'EDT_PardakhtPal_Gateway' );
    }
}