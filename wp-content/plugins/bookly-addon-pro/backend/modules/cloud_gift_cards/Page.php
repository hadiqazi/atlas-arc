<?php
namespace BooklyPro\Backend\Modules\CloudGiftCards;

use Bookly\Lib as BooklyLib;

/**
 * Class Page
 * @package BooklyPro\Backend\Modules\CloudGiftCards
 */
class Page extends BooklyLib\Base\Component
{
    /**
     * Render page.
     */
    public static function render()
    {
        self::enqueueStyles( array(
            'alias' => array( 'bookly-backend-globals' ),
        ) );

        self::enqueueScripts( array(
            'module' => array( 'js/gift-cards.js' => array( 'bookly-backend-globals' ) ),
        ) );

        $services = BooklyLib\Entities\Service::query()
            ->select( 'id, title' )
            ->indexBy( 'id' )
            ->fetchArray();
        $staff_members = BooklyLib\Entities\Staff::query()
            ->select( 'id, full_name AS title' )
            ->indexBy( 'id' )
            ->whereNot( 'visibility', 'archive' )
            ->fetchArray();
        $customers_count = BooklyLib\Entities\Customer::query()->count();
        if ( $customers_count < BooklyLib\Entities\Customer::REMOTE_LIMIT ) {
            $remote = false;
            $customers = BooklyLib\Entities\Customer::query()
                ->select( 'id, full_name, phone, email' )->sortBy( 'full_name' )->indexBy( 'id' )->fetchArray();
        } else {
            $customers = array();
            $remote = true;
        }

        $datatables = BooklyLib\Utils\Tables::getSettings( BooklyLib\Utils\Tables::GIFT_CARDS );

        wp_localize_script( 'bookly-gift-cards.js', 'BooklyGiftCardsL10n', array(
            'edit' => __( 'Edit', 'bookly' ),
            'duplicate' => __( 'Duplicate', 'bookly' ),
            'zeroRecords' => __( 'No gift cards found.', 'bookly' ),
            'processing' => __( 'Processing...', 'bookly' ),
            'areYouSure' => __( 'Are you sure?', 'bookly' ),
            'noResultFound' => __( 'No result found', 'bookly' ),
            'searching' => __( 'Searching', 'bookly' ),
            'remove' => __( 'Remove', 'bookly' ),
            'services' => array(
                'allSelected' => __( 'All services', 'bookly' ),
                'nothingSelected' => __( 'No service selected', 'bookly' ),
                'collection' => $services,
                'count' => count( $services ),
            ),
            'staff' => array(
                'allSelected' => __( 'All staff', 'bookly' ),
                'nothingSelected' => __( 'No staff selected', 'bookly' ),
                'collection' => $staff_members,
                'count' => count( $staff_members ),
            ),
            'customers' => array(
                'nothingSelected' => __( 'No limit', 'bookly' ),
                'collection' => $customers,
                'count' => $customers_count,
                'remote' => $remote,
            ),
            'defaultCodeMask' => get_option( 'bookly_cloud_gift_default_code_mask' ),
            'datatables' => $datatables,
        ) );

        $dropdown_data = array(
            'service' => BooklyLib\Utils\Common::getServiceDataForDropDown( 's.type <> "package"' ),
            'staff' => BooklyLib\Proxy\Pro::getStaffDataForDropDown(),
        );

        $datatable = $datatables[ BooklyLib\Utils\Tables::GIFT_CARDS ];
        self::renderTemplate( 'index', compact( 'services', 'staff_members', 'customers', 'remote', 'dropdown_data', 'datatable' ) );
    }


    /**
     * Show 'Gift Cards' submenu inside Bookly Cloud main menu.
     *
     * @param array $product
     */
    public static function addBooklyCloudMenuItem( $product )
    {
        $title = $product['texts']['title'];

        add_submenu_page(
            'bookly-cloud-menu',
            $title,
            $title,
            BooklyLib\Utils\Common::getRequiredCapability(),
            self::pageSlug(),
            function () {
                Page::render();
            }
        );
    }
}