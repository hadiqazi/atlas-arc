<?php
namespace BooklyPro\Frontend\Modules\Square;

use Bookly\Lib as BooklyLib;

/**
 * Class Ajax
 * @package  Bookly\Frontend\Modules\Square
 */
class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => 'anonymous' );
    }

    /**
     * Processing Square webhooks
     */
    public static function squareWebhooks()
    {
        $data = json_decode( file_get_contents( 'php://input' ), true );
        $response_code = 200;
        if ( isset( $data['type'] ) && $data['type'] === 'order.updated' ) {
            try {
                Controller::processOrderData( $data['data']['id'] );
            } catch ( \Exception $e ) {
                $response_code = 400;
            }
        }
        if ( ! headers_sent() ) {
            header( 'Content-Type: text/html; charset=utf-8' );
            http_response_code( $response_code );
        }
        exit;
    }

    /**
     * @inheritDoc
     */
    protected static function csrfTokenValid( $action = null )
    {
        return true;
    }
}