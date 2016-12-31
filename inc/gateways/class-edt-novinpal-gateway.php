<?php
/**
 * PardakhtShahr Gateway for easy donations
 * 
 */

add_action( 'plugins_loaded', 'run_edt_novinpal_gateway' );

function run_edt_psh_gateway() {

    if( ! class_exists( 'EDT_NovinPal_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) ) {
        
        
        
        class EDT_NovinPal_Gateway extends Easy_Donations_Gateway {
            
            const version = '1.0';
            
            public $errorCode = array(
				-20=>'api نامعتبر است' ,
				-21=>'آی پی نامعتبر است' ,
				-22=>'مبلغ از کف تعریف شده کمتر است' ,
				-23=>'مبلغ از سقف تعریف شده بیشتر است' ,
				-24=>'مبلغ نامعتبر است' ,
				-6=>'ارتباط با بانک برقرار نشد' ,
				-26=>'درگاه غیرفعال است' ,
				-27=>'آی پی شما مسدود است' ,
				-9=>'خطای ناشناخته' ,
				-29=>'آدرس کال بک خالی است ' ,
				-30=>'چنین تراکنشی یافت نشد' ,
				-31=>'تراکنش انجام نشده ' ,
				-32=>'تراکنش انجام شده اما مبلغ نادرست است ' ,
				//1 => "تراکنش با موفقیت انجام شده است " ,
			);	
            
            public function __construct() {
            }

            /**
             * (Required) This is a necessary function for all gateways, to add their gateway settings to the plugin settings
             * 
             */
            public function gateway_settings_fields() {
                $gateway_id = 'edt_novinpal_gateway';
                $gtw_data = edt_ins()->options->get_option( $gateway_id );
                $setting_fields = array(
                            array(
                                'id'      => 'novinpal_api',
                                'name'    => '[novinpal_api]',
                                'type'    => 'text',
                                'text'    => 'کد API دریافتی از نوین پال را وارد نمایید.',
                                'value'   => ( isset( $gtw_data['novinpal_api'] ) ) ? $gtw_data['novinpal_api'] : ''
                            )
                        );
                $this->add_gtw_setting( $gateway_id, 'نوین پال', $setting_fields );
                
            }
            
            public function before_send( $payment ) {
                
                $target_url = 'http://novinpal.com/pay/webservice/?wsdl';

                $gtw_data = edt_ins()->options->get_option( 'edt_novinpal_gateway' );
                
                $API = ( isset( $gtw_data['novinpal_api'] ) ) ? $gtw_data['novinpal_api'] : '';
                $Amount = intval( $payment['amount'] ); // Required
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRR' )
                    $Amount = round($Amount / 10); // Convert value to toman
                
                $Description = 'پرداخت فاکتور به شماره ی' . $payment['id']; // Required 
                $CallbackURL =  edt_ins()->gateways->return_url( $payment['id'] );
                $OrderId = $payment['id']; // Required 
                
                $client = new SoapClient( $target_url ); 

                $res = $client->requestpayment( $API, $Amount, $CallbackURL, $OrderId, $Description ); 
                
                
                if($res > 0 && is_numeric($res)){
                    $payment_url = 'http://novinpal.com/pay/payment/';
                    
                    Header( "Location: $payment_url" . $res );
                    die();
                }
                else{
                    $this->add_message( 'خطا در ارسال به ایسلند پی کد خطا:' . $res . ' علت آن به این شرح است: ' .  ((isset($this->errorCode[$res])) ? $this->errorCode[$res] : 'کد خطا قابل تفسیر نیست.' ), 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    header( "Location: {$payment['pay_url']}" );
                    return;
                }
            }
            
            public function on_return( $payment, $post ) {
                $gtw_data = edt_ins()->options->get_option( 'edt_novinpal_gateway' );
                
                $API = ( isset( $gtw_data['novinpal_api'] ) ) ? $gtw_data['novinpal_api'] : '';
                $Amount = intval( $payment['amount'] ); //  - ریال به مبلغ Required
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRR' )
                    $Amount = round($Amount / 10); // convert value to toman
                
                $Authority = ( isset( $_GET['au'] ) ) ? $_GET['au'] : '';
                if( !empty($Authority) ){ 
                    $target_url = 'http://novinpal.com/pay/webservice/?wsdl';
                        
                    $client = new SoapClient( $target_url ); 
                    
                    $res = $client->verification($API, $Amount, $Authority); 
                        
                    if( ! empty($res) and $res == 1){
                        $this->add_message( 'پرداخت شما با موفقیت دریافت شد.', 'updated' );
                        edt_ins()->payment->complete_payment( $payment, 'completed', $Authority );
                        header( "Location: {$payment['pay_url']}" );
                    }
                    else{
                        $this->add_message( 'خطایی به هنگام پرداخت پیش آمده. کد خطا  :' . $res . ' علت آن به این شرح است: ' . ((isset($this->errorCode[$res])) ? $this->errorCode[$res] : 'کد خطا قابل تفسیر نیست.' ) , 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        header( "Location: {$payment['pay_url']}" );
                    }
                }
                else{
                    $this->add_message( 'کد لازم برای بررسی صحت پرداخت به درستی دریافت نشد.', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    header( "Location: {$payment['pay_url']}" );
                }
            }
        }
        
        edt_ins()->gateways->register_gateway( 'edt_novinpal_gateway', 'نوین پال', 'EDT_NovinPal_Gateway' );
    }
}