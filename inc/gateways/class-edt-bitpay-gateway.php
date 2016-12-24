<?php
/**
 * bitpay Gateway for easy donations
 * 
 */

add_action( 'plugins_loaded', 'run_edt_bitpay_gateway' );

function run_edt_bitpay_gateway() {

    if( ! class_exists( 'EDT_bitpay_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) ) {
        
        
        
        class EDT_bitpay_Gateway extends Easy_Donations_Gateway {
            
            const version = '1.0';
            
            public function __construct() {
            }
            
            /**
             * (Required) This is a necessary function for all gateways, to add their gateway settings to the plugin settings
             * 
             */
            public function gateway_settings_fields() {
                $gateway_id = 'edt_bitpay_gateway';
                $gtw_data = edt_ins()->options->get_option( $gateway_id );
                $setting_fields = array(
                            array(
                                'id'      => 'bitpay_api',
                                'name'    => '[bitpay_api]',
                                'type'    => 'text',
                                'text'    => '˜Ï API ÏÑíÇİÊí ÇÒ ÈíÊ í ÑÇ æÇÑÏ äãÇííÏ.',
                                'value'   => ( isset( $gtw_data['bitpay_api'] ) ) ? $gtw_data['bitpay_api'] : ''
                            ),
                            array(
                                'name'    => '[bitpay_test_mode]',
                                'type'    => 'checkbox',
                                'value'   => 'active',
                                'text'    => 'ÈÑÇí ÂÒãÇíÔ ÏÑÇå ÈíÊ í Çíä Òíäå ÑÇ İÚÇá äãÇííÏ.',
                                'checked' => ( isset( $gtw_data['bitpay_test_mode'] ) ) ? true : false
                            )
                        );
                $this->add_gtw_setting( $gateway_id, 'ÈíÊ í', $setting_fields );
                
            }
            
            public function before_send( $payment ) {
                
                $gtw_data = edt_ins()->options->get_option( 'edt_bitpay_gateway' );
                if( isset( $gtw_data['bitpay_test_mode'] ) ) 
                    $api = 'adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567';
                else {
                    $api = ( isset( $gtw_data['bitpay_api'] ) ) ? $gtw_data['bitpay_api'] : '';
                }
                
                $amount = intval( $payment['amount'] ); // Required
                
                $active_currency = edt_ins()->options->get_option( 'donate_form_active_currency' );
                
                if( $active_currency['Code'] == 'IRT' )
                    $amount = $amount * 10; // convert value to rial, pardakhtpal only accepts rial
                
                $redirect =  edt_ins()->gateways->return_url( $payment['id'], true );
                
                if( isset( $gtw_data['bitpay_test_mode'] ) ) 
                    $url = 'https://bitpay.ir/payment-test/gateway-send';
                else {
                    $url = 'https://bitpay.ir/payment/gateway-send';
                }
                
                //$OrderId = $payment['id']; // Required 
                
                
                
                $ch = curl_init();         
                curl_setopt($ch,CURLOPT_URL, $url );          
                curl_setopt($ch,CURLOPT_POSTFIELDS,"api={$api}&amount={$amount}&redirect={$redirect}");
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                $res = curl_exec($ch);
                curl_close($ch);
                
                switch( $res ) {
                    case '-1' :   
                        $this->add_message( '˜Ï api ÇÑÓÇáí ÈÇ ˜Ï api ËÈÊ ÔÏå ÏÑ ÈíÊ í ÓÇÒÇÑ äíÓÊ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );        
                        die();
                    case '-2' :  
                        $this->add_message( 'ãÈáÛ í˜ ãŞÏÇÑ ÚÏÏí ÇÓÊ æ ÍÏÇŞá ÈÇíÏ 1000 ÑíÇá ÈÇÔÏ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );       
                        die();    
                    case '-3' : 
                        $this->add_message( 'ÂÏÑÓ ÈÇÒÔÊ ÇÒ ÏÑÇå í˜ ãŞÏÇÑ äÇá ãí ÈÇÔÏ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );       
                        die();    
                    case '-4' :
                        $this->add_message( 'äíä ÏÑÇåí æÌæÏ äÏÇÑÏ æ íÇ åäæÒ ÏÑ ÇäÊÙÇÑ ÊÇííÏ ãí ÈÇÔÏ!', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );
                        die();    
                    default :
                        
                        if( $res > 0 && is_numeric( $res ) ){
                            if( isset( $gtw_data['bitpay_test_mode'] ) ) 
                                $go = "https://bitpay.ir/payment-test/gateway-{$res}";
                            else {
                                $go = "https://bitpay.ir/payment/gateway-{$res}";
                            }
                            
                            header("Location: {$go}");
                            die();
                        }
                        else {
                            $this->add_message( 'ÎØÇ ÏÑ ÇÑÓÇá Èå ÈíÊ í', 'error' );
                            return;
                        }
                }
            }
            
            public function on_return( $payment, $post ) {
                
                if( isset( $gtw_data['bitpay_test_mode'] ) ) 
                    $url = 'https://bitpay.ir/payment-test/gateway-result-second';
                else {
                    $url = 'https://bitpay.ir/payment/gateway-result-second';
                }
                
                
                $gtw_data = edt_ins()->options->get_option( 'edt_pl_gateway' );
                if( isset( $gtw_data['bitpay_test_mode'] ) ) 
                    $api = 'adxcv-zzadq-polkjsad-opp13opoz-1sdf455aadzmck1244567';
                else {
                    $api = ( isset( $gtw_data['bitpay_api'] ) ) ? $gtw_data['bitpay_api'] : '';
                }
                
                if( ! isset( $post['trans_id'] ) || ! isset( $post['id_get'] ) ){
                    $this->add_message( 'Èå äÙÑ ãí ÑÓÏ ÏÑÇå ÈíÊ í ÏÇÑ ãÔ˜áí ÔÏå æ íÇ ŞæÇÚÏ ÇÊÕÇá Èå ÏÑÇå ÊÛííÑ ˜ÑÏå áØİÇ ãÓÆæá ÓÇíÊ ÑÇ ÇÒ Çíä ãæÖæÚ ãØáÚ äãÇííÏ.', 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed' );
                    wp_redirect( $payment['pay_url'] );
                    die();
                }
                
                $trans_id = $_POST['trans_id']; 
                $id_get = $_POST['id_get']; 
                
                $ch = curl_init();     
                curl_setopt($ch,CURLOPT_URL,$url);     
                curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&id_get=$id_get&trans_id=$trans_id");
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                $res = curl_exec($ch);
                curl_close($ch); 
                
                switch( $res ) {
                    case '-1' :   
                        $this->add_message( '˜Ï api ÇÑÓÇáí ÈÇ ˜Ï api ËÈÊ ÔÏå ÏÑ ÈíÊ í ÓÇÒÇÑ äíÓÊ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );        
                        die();
                    case '-2' :  
                        $this->add_message( 'ÔãÇÑå ÊÑÇ˜äÔ ÇÑÓÇáí äÇãÚÊÈÑ ÇÓÊ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );       
                        die();    
                    case '-3' : 
                        $this->add_message( 'id_get ÇÑÓÇáí äÇãÚÊÈÑ ãí ÈÇÔÏ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );       
                        die();    
                    case '-4' :
                        $this->add_message( 'äíä ÊÑÇ˜äÔí æÌæÏ äÏÇÑÏ æ íÇ ŞÈáÇ ÈÇ ãæİŞíÊ Èå ÇÊãÇã ÑÓíÏå ÇÓÊ. åãäíä Çã˜Çä ÏÇÑÏ ÚãáíÇÊ ÑÏÇÎÊ ÊæÓØ ˜ÇÑÈÑ áÛæ ÔÏå ÈÇÔÏ.', 'error' );
                        edt_ins()->payment->complete_payment( $payment, 'failed' );
                        wp_redirect( $payment['pay_url'] );
                        die();    
                    case '1' :
                        $this->add_message( 'ÑÏÇÎÊ ÔãÇ ÈÇ ãæİŞíÊ ÏÑíÇİÊ ÔÏ.', 'updated' );
                        edt_ins()->payment->complete_payment( $payment, 'completed', $trans_id );
                        wp_redirect( $payment['pay_url'] );       
                        die(); 
                } 

            }
        }
        
        edt_ins()->gateways->register_gateway( 'edt_bitpay_gateway', 'ÈíÊ í', 'EDT_bitpay_Gateway' );
    }
}