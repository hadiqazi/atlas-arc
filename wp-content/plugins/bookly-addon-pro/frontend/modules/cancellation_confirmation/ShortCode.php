<?php
namespace BooklyPro\Frontend\Modules\CancellationConfirmation;

use Bookly\Lib as BooklyLib;
use BooklyPro\Lib;
use BooklyPro\Backend\Modules\Appearance;

/**
 * Class Controller
 *
 * @package BooklyPro\Frontend\Modules\CancellationConfirmation
 */
class ShortCode extends BooklyLib\Base\Component
{
    /**
     * Init component.
     */
    public static function init()
    {
        // Register short code.
        add_shortcode( 'bookly-cancellation-confirmation', array( __CLASS__, 'render' ) );

        // Assets.
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'linkStyles' ) );
    }

    /**
     * Link styles.
     */
    public static function linkStyles()
    {
        if (
            get_option( 'bookly_gen_link_assets_method' ) == 'enqueue' ||
            BooklyLib\Utils\Common::postsHaveShortCode( 'bookly-cancellation-confirmation' )
        ) {
            self::enqueueStyles( array(
                'alias' => array( 'bookly-backend-globals' ),
            ) );
        }
    }

    /**
     * Render shortcode.
     *
     * @param array $attributes
     * @return string
     */
    public static function render( $attributes )
    {
        // Disable caching.
        BooklyLib\Utils\Common::noCache();

        // Prepare URL for AJAX requests.
        $ajax_url = admin_url( 'admin-ajax.php' );

        $token = self::parameter( 'bookly-appointment-token', '' );

        $appearance = Appearance\ProxyProviders\Local::getAppearance( Lib\Entities\Form::TYPE_CANCELLATION_FORM, is_array( $attributes ) ? current( $attributes ) : null );

        return self::renderTemplate( 'short_code', compact( 'ajax_url', 'token', 'attributes', 'appearance' ), false );
    }
}