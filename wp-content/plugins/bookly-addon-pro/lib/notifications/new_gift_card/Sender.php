<?php
namespace BooklyPro\Lib\Notifications\NewGiftCard;

use Bookly\Lib\Entities\Customer;
use Bookly\Lib\Entities\Staff;
use Bookly\Lib\Notifications\Base;
use BooklyPro\Lib\Entities\GiftCard;
use BooklyPro\Lib\Notifications\Assets\NewGiftCard;

/**
 * Class Sender
 * @package BooklyPro\Lib\Notifications\NewGiftCard
 */
abstract class Sender extends Base\Sender
{
    /**
     * Send notification abount gift card.
     *
     * @param GiftCard $gift_card
     * @param array $queue
     */
    public static function send( GiftCard $gift_card, &$queue = array() )
    {
        $codes = new NewGiftCard\Client\Codes( $gift_card );
        $attachments = null;
        $notifications = static::getNotifications( 'new_gift_card' );
        $customer = Customer::find( $gift_card->getCustomerId() );
        if ( $customer ) {
            foreach ( $notifications['client'] as $notification ) {
                static::sendToClient( $customer, $notification, $codes, $attachments, $queue );
            }
        }

        if ( $notifications['staff'] ) {
            // Reply to customer.
            $reply_to = null;
            if ( get_option( 'bookly_email_reply_to_customers' ) ) {
                $reply_to = array( 'email' => $customer->getEmail(), 'name' => $customer->getFullName() );
            }

            $staff_list = Staff::query( 's' )
                ->leftJoin( 'GiftCardStaff', 'gcs', 'gcs.staff_id = s.id', 'BooklyPro\Lib\Entities' )
                ->where( 'gcs.gift_card_id', $gift_card->getId() )
                ->find();
            foreach ( $notifications['staff'] as $notification ) {
                foreach ( $staff_list as $staff ) {
                    static::sendToStaff( $staff, $notification, $codes, $attachments, $reply_to, $queue );
                }
                static::sendToAdmins( $notification, $codes, $attachments, $reply_to, $queue );
                static::sendToCustom( $notification, $codes, $attachments, $reply_to, $queue );
            }
        }
    }
}