<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Settings\Selects;
use Bookly\Backend\Components\Controls\Elements;
use BooklyPro\Lib;
?>
<div class="card bookly-collapse-with-arrow" data-slug="cloud_gift">
    <div class="card-header d-flex align-items-center">
        <?php Elements::renderReorder() ?>
        <a href="#bookly_pmt_gift_cards" class="ml-2" role="button" data-toggle="bookly-collapse">
            <?php esc_html_e( 'Gift Cards', 'bookly' ) ?>
        </a>
        <img class="ml-auto" height="24" src="<?php echo plugins_url( 'backend/modules/settings/resources/images/gift-cards.svg', Lib\Plugin::getMainFile() ) ?>"/>
    </div>
    <div id="bookly_pmt_gift_cards" class="bookly-collapse bookly-show">
        <div class="card-body">
            <?php Selects::renderSingle( 'bookly_cloud_gift_enabled' ) ?>
        </div>
    </div>
</div>