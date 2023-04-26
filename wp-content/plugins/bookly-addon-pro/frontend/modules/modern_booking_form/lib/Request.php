<?php
namespace BooklyPro\Frontend\Modules\ModernBookingForm\Lib;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Payment;
use Bookly\Frontend\Components\Booking;
use Bookly\Frontend\Modules\Booking\Lib\Steps;
use Bookly\Frontend\Modules\Booking\Proxy as BookingProxy;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyPro\Frontend\Modules\WooCommerce;
use BooklyPro\Lib\Entities\GiftCard;
use BooklyPro\Lib\Entities\Form;
use BooklyPro\Lib\Payment\Square;
use BooklyPro\Lib\Utils\Common;

/**
 * Class Request
 *
 * @package BooklyPro\Frontend\Modules\ModernBookingForm\Lib
 */
class Request extends BooklyLib\Base\Component
{
    const BOOKING_STATUS_COMPLETED                  = 'completed';
    const BOOKING_STATUS_GROUP_SKIP_PAYMENT         = 'group_skip_payment';
    const BOOKING_STATUS_PAYMENT_IMPOSSIBLE         = 'payment_impossible';
    const BOOKING_STATUS_APPOINTMENTS_LIMIT_REACHED = 'appointments_limit_reached';

    /** @var array */
    protected $customer = array();
    /** @var array */
    protected $custom_fields = array();
    /** @var string */
    protected $form_id;
    /** @var array */
    protected $notices = array();
    /** @var string */
    protected $step = 'details';
    /** @var string self::BOOKING_STATUS_* */
    protected $booking_status;
    /** @var array */
    protected $data = array();
    /** @var BooklyLib\UserBookingData */
    protected $userData;
    /** @var BooklyLib\CartInfo */
    protected $cart_info;
    /** @var string */
    protected $type; // appointment || package
    /** @var Payment */
    protected $payment;
    /** @var string */
    protected $gateway;

    public function __construct()
    {
        $this->customer = self::parameter( 'customer' );
        $this->type = self::parameter( 'type' );
        $this->form_id = self::parameter( 'form_id' );
        $this->custom_fields = array_map( function ( $id, $value ) {
            return compact( 'id', 'value' );
        }, array_keys( self::parameter( 'custom_fields', array() ) ), self::parameter( 'custom_fields', array() ) );
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $this->notices = array();

        if ( $this->customer['email'] === '' || ! is_email( $this->customer['email'] ) ) {
            $this->notices['email'] = 'required';
        }

        foreach ( array( 'phone', 'first_name', 'last_name' ) as $field ) {
            if ( $this->customer[ $field ] === '' ) {
                $this->notices[ $field ] = 'required';
            }
        }

        Proxy\Shared::validate( $this );

        if ( $this->notices ) {
            return false;
        }

        if ( $this->isAppointment() && $this->getUserData()->cart->getFailedKey() !== null ) {
            $this->step = 'slots';
            $this->notices['slots'] = 'slot_not_available';

            return false;
        }

        if ( $this->getGateway() == '' ) {
            if ( $this->isReachedAppointmentsLimit() ) {
                $this->step = 'done';
                $this->setBookingStatus( self::BOOKING_STATUS_APPOINTMENTS_LIMIT_REACHED );

                return false;
            }

            if ( BookingProxy\CustomerGroups::getSkipPayment( $this->getUserData()->getCustomer() ) ) {
                $this->step = 'done';
                $this->setBookingStatus( self::BOOKING_STATUS_GROUP_SKIP_PAYMENT );

                return false;
            }

            if ( BooklyLib\Config::paymentStepDisabled() || BooklyLib\Config::wooCommerceEnabled() ) {
                $this->step = 'done';
                $this->setBookingStatus( self::BOOKING_STATUS_COMPLETED );

                return true;
            }

            $pay_now = $this->getUserData()->cart->getInfo()->getPayNow();
            if ( $pay_now > 0 ) {
                $gateways = PaymentFlow::getAllowedGateways( $this->getUserData() );
                if ( $gateways ) {
                    $payment_gateways = array();
                    foreach ( PaymentFlow::orderGateways( $gateways ) as $type ) {
                        $payment_gateways[] = array( 'type' => $type, 'image' => Payment::typeToImage( $type ), 'discount' => - (float) get_option( 'bookly_' . $type . '_increase' ), 'deduction' => - (float) get_option( 'bookly_' . $type . '_addition' ) );
                    }
                    if ( count( $payment_gateways ) === 1 && $payment_gateways[0]['type'] === Payment::TYPE_LOCAL && ! BooklyLib\Config::couponsActive() ) {
                        $this->setGateway( Payment::TYPE_LOCAL );

                        return true;
                    }
                    $this->step = 'payment';
                    $this->data = compact( 'payment_gateways' );
                } else {
                    $this->step = 'done';
                    $this->setBookingStatus( self::BOOKING_STATUS_PAYMENT_IMPOSSIBLE );
                }

                return false;
            }
        } elseif ( $this->getGateway() === Payment::TYPE_CLOUD_GIFT ) {
            try {
                Common::validateGiftCard( self::parameter( 'gift_card' ), $this->getUserData() );
            } catch ( \LogicException $e ) {
                $result = Form::query()->where( 'token', self::parameter( 'slug' ) )->fetchVar( 'settings' );
                $settings = $result ? json_decode( $result, true ) : array();
                if ( isset( $settings['l10n'][ 'gift_card_' . $e->getMessage() ] ) ) {
                    $error = $settings['l10n'][ 'gift_card_' . $e->getMessage() ];
                } else {
                    switch ( $e->getMessage() ) {
                        case GiftCard::VALIDATION_EXPIRED:
                            $error = __( 'This gift card has expired', 'bookly' );
                            break;
                        case GiftCard::VALIDATION_INVALID:
                            $error = __( 'This gift card cannot be used for the current order', 'bookly' );
                            break;
                        case GiftCard::VALIDATION_LOW_BALANCE:
                            $error = __( 'Gift card balance is not enough', 'bookly' );
                            break;
                        case GiftCard::VALIDATION_NOT_FOUND:
                            $error = __( 'Gift card not found', 'bookly' );
                            break;
                        default:
                            $error = __( 'Error', 'bookly' );
                    }
                }
                $this->step = 'payment';
                $this->data = array(
                    'error' => BooklyLib\Utils\Common::getTranslatedString( 'appearance_string_' . md5( $error ), $error ),
                );
                $this->setBookingStatus( self::BOOKING_STATUS_PAYMENT_IMPOSSIBLE );

                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getError()
    {
        $result = array(
            'step' => $this->step,
            'data' => $this->data,
        );
        if ( $this->notices ) {
            $result['data']['notices'] = $this->notices;
        }
        if ( $this->booking_status ) {
            $result['status'] = $this->booking_status;
        }

        return $result;
    }

    /**
     * @param array $notice
     * @return void
     */
    public function addNotice( $notice )
    {
        $this->notices = array_merge( $this->notices, $notice );
    }

    /**
     * Get staff id
     *
     * @return int
     */
    public function getStaffId()
    {
        return self::parameter( 'staff_id' );
    }

    /**
     * Get location id
     *
     * @return int
     */
    public function getLocationId()
    {
        return self::parameter( 'location_id' );
    }

    /**
     * Get service id
     *
     * @return int
     */
    public function getServiceId()
    {
        return self::parameter( 'service_id' );
    }

    /**
     * Get customer data
     *
     * @return array
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Get custom fields
     *
     * @return array
     */
    public function getCustomFields()
    {
        return $this->custom_fields;
    }

    /**
     * Get form ID
     *
     * @return array
     */
    public function getFormId()
    {
        return $this->form_id;
    }

    /**
     * Process payment
     *
     * @return array
     * @throws \Exception
     */
    public function processPayment()
    {
        if ( BooklyLib\Config::wooCommerceEnabled()
            && ( get_option( 'bookly_wc_create_order_at_zero_cost' ) || $this->getCartInfo()->getTotal() > 0 )
        ) {
            return $this->addToWooCommerceCart();
        }
        if ( BooklyLib\Config::paymentStepDisabled() ) {
            return $this->createAppointment( false );
        }
        if ( $this->getGateway() === BooklyLib\Entities\Payment::TYPE_LOCAL
            || $this->getCartInfo()->getPayNow() == 0
        ) {
            return $this->createAppointment( true );
        }
        if ( $this->getGateway() === BooklyLib\Entities\Payment::TYPE_CLOUD_GIFT && BooklyLib\Config::giftEnabled() ) {
            $gift_card = $this->getUserData()->getGiftCard();
            if ( $gift_card && ( $gift_card->getBalance() >= $this->getCartInfo()->getPayNow() ) ) {
                return $this->createAppointment( true );
            }
        }

        try {
            $response_url = add_query_arg( array( 'action' => 'bookly_pro_checkout_response' ), admin_url( 'admin-ajax.php' ) );
            switch ( $this->getGateway() ) {
                case BooklyLib\Entities\Payment::TYPE_CLOUD_STRIPE;
                    $redirect_url = $this->getCheckoutUrlForCloudStripe( $response_url );
                    break;
                case BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE;
                    $redirect_url = $this->getCheckoutUrlForCloudSquare( $response_url );
                    break;
                default:
                    $redirect_url = Proxy\Shared::getCheckoutUrl( '', $this, $response_url ) ?: '';
                    $this->payment = Payment::find( $this->getUserData()->getPaymentId() );
            }
        } catch ( \Exception $e ) {
            $payment = $this->payment ?: Payment::find( $this->getUserData()->getPaymentId() );
            $payment && PaymentFlow::removeCascade( $payment );
            $this->step = 'payment';
            $this->data = array(
                'error' => $e->getMessage(),
            );
            throw $e;
        }
        $payment = $this->getPayment()->getToken();

        return compact( 'redirect_url', 'payment' );
    }

    /**
     * @return bool
     */
    protected function isAppointment()
    {
        return $this->type === 'appointment';
    }

    /**
     * @return BooklyLib\UserBookingData
     */
    public function getUserData()
    {
        if ( $this->userData === null ) {
            $this->userData = new BooklyLib\UserBookingData( null );
            $service_id = $this->getServiceId();
            $staff_id = $this->getStaffId();
            $location_id = $this->getLocationId() ?: null;
            $nop = self::parameter( 'nop' );
            $units = self::parameter( 'units', 1 );
            $extras = self::parameter( 'extras', array() );
            $coupon = self::parameter( 'coupon' );
            $gift_card = self::parameter( 'gift_card' );
            foreach ( array_keys( $extras, 0, false ) as $key ) {
                unset( $extras[ $key ] );
            }
            $slot = $this->isAppointment()
                ? self::parameter( 'slot' )
                : array( 'value' => sprintf( '[[%d,%d,null,%s]]', $service_id, $staff_id, $location_id ?: 'null' ) );
            $slots = json_decode( $slot['value'], true );

            $this->userData
                ->setCouponCode( $coupon )
                ->setGiftCode( $gift_card )
                ->setPhone( isset( $this->customer['phone_formatted'] ) ? $this->customer['phone_formatted'] : $this->customer['phone'] )
                ->setSlots( $slots );

            $client_fields = array( 'first_name', 'last_name', 'email' );

            if ( $this->getSettings()->get( 'show_address' ) ) {
                $address = $this->getSettings()->get( 'address' );
                if ( isset( $address['show'] ) ) {
                    $client_fields = array_merge( $client_fields, $address['show'] );
                }
            }
            foreach ( $client_fields as $field ) {
                if ( array_key_exists( $field, $this->customer ) ) {
                    $this->userData->fillData( array( $field => $this->customer[ $field ] ) );
                }
            }

            $cart_item = new BooklyLib\CartItem();
            $cart_item
                ->setStaffIds( array( $staff_id ) )
                ->setServiceId( $service_id )
                ->setNumberOfPersons( $nop )
                ->setLocationId( $location_id )
                ->setUnits( $units )
                ->setExtras( $extras )
                ->setCustomFields( $this->custom_fields )
                ->setSlots( $slots );

            $this->userData->cart->add( $cart_item );
        }

        return $this->userData;
    }

    /**
     * @return BooklyLib\CartInfo
     */
    protected function getCartInfo()
    {
        if ( $this->cart_info === null ) {
            $this->cart_info = $this->getUserData()->cart->getInfo( $this->getGateway() );
        }

        return $this->cart_info;
    }

    /**
     * Get payment system
     *
     * @return string
     */
    protected function getGateway()
    {
        return $this->gateway ?: self::parameter( 'gateway' );
    }

    /**
     * @param string $gateway
     * @return void
     */
    protected function setGateway( $gateway )
    {
        $this->gateway = $gateway;
    }

    /**
     * Create payment
     *
     * @return Payment
     */
    protected function createPayment()
    {
        $this->payment = new Payment();
        $cart_info = $this->getCartInfo();
        if ( $cart_info->getPayNow() > 0 ) {
            $this->payment
                ->setType( $this->getGateway() )
                ->setStatus( Payment::STATUS_PENDING );
        } else {
            $this->payment
                ->setType( Payment::TYPE_FREE )
                ->setStatus( Payment::STATUS_COMPLETED );
        }
        if ( $cart_info->getCoupon() ) {
            $this->payment->setCouponId( $cart_info->getCoupon()->getId() );
        }
        if ( $this->payment->getType() === Payment::TYPE_LOCAL
            || $this->payment->getType() === Payment::TYPE_FREE
        ) {
            $this->payment
                ->setPaidType( Payment::PAY_IN_FULL )
                ->setTotal( $cart_info->getTotal() )
                ->setTax( $cart_info->getTotalTax() )
                ->setPaid( 0 );
        } else {
            $this->payment
                ->setCartInfo( $cart_info );
        }
        $this->payment->save();

        return $this->payment;
    }

    /**
     * Create appointment or package with payment
     *
     * @return array
     */
    protected function createAppointment( $with_payment = true )
    {
        $payment = $with_payment
            ? $this->createPayment()
            : null;
        $status = PaymentFlow::STATUS_COMPLETED;
        $data = array();
        if ( $this->isAppointment() ) {
            $order = $this->getUserData()->save( $payment );
            $coupon = $this->getUserData()->getCoupon();
            if ( $coupon ) {
                $coupon->claim();
                $coupon->save();
            }
            $gift_card = $this->getUserData()->getGiftCard();
            if ( $gift_card ) {
                $gift_card->charge( $this->getCartInfo()->getPayNow() )->save();
            }

            $payment && $payment->setDetailsFromOrder( $order, $this->getCartInfo() )->save();
            $order_id = current( $order->getItems() )->getCA()->getOrderId();
            PaymentFlow::sendNotifications( $order_id );
            $data = PaymentFlow::getBookingResultFromOrderId( $status, $order_id );
        } else {
            Proxy\Packages::sendNotifications( Proxy\Packages::createPackage( $this, $payment ) );
        }

        return compact( 'status', 'data' );
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function addToWooCommerceCart()
    {
        $session = WC()->session;
        /** @var \WC_Session_Handler $session */
        if ( $session instanceof \WC_Session_Handler && $session->get_session_cookie() === false ) {
            $session->set_customer_session_cookie( true );
        }
        $userData = $this->getUserData();
        if ( WooCommerce\Controller::addToCart( $userData ) === true ) {
            $status = PaymentFlow::STATUS_COMPLETED;
            $data = array( 'redirect_url' => wc_get_cart_url() );
        } else {
            $this->step = 'slots';
            $this->notices['slots'] = 'slot_not_available';
            throw new \Exception();
        }

        return compact( 'status', 'data' );
    }

    /**
     * Get checkout url for Cloud Stripe
     *
     * @param string $response_url
     * @return string
     * @throws \Exception
     */
    protected function getCheckoutUrlForCloudStripe( $response_url )
    {
        $payment = $this->createPayment();
        $metadata = array();
        // Build custom metadata.
        if ( get_option( 'bookly_cloud_stripe_custom_metadata' ) ) {
            $codes = Booking\InfoText::getCodes( Steps::PAYMENT, $this->getUserData() );
            foreach ( get_option( 'bookly_cloud_stripe_metadata', array() ) as $meta ) {
                $metadata[ preg_replace( '/[^ \w]+/', '', $meta['name'] ) ] = BooklyLib\Utils\Codes::replace( $meta['value'], $codes, false );
            }
        }
        $metadata['payment_id'] = $payment->getId();
        $info = array(
            'total' => $payment->getPaid(),
            'description' => $this->getUserData()->cart->getItemsTitle(),
            'customer_email' => $this->customer['email'],
            'metadata' => $metadata,
        );
        $api = BooklyLib\Cloud\API::getInstance();
        $response = $api->stripe
            ->createSession(
                $info,
                add_query_arg( array( 'payment' => $payment->getToken() ), $response_url ),
                add_query_arg( array( 'payment' => $payment->getToken() ), $response_url )
            );
        if ( $response ) {
            if ( $this->isAppointment() ) {
                $order = $this->getUserData()->save( $payment );
                $payment
                    ->setDetailsFromOrder( $order, $this->getCartInfo() );
            } else {
                Proxy\Packages::sendNotifications( Proxy\Packages::createPackage( $this, $payment ) );
            }
            $payment
                ->setRefId( $response['payment_intent'] )
                ->save();

            return $response['redirect_url'];
        } else {
            $payment->delete();
        }

        throw new \Exception( current( $api->getErrors() ) );
    }

    /**
     * Get checkout url for Cloud Square
     *
     * @param string $response_url
     * @return string
     * @throws \Exception
     */
    protected function getCheckoutUrlForCloudSquare( $response_url )
    {
        $square = new Square();
        $checkout_url = $square->getCheckoutUrl( $this->getUserData(), $response_url );
        $this->payment = Payment::find( $this->getUserData()->getPaymentId() );

        return $checkout_url;
    }

    /**
     * Get payment
     *
     * @return BooklyLib\Entities\Payment
     */
    protected function getPayment()
    {
        return $this->payment;
    }

    /**
     * Set booking status
     *
     * @param string $status
     * @return void
     */
    protected function setBookingStatus( $status )
    {
        $this->booking_status = $status;
    }

    /**
     * Check if the client has reached the appointments limit
     *
     * @return bool
     */
    protected function isReachedAppointmentsLimit()
    {
        $data = array();
        foreach ( $this->getUserData()->cart->getItems() as $cart_item ) {
            if ( $cart_item->toBePutOnWaitingList() ) {
                // Skip waiting list items.
                continue;
            }
            $service = $cart_item->getService();
            if ( $service->getLimitPeriod() != 'off' ) {
                $slots = $cart_item->getSlots();
                $data[ $service->getId() ]['service'] = $service;
                $data[ $service->getId() ]['dates'][] = $slots[0][2];
            }
        }
        if ( $data ) {
            $customer = $this->getUserData()->getCustomer();
            foreach ( $data as $service_data ) {
                if ( $service_data['service']->appointmentsLimitReached( $customer->getId(), $service_data['dates'] ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return BooklyLib\Utils\Collection
     */
    protected function getSettings()
    {
        static $settings;
        if ( $settings === null ) {
            $settings = new BooklyLib\Utils\Collection( json_decode( Form::query()->where( 'token', self::parameter( 'slug' ) )->fetchVar( 'settings' ), true ) );
        }

        return $settings;
    }
}