<?php
namespace BooklyPro\Lib\Payment;

use Bookly\Lib as BooklyLib;

/**
 * Class Square
 * @package BooklyPro\Lib\Payment
 */
class Square
{
    /** @var Square\Api */
    protected $api;

    public function __construct()
    {
        $this->api = new Square\Api(
            get_option( 'bookly_cloud_square_api_access_token' ),
            get_option( 'bookly_cloud_square_api_location_id' ),
            get_option( 'bookly_cloud_square_sandbox' )
        );
    }

    /**
     * @param BooklyLib\UserBookingData $userData
     * @param string $response_url
     * @return string
     * @throws \Exception
     */
    public function getCheckoutUrl( BooklyLib\UserBookingData $userData, $response_url )
    {
        $cart_info = $userData->cart->getInfo( BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE );

        $payment = new BooklyLib\Entities\Payment();
        $payment->setType( BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE )
            ->setStatus( BooklyLib\Entities\Payment::STATUS_PENDING )
            ->setCartInfo( $cart_info )
            ->save();
        $order = $userData->save( $payment );
        $payment->setDetailsFromOrder( $order, $cart_info )->save();
        $userData->sessionSave();
        $response_url = add_query_arg( array( 'payment' => $payment->getToken() ), $response_url );

        return $this->api->createCheckout( $order, $userData->cart->getItemsTitle(), $response_url );
    }

    /**
     * @param string $order_id
     * @return array
     * @throws \Exception
     */
    public function retrieveOrder( $order_id )
    {
        return $this->api->retrieveOrder( $order_id );
    }
}