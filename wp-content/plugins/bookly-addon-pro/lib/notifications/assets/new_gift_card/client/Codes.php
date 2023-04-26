<?php
namespace BooklyPro\Lib\Notifications\Assets\NewGiftCard\Client;

use Bookly\Lib\Entities\Customer;
use Bookly\Lib\Notifications\Assets\ClientBirthday;
use BooklyPro\Lib\Entities\GiftCard;
use Bookly\Lib\Utils;

/**
 * Class Codes
 * @package BooklyPro\Lib\Notifications\Assets\NewGiftCard\Client
 */
class Codes extends ClientBirthday\Codes
{
    /** @var GiftCard */
    protected $gift_card;

    /**
     * @param GiftCard $gift_card
     */
    public function __construct( GiftCard $gift_card )
    {
        $customer = Customer::find( $gift_card->getCustomerId() );
        parent::__construct( $customer ?: new Customer() );

        $this->gift_card = $gift_card;
    }

    /**
     * @inheritDoc
     */
    protected function getReplaceCodes( $format )
    {
        $replace_codes = parent::getReplaceCodes( $format );

        // Add replace codes.
        $replace_codes += array(
            'gift_card' => $this->gift_card->getCode(),
            'gift_card_amount' => $this->gift_card->getAmount(),
            'gift_card_date_limit_from' => $this->gift_card->getDateLimitStart() ? Utils\DateTime::formatDate( $this->gift_card->getDateLimitStart() ) : '',
            'gift_card_date_limit_to' => $this->gift_card->getDateLimitEnd() ? Utils\DateTime::formatDate( $this->gift_card->getDateLimitEnd() ) : '',
            'site_address' => site_url(),
        );

        return $replace_codes;
    }
}