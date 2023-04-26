<?php
namespace BooklyPro\Frontend\Modules\Square;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib\Payment\Square;

/**
 * Class Controller
 * @package Bookly\Frontend\Modules\Square
 */
class Controller extends BooklyLib\Base\Component
{
    /**
     * Checkout.
     */
    public static function checkout()
    {
        $square = new Square();
        $userData = new BooklyLib\UserBookingData( self::parameter( 'bookly_fid' ) );
        $userData->load();
        $response_url = add_query_arg( array( 'bookly_action' => 'cloud_square-return', 'bookly_fid' => $userData->getFormId() ), self::parameter( 'response_url' ) );
        try {
            $url = $square->getCheckoutUrl( $userData, $response_url );
            header( 'Location: ' . $url );
        } catch ( \Exception $e ) {
            $userData->setFailedPaymentStatus( BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE, 'error', $e->getMessage() )
                ->sessionSave();

            @wp_redirect( $response_url );
        }
        exit;
    }

    /**
     * Redirect from Payment Form to Bookly page.
     */
    public static function response()
    {
        $form_id  = self::parameter( 'bookly_fid' );
        $userData = new BooklyLib\UserBookingData( $form_id );
        if ( $userData->load() ) {
            try {
                if ( self::processOrderData( self::parameter( 'transactionId' ) ) ) {
                    $userData
                        ->setPaymentStatus( BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE, 'success' )
                        ->sessionSave();
                    @wp_redirect( remove_query_arg( array( 'checkoutId', 'transactionId', 'bookly_action', 'bookly_fid', 'payment' ), BooklyLib\Utils\Common::getCurrentPageURL() ) );
                    exit;
                } else {
                    $userData
                        ->setPaymentStatus( BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE, 'cancelled' )
                        ->sessionSave();
                }
            } catch ( \Exception $e ) {
                $userData->setFailedPaymentStatus( BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE, 'error', $e->getMessage() )
                    ->sessionSave();
                @wp_redirect( remove_query_arg( array( 'checkoutId', 'transactionId', 'bookly_action', 'bookly_fid', 'payment' ), BooklyLib\Utils\Common::getCurrentPageURL() ) );
                exit;
            }
        } else {
            @wp_redirect( remove_query_arg( array( 'checkoutId', 'transactionId', 'bookly_action', 'bookly_fid', 'payment' ), BooklyLib\Utils\Common::getCurrentPageURL() ) );
            exit;
        }
    }

    /**
     * @param string $order_id
     * @return bool
     * @throws \Exception
     */
    public static function processOrderData( $order_id )
    {
        $square = new Square();
        $data = $square->retrieveOrder( $order_id );
        switch ( $data['status'] ) {
            case 'CANCELED':
            case 'OPEN':
                return false;
            case 'COMPLETED':
            default:
                $payment = new BooklyLib\Entities\Payment();
                $loaded = $payment->loadBy( array(
                    'type' => BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE,
                    'id' => $data['metadata']['payment_id'],
                ) );
                if ( $loaded ) {
                    if ( $payment->getStatus() === BooklyLib\Entities\Payment::STATUS_PENDING ) {
                        $money = new Square\Money( $payment->getPaid() );
                        $paid = $money->toArray();
                        if ( $paid['amount'] == $data['total'] ) {
                            $payment->setStatus( BooklyLib\Entities\Payment::STATUS_COMPLETED )->save();
                            if ( $order = BooklyLib\DataHolders\Booking\Order::createFromPayment( $payment ) ) {
                                current( $order->getItems() )->getCA()->setJustCreated( true );
                                BooklyLib\Notifications\Cart\Sender::send( $order );
                            }
                            foreach (
                                BooklyLib\Entities\Appointment::query( 'a' )
                                    ->leftJoin( 'CustomerAppointment', 'ca', 'a.id = ca.appointment_id' )
                                    ->where( 'ca.payment_id', $payment->getId() )->find() as $appointment
                            ) {
                                BooklyLib\Proxy\Pro::syncGoogleCalendarEvent( $appointment );
                                BooklyLib\Proxy\OutlookCalendar::syncEvent( $appointment );
                            }
                        }
                    }
                }

                return true;
        }
    }
}