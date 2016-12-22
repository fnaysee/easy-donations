<?php
/**
 * PerfectMoney Gateway for easy donations
 * 
 */

add_action( 'plugins_loaded', 'run_edt_pm_gateway' );

function run_edt_pm_gateway() {

    if( ! class_exists( 'EDT_PerfectMoney_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) ) {
        
        
        
        class EDT_PerfectMoney_Gateway extends Easy_Donations_Gateway {
            
            const version = '1.0';
            
            public function __construct() {
            }
            
            /**
             * (Required) This is a necessary function for all gateways, to add their gateway settings to the plugin settings
             * 
             */
            public function gateway_settings_fields() {
                $gateway_id = 'edt_pm_gateway';
                $gtw_data = edt_ins()->options->get_option( $gateway_id );
                $setting_fields = array(
                            array(
                                'id'      => 'perfectmoney_acc',
                                'name'    => '[perfectmoney_acc]',
                                'type'    => 'text',
                                'text'    => __( 'Payee Account', EDT_TEXT_DOMAIN ),
                                'value'   => ( isset( $gtw_data['perfectmoney_acc'] ) ) ? $gtw_data['perfectmoney_acc'] : ''
                            ),
                            array(
                                'id'      => 'perfectmoney_payee_name',
                                'name'    => '[perfectmoney_payee_name]',
                                'type'    => 'text',
                                'text'    => __( 'Payee Name', EDT_TEXT_DOMAIN ),
                                'value'   => ( isset( $gtw_data['perfectmoney_payee_name'] ) ) ? $gtw_data['perfectmoney_payee_name'] : 'Shop'
                            ),
                            array(
                                'id'      => 'perfectmoney_alternate_phrase',
                                'name'    => '[perfectmoney_alternate_phrase]',
                                'type'    => 'text',
                                'text'    => __('Alternate PassPhrase', EDT_TEXT_DOMAIN ),
                                'value'   => ( isset( $gtw_data['perfectmoney_alternate_phrase'] ) ) ? $gtw_data['perfectmoney_alternate_phrase'] : ''
                            )
                        );
                $this->add_gtw_setting( $gateway_id,  __('Perfect Money', EDT_TEXT_DOMAIN ), $setting_fields );
                
            }
            
            public function before_send( $payment ) {
                
                $gtw_data = edt_ins()->options->get_option( 'edt_pm_gateway' );

                $user = ( isset( $gtw_data['perfectmoney_acc'] ) ) ? $gtw_data['perfectmoney_acc'] : '';
                
                $name = ( isset( $gtw_data['perfectmoney_payee_name'] ) ) ? $gtw_data['perfectmoney_payee_name'] : '';
                
                $productinfo = __( "Payment number : ", EDT_TEXT_DOMAIN ) ;
                
                $amount = intval( $payment['amount'] ); // Required
                
                $redirect_url =  edt_ins()->gateways->return_url( $payment['id'], false );
                
                $order_id = $payment['id']; // Required 
                
                echo '
		        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		        <html>
		        <head></head>
		        <body>
		        <form name="payment_form" action="https://perfectmoney.is/api/step1.asp" method="post" id="perfectmoney_payment_form">
                    
                    <input type="hidden" name="SUGGESTED_MEMO" value="' . $productinfo . '">

                    <input type="hidden" name="PAYMENT_ID" value="' . $order_id .'" />
                    <input type="hidden" name="PAYMENT_AMOUNT" value="' . $amount . '" />
                    <input type="hidden" name="PAYEE_ACCOUNT" value="' . $user . '" />
                    <input type="hidden" name="PAYMENT_UNITS" value="USD" />
                    <input type="hidden" name="PAYEE_NAME" value="' . $name . '" />
                    <input type="hidden" name="PAYMENT_URL" value="' . $redirect_url . '" />
                    <input type="hidden" name="PAYMENT_URL_METHOD" value="LINK" />
                    <input type="hidden" name="NOPAYMENT_URL" value="'. $redirect_url . '" />
                    <input type="hidden" name="NOPAYMENT_URL_METHOD" value="LINK" />
                    <input type="hidden" name="STATUS_URL" value="' . $redirect_url . '" />

                </form>
		        <script type="text/javascript">
			        document.forms["payment_form"].submit();
		        </script>
		        </body>
		        </html>
		        ';

            }
            
            public function on_return( $payment, $post ) {
                
                $gtw_data = edt_ins()->options->get_option( 'edt_pm_gateway' );
                
                define( 'ALTERNATE_PHRASE_HASH', strtoupper( md5( $gtw_data['perfectmoney_alternate_phrase'] ) ) );
                
                if( ! isset( $_POST['PAYMENT_ID'] ) || ! isset( $_POST['PAYEE_ACCOUNT'] ) || ! isset( $_POST['PAYMENT_AMOUNT'] )
                    || ! isset( $_POST['PAYMENT_UNITS'] ) || ! isset( $_POST['PAYMENT_BATCH_NUM'] ) || ! isset( $_POST['PAYER_ACCOUNT'] )
                    || ! isset( $_POST['TIMESTAMPGMT'] ) || ! isset( $_POST['V2_HASH'] ) ) {
                    $this->add_message( __( 'Your Transaction Was Failed. You Can Try Paying Again.', EDT_TEXT_DOMAIN ), 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed');
                    header( "Location:" . $payment['pay_url'] );       
                    die(); 
                }
                
                $string =
                      $_POST['PAYMENT_ID'] . ':' . $_POST['PAYEE_ACCOUNT'] . ':' .
                      $_POST['PAYMENT_AMOUNT'] . ':' . $_POST['PAYMENT_UNITS'] . ':' .
                      $_POST['PAYMENT_BATCH_NUM'] . ':' .
                      $_POST['PAYER_ACCOUNT'] . ':' . ALTERNATE_PHRASE_HASH . ':' .
                      $_POST['TIMESTAMPGMT'];

                $hash = strtoupper( md5( $string ) );

                if( $hash == $_POST['V2_HASH'] ) { // proccessing payment if only hash is valid
                    $this->add_message( __( 'Your Transaction Was Successfull. Thank You.', EDT_TEXT_DOMAIN ), 'updated' );
                    edt_ins()->payment->complete_payment( $payment, 'completed', $_POST['PAYMENT_BATCH_NUM'] . ' Payer Acc: ' . $_POST['PAYER_ACCOUNT'] );
                    header( "Location:" . $payment['pay_url'] );       
                    die(); 
                }
                else{
                    $this->add_message( __( 'Your Transaction Was Failed. You Can Try Paying Again.', EDT_TEXT_DOMAIN ), 'error' );
                    edt_ins()->payment->complete_payment( $payment, 'failed', $_POST['PAYMENT_BATCH_NUM'] . ' Payer Acc: ' . $_POST['PAYER_ACCOUNT'] );
                    header( "Location:" . $payment['pay_url'] );       
                    die(); 
                }
            }
        }
        
        edt_ins()->gateways->register_gateway( 'edt_pm_gateway', __( 'Perfect Money', EDT_TEXT_DOMAIN ), 'EDT_PerfectMoney_Gateway' );
    }
}