<?php
namespace BooklyPro\Lib\Payment;

use Bookly\Lib\Config;
use Bookly\Lib\Utils\Common;

/**
 * Class PayPal
 * @package BooklyPro\Lib\Payment
 */
class PayPal
{
    const TYPE_EXPRESS_CHECKOUT  = 'ec';
    const TYPE_PAYMENTS_STANDARD = 'ps';
    const TYPE_CHECKOUT          = 'checkout';

    // Array for cleaning PayPal request
    static public $remove_parameters = array( 'bookly_action', 'bookly_fid', 'error_msg', 'token', 'PayerID',  'type' );

    /** @var  string */
    private $error;

    /**
     * The array of products for checkout
     *
     * @var \stdClass
     */
    protected $product;

    /** @var  float */
    protected $tax = 0;
    /**
     * Send the Express Checkout NVP request
     *
     * @param $form_id
     */
    public function sendECRequest( $form_id )
    {
        $current_url = Common::getCurrentPageURL();

        // create the data to send on PayPal
        $data = array(
            'BRANDNAME'    => get_option( 'bookly_co_name' ),
            'SOLUTIONTYPE' => 'Sole',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'  => Config::getCurrency(),
            'NOSHIPPING' => 1,
            'RETURNURL'  => add_query_arg( array( 'bookly_action' => 'paypal-express-checkout-return', 'bookly_fid' => $form_id ), $current_url ),
            'CANCELURL'  => add_query_arg( array( 'bookly_action' => 'paypal-express-checkout-cancel', 'bookly_fid' => $form_id ), $current_url )
        );
        $data['L_PAYMENTREQUEST_0_NAME0'] = $this->product->name;
        $data['L_PAYMENTREQUEST_0_AMT0']  = $this->product->price;
        $data['L_PAYMENTREQUEST_0_QTY0']  = $this->product->qty;

        $total = $this->product->qty * $this->product->price;
        $data['PAYMENTREQUEST_0_ITEMAMT'] = $total;
        $data['PAYMENTREQUEST_0_AMT']     = $total + $this->tax;
        if ( get_option( 'bookly_paypal_send_tax' ) ) {
            $data['PAYMENTREQUEST_0_TAXAMT'] = $this->tax;
        }

        // send the request to PayPal
        $response = $this->sendNvpRequest( 'SetExpressCheckout', $data );
        if ( $response === null ) {
            $url = wp_sanitize_redirect(
                add_query_arg( array(
                    'bookly_action' => 'paypal-express-checkout-error',
                    'bookly_fid'    => $form_id,
                    'error_msg'     => urlencode( $this->error ),
                ), $current_url ) );
        } else {
            // Respond according to message we receive from PayPal
            $ack = strtoupper( $response['ACK'] );
            if ( $ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING' ) {
                // Redirect url to PayPal.
                $url = sprintf(
                    'https://www%s.paypal.com/cgi-bin/webscr?cmd=_express-checkout&useraction=commit&token=%s',
                    get_option( 'bookly_paypal_sandbox' ) ? '.sandbox' : '',
                    urlencode( $response['TOKEN'] )
                );
            } else {
                $url = wp_sanitize_redirect(
                    add_query_arg( array(
                        'bookly_action' => 'paypal-express-checkout-error',
                        'bookly_fid'    => $form_id,
                        'error_msg'     => urlencode( $response['L_LONGMESSAGE0'] ),
                    ), $current_url ) );
            }
        }
        header( 'Location: ' . $url );
        exit;
    }

    /**
     * Send the NVP Request to the PayPal
     *
     * @param       $method
     * @param array $data
     * @return array|null
     */
    public function sendNvpRequest( $method, array $data )
    {
        $paypal_response = array();
        $url  = 'https://api-3t' . ( get_option( 'bookly_paypal_sandbox' ) ? '.sandbox' : '' ) . '.paypal.com/nvp';

        $data['METHOD']    = $method;
        $data['VERSION']   = '124.0';
        $data['USER']      = get_option( 'bookly_paypal_api_username' );
        $data['PWD']       = get_option( 'bookly_paypal_api_password' );
        $data['SIGNATURE'] = get_option( 'bookly_paypal_api_signature' );

        $args = array(
            'sslverify' => false,
            'body'      => $data,
            'timeout'   => 60,
        );

        $response = wp_remote_post( $url, $args );
        if ( $response instanceof \WP_Error ) {
            $this->error = 'Invalid HTTP Response for POST request to ' . $url;
            return null;
        } else {
            // Extract the response details.
            parse_str( $response['body'], $paypal_response );

            if ( ! array_key_exists( 'ACK', $paypal_response ) ) {
                $this->error = 'Invalid HTTP Response for POST request to ' . $url;
                return null;
            }
        }

        return $paypal_response;
    }

    /**
     * Add the Product for payment
     *
     * @param \stdClass $product
     */
    public function setProduct( \stdClass $product )
    {
        $this->product = $product;
    }

    /**
     * @param float $tax
     */
    public function setTotalTax( $tax )
    {
        $this->tax = $tax;
    }

    /**
     * Gets error
     *
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
}