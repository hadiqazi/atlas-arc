<?php
namespace BooklyPro\Lib\Entities;

use Bookly\Lib as BooklyLib;

/**
 * Class GiftCardStaff
 * @package BooklyPro\Lib\Entities
 */
class GiftCardStaff extends BooklyLib\Base\Entity
{
    /** @var  int */
    protected $gift_card_id = 0;
    /** @var  int  */
    protected $staff_id = 0;

    protected static $table = 'bookly_gift_card_staff';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'gift_card_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'GiftCard', ) ),
        'staff_id' => array( 'format' => '%d', 'reference' => array( 'entity' => 'Staff', 'namespace' => '\Bookly\Lib\Entities' ) ),
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
     * Gets staff_id
     *
     * @return int
     */
    public function getStaffId()
    {
        return $this->staff_id;
    }

    /**
     * Sets staff_id
     *
     * @param int $staff_id
     * @return $this
     */
    public function setStaffId( $staff_id )
    {
        $this->staff_id = $staff_id;

        return $this;
    }

}
