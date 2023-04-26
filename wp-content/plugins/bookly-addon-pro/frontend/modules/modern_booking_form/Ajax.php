<?php

namespace BooklyPro\Frontend\Modules\ModernBookingForm;

use Bookly\Lib as BooklyLib;
use Bookly\Lib\Entities\Payment;
use BooklyPro\Frontend\Modules\ModernBookingForm\Lib\PaymentFlow;

/**
 * Class Ajax
 *
 * @package BooklyPro\Frontend\Modules\ModernBookingForm
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

    public static function modernBookingFormGetServices()
    {
        $list = array();
        $filters = self::parameter( 'filters' );
        $date = date_create( self::parameter( 'date' ) );
        if ( ! isset( $filters['locations'] ) ) {
            $filters['locations'] = array( 0 );
        }
        foreach ( $filters['services'] as $service_id ) {
            $service = BooklyLib\Entities\Service::find( $service_id );
            foreach ( $filters['staff'] as $staff_id ) {
                foreach ( $filters['locations'] as $location_id ) {
                    $chain_item = new BooklyLib\ChainItem();
                    $chain_item
                        ->setStaffIds( array( $staff_id ) )
                        ->setServiceId( $service_id )
                        ->setNumberOfPersons( $service->getCapacityMin() )
                        ->setQuantity( 1 )
                        ->setLocationId( $location_id )
                        ->setUnits( $service->getUnitsMin() )
                        ->setExtras( array() );

                    $chain = new BooklyLib\Chain();
                    $chain->add( $chain_item );

                    $scheduler = new BooklyLib\Scheduler( $chain, $date->format( 'Y-m-d 00:00' ), $date->format( 'Y-m-d' ), 'daily', array( 'every' => 1 ), array(), false );
                    $schedule = $scheduler->scheduleForFrontend( 1 );
                    if ( isset( $schedule[0]['options'] ) && count( $schedule[0]['options'] ) ) {
                        $list[] = compact( 'service_id', 'staff_id', 'location_id' );
                    }
                }
            }
        }
        wp_send_json_success( $list );
    }

    public static function modernBookingFormGetSlots()
    {
        $date = self::parameter( 'date' );
        $service_id = self::parameter( 'service_id' );
        $staff_id = self::parameter( 'staff_id' );
        $location_id = self::parameter( 'location_id' );
        $nop = self::parameter( 'nop' );
        $units = self::parameter( 'units', 1 );
        $extras = self::parameter( 'extras', array() );
        foreach ( array_keys( $extras, 0, false ) as $key ) {
            unset( $extras[ $key ] );
        }

        $chain_item = new BooklyLib\ChainItem();
        $chain_item
            ->setStaffIds( array( $staff_id ) )
            ->setServiceId( $service_id )
            ->setNumberOfPersons( $nop )
            ->setQuantity( 1 )
            ->setLocationId( $location_id )
            ->setUnits( $units )
            ->setExtras( $extras );

        $chain = new BooklyLib\Chain();
        $chain->add( $chain_item );

        $scheduler = new BooklyLib\Scheduler( $chain, date_create( $date )->format( 'Y-m-d 00:00' ), date_create( $date )->format( 'Y-m-d' ), 'daily', array( 'every' => 1 ), array(), BooklyLib\Config::waitingListActive() );
        $schedule = $scheduler->scheduleForFrontend( 1 );

        if ( isset( $schedule[0]['options'] ) ) {
            $service = BooklyLib\Entities\Service::find( $service_id );
            $staff_service = new BooklyLib\Entities\StaffService();
            $location_id = BooklyLib\Proxy\Locations::prepareStaffLocationId( $location_id, $staff_id ) ?: null;
            $staff_service->loadBy( compact( 'staff_id', 'service_id', 'location_id' ) );
            foreach ( $schedule[0]['options'] as &$option ) {
                $slot = json_decode( $option['value'], false );
                $date = strtotime( $slot[0][2] );
                $time = substr( $slot[0][2], 11 );
                $option['price'] = BooklyLib\Proxy\SpecialHours::adjustPrice( $service->isCompound() || $service->isCollaborative() ? $service->getPrice() : $staff_service->getPrice(), $staff_id, $service_id, $location_id, $time, $units, date( 'w', $date ) + 1 );
                $option['datetime'] = BooklyLib\Utils\DateTime::formatDateTime( $date );
            }
        }

        wp_send_json_success( $schedule );
    }

    public static function modernBookingFormSave()
    {
        $request = new Lib\Request();
        if ( $request->isValid() ) {
            try {
                wp_send_json_success( $request->processPayment() );
            } catch ( \Exception $e ) {
            }
        }

        wp_send_json_error( $request->getError() );
    }

    /**
     * Check payment system status when customer closed window at the time of payment processing
     *
     * @return void
     */
    public static function retrievePaymentStatus()
    {
        $status = Lib\PaymentFlow::STATUS_PROCESSING;
        $payment = new Payment();
        if ( $payment->loadBy( array( 'token' => self::parameter( 'payment' ) ) ) ) {
            if ( $payment->getStatus() === Payment::STATUS_PENDING ) {
                try {
                    $status = Lib\PaymentFlow::retrieveStatus( $payment ) ?: $status;
                } catch ( \Exception $e ) {
                    Lib\PaymentFlow::removeCascade( $payment );
                    wp_send_json_error();
                }
                if ( $status === Lib\PaymentFlow::STATUS_COMPLETED ) {
                    Lib\PaymentFlow::setCompleted( $payment );
                }
            }
        }

        wp_send_json_success( array( 'status' => $status, 'data' => Lib\PaymentFlow::getBookingResultFromPayment( $status, $payment ) ) );
    }

    /**
     * Endpoint for payment systems window.
     *
     * @return void
     */
    public static function checkoutResponse()
    {
        $status = PaymentFlow::STATUS_FAILED;
        $payment = new Payment();
        if ( $payment->loadBy( array( 'token' => self::parameter( 'payment', '' ) ) ) ) {
            switch ( $payment->getStatus() ) {
                case Payment::STATUS_PENDING:
                    try {
                        $status = Lib\PaymentFlow::retrieveStatus( $payment ) ?: $status;
                        if ( $status === Lib\PaymentFlow::STATUS_COMPLETED ) {
                            Lib\PaymentFlow::setCompleted( $payment );
                        }
                    } catch ( \Exception $e ) {
                        Lib\PaymentFlow::removeCascade( $payment );
                    }
                    break;
                case Payment::STATUS_COMPLETED:
                    $status = Lib\PaymentFlow::STATUS_COMPLETED;
                    break;
            }
        }

        print '<script>window.opener.BooklyModernBookingForm.setBookingResult( \'' . $status . '\', ' . json_encode( Lib\PaymentFlow::getBookingResultFromPayment( $status, $payment ) ) . ' );</script>';
        exit;
    }

    /**
     * Get staff schedule for modern form calendar
     *
     * @return void
     */
    public static function modernBookingFormGetCalendarSchedule()
    {
        $holidays = array();
        $filters = self::parameter( 'filters' );
        $staff_ids = isset( $filters['staff'] ) ? $filters['staff'] : array();

        if ( $staff_ids ) {
            $month = self::parameter( 'month' );
            $year = self::parameter( 'year' );

            $start_date = date_create( $year . '-' . $month . '-1' )->modify( '-7 days' );
            $end_date = date_create( $year . '-' . $month . '-1' )->modify( '1 month 7 days' );

            // Holidays.
            $holidays = BooklyLib\Entities\Holiday::query( 'h' )
                ->select( 'DISTINCT(DATE_FORMAT(h.date, "%%m-%%d")) AS short_date' )
                ->whereIn( 'h.staff_id', $staff_ids )
                ->whereRaw( 'h.repeat_event = 1 OR (h.date >= %s AND h.date <= %s)', array( $start_date->format( 'Y-m-d' ), $end_date->format( 'Y-m-d' ) ) )
                ->groupBy( 'short_date' )
                ->havingRaw( 'COUNT(id) >= %d', array( count( $staff_ids ) ) )
                ->fetchArray();

            $holidays = array_map( function ( $h ) { return $h['short_date']; }, $holidays );

            $staff_timezones = array();
            foreach ( BooklyLib\Entities\Staff::query( 'st' )->select( 'id, time_zone' )->whereIn( 'id', $staff_ids )->fetchArray() as $staff ) {
                $staff_timezones[ $staff['id'] ] = $staff['time_zone'];
            }
            // Calculate weekly schedule
            $weekly_schedule = array();
            $res = BooklyLib\Entities\StaffScheduleItem::query()
                ->select( 'r.day_index, r.start_time, r.end_time, r.staff_id' )
                ->whereIn( 'r.staff_id', $staff_ids )
                ->whereNot( 'r.start_time', null )
                ->groupBy( 'r.staff_id' )
                ->groupBy( 'day_index' )
                ->fetchArray();

            foreach ( $res as $row ) {
                $weekly_schedule[ $row['day_index'] ]['start_time'] = $row['start_time'];
                $weekly_schedule[ $row['day_index'] ]['end_time'] = $row['end_time'];
                $weekly_schedule[ $row['day_index'] ]['time_zone'] = isset( $staff_timezones[ $row['staff_id'] ] ) ? $staff_timezones[ $row['staff_id'] ] : null;
            }

            $special_days = array();
            foreach ( BooklyLib\Proxy\SpecialDays::getSchedule( $staff_ids, $start_date, $end_date ) ?: array() as $special_day ) {
                $special_days[ $special_day['date'] ] = array( 'start_time' => $special_day['start_time'], 'end_time' => $special_day['end_time'], 'time_zone' => isset( $staff_timezones[ $row['staff_id'] ] ) ? $staff_timezones[ $row['staff_id'] ] : null );
            }

            $current_date = clone( $start_date );
            $working_days = array();
            do {
                $month_day = $current_date->format( 'm-d' );
                if ( ! in_array( $month_day, $holidays, true ) ) {
                    $weekday = 1 + (int) $current_date->format( 'w' );
                    $day = $current_date->format( 'Y-m-d' );
                    if ( isset( $weekly_schedule[ $weekday ] ) ) {
                        $working_days = call_user_func_array( 'array_merge', array( $working_days, self::_prepareDates( $current_date, $weekly_schedule[ $weekday ]['start_time'], $weekly_schedule[ $weekday ]['end_time'], $weekly_schedule[ $weekday ]['time_zone'] ) ) );
                    }
                    if ( isset( $special_days[ $day ] ) ) {
                        $working_days = call_user_func_array( 'array_merge', array( $working_days, self::_prepareDates( $current_date, $special_days[ $day ]['start_time'], $special_days[ $day ]['end_time'], $special_days[ $day ]['time_zone'] ) ) );
                    }
                }
                $current_date->modify( '1 day' );
            } while ( $current_date < $end_date );

            $current_date = clone( $start_date );
            do {
                $formatted_date = $current_date->format( 'Y-m-d' );
                if ( ! in_array( $formatted_date, $working_days, true ) ) {
                    $holidays[] = $formatted_date;
                }
                $current_date->modify( '1 day' );
            } while ( $current_date < $end_date );
        }

        wp_send_json_success( $holidays );
    }

    /**
     * @param \DateTime $date
     * @param string $start_time
     * @param string $end_time
     * @param string|null $timezone
     * @return array
     */
    protected static function _prepareDates( $date, $start_time, $end_time, $timezone )
    {
        $start = BooklyLib\Slots\TimePoint::fromStr( $start_time );
        $end = BooklyLib\Slots\TimePoint::fromStr( $end_time );
        $wp_tz_offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

        if ( $timezone ) {
            $staff_tz_offset = BooklyLib\Utils\DateTime::timeZoneOffset( $timezone );
            $start = $start->toTz( $staff_tz_offset, $wp_tz_offset );
            $end = $end->toTz( $staff_tz_offset, $wp_tz_offset );
        }

        // Convert to client time zone.
        $start = $start->toClientTz();
        $end = $end->toClientTz();

        $result = array();
        if ( $start->value() < 0 ) {
            $clone_date = clone( $date );
            $result[] = $clone_date->modify( '-1 day' )->format( 'Y-m-d' );
        }
        if ( $start->value() < HOUR_IN_SECONDS * 24 && $end->value() > 0 ) {
            $result[] = $date->format( 'Y-m-d' );
        }
        if ( $end->value() > HOUR_IN_SECONDS * 24 ) {
            $clone_date = clone( $date );
            $result[] = $clone_date->modify( '1 day' )->format( 'Y-m-d' );
        }

        return $result;
    }

    /**
     * Override parent method to exclude actions from CSRF token verification.
     *
     * @param string $action
     * @return bool
     */
    protected static function csrfTokenValid( $action = null )
    {
        return $action === 'checkoutResponse' || parent::csrfTokenValid( $action );
    }
}