<?php
namespace BooklyPro\Frontend\Modules\ModernBookingForm;

use BooklyPro\Lib;
use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;

/**
 * Class Form
 *
 * @package BooklyPro\Frontend\Modules\ModernBookingForm
 */
class Form extends BooklyLib\Base\Component
{
    public static function render()
    {
        $tel_input_enabled = get_option( 'bookly_cst_phone_default_country' ) !== 'disabled';

        self::enqueueScripts( array(
            'backend' => array(
                'js/common.js' => array( 'jquery' ),
            ),
            'frontend' => array(
                'js/bootstrap.bundle.min.js' => array( 'jquery' ),
            ),
            'module' => array(
                'js/modern-booking-form.js' => array( 'bookly-bootstrap.bundle.min.js', 'bookly-frontend-globals' ),
            ),
        ) );
        if ( $tel_input_enabled ) {
            self::enqueueStyles( array(
                'bookly' => array( 'frontend/resources/css/intlTelInput.css' ),
            ) );
            self::enqueueScripts( array(
                'bookly' => array( 'frontend/resources/js/intlTelInput.min.js' => array( 'jquery' ) ),
            ) );
        }

        $customer = new BooklyLib\Entities\Customer();
        is_user_logged_in() && ( $wp_user_id = get_current_user_id() ) && $customer->loadBy( array( 'wp_user_id' => $wp_user_id ) );

        wp_localize_script(
            'bookly-modern-booking-form.js', 'BooklyL10nModernBookingForm', Proxy\Shared::prepareFormOptions( array(
            'customer' => array(
                'first_name' => $customer->getFirstName(),
                'last_name' => $customer->getLastName(),
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhone(),
                'country' => $customer->getCountry(),
                'state' => $customer->getState(),
                'postcode' => $customer->getPostcode(),
                'city' => $customer->getCity(),
                'street' => $customer->getStreet(),
                'street_number' => $customer->getStreetNumber(),
                'additional_address' => $customer->getAdditionalAddress(),
            ),
            'complex_services' => array(),
            'format_price' => BooklyLib\Utils\Price::formatOptions(),
            'datePicker' => BooklyLib\Utils\DateTime::datePickerOptions(),
            'maxDaysForBooking' => BooklyLib\Config::getMaximumAvailableDaysForBooking(),
            'moment_format_date' => BooklyLib\Utils\DateTime::convertFormat( 'date', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'moment_format_time' => BooklyLib\Utils\DateTime::convertFormat( 'time', BooklyLib\Utils\DateTime::FORMAT_MOMENT_JS ),
            'casest' => BooklyLib\Config::getCaSeSt(),
            'bs' => plugins_url( 'frontend/resources/css/bootstrap.bundle.min.css', Lib\Plugin::getMainFile() ) . '?ver=' . Lib\Plugin::getVersion(),
            'intlTelInput' => array(
                'enabled' => $tel_input_enabled,
                'utils' => plugins_url( 'intlTelInput.utils.js', BooklyLib\Plugin::getDirectory() . '/frontend/resources/js/intlTelInput.utils.js' ),
                'country' => get_option( 'bookly_cst_phone_default_country' ),
            ),
        ) )
        );
    }
}