<?php
namespace BooklyPro\Lib\Entities;

use Bookly\Lib as BooklyLib;

/**
 * Class GiftCardService
 * @package BooklyPro\Lib\Entities
 */
class GiftCardService extends BooklyLib\Base\Entity
{
    /** @var  int */
    protected $gift_card_id = 0;
    /** @var  int  */
    protected $service_id = 0;

    protected static $table = 'bookly_gift_card_services';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'gift_card_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'GiftCard', ) ),
        'service_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Service', 'namespace' => '\Bookly\Lib\Entities' ) ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets gift_card_id
     *
     * @return int
     */
    public function getGiftCardId()
    {
        return $this->gift_card_id;
    }

    /**
     * Sets gift_card_id
     *
     * @param int $gift_card_id
     * @return $this
     */
    public function setGiftCardId( $gift_card_id )
    {
        $this->gift_card_id = $gift_card_id;

        return $this;
    }

    /**
     * Gets service_id
     *
     * @return int
     */
    public function getServiceId()
    {
        return $this->service_id;
    }

    /**
     * Sets service_id
     *
     * @param int $service_id
     * @return $this
     */
    public function setServiceId( $service_id )
    {
        $this->service_id = $service_id;

        return $this;
    }

}
