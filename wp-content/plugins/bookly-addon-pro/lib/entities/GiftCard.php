<?php
namespace BooklyPro\Lib\Entities;

use Bookly\Lib as BooklyLib;

/**
 * Class GiftCard
 * @package BooklyPro\Lib\Entities
 */
class GiftCard extends BooklyLib\Base\Entity
{
    const VALIDATION_EXPIRED = 'expired';
    const VALIDATION_INVALID = 'invalid';
    const VALIDATION_LOW_BALANCE = 'low_balance';
    const VALIDATION_NOT_FOUND = 'not_found';

    /** @var string */
    protected $code = '';
    /** @var float */
    protected $amount = 0;
    /** @var float */
    protected $balance = 0;
    /** @var int */
    protected $customer_id;
    /** @var string */
    protected $date_limit_start;
    /** @var string */
    protected $date_limit_end;
    /** @var int */
    protected $min_appointments = 1;
    /** @var int */
    protected $max_appointments;

    protected static $table = 'bookly_gift_cards';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'code' => array( 'format' => '%s' ),
        'amount' => array( 'format' => '%f' ),
        'balance' => array( 'format' => '%f' ),
        'customer_id' => array( 'format' => '%d' ),
        'date_limit_start' => array( 'format' => '%s' ),
        'date_limit_end' => array( 'format' => '%s' ),
        'min_appointments' => array( 'format' => '%d' ),
        'max_appointments' => array( 'format' => '%d' ),
    );

    /**
     * @param float $amount
     * @return $this
     */
    public function charge( $amount )
    {
        $this->setBalance( $this->balance - $amount );

        return $this;
    }

    /**
     * Check if gift has started.
     *
     * @return bool
     */
    public function started()
    {
        if ( $this->date_limit_start ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            if ( $today < $this->date_limit_start ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if gift is expired.
     *
     * @return bool
     */
    public function expired()
    {
        if ( $this->date_limit_end ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            if ( $today > $this->date_limit_end ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if gift is valid for given customer.
     *
     * @param BooklyLib\Entities\Customer $customer
     * @return bool
     */
    public function validForCustomer( BooklyLib\Entities\Customer $customer )
    {
        return ! ( $this->customer_id > 0 ) || $customer->getId() == $this->customer_id;
    }

    /**
     * Check if gift is valid for given cart item.
     *
     * @param BooklyLib\CartItem $cart_item
     * @return bool
     */
    public function validForCartItem( BooklyLib\CartItem $cart_item )
    {
        $gs = new GiftCardService();
        if ( $gs->loadBy( array( 'gift_card_id' => $this->id, 'service_id' => $cart_item->getServiceId() ) ) ) {
            $gst = new GiftCardStaff();

            return $gst->loadBy( array( 'gift_card_id' => $this->id, 'staff_id' => $cart_item->getStaffId() ) );
        }

        return false;
    }

    /**
     * Check if gift is valid for given cart.
     *
     * @param BooklyLib\Cart $cart
     * @return bool
     */
    public function validForCart( BooklyLib\Cart $cart )
    {
        $valid = false;

        $services   = array();
        $cart_items = $cart->getItems();
        foreach ( $cart_items as $item ) {
            if ( $this->validForCartItem( $item ) ) {
                // Count each service in cart.
                $service_id = $item->getServiceId();
                if ( ! isset ( $services[ $service_id ] ) ) {
                    $services[ $service_id ] = 0;
                }
                ++ $services[ $service_id ];
            }
        }

        if ( ! empty ( $services ) ) {
            // Find min and max count.
            $min_count = PHP_INT_MAX;
            $max_count = 0;
            foreach ( $services as $count ) {
                if ( $count < $min_count ) {
                    $min_count = $count;
                }
                if ( $count > $max_count ) {
                    $max_count = $count;
                }
            }
            if ( $min_count >= $this->min_appointments ) {
                if ( $this->max_appointments === null || $max_count <= $this->max_appointments ) {
                    $valid = true;
                }
            }
        }

        return $valid;
    }

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets code
     *
     * @param string $code
     * @return $this
     */
    public function setCode( $code )
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return $this
     */
    public function setAmount( $amount )
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param float $balance
     * @return $this
     */
    public function setBalance( $balance )
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Gets date_limit_start
     *
     * @return string
     */
    public function getDateLimitStart()
    {
        return $this->date_limit_start;
    }

    /**
     * Sets date_limit_start
     *
     * @param string $date_limit_start
     * @return $this
     */
    public function setDateLimitStart( $date_limit_start )
    {
        $this->date_limit_start = $date_limit_start;

        return $this;
    }

    /**
     * Gets date_limit_end
     *
     * @return string
     */
    public function getDateLimitEnd()
    {
        return $this->date_limit_end;
    }

    /**
     * Sets date_limit_end
     *
     * @param string $date_limit_end
     * @return $this
     */
    public function setDateLimitEnd( $date_limit_end )
    {
        $this->date_limit_end = $date_limit_end;

        return $this;
    }

    /**
     * Gets min_appointments
     *
     * @return int
     */
    public function getMinAppointments()
    {
        return $this->min_appointments;
    }

    /**
     * Sets min_appointments
     *
     * @param int $min_appointments
     * @return $this
     */
    public function setMinAppointments( $min_appointments )
    {
        $this->min_appointments = $min_appointments;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     * @return GiftCard
     */
    public function setCustomerId( $customer_id )
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

    /**
     * @return false|int
     */
    public function save()
    {
        if ( ! $this->isLoaded() && $this->getBalance() == 0 ) {
            $this->setBalance( $this->getAmount() );
        }

        return parent::save();
    }
}