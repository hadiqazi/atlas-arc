<?php
namespace BooklyPro\Backend\Modules\Appearance\ProxyProviders;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules\Appearance\Proxy;
use Bookly\Lib\Config;
use BooklyPro\Lib\Plugin;

/**
 * Class Shared
 * @package BooklyPro\Backend\Modules\Appointments\ProxyProviders
 */
class Shared extends Proxy\Shared
{
    /**
     * @inheritDoc
     */
    public static function prepareOptions( array $options_to_save, array $options )
    {
        $add_options = array(
            'bookly_app_show_address',
            'bookly_app_show_appointment_qr',
            'bookly_app_show_birthday',
            'bookly_app_show_tips',
            'bookly_l10n_info_address',
            'bookly_l10n_info_payment_step_with_100percents_off_price',
            'bookly_l10n_info_payment_step_without_intersected_gateways',
            'bookly_l10n_invalid_day',
            'bookly_l10n_label_additional_address',
            'bookly_l10n_label_birthday_day',
            'bookly_l10n_label_birthday_month',
            'bookly_l10n_label_birthday_year',
            'bookly_l10n_label_city',
            'bookly_l10n_label_country',
            'bookly_l10n_label_postcode',
            'bookly_l10n_label_state',
            'bookly_l10n_label_street',
            'bookly_l10n_label_street_number',
            'bookly_l10n_required_additional_address',
            'bookly_l10n_required_city',
            'bookly_l10n_required_country',
            'bookly_l10n_required_day',
            'bookly_l10n_required_month',
            'bookly_l10n_required_postcode',
            'bookly_l10n_required_state',
            'bookly_l10n_required_street',
            'bookly_l10n_required_street_number',
            'bookly_l10n_required_year',
            'bookly_l10n_label_tips',
            'bookly_l10n_tips_error',
            'bookly_l10n_button_apply_tips',
        );
        if ( Config::paypalEnabled() ) {
            $add_options[] = 'bookly_l10n_label_pay_paypal';
        }
        if ( Config::squareEnabled() ) {
            $add_options[] = 'bookly_l10n_label_pay_cloud_square';
        }
        if ( Config::giftEnabled() ) {
            $add_options[] = 'bookly_l10n_label_pay_cloud_gift';
        }

        return array_merge( $options_to_save, array_intersect_key( $options, array_flip( $add_options ) ) );
    }

    /**
     * @inheritDoc
     */
    public static function paymentGateways( $data )
    {
        if( Config::paypalEnabled() ) {
            $data[ BooklyLib\Entities\Payment::TYPE_PAYPAL ] = array(
                'label_option_name' => 'bookly_l10n_label_pay_paypal',
                'title' => 'PayPal',
                'with_card' => true,
                'logo_url' => plugins_url( 'frontend/resources/images/paypal.svg', Plugin::getMainFile() ),
            );
        }

        if ( Config::squareEnabled() ) {
            $data[ BooklyLib\Entities\Payment::TYPE_CLOUD_SQUARE ] = array(
                'label_option_name' => 'bookly_l10n_label_pay_cloud_square',
                'title' => 'Square Cloud',
                'with_card' => false,
                'logo_url' => 'default',
            );
        }

        if ( Config::giftEnabled() ) {
            $data[ BooklyLib\Entities\Payment::TYPE_CLOUD_GIFT ] = array(
                'label_option_name' => 'bookly_l10n_label_pay_cloud_gift',
                'title' => __( 'Gift Cards', 'bookly' ),
                'with_card' => false,
                'logo_url' => false,
            );
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public static function prepareCodes( array $codes )
    {
        return array_merge( $codes, array(
            'online_meeting_url' => array( 'description' => __( 'Online meeting URL', 'bookly' ), 'if' => true, 'flags' => array( 'step' => 8, 'extra_codes' => true ) ),
            'online_meeting_password' => array( 'description' => __( 'Online meeting password', 'bookly' ), 'if' => true, 'flags' => array( 'step' => 8, 'extra_codes' => true ) ),
            'online_meeting_join_url' => array( 'description' => __( 'Online meeting join URL', 'bookly' ), 'if' => true, 'flags' => array( 'step' => 8, 'extra_codes' => true ) ),
        ) );
    }

    /**
     * @inheritDoc
     */
    public static function renderGatewayOptions( $slug )
    {
        if ( $slug === BooklyLib\Entities\Payment::TYPE_CLOUD_GIFT ) {
            self::renderTemplate( 'cloud_gift_options' );
        }
    }

    /**
     * @inerhitDoc
     */
    public static function prepareGatewayTitle( $title, $gateway )
    {
        return $gateway === BooklyLib\Entities\Payment::TYPE_PAYPAL
            ? __( 'I will pay now with PayPal', 'bookly' )
            : $title;
    }
}