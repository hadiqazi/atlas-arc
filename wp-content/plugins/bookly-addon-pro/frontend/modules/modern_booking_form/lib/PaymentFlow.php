<?php
namespace BooklyPro\Frontend\Modules\ModernBookingForm\Lib;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Config;
use Bookly\Lib\Entities\Payment;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use Bookly\Frontend\Modules\Booking;
use BooklyPro\Frontend\Modules\Square\Controller as SquareController;

/**
 * Class PaymentFlow
 *
 * @package BooklyPro\Frontend\Modules\ModernBookingForm\Lib
 */
class PaymentFlow extends BooklyLib\Base\Component
{
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /** @var array */
    protected static $support_gateways = array(
        Payment::TYPE_LOCAL,
        Payment::TYPE_CLOUD_STRIPE,
        Payment::TYPE_CLOUD_SQUARE,
        Payment::TYPE_CLOUD_GIFT,
        Payment::TYPE_PAYPAL,   // only payments standard
    );

    /**
     * Set payment flow completed
     *
     * @param BooklyLib\Entities\Payment $payment
     * @return void
     */
    public static function setCompleted( BooklyLib\Entities\Payment $payment )
    {
        $payment->setStatus( BooklyLib\Entities\Payment::STATUS_COMPLETED )->save();
        $order_id = BooklyLib\Entities\CustomerAppointment::query()
            ->where( 'payment_id', $payment->getId() )
            ->fetchVar( 'order_id' );

        self::sendNotifications( $order_id );
    }

    /**
     * Get data for step done
     *
     * @param string $status
     * @param BooklyLib\Entities\Payment $payment
     * @return array
     */
    public static function getBookingResultFromPayment( $status, BooklyLib\Entities\Payment $payment )
    {
        $data = array();
        switch ( $status ) {
            case self::STATUS_PROCESSING:
            case self::STATUS_COMPLETED:
                if ( $payment->getTarget() === BooklyLib\Entities\Payment::TARGET_APPOINTMENTS ) {
                    /** @var BooklyLib\Entities\Appointment $appointment */
                    $appointment = BooklyLib\Entities\Appointment::query( 'a' )
                        ->leftJoin( 'CustomerAppointment', 'ca', 'a.id = ca.appointment_id' )
                        ->where( 'ca.payment_id', $payment->getId() )
                        ->findOne();

                    $data['qr'] = self::getQr( $appointment );
                }
                break;
        }

        return $data;
    }

    /**
     * Get data for step done
     *
     * @param string $status
     * @param int $order_id
     * @return array
     */
    public static function getBookingResultFromOrderId( $status, $order_id )
    {
        $data = array();
        switch ( $status ) {
            case self::STATUS_PROCESSING:
            case self::STATUS_COMPLETED:
                /** @var BooklyLib\Entities\Appointment $appointment */
                $appointment = BooklyLib\Entities\Appointment::query( 'a' )
                    ->leftJoin( 'CustomerAppointment', 'ca', 'a.id = ca.appointment_id' )
                    ->where( 'ca.order_id', $order_id )
                    ->findOne();
                if ( $appointment ) {
                    $data['qr'] = self::getQr( $appointment );
                }
        }

        return $data;
    }

    /**
     * @param BooklyLib\Entities\Appointment $appointment
     * @return string
     */
    private static function getQr( $appointment )
    {
        $service = BooklyLib\Entities\Service::find( $appointment->getServiceId() );
        $ics = new BooklyLib\Utils\Ics\Feed();
        $description = BooklyLib\Utils\Codes::replace( BooklyLib\Utils\Common::getTranslatedOption( 'bookly_l10n_ics_customer_template' ), BooklyLib\Utils\Codes::getAppointmentCodes( $appointment ), false );
        $ics->addEvent( $appointment->getStartDate(), $appointment->getEndDate(), $service->getTranslatedTitle(), $description, self::parameter( 'location_id' ) );

        return add_query_arg(
            array(
                'cht' => 'qr',
                'chs' => '298x298',
                'chl' => urlencode( $ics->render() ),
            ),
            'https://chart.googleapis.com/chart'
        );
    }

    /**
     * Delete cascade related items
     *
     * @param BooklyLib\Entities\Payment $payment
     * @return void
     */
    public static function removeCascade( BooklyLib\Entities\Payment $payment )
    {
        if ( $payment->getTarget() === BooklyLib\Entities\Payment::TARGET_APPOINTMENTS ) {
            foreach ( BooklyLib\Entities\CustomerAppointment::query()->where( 'payment_id', $payment->getId() )->find() as $ca ) {
                $ca->deleteCascade();
            }
        } else {
            Proxy\Packages::deleteCascade( $payment );
        }
        $payment->delete();
    }

    /**
     * Retrieve payment system status
     *
     * @param BooklyLib\Entities\Payment $payment
     * @return string self::STATUS_*
     * @throws \Exception
     */
    public static function retrieveStatus( BooklyLib\Entities\Payment $payment )
    {
        if ( in_array( $payment->getType(), self::$support_gateways, true ) ) {
            switch ( $payment->getType() ) {
                case BooklyLib\Entities\Payment::TYPE_CLOUD_STRIPE:
                    return self::retrieveStatusCloudStripe( $payment );
                case BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE:
                    return self::retrieveStatusCloudSquare( $payment );
                default:
                    return Proxy\Shared::retrieveStatus( self::STATUS_PROCESSING, $payment );
            }
        }
        throw new \Exception();
    }

    /**
     * @param Payment $payment
     * @return string
     * @throws \Exception
     */
    protected static function retrieveStatusCloudStripe( BooklyLib\Entities\Payment $payment )
    {
        $payment_intent = $payment->getRefId();
        $data = BooklyLib\Cloud\API::getInstance()->stripe->retrievePaymentIntent( $payment_intent );
        if ( $data['status'] === 'canceled' ) {
            throw new \Exception();
        }
        if ( strtoupper( $data['currency'] ) == BooklyLib\Config::getCurrency() ) {
            $paid = $payment->getPaid();
            if ( ! BooklyLib\Config::isZeroDecimalsCurrency() ) {
                $paid *= 100;
            }
            if ( (int) $paid == $data['amount'] ) {
                $pi_status = $data['status'];
                $good_statuses = array(
                    'succeeded' => self::STATUS_COMPLETED,
                    'processing' => self::STATUS_PROCESSING,
                );
                if ( array_key_exists( $pi_status, $good_statuses ) ) {
                    return $good_statuses[ $pi_status ];
                }
            }
        }

        throw new \Exception();
    }

    /**
     * @param Payment $payment
     * @return string
     * @throws \Exception
     */
    protected static function retrieveStatusCloudSquare( BooklyLib\Entities\Payment $payment )
    {
        if ( SquareController::processOrderData( $payment->getRefId() ) ) {
            return self::STATUS_COMPLETED;
        }

        throw new \Exception();
    }

    /**
     * Send notifications
     *
     * @param int $order_id
     * @return void
     */
    public static function sendNotifications( $order_id )
    {
        $order = BooklyLib\DataHolders\Booking\Order::createFromOrderId( $order_id );
        if ( $order ) {
            current( $order->getItems() )->getCA()->setJustCreated( true );
            BooklyLib\Notifications\Cart\Sender::send( $order );
        }
        foreach (
            BooklyLib\Entities\Appointment::query( 'a' )
                ->leftJoin( 'CustomerAppointment', 'ca', 'a.id = ca.appointment_id' )
                ->where( 'ca.order_id', $order_id )->find() as $appointment
        ) {
            BooklyLib\Proxy\Pro::syncGoogleCalendarEvent( $appointment );
            BooklyLib\Proxy\OutlookCalendar::syncEvent( $appointment );
        }
    }

    /**
     * @param BooklyLib\UserBookingData $userData
     * @return array
     */
    public static function getAllowedGateways( $userData )
    {
        $gateways = array();
        foreach ( self::getSupportedGateways() as $gateway ) {
            if ( Booking\Proxy\CustomerGroups::allowedGateway( $gateway, $userData ) !== false ) {
                switch ( $gateway ) {
                    case BooklyLib\Entities\Payment::TYPE_LOCAL:
                    case BooklyLib\Entities\Payment::TYPE_PAYPAL:
                        $gateways[ $gateway ] = true;
                        break;
                    default:
                        $product = BooklyLib\Entities\Payment::typeToProduct( $gateway );
                        if ( $product && get_option( 'bookly_' . $gateway . '_enabled' ) && BooklyLib\Cloud\API::getInstance()->account->productActive( $product ) ) {
                            $gateways[ $gateway ] = true;
                        }
                }
            }
        }

        return array_keys( Booking\Proxy\Pro::filterGateways( $gateways, $userData ) );
    }

    /**
     * @return array
     */
    public static function getSupportedGateways()
    {
        $gateways = array();
        if ( BooklyLib\Config::payLocallyEnabled() ) {
            $gateways[] = BooklyLib\Entities\Payment::TYPE_LOCAL;
        }
        $products = array(
            BooklyLib\Cloud\Account::PRODUCT_STRIPE => BooklyLib\Entities\Payment::TYPE_CLOUD_STRIPE,
            BooklyLib\Cloud\Account::PRODUCT_SQUARE => BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE,
            BooklyLib\Cloud\Account::PRODUCT_GIFT   => BooklyLib\Entities\Payment::TYPE_CLOUD_GIFT,
        );
        foreach ( $products as $product => $gateway ) {
            $pay_cloud_gateway = BooklyLib\Cloud\API::getInstance()->account->productActive( $product ) && get_option( 'bookly_' . $gateway . '_enabled' );
            if ( $pay_cloud_gateway ) {
                $gateways[] = $gateway;
            }
        }

        switch ( get_option( 'bookly_paypal_enabled' ) ) {
            case \BooklyPro\Lib\Payment\PayPal::TYPE_PAYMENTS_STANDARD:
                if ( Config::paypalPaymentsStandardActive() ) {
                    $gateways[] = BooklyLib\Entities\Payment::TYPE_PAYPAL;
                }
                break;
        }

        return $gateways;
    }

    /**
     * @param array $gateways
     * @return array
     */
    public static function orderGateways( array $gateways )
    {
        $order = BooklyLib\Config::getGatewaysPreference();
        $ordered = array();
        if ( $order ) {
            foreach ( $order as $payment_system ) {
                if ( in_array( $payment_system, $gateways ) ) {
                    $ordered[] = $payment_system;
                }
            }
        }
        foreach ( $gateways as $payment_system ) {
            if ( ! in_array( $payment_system, $ordered ) ) {
                $ordered[] = $payment_system;
            }
        }

        $list = array();
        foreach ( $ordered as $gateway ) {
            if ( self::isSupported( $gateway ) ) {
                $list[] = $gateway;
            }
        }

        return $list;
    }

    /**
     * @param string $gateway
     * @return bool
     */
    protected static function isSupported( $gateway )
    {
        return in_array( $gateway, self::$support_gateways );
    }
}