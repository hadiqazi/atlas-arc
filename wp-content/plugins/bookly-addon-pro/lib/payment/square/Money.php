<?php
namespace BooklyPro\Lib\Payment\Square;

use Bookly\Lib\Config;

/**
 * Class Money
 * @package BooklyPro\Lib\Payment\Square
 */
class Money
{
    /** @var float */
    protected $amount;

    /**
     * @param float $amount
     */
    public function __construct( $amount )
    {
        $this->amount = $amount;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'amount' => (int) ( Config::isZeroDecimalsCurrency()
                ? $this->amount
                : $this->amount * 100
            ),
            'currency' => Config::getCurrency()
        );
    }
}