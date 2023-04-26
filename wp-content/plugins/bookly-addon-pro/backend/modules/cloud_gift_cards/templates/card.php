<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
use Bookly\Backend\Components\Controls\Buttons;
use Bookly\Backend\Components\Controls\Inputs;
?>
<style type="text/css">
    #bookly-gift-card-modal input::-moz-placeholder {
        font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
    }
</style>
<div class="bookly-modal bookly-fade" id="bookly-gift-card-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h5 class="modal-title" id="bookly-new-gift-card-series-title"><?php esc_html_e( 'New gift cards series', 'bookly' ) ?></h5>
                    <h5 class="modal-title" id="bookly-new-gift-card-title"><?php esc_html_e( 'New gift card', 'bookly' ) ?></h5>
                    <h5 class="modal-title" id="bookly-edit-gift-card-title"><?php esc_html_e( 'Edit gift card', 'bookly' ) ?></h5>
                    <button type="button" class="close" data-dismiss="bookly-modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="col-sm-12 bookly-js-gift-card-field">
                            <div class=form-group>
                                <label for="bookly-gift-card-code"><?php esc_html_e( 'Code', 'bookly' ) ?></label>
                                <div class="input-group">
                                    <input type="text" id="bookly-gift-card-code" class="form-control" name="code" autocomplete="off" />
                                    <span class="input-group-append">
                                        <button class="btn btn-default ladda-button" type="button" id="bookly-generate-code" data-style="zoom-in" data-spinner-size="30" data-spinner-color="#333"><span class="ladda-label"><?php esc_html_e( 'Generate', 'bookly' ) ?></span></button>
                                    </span>
                                </div>
                                <small class="form-text text-muted"><?php esc_html_e( 'You can enter a mask containing asterisks "*" for variables here and click Generate.', 'bookly' ) ?></small>
                            </div>
                        </div>
                        <div class="col-sm-9 bookly-js-series-field">
                            <div class=form-group>
                                <label for="bookly-gift-card-series-mask"><?php esc_html_e( 'Mask', 'bookly' ) ?></label>
                                <input type="text" id="bookly-gift-card-series-mask" class="form-control" name="mask" autocomplete="off" />
                                <small class="form-text text-muted"><?php esc_html_e( 'Enter a mask containing asterisks "*" for variables.', 'bookly' ) ?></small>
                            </div>
                        </div>
                        <div class="col-sm-3 bookly-js-series-field">
                            <div class=form-group>
                                <label for="bookly-gift-card-series-amount"><?php esc_html_e( 'Quantity', 'bookly' ) ?></label>
                                <input type="number" id="bookly-gift-card-series-amount" class="form-control" name="series_amount" min="1" />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <label for="bookly-gift-card-amount"><?php esc_html_e( 'Amount', 'bookly' ) ?></label>
                                <input type="number" id="bookly-gift-card-amount" class="form-control" name="amount" min="0"/>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <label for="bookly-gift-card-balance"><?php esc_html_e( 'Balance', 'bookly' ) ?></label>
                                <input type="number" id="bookly-gift-card-balance" class="form-control" name="balance" min="0"/>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <div><label><?php esc_html_e( 'Services', 'bookly' ) ?></label></div>
                                <ul id="bookly-js-gift-card-services"
                                    data-icon-class="far fa-dot-circle"
                                    data-txt-select-all="<?php esc_attr_e( 'All services', 'bookly' ) ?>"
                                    data-txt-all-selected="<?php esc_attr_e( 'All services', 'bookly' ) ?>"
                                    data-txt-nothing-selected="<?php esc_attr_e( 'No service selected', 'bookly' ) ?>"
                                >
                                    <?php foreach ( $dropdown_data['service'] as $category_id => $category ): ?>
                                        <li<?php if ( ! $category_id ) : ?> data-flatten-if-single<?php endif ?>><?php echo esc_html( $category['name'] ) ?>
                                            <ul>
                                                <?php foreach ( $category['items'] as $service ): ?>
                                                    <li data-input-name="service_ids[]" data-value="<?php echo $service['id'] ?>">
                                                        <?php echo esc_html( $service['title'] ) ?>
                                                    </li>
                                                <?php endforeach ?>
                                            </ul>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <div><label><?php esc_html_e( 'Providers', 'bookly' ) ?></label></div>
                                <ul id="bookly-js-gift-card-providers"
                                    data-txt-select-all="<?php esc_attr_e( 'All staff', 'bookly' ) ?>"
                                    data-txt-all-selected="<?php esc_attr_e( 'All staff', 'bookly' ) ?>"
                                    data-txt-nothing-selected="<?php esc_attr_e( 'No staff selected', 'bookly' ) ?>"
                                >
                                    <?php foreach ( $dropdown_data['staff'] as $category_id => $category ): ?>
                                        <li<?php if ( ! $category_id ) : ?> data-flatten-if-single<?php endif ?>><?php echo esc_html( $category['name'] ) ?>
                                            <ul>
                                                <?php foreach ( $category['items'] as $staff ): ?>
                                                    <li data-input-name="staff_ids[]" data-value="<?php echo $staff['id'] ?>">
                                                        <?php echo esc_html( $staff['full_name'] ) ?>
                                                    </li>
                                                <?php endforeach ?>
                                            </ul>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <label for="bookly-gift-card-date-limit-start"><?php esc_html_e( 'Date limit (from and to)', 'bookly' ) ?></label>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <div class="input-group">
                                    <input type="text" id="bookly-gift-card-date-limit-start" class="form-control" autocomplete="off" placeholder="<?php esc_attr_e( 'No limit', 'bookly' ) ?>" />
                                    <input type="hidden" name="date_limit_start" />
                                    <span class="input-group-append">
                                        <button class="btn btn-default" type="button" id="bookly-clear-date-limit-start" title="<?php esc_attr_e( 'Clear field', 'bookly' ) ?>">&times;</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class=form-group>
                                <div class="input-group">
                                    <input type="text" id="bookly-gift-card-date-limit-end" class="form-control" autocomplete="off" placeholder="<?php esc_attr_e( 'No limit', 'bookly' ) ?>" />
                                    <input type="hidden" name="date_limit_end" />
                                    <span class="input-group-append">
                                        <button class="btn btn-default" type="button" id="bookly-clear-date-limit-end" title="<?php esc_attr_e( 'Clear field', 'bookly' ) ?>">&times;</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <label for="bookly-gift-card-min-appointments"><?php esc_html_e( 'Limit appointments in cart (min and max)', 'bookly' ) ?></label>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-sm-0">
                                <input type="number" id="bookly-gift-card-min-appointments" class="form-control" name="min_appointments" min="1" />
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-0">
                                <input type="number" id="bookly-gift-card-max-appointments" class="form-control" name="max_appointments" min="1" placeholder="<?php esc_attr_e( 'No limit', 'bookly' ) ?>" />
                            </div>
                        </div>
                        <div class="col-sm-12 mb-3">
                            <small class="form-text text-muted"><?php esc_html_e( 'Specify minimum and maximum (optional) number of services of the same type required to apply a gift card.', 'bookly' ) ?></small>
                        </div>
                        <div class="col-sm-12">
                            <div class=form-group>
                                <label for="bookly-gift-card-customer"><?php esc_html_e( 'Customer', 'bookly' ) ?></label>
                                <ul id="bookly-customers-list" class="bookly-hide-empty list-unstyled p-0"></ul>
                                <select id="bookly-gift-card-cards-customer"  data-placeholder="<?php esc_attr_e( 'No limit', 'bookly' ) ?>" class="form-control custom-select" name="customer_id">
                                    <?php foreach ( $customers as $customer_id => $customer ): ?>
                                        <option value=""></option>
                                        <option value="<?php echo $customer_id ?>">
                                            <?php echo esc_html( $customer['full_name'] ) ?>
                                            <?php if ( $customer['email'] != '' || $customer['phone'] != '' ) : ?>
                                                (<?php echo esc_html( trim( $customer['email'] . ', ' . $customer['phone'], ', ' ) ) ?>)
                                            <?php endif ?>
                                            <?php ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="border-left ml-4 pl-3 bookly-gift-card-cards-customer-expander">
                                <?php Inputs::renderCheckBox( __( 'Send notifications', 'bookly' ), null, null, array( 'id' => 'bookly-send-notifications' ) ) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex">
                    <div class="bookly-js-gift-card-field mr-auto">
                        <?php Inputs::renderCheckBox( __( 'Create another gift card', 'bookly' ), null, null, array( 'id' => 'bookly-create-another-gift' ) ) ?>
                    </div>
                    <?php Buttons::render( 'bookly-gift-card-save', 'btn-success', __( 'Save', 'bookly' ) ) ?>
                    <?php Buttons::renderCancel() ?>
                </div>
            </form>
        </div>
    </div>
</div>