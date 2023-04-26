<?php

namespace BooklyPro\Frontend\Modules\ServicesForm;

use Bookly\Lib as BooklyLib;
use Bookly\Backend\Modules;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyPro\Lib;
use BooklyPro\Backend\Modules\Appearance;
use BooklyPro\Frontend\Modules\ModernBookingForm;

/**
 * Class ShortCode
 *
 * @package BooklyPro\Frontend\Modules\ServicesForm
 */
class ShortCode extends BooklyLib\Base\Component
{
    /**
     * Init component.
     */
    public static function init()
    {
        // Register short code.
        add_shortcode( 'bookly-services-form', array( __CLASS__, 'render' ) );

        // Assets.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'linkStyles' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'linkScripts' ) );
    }

    /**
     * Link styles.
     */
    public static function linkStyles()
    {
        if ( BooklyLib\Utils\Common::postsHaveShortCode( 'bookly-services-form' ) ) {
            self::enqueueStyles( array(
                'frontend' => array(
                    'css/bootstrap.bundle.min.css' => array(),
                    'css/bootstrap-icons.css' => array(),
                ),
            ) );
        }
    }

    /**
     * Link scripts.
     */
    public static function linkScripts()
    {
        if ( BooklyLib\Utils\Common::postsHaveShortCode( 'bookly-services-form' ) ) {

            ModernBookingForm\Form::render();

            self::enqueueScripts( array(
                'module' => array(
                    'js/services-form.js' => array( 'bookly-modern-booking-form.js' ),
                ),
            ) );

            wp_localize_script( 'bookly-services-form.js', 'BooklyL10nServicesForm', array() );
        }
    }

    /**
     * Render shortcode.
     *
     * @param array $attr
     * @return string
     */
    public static function render( $attr )
    {
        global $sitepress;

        // Disable caching.
        BooklyLib\Utils\Common::noCache();

        // Prepare URL for AJAX requests.
        $ajaxurl = admin_url( 'admin-ajax.php' );

        // Support WPML.
        if ( $sitepress instanceof \SitePress ) {
            $ajaxurl = add_query_arg( array( 'lang' => $sitepress->get_current_language() ), $ajaxurl );
        }

        $appearance = Appearance\ProxyProviders\Local::getAppearance( Lib\Entities\Form::TYPE_SERVICES_FORM, is_array( $attr ) ? current( $attr ) : null );
        if ( isset( $appearance['token'] ) ) {
            $form_id = uniqid( 'bookly-services-form-' . $appearance['token'] . '-', false );
        } else {
            $form_id = uniqid( 'bookly-services-form-', false );
        }

        Proxy\Shared::renderForm( $form_id );

        return self::renderTemplate( 'short_code', compact( 'ajaxurl', 'form_id', 'appearance' ), false );
    }
}