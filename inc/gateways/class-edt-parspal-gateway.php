<?php
/**
 * ParsPal Gateway for easy donations
 * 
 */

add_action( 'plugins_loaded', 'run_edt_ps_gateway' );

function run_edt_ps_gateway() {

    if( ! class_exists( 'EDT_ParsPal_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) ) {
        
        
        
        class EDT_ParsPal_Gateway extends Easy_Donations_Gateway {
            
            const version = '1.0';
            
            public function __construct() {
            }
            
            /**
             * (Required) This is a necessary function for all gateways, to add their gateway settings to the plugin settings
             * 
             */
            public function gateway_settings_fields() {
                $gateway_id = 'edt_ps_gateway';
                $gtw_data = edt_ins()->options->get_option( $gateway_id );
                $setting_fields = array(
                            array(
                                'id'      => 'parspal_merchant_id',
                                'name'    => '[parspal_merchant_id]',
                                'type'    => 'text',
                                'text'    => 'شناسه کاربری پارس پالتان را وارد نمایید.',
                                'value'   => ( isset( $gtw_data['parspal_merchant_id'] ) ) ? $gtw_data['parspal_merchant_id'] : ''
                            ),
                            array(
                                'id'      => 'parspal_merchant_passwd',
                                'name'    => '[parspal_merchant_passwd]',
                                'type'    => 'password',
                                'text'    => 'کلمه عبور پارس پالتان را وارد نمایید.',
                                'value'   => ( isset( $gtw_data['parspal_merchant_passwd'] ) ) ? $gtw_data['parspal_merchant_passwd'] : ''
                            ),                            
                            array(
                                'name'    => '[parspal_sandbox]',
                                'type'    => 'checkbox',
                                'value'   => 'active',
                                'text'    => 'برای فعال سازی حالت ازمایشی پارس پال این گزینه را فعال نمایید.',
                                'checked' => ( isset( $gtw_data['parspal_sandbox'] ) ) ? true : false
                            )
                        );
                $this->add_gtw_setting( $gateway_id, 'پارس پال', $setting_fields );
                
            }
            
            public function before_send( $payment ) {
                
                $gtw_data = edt_ins()->options->get_option( 'edt_ps_gateway' );
                if( isset( $gtw_data['parspal_sandbox'] ) ) {
                    $MerchantID = '100001';
                    $Password = 'abcdeFGHI';
                }
                else {
                    $MerchantID = ( isset( $gtw_data['parspal_merchant_id'] ) ) ? $gtw_data['parspal_merchant_id'] : '';;
                    $Password = ( isset( $gtw_data['parspal_merchant_passwd'] ) ) ? $gtw_data['parspal_merchant_passwd'] : '';;
                }
                
                $Price = intval( $payment['amount'] ); // Required
                $ResNumber = $payment['id'];// Order Id In Your System
                $Description = 'پرداخت فاکتور به شماره ی ' . $payment['id'];
                $Paymenter = '';
                $Email = '';
                $Mobile = '';
                $ReturnPath =  edt_ins()->gateways->return_url( $payment['id'] );
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRR' )
                    $Price = intval( $Price / 10 ); // convert value to toman, parspal only accepts toman
                
                if( isset( $gtw_data['parspal_sandbox'] ) ) 
                    $url = 'http://sandbox.parspal.com/WebService.asmx?wsdl';
                else {
                    $url = 'http://merchant.parspal.com/WebService.asmx?wsdl';
                }

                $client = new SoapClient( $url );
                $res = $client->RequestPayment( array( "MerchantID" => $MerchantID , "Password" =>$Password , "Price" =>$Price, "ReturnPath" =>$ReturnPath, "ResNumber" =>$ResNumber, "Description" =>$Description, "Paymenter" =>$Paymenter, "Email" =>$Email, "Mobile" =>$Mobile ) );
                
                $PayPath = $res->RequestPaymentResult->PaymentPath;
                $Status = $res->RequestPaymentResult->ResultStatus;
                
                if($Status == 'Succeed') {
                    Header( "Location: $PayPath" );
                    die();
                }
                else {
                    $this->add_message( 'خطا در اتصال اولیه به پارس پال.', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    header( "Location:" . $payment['pay_url'] );        
                    die();
                }
            }
            
            public function on_return( $payment, $post ) {
                
                $gtw_data = edt_ins()->options->get_option( 'edt_ps_gateway' );
                if( isset( $gtw_data['parspal_sandbox'] ) ) {
                    $MerchantID = '100001';
                    $Password = 'abcdeFGHI';
                }
                else {
                    $MerchantID = ( isset( $gtw_data['parspal_merchant_id'] ) ) ? $gtw_data['parspal_merchant_id'] : '';;
                    $Password = ( isset( $gtw_data['parspal_merchant_passwd'] ) ) ? $gtw_data['parspal_merchant_passwd'] : '';;
                }

                $Price = intval( $payment['amount'] ); // Required
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRR' )
                    $Price = intval( $Price / 10 ); // convert value to toman, parspal only accepts toman
                
                if( ! isset( $post['status'] ) || $post['status'] != 100  || !isset( $post['refnumber'] ) ){
                    $this->add_message( 'خطا در بازگشت از عملیات پرداخت ( پرداخت ناموفق )', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    header( "Location:" . $payment['pay_url'] );        
                    die();
                }

		        $Status = $post['status'];
		        $Refnumber = $post['refnumber'];
		        //$Resnumber = $post['resnumber'];
                    
                if( isset( $gtw_data['parspal_sandbox'] ) ) 
                    $url = 'http://sandbox.parspal.com/WebService.asmx?wsdl';
                else {
                    $url = 'http://merchant.parspal.com/WebService.asmx?wsdl';
                }
                    
                $client = new SoapClient( $url );
                
                $res = $client->VerifyPayment( array( "MerchantID" => $MerchantID , "Password" =>$Password , "Price" =>$Price, "RefNum" =>$Refnumber ) );
                
                $Status = $res->verifyPaymentResult->ResultStatus;
                //$PayPrice = $res->verifyPaymentResult->PayementedPrice;
                if( $Status == 'success' ) {
                    $this->add_message( 'پرداخت شما با موفقیت دریافت شد.', 'updated' );
                    edt_ins()->payment->complete_payment( $payment, 'completed', $Refnumber );
                    header( "Location:" . $payment['pay_url'] );       
                    die(); 
                }
                else {
                    $this->add_message( 'خطا در بازگشت از عملیات پرداخت ( پرداخت ناموفق )', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    header( "Location:" . $payment['pay_url'] );        
                    die();
                }
            }
        }
        
        edt_ins()->gateways->register_gateway( 'edt_ps_gateway', 'پارس پال', 'EDT_ParsPal_Gateway' );
    }
}