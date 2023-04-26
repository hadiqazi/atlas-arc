<?php
namespace BooklyPro\Backend\Components\Dialogs\Payment;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Payment;

/**
 * Class Ajax
 *
 * @package BooklyPro\Backend\Modules\Payments
 */
class Ajax extends BooklyLib\Base\Ajax
{
    /**
     * @inheritDoc
     */
    protected static function permissions()
    {
        return array( '_default' => array( 'staff', 'supervisor' ) );
    }

    /**
     * Create payment adjustment.
     */
    public static function createPaymentAdjustment()
    {
        $payment_id = self::parameter( 'payment_id' );
        $adjustment = self::parameter( 'adjustment' );

        $payment = new Payment();
        $payment->load( $payment_id );

        if ( isset( $adjustment['amount'] ) && is_numeric( $adjustment['amount'] ) ) {
            $details = json_decode( $payment->getDetails(), true );

            $details['adjustments'][] = $adjustment;
            $payment
                ->setDetails( json_encode( $details ) )
                ->setTotal( $payment->getTotal() + $adjustment['amount'] )
                ->setTax( $payment->getTax() + $adjustment['tax'] )
                ->save();
        }

        wp_send_json_success();
    }

    /**
     * Update payment adjustment.
     */
    public static function updatePaymentAdjustment()
    {
        $payment_id = self::parameter( 'payment_id' );
        $index = self::parameter( 'index' );
        $adjustment = self::parameter( 'adjustment' );

        $payment = new Payment();
        $payment->load( $payment_id );

        if ( isset( $adjustment['amount'] ) && is_numeric( $adjustment['amount'] ) ) {
            $details = json_decode( $payment->getDetails(), true );
            if ( isset( $details['adjustments'][ $index ] ) ) {
                $total = $payment->getTotal() - $details['adjustments'][ $index ]['amount'];
                $tax = $payment->getTax() - $details['adjustments'][ $index ]['tax'];
                $details['adjustments'][ $index ] = $adjustment;
                $payment
                    ->setDetails( json_encode( $details ) )
                    ->setTotal( $total + $adjustment['amount'] )
                    ->setTax( $tax + $adjustment['tax'] )
                    ->save();
            }
        }

        wp_send_json_success();
    }

    /**
     * Delete payment adjustment.
     */
    public static function deletePaymentAdjustment()
    {
        $payment_id = self::parameter( 'payment_id' );
        $index = self::parameter( 'index' );

        $payment = new Payment();
        $payment->load( $payment_id );

        $details = json_decode( $payment->getDetails(), true );
        if ( isset( $details['adjustments'][ $index ] ) ) {
            $total = $payment->getTotal() - $details['adjustments'][ $index ]['amount'];
            $tax = $payment->getTax() - $details['adjustments'][ $index ]['tax'];
            array_splice( $details['adjustments'], $index, 1 );
            $payment
                ->setDetails( json_encode( $details ) )
                ->setTotal( $total )
                ->setTax( $tax )
                ->save();
        }

        wp_send_json_success();
    }
}