<?php
namespace BooklyPro\Backend\Modules\CloudGiftCards;

use BooklyPro\Lib;
use Bookly\Lib as BooklyLib;

/**
 * Class Ajax
 * @package BooklyPro\Backend\Modules\CloudFiftCards
 */
class Ajax extends BooklyLib\Base\Ajax
{
     /**
     * Get gift cards list
     */
    public static function getGiftCards()
    {
        global $wpdb;

        $columns = self::parameter( 'columns' );
        $order = self::parameter( 'order', array() );
        $filter = self::parameter( 'filter' );

        $total = Lib\Entities\GiftCard::query()->count();

        $query = Lib\Entities\GiftCard::query( 'g' )
            ->leftJoin( 'GiftCardService', 'gs', 'gs.gift_card_id = g.id' )
            ->leftJoin( 'GiftCardStaff', 'gst', 'gst.gift_card_id = g.id' )
        ;

        // Filters.
        if ( $filter['code'] != '' ) {
            $query->whereLike( 'g.code', '%' . BooklyLib\Query::escape( $filter['code'] ) . '%' );
        }
        if ( $filter['service'] != '' ) {
            $query->where( 'gs.service_id', $filter['service'] );
        }
        if ( $filter['staff'] != '' ) {
            $query->where( 'gst.staff_id', $filter['staff'] );
        }
        if ( $filter['customer'] != '' ) {
            $query->where( 'g.customer_id', $filter['customer'] );
        }
        if ( $filter['active'] ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            $query->whereRaw(
                'g.balance > 0 AND (g.date_limit_start IS NULL OR %s >= g.date_limit_start) AND (g.date_limit_end IS NULL OR %s <= g.date_limit_end)',
                array( $today, $today )
            );
        }

        $ids = $query->fetchCol( 'DISTINCT g.id' );

        $query = Lib\Entities\GiftCard::query( 'g' )
            ->select( 'SQL_CALC_FOUND_ROWS g.*,
                gs.service_id,
                gst.staff_id,
                g.customer_id,
                c.full_name AS customer_full_name,
                COUNT(DISTINCT gs.service_id) AS services,
                COUNT(DISTINCT gst.staff_id) AS staff'
            )
            ->leftJoin( 'GiftCardService', 'gs', 'gs.gift_card_id = g.id' )
            ->leftJoin( 'GiftCardStaff', 'gst', 'gst.gift_card_id = g.id' )
            ->leftJoin( 'Customer', 'c', 'c.id = g.customer_id', '\Bookly\Lib\Entities' )
            ->whereIn( 'g.id', $ids )
            ->groupBy( 'g.id' );

        foreach ( $order as $sort_by ) {
            $query
                ->sortBy( str_replace( '.', '_', $columns[ $sort_by['column'] ]['data'] ) )
                ->order( $sort_by['dir'] == 'desc' ? BooklyLib\Query::ORDER_DESCENDING : BooklyLib\Query::ORDER_ASCENDING );
        }

        $cards = $query
            ->limit( self::parameter( 'length' ) )
            ->offset( self::parameter( 'start' ) )
            ->fetchArray();

        $filtered = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        foreach ( $cards as &$card ) {
            $card['date_limit_start_formatted'] = is_null( $card['date_limit_start'] ) ? '' : BooklyLib\Utils\DateTime::formatDate( $card['date_limit_start'] );
            $card['date_limit_end_formatted'] = is_null( $card['date_limit_end'] ) ? '' : BooklyLib\Utils\DateTime::formatDate( $card['date_limit_end'] );
        }

        BooklyLib\Utils\Tables::updateSettings( BooklyLib\Utils\Tables::GIFT_CARDS, $columns, $order, $filter );

        wp_send_json( array(
            'draw' => (int) self::parameter( 'draw' ),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $cards,
        ) );
    }

    /**
     * Create/update gift card
     */
    public static function saveGiftCard()
    {
        $request = self::getRequest();
        $queue = array();
        if ( $request->get( 'code' ) === null ) {
            $request->set( 'code', '' );
        }
        $duplicates_query = Lib\Entities\GiftCard::query()->where( 'code', $request->get( 'code' ) );
        if ( $request->get( 'id' ) ) {
            $duplicates_query->whereNot( 'id', $request->get( 'id' ) );
        }

        if ( $request->get( 'min_appointments' ) < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Min appointments should be greater than zero.', 'bookly' ) ) );
        } elseif ( $request->get( 'max_appointments', false ) && $request->get( 'max_appointments' ) < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Max appointments should be greater than zero.', 'bookly' ) ) );
        } elseif ( $duplicates_query->count() > 0 ) {
            wp_send_json_error( array( 'message' => __( 'The code already exists', 'bookly' ) ) );
        } else {
            if ( $request->get( 'create_series' ) ) {
                if ( $request->get( 'mask' ) == '' ) {
                    wp_send_json_error( array( 'message' => __( 'Please enter a non empty mask.', 'bookly' ) ) );
                }
                try {
                    $codes = Lib\CodeGenerator::generateUniqueCodeSeries( '\BooklyPro\Lib\Entities\GiftCard', $request->get( 'mask' ), $request->get( 'series_amount' ) );
                } catch ( \Exception $e ) {
                    wp_send_json_error( array( 'message' => sprintf(
                        __( 'It is not possible to generate %d codes for this mask. Only %d codes available.', 'bookly' ),
                        $request->get( 'series_amount' ),
                        $e->getMessage()
                    ) ) );
                }
            } else {
                $codes = array( $request->get( 'code' ) );
            }

            $data = $request->getAll();
            foreach ( $codes as $code ) {
                $data['code'] = $code;
                $gift_card = new Lib\Entities\GiftCard();
                $gift_card->setFields( $data )->save();
                // Services.
                $service_ids = $request->get( 'service_ids', array() );
                if ( empty ( $service_ids ) ) {
                    Lib\Entities\GiftCardService::query()
                        ->delete()
                        ->where( 'gift_card_id', $gift_card->getId() )
                        ->execute();
                } else {
                    Lib\Entities\GiftCardService::query()
                        ->delete()
                        ->where( 'gift_card_id', $gift_card->getId() )
                        ->whereNotIn( 'service_id', $service_ids )
                        ->execute();
                    $existing_services = Lib\Entities\GiftCardService::query()
                        ->select( 'service_id' )
                        ->where( 'gift_card_id', $gift_card->getId() )
                        ->indexBy( 'service_id' )
                        ->fetchArray();
                    foreach ( $service_ids as $service_id ) {
                        if ( ! isset ( $existing_services[ $service_id ] ) ) {
                            $gift_service = new Lib\Entities\GiftCardService();
                            $gift_service
                                ->setGiftCardId( $gift_card->getId() )
                                ->setServiceId( $service_id )
                                ->save();
                        }
                    }
                }
                // Staff.
                $staff_ids = $request->get( 'staff_ids', array() );
                if ( empty ( $staff_ids ) ) {
                    Lib\Entities\GiftCardStaff::query()
                        ->delete()
                        ->where( 'gift_card_id', $gift_card->getId() )
                        ->execute();
                } else {
                    Lib\Entities\GiftCardStaff::query()
                        ->delete()
                        ->where( 'gift_card_id', $gift_card->getId() )
                        ->whereNotIn( 'staff_id', $staff_ids )
                        ->execute();
                    $existing_staff = Lib\Entities\GiftCardStaff::query()
                        ->select( 'staff_id' )
                        ->where( 'gift_card_id', $gift_card->getId() )
                        ->indexBy( 'staff_id' )
                        ->fetchArray();
                    foreach ( $staff_ids as $staff_id ) {
                        if ( ! isset ( $existing_staff[ $staff_id ] ) ) {
                            $gift_staff = new Lib\Entities\GiftCardStaff();
                            $gift_staff
                                ->setGiftCardId( $gift_card->getId() )
                                ->setStaffId( $staff_id )
                                ->save();
                        }
                    }
                }
                if ( $request->get( 'send_notification', false ) ) {
                    Lib\Notifications\NewGiftCard\Sender::send( $gift_card, $queue );
                }
            }

            wp_send_json_success( compact( 'queue' ) );
        }
    }

    public static function getGiftCardsLists()
    {
        $id = self::parameter( 'gift_card_id' );
        $remote = self::parameter( 'remote' );
        $query = Lib\Entities\GiftCard::query( 'g' )
            ->select( 'gs.service_id, gst.staff_id, g.customer_id' )
            ->leftJoin( 'GiftCardService', 'gs', 'gs.gift_card_id = g.id' )
            ->leftJoin( 'GiftCardStaff', 'gst', 'gst.gift_card_id = g.id' )
            ->where( 'g.id', $id );
        if ( $remote ) {
            $query->addSelect( 'customer.full_name, customer.email, customer.phone' )
                ->leftJoin( 'Customer', 'customer', 'customer.id = g.customer_id', '\Bookly\Lib\Entities' );
        }
        $service_id = $staff_id = $customer_id = $customers = array();
        foreach ( $query->fetchArray() as $record ) {
            if ( $record['service_id'] ) {
                $service_id[] = $record['service_id'];
            }
            if ( $record['staff_id'] ) {
                $staff_id[] = $record['staff_id'];
            }
            if ( $record['customer_id'] ) {
                $customer_id[] = $record['customer_id'];
            }
            if ( $remote && ! isset ( $customers[ $record['customer_id'] ] ) ) {
                $name = $record['full_name'];
                if ( $record['email'] != '' || $record['phone'] != '' ) {
                    $name .= ' (' . trim( $record['email'] . ', ' . $record['phone'], ', ' ) . ')';
                }
                $customers[ $record['customer_id'] ] = array(
                    'id' => $record['customer_id'],
                    'text' => $name,
                );
            }
        }

        wp_send_json_success( array(
            'service_id' => array_values( array_unique( $service_id ) ),
            'staff_id' => array_values( array_unique( $staff_id ) ),
            'customer_id' => array_values( array_unique( $customer_id ) ),
            'customers' => array_values( $customers ),
        ) );
    }

    /**
     * Generate code.
     */
    public static function generateGiftCardCode()
    {
        $mask = self::parameter( 'mask' );

        if ( $mask == '' ) {
            $mask = get_option( 'bookly_cloud_gift_default_code_mask' );
        }
        if ( $mask == '' ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a non empty mask.', 'bookly' ) ) );
        }

        try {
            $code = Lib\CodeGenerator::generateUniqueCode( '\BooklyPro\Lib\Entities\GiftCard', $mask );
            wp_send_json_success( compact( 'code' ) );
        } catch ( \Exception $e ) {
            wp_send_json_error( array( 'message' => __( 'All possible codes have already been generated for this mask.', 'bookly' ) ) );
        }
    }

    /**
     * Delete gift cards.
     */
    public static function deleteGiftCards()
    {
        $gift_ids = array_map( 'intval', self::parameter( 'data', array() ) );
        Lib\Entities\GiftCard::query()->delete()->whereIn( 'id', $gift_ids )->execute();

        wp_send_json_success();
    }

    /**
     * Export gift cards
     */
    public static function exportGiftCards()
    {
        $delimiter = self::parameter( 'export_customers_delimiter', ',' );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=gift_cards.csv' );

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::GIFT_CARDS );

        $header = array();
        $column = array();
        foreach ( self::parameter( 'exp', array() ) as $key => $value ) {
            $header[] = $datatables[ BooklyLib\Utils\Tables::GIFT_CARDS ]['titles'][ $key ];
            $column[] = $key;
        }

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, pack( 'CCC', 0xef, 0xbb, 0xbf ) );
        fputcsv( $output, $header, $delimiter );

        $query = Lib\Entities\GiftCard::query( 'g' )
            ->select( 'g.id, g.code, g.amount, g.balance, g.date_limit_start, g.date_limit_end, g.min_appointments, g.max_appointments' )
            ->addSelect( 'GROUP_CONCAT(DISTINCT s.title) AS services,
                GROUP_CONCAT(DISTINCT st.full_name) AS staff,
                c.full_name AS customer_id' )
            ->leftJoin( 'GiftCardService', 'gs', 'gs.gift_card_id = g.id' )
            ->leftJoin( 'Service', 's', 's.id = gs.service_id', '\Bookly\Lib\Entities' )
            ->leftJoin( 'GiftCardStaff', 'gst', 'gst.gift_card_id = g.id' )
            ->leftJoin( 'Staff', 'st', 'st.id = gst.staff_id', '\Bookly\Lib\Entities' )
            ->leftJoin( 'Customer', 'c', 'c.id = g.customer_id', '\Bookly\Lib\Entities' )
            ->groupBy( 'g.id' )
        ;

        if ( self::parameter( 'active' ) ) {
            $today = BooklyLib\Slots\DatePoint::now()->format( 'Y-m-d' );
            $query->whereRaw(
                'g.balance > 0 AND (g.date_limit_start IS NULL OR %s >= g.date_limit_start) AND (g.date_limit_end IS NULL OR %s <= g.date_limit_end)',
                array( $today, $today )
            );
        }

        foreach ( $query->fetchArray() as $row ) {
            $row_data = array_fill( 0, count( $column ), '' );
            foreach ( $row as $key => $value ) {
                $pos = array_search( $key, $column );
                if ( $pos !== false ) {
                    if ( ( $key == 'customer_id' ) && $value === null ) {
                        $value = __( 'All', 'bookly' );
                    }
                    $row_data[ $pos ] = $value;
                }
            }

            fputcsv( $output, $row_data, $delimiter );
        }

        fclose( $output );

        exit;
    }
}