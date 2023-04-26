<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/** @var Bookly\Lib\CartInfo $cart_info */
use Bookly\Lib\Utils;
?>
<div class="bookly-box bookly-list">
    <label>
        <input type="radio" class="bookly-payment bookly-js-cloud_gift" name="payment-method-<?php echo $form_id ?>" value="cloud_gift" data-with-details="1"/>
        <span><?php echo Utils\Common::getTranslatedOption( 'bookly_l10n_label_pay_cloud_gift' ) ?>
            <?php if ( $show_price ) : ?>
                <span class="bookly-js-pay"><?php echo Utils\Price::format( $cart_info->getPayNow() ) ?></span>
            <?php endif ?>
        </span>
    </label>

    <div class="bookly-js-details" style="display: none; margin-top: 15px;">
        <div class="bookly-form-group" style="width:200px!important">
            <label><?php echo Utils\Common::getTranslatedOption( 'bookly_l10n_label_cloud_gift' ) ?></label>
            <div>
                <input type="text" name="gift_card" autocomplete="off" />
            </div>
        </div>
    </div>

    <div class="bookly-label-error bookly-js-cloud_gift-error"></div>
</div>