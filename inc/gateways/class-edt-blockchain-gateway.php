<?php
/**
 * Blockchain Gateway for easy donations
 *
 */
add_action('init','edt_blc_gateway_check_payment_status');
function edt_blc_gateway_check_payment_status(){
    if(isset($_GET['secret']) && !empty($_GET['secret']) ){
        global $edt_gateways_data;
        $edt_gateways_data['blcdata'] = array(
            'secret' => $_GET['secret'],
            'pay_id' =>  isset($_GET['pay_id'])? $_GET['pay_id'] : '',
            'value' =>  isset($_GET['value'])? $_GET['value'] : '',
            'confs' => isset($_GET['confirmations'])? $_GET['confirmations'] : '',
            'hash'  => isset($_GET['transaction_hash'])? $_GET['transaction_hash'] : '',
            );
    }
}

add_action( 'plugins_loaded', 'run_edt_blc_gateway' );

function run_edt_blc_gateway() {

    if( ! class_exists( 'EDT_BlockChain_Gateway' ) && class_exists( 'Easy_Donations_Gateway' ) ) {

        class EDT_BlockChain_Gateway extends Easy_Donations_Gateway {

            const version = '1.0';

            public function __construct() {
                global $edt_gateways_data;
                if( !empty( $edt_gateways_data ) ) {
                    if( isset( $edt_gateways_data['blcdata'] ) ) {
                        $this->received_blockchain_hit();
                    }
                }
            }

            /**
             * (Required) This is a necessary function for all gateways, to add their gateway settings to the plugin settings
             *
             */
            public function gateway_settings_fields() {
                $gateway_id = 'edt_blc_gateway';
                $gtw_data = edt_ins()->options->get_option( $gateway_id );
                $setting_fields = array(
                            array(
                                'id'      => 'blockchain_api',
                                'name'    => '[blockchain_api]',
                                'type'    => 'text',
                                'text'    => __( 'Your API Key', EDT_TEXT_DOMAIN ) . '<a href="https://api.blockchain.info/v2/apikey/request/">'. __( 'Click here to request one', EDT_TEXT_DOMAIN ) .'</a>',
                                'value'   => ( isset( $gtw_data['blockchain_api'] ) ) ? $gtw_data['blockchain_api'] : ''
                            ),
                            array(
                                'id'      => 'blockchain_xpub',
                                'name'    => '[blockchain_xpub]',
                                'type'    => 'text',
                                'text'    => __( 'Your account xpub key. You can find it in your wallet. Do not share this key with others!', EDT_TEXT_DOMAIN ),
                                'value'   => ( isset( $gtw_data['blockchain_xpub'] ) ) ? $gtw_data['blockchain_xpub'] : '',
                            ),
                            array(
                                'id'      => 'blockchain_secret',
                                'name'    => '[blockchain_secret]',
                                'type'    => 'text',
                                'text'    => __( 'Choose a secret key for transactions between you and blockchain. Blockchain uses this secret to tell you about the payment status, so it is better you use a strong secret key that contains uppercase and lowercase letters and numbers. I recommend you to not use any other characters.', EDT_TEXT_DOMAIN ),
                                'value'   => ( isset( $gtw_data['blockchain_secret'] ) ) ? $gtw_data['blockchain_secret'] : '',
                            ),
                        );
                $this->add_gtw_setting( $gateway_id, __('Blockchain', EDT_TEXT_DOMAIN ), $setting_fields );

            }

            public function before_send( $payment ) {

                $gtw_data = edt_ins()->options->get_option( 'edt_ip_gateway' );
                $XPUB = ( isset( $gtw_data['blockchain_xpub'] ) ) ? $gtw_data['blockchain_xpub'] : '';
                $API = ( isset( $gtw_data['blockchain_api'] ) ) ? $gtw_data['blockchain_api'] : '';
                $SECRET = ( isset( $gtw_data['blockchain_secret'] ) ) ? $gtw_data['blockchain_secret'] : '';;

                $callback_url = add_query_arg( array('secret' => $SECRET), edt_ins()->gateways->return_url( $payment['id'], false ) );
                $callback_url = remove_query_arg( 'edt_after_payment', $callback_url );

                $target_url = 'https://api.blockchain.info/v2/receive';

                $parameters = 'xpub=' . $XPUB . '&callback=' . urlencode( $callback_url ) . '&key=' . $API;

                $response = $this->getSslPage( $target_url . '?' . $parameters );

                if( $object = json_decode( $response ) ){
                    if( !empty($object) && !empty($object->address) ){
                        $this->add_message( __( 'Your request is set. Please send the price to this blockchain address: ', EDT_TEXT_DOMAIN ) . $object->address, 'updated' );
                        header( "Location: {$payment['pay_url']}" );
                    }
                    else{
                        $this->add_message( __( 'Unable to get an payment address from Blockchain. Resubmit the form or tell the site owner about the issue !', EDT_TEXT_DOMAIN ), 'error' );
                        header( "Location: {$payment['pay_url']}" );
                    }
                }
                else{
                    $this->add_message( __( 'Unable to get an payment address from Blockchain. Resubmit the form or tell the site owner about the issue !', EDT_TEXT_DOMAIN ), 'error' );
                    header( "Location: {$payment['pay_url']}" );
                }
            }

            public function on_return( $payment, $post ) {
                //we broken this function in this gateway because we need to handle it in other way.
            }

            public function received_blockchain_hit() {
                global $edt_gateways_data;
                $edt_gateways_data['blcdata'];

                if( !empty( $edt_gateways_data['blcdata']['pay_id'] ) && !empty( $edt_gateways_data['blcdata']['value'] ) && is_numeric( $edt_gateways_data['blcdata']['pay_id'] ) ){
                    if( !empty( $edt_gateways_data['blcdata']['confs'] ) && $edt_gateways_data['blcdata']['confs'] >= 6 ){
                        $payment = edt_ins()->payment->get_payment_by_id($edt_gateways_data['blcdata']['pay_id']);
                        if( !empty( $payment ) ) {
                            //We extracted payment successfully. lets change its status

                            edt_ins()->payment->complete_payment( $payment, 'completed', $edt_gateways_data['blcdata']['hash'] );
                            echo '*ok*';
                            die();
                        }
                    }
                }
            }

            public function getSslPage($url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_REFERER, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                $result = curl_exec($ch);
                curl_close($ch);
                return $result;
            }
        }

        edt_ins()->gateways->register_gateway( 'edt_blc_gateway', __('Blockchain', EDT_TEXT_DOMAIN ), 'EDT_BlockChain_Gateway' );
    }
}