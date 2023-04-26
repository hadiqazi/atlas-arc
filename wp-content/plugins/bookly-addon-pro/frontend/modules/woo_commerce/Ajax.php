<?php
namespace BooklyPro\Frontend\Modules\WooCommerce;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\Booking\Lib\Errors;

/**
 * Class Ajax
 * @package BooklyPro\Frontend\Modules\WooCommerce
 */
class Ajax extends Controller
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Add product to cart
     *
     * return string JSON
     */
    public static function addToWoocommerceCart()
    {
        $userData = new BooklyLib\UserBookingData( self::parameter( 'form_id' ) );

        if ( $userData->load() ) {
            $success = self::addToCart( $userData );
            wp_send_json ( $success === true
                ? array( 'success' => true )
                : array( 'success' => false, 'error' => $success )
            );
        }

        Errors::sendSessionError();
    }

}