<?php

namespace BooklyPro\Frontend\Modules\SearchForm;

use Bookly\Lib as BooklyLib;
use Bookly\Frontend\Modules\ModernBookingForm\Proxy;
use BooklyPro\Lib;
use Bookly\Backend\Modules;
use BooklyPro\Backend\Modules\Appearance;
use BooklyPro\Frontend\Modules\ModernBookingForm;

/**
 * Class ShortCode
 *
 * @package BooklyPro\Frontend\Modules\SearchForm
 */
class ShortCode extends BooklyLib\Base\Component
{
    /**
     * Init component.
     */
    public static function init()
    {
        // Register short code.
        add_shortcode( 'bookly-search-form', array( __CLASS__, 'render' ) );

        // Assets.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'linkStyles' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'linkScripts' ) );
    }

    /**
     * Link styles.
     */
    public static function linkStyles()
    {
        if ( BooklyLib\Utils\Common::postsHaveShortCode( 'bookly-search-form' ) ) {
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
        if ( BooklyLib\Utils\Common::postsHaveShortCode( 'bookly-search-form' ) ) {

            ModernBookingForm\Form::render();

            self::enqueueScripts( array(
                'module' => array(
                    'js/search-form.js' => array( 'bookly-modern-booking-form.js' ),
                ),
            ) );

            wp_localize_script( 'bookly-search-form.js', 'BooklyL10nSearchForm', array() );
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

        $appearance = Appearance\ProxyProviders\Local::getAppearance( Lib\Entities\Form::TYPE_SEARCH_FORM, is_array( $attr ) ? current( $attr ) : null );
        if ( isset( $appearance['token'] ) ) {
            $form_id = uniqid( 'bookly-search-form-' . $appearance['token'] . '-', false );
        } else {
            $form_id = uniqid( 'bookly-search-form-', false );
        }

        Proxy\Shared::renderForm( $form_id );

        return self::renderTemplate( 'short_code', compact( 'ajaxurl', 'form_id', 'appearance' ), false );
    }
}