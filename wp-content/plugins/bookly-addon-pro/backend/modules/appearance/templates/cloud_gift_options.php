<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
use Bookly\Backend\Components\Editable\Elements;
?>
<div class="bookly-form-group" style="width:200px!important">
    <label>
        <?php Elements::renderString( array( 'bookly_l10n_label_cloud_gift', 'bookly_l10n_cloud_gift_error_not_found', 'bookly_l10n_cloud_gift_error_invalid', 'bookly_l10n_cloud_gift_error_expired', 'bookly_l10n_cloud_gift_error_low_balance' ) ) ?>
    </label>
    <div>
        <input type="text"/>
    </div>
</div>