jQuery(function($) {

    let $codeFilter = $('#bookly-filter-code'),
        $serviceFilter = $('#bookly-filter-service'),
        $staffFilter = $('#bookly-filter-staff'),
        $customerFilter = $('#bookly-filter-customer'),
        $onlyActiveFilter = $('#bookly-filter-active'),
        $giftCardsList = $('#bookly-gift-cards-list'),
        $checkAllButton = $('#bookly-check-all'),
        $giftCardModal = $('#bookly-gift-card-modal'),
        $seriesNewTitle = $('#bookly-new-gift-card-series-title'),
        $giftNewTitle = $('#bookly-new-gift-card-title'),
        $giftEditTitle = $('#bookly-edit-gift-card-title'),
        $giftCode = $('#bookly-gift-card-code'),
        $generateCode = $('#bookly-generate-code'),
        $seriesMask = $('#bookly-gift-card-series-mask'),
        $seriesAmount = $('#bookly-gift-card-series-amount'),
        $giftCardAmount = $('#bookly-gift-card-amount', $giftCardModal),
        $giftCardAmountContainer = $giftCardAmount.closest('[class*=\'col-\']'),
        $giftCardBalance = $('#bookly-gift-card-balance', $giftCardModal),
        $giftCardBalanceContainer = $giftCardBalance.closest('[class*=\'col-\']'),
        $giftCardCustomer = $('#bookly-gift-card-cards-customer',$giftCardModal),
        $giftCardDateStart = $('#bookly-gift-card-date-limit-start'),
        $clearDateStart = $('#bookly-clear-date-limit-start'),
        $giftCardDateEnd = $('#bookly-gift-card-date-limit-end'),
        $clearDateEnd = $('#bookly-clear-date-limit-end'),
        $giftCardMinApps = $('#bookly-gift-card-min-appointments'),
        $giftCardMaxApps = $('#bookly-gift-card-max-appointments'),
        $giftCardServices = $('#bookly-js-gift-card-services'),
        $giftCardProviders = $('#bookly-js-gift-card-providers'),
        $saveButton = $('#bookly-gift-card-save', $giftCardModal),
        $addButton = $('#bookly-add'),
        $addSeriesButton = $('#bookly-add-series'),
        $deleteButton = $('#bookly-delete'),
        $createAnother = $('#bookly-create-another-gift'),
        $exportDialog = $('#bookly-export-gift-cards-dialog'),
        $exportSelectAll = $('#bookly-js-export-select-all', $exportDialog),
        columns = [],
        order = [],
        edit_and_duplicate =
            $('<div class="d-inline-flex">').append(
                $('<button type="button" class="btn btn-default mr-1" data-action="edit"></button>').append($('<i class="far fa-fw fa-edit mr-lg-1" />'), '<span class="d-none d-lg-inline">' + BooklyGiftCardsL10n.edit + '…</span>'),
                $('<button type="button" class="btn btn-default" data-action="edit" data-mode="duplicate"></button>').append($('<i class="far fa-fw fa-clone mr-lg-1" />'), '<span class="d-none d-lg-inline">' + BooklyGiftCardsL10n.duplicate + '…</span>')
            ).get(0).outerHTML,
        row,
        series,
        duplicate
    ;

    $('.bookly-js-select').val(null);
    $.each(BooklyGiftCardsL10n.datatables.gift_cards.settings.filter, function (field, value) {
        if (value != '') {
            let $elem = $('#bookly-filter-' + field);
            if ($elem.is(':checkbox')) {
                $elem.prop('checked', value == '1');
            } else {
                $elem.val(value);
            }
        }
        // check if select has correct values
        if ($('#bookly-filter-' + field).prop('type') == 'select-one') {
            if ($('#bookly-filter-' + field + ' option[value="' + value + '"]').length == 0) {
                $('#bookly-filter-' + field).val(null);
            }
        }
    });

    /**
     * Init filters.
     */
    $('.bookly-js-select').on('change', function() {
        dt.ajax.reload();
    })
        .booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: true,
            placeholder: '',
            language: {
                noResults: function() {
                    return BooklyGiftCardsL10n.noResultFound;
                },
                removeAllItems: function() {
                    return BooklyGiftCardsL10n.remove;
                }
            },
            matcher: function(params, data) {
                const term = $.trim(params.term).toLowerCase();
                if (term === '' || data.text.toLowerCase().indexOf(term) !== -1) {
                    return data;
                }

                let result = null;
                const search = $(data.element).data('search');
                search &&
                search.find(function(text) {
                    if (result === null && text.toLowerCase().indexOf(term) !== -1) {
                        result = data;
                    }
                });

                return result;
            }
        });

    $('.bookly-js-select-ajax')
        .val(null)
        .on('change', function() {
            dt.ajax.reload();
        })
        .booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: true,
            placeholder: '',
            language: {
                noResults: function() {
                    return BooklyGiftCardsL10n.noResultFound;
                },
                searching: function() {
                    return BooklyGiftCardsL10n.searching;
                }
            },
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    params.page = params.page || 1;
                    return {
                        action: this.action === undefined ? $(this).data('ajax--action') : this.action,
                        filter: params.term,
                        page: params.page,
                        csrf_token: BooklyL10nGlobal.csrf_token
                    };
                }
            },
        });
    $onlyActiveFilter.on('change', function() { dt.ajax.reload(); });
    $codeFilter.on('keyup', function() { dt.ajax.reload(); });

    /**
     * Init Columns.
     */
    $.each(BooklyGiftCardsL10n.datatables.gift_cards.settings.columns, function(column, show) {
        if (show) {
            switch (column) {
                case 'services':
                    columns.push({
                        data: column,
                        render: function (data, type, row, meta) {
                            if (data == 0) {
                                return BooklyGiftCardsL10n.services.nothingSelected;
                            } else if (data == 1) {
                                return $.fn.dataTable.render.text().display(BooklyGiftCardsL10n.services.collection[row.service_id].title);
                            } else {
                                if (data == BooklyGiftCardsL10n.services.count) {
                                    return BooklyGiftCardsL10n.services.allSelected;
                                } else {
                                    return data + '/' + BooklyGiftCardsL10n.services.count;
                                }
                            }
                        }
                    });
                    break;
                case 'staff':
                    columns.push({
                        data: column,
                        render: function(data, type, row, meta) {
                            if (data == 0) {
                                return BooklyGiftCardsL10n.staff.nothingSelected;
                            } else if (data == 1) {
                                if (typeof BooklyGiftCardsL10n.staff.collection[row.staff_id] === 'undefined') {
                                    return BooklyGiftCardsL10n.staff.nothingSelected;
                                } else {
                                    return $.fn.dataTable.render.text().display(BooklyGiftCardsL10n.staff.collection[row.staff_id].title);
                                }
                            } else {
                                if (data == BooklyGiftCardsL10n.staff.count) {
                                    return BooklyGiftCardsL10n.staff.allSelected;
                                } else {
                                    return data + '/' + BooklyGiftCardsL10n.staff.count;
                                }
                            }
                        }
                    });
                    break;
                case 'customer_id':
                    columns.push({
                        data: column,
                        render: function(data, type, row, meta) {
                            if (data > 0) {
                                return $.fn.dataTable.render.text().display(row.customer_full_name);
                            } else {
                                return BooklyGiftCardsL10n.customers.nothingSelected
                            }
                        }
                    });
                    break;
                case 'date_limit_end':
                    columns.push({
                        data: column,
                        render: function(data, type, row, meta) {
                            return row.date_limit_end_formatted;
                        }
                    });
                    break;
                case 'date_limit_start':
                    columns.push({
                        data: column,
                        render: function(data, type, row, meta) {
                            return row.date_limit_start_formatted;
                        }
                    });
                    break;
                default:
                    columns.push({data: column, render: $.fn.dataTable.render.text()});
                    break;
            }
        }
    });

    columns.push({
        data: null,
        responsivePriority: 1,
        orderable: false,
        width: 180,
        render: function(data, type, row, meta) {
            return edit_and_duplicate;
        }
    });

    columns.push({
        data: null,
        responsivePriority: 1,
        orderable: false,
        render: function(data, type, row, meta) {
            return '<div class="custom-control custom-checkbox">' +
                '<input value="' + row.id + '" id="bookly-dt-' + row.id + '" type="checkbox" class="custom-control-input">' +
                '<label for="bookly-dt-' + row.id + '" class="custom-control-label"></label>' +
                '</div>';
        }
    });

    columns[0].responsivePriority = 0;

    $.each(BooklyGiftCardsL10n.datatables.gift_cards.settings.order, function(_, value) {
        const index = columns.findIndex(function(c) { return c.data === value.column; });
        if (index !== -1) {
            order.push([index, value.order]);
        }
    });

    /**
     * Init DataTables.
     */
    var dt = $giftCardsList.DataTable({
        order: order,
        info: false,
        searching: false,
        lengthChange: false,
        pageLength: 25,
        pagingType: 'numbers',
        processing: true,
        responsive: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                return $.extend({action: 'bookly_pro_get_gift_cards', csrf_token: BooklyL10nGlobal.csrf_token}, {
                    filter: {
                        code: $codeFilter.val(),
                        service: $serviceFilter.val(),
                        staff: $staffFilter.val(),
                        customer: $customerFilter.val(),
                        active: $onlyActiveFilter.prop('checked') ? 1 : 0
                    }
                }, d);
            }
        },
        columns: columns,
        dom: "<'row'<'col-sm-12'tr>><'row float-left mt-3'<'col-sm-12'p>>",
        language: {
            zeroRecords: BooklyGiftCardsL10n.zeroRecords,
            processing: BooklyGiftCardsL10n.processing
        }
    });

    /**
     * Select all gifts.
     */
    $checkAllButton.on('change', function () {
        $giftCardsList.find('tbody input:checkbox').prop('checked', this.checked);
    });

    $giftCardsList
        // On gift select.
        .on('change', 'tbody input:checkbox', function() {
            $checkAllButton.prop('checked', $giftCardsList.find('tbody input:not(:checked)').length == 0);
        })
        // Edit gift
        .on('click', '[data-action=edit]', function() {
            row = dt.row($(this).closest('td'));
            series = false;
            duplicate = $(this).data('mode') === 'duplicate';
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bookly_pro_get_gift_cards_lists',
                    csrf_token: BooklyL10nGlobal.csrf_token,
                    gift_card_id: row.data().id,
                    remote: BooklyGiftCardsL10n.customers.remote?'1':'0'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $giftCardServices.booklyDropdown('setSelected', response.data.service_id);
                        $giftCardProviders.booklyDropdown('setSelected', response.data.staff_id);
                        if (BooklyGiftCardsL10n.customers.remote) {
                            $giftCardCustomer.html('');
                            response.data.customers.forEach(function(customer) {
                                $giftCardCustomer[0].appendChild(new Option(customer.text, customer.id));
                            });
                        }
                        $giftCardCustomer.val(response.data.customer_id).trigger('change');
                    }
                    $giftCardModal.booklyModal('show');
                }
            });
        });

    /**
     * New gift.
     */
    $addButton.on('click', function() {
        series = false;
        duplicate = false;
        $giftCardModal.booklyModal('show');
    });

    /**
     * New gift series.
     */
    $addSeriesButton.on('click', function() {
        series = true;
        duplicate = false;
        $giftCardModal.booklyModal('show');
    });

    /**
     * On show modal.
     */
    $giftCardModal
        .on('show.bs.modal', function() {
            if (row) {
                $giftCardAmountContainer.addClass('col-sm-6').removeClass('col-12');
                $giftCardBalanceContainer.show();
                let card_data = row.data();
                $giftCode.val(card_data.code);
                $giftCardAmount.val(card_data.amount);
                $giftCardBalance.val(card_data.balance);
                $giftCardDateStart.val(card_data.date_limit_start !== null ? moment(card_data.date_limit_start, 'YYYY-MM-DD').format(BooklyL10nGlobal.datePicker.format) : '');
                $giftCardDateStart.next('input:hidden').val(card_data.date_limit_start);
                $giftCardDateEnd.val(card_data.date_limit_end !== null ? moment(card_data.date_limit_end, 'YYYY-MM-DD').format(BooklyL10nGlobal.datePicker.format) : '');
                $giftCardDateEnd.next('input:hidden').val(card_data.date_limit_end);
                $giftCardMinApps.val(card_data.min_appointments);
                $giftCardMaxApps.val(card_data.max_appointments);
                $seriesNewTitle.hide();
                if (duplicate) {
                    $giftEditTitle.hide();
                    $giftNewTitle.show();
                } else {
                    $giftEditTitle.show();
                    $giftNewTitle.hide();
                }
                $giftCardCustomer.val(card_data.customer_id);
            } else {
                $giftCardAmountContainer.addClass('col-12').removeClass('col-sm-6');
                $giftCardBalanceContainer.hide();
                $giftCode.val('');
                $seriesMask.val(BooklyGiftCardsL10n.defaultCodeMask);
                $seriesAmount.val(1);
                $giftCardAmount.val('0');
                $giftCardBalance.val('0');
                $giftCardDateStart.val('');
                $giftCardDateEnd.val('');
                $giftCardMinApps.val('1');
                $giftCardMaxApps.val('');
                $giftEditTitle.hide();
                if (series) {
                    $giftNewTitle.hide();
                    $seriesNewTitle.show();
                } else {
                    $giftNewTitle.show();
                    $seriesNewTitle.hide();
                }
                $giftCardServices.booklyDropdown('selectAll');
                $giftCardProviders.booklyDropdown('selectAll');
                $giftCardCustomer.val(null);
            }
            $giftCardCustomer.trigger('change');
            $('.bookly-js-series-field').toggle(series);
            $('.bookly-js-gift-card-field').toggle(!series);
            $giftCode.trigger('change');
            $createAnother.prop('checked', false);
        })
        .on('hidden.bs.modal', function() {
            row = null;
            $('[name=date_limit_start]', $giftCardModal).val('');
            $('[name=date_limit_end]', $giftCardModal).val('');
        });

    /**
     * Code.
     */
    $giftCode.on('keyup change', function() {
        $generateCode.prop('disabled', $giftCode.val().length && $giftCode.val().indexOf('*') === -1);
    });

    /**
     * Generate code.
     */
    $generateCode.on('click', function() {
        let ladda = Ladda.create(this);
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bookly_pro_generate_gift_card_code',
                csrf_token: BooklyL10nGlobal.csrf_token,
                mask: $giftCode.val()
            },
            dataType: 'json',
            success: function (response) {
                ladda.stop();
                if (response.success) {
                    $giftCode.val(response.data.code);
                    $generateCode.prop('disabled', true);
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    /**
     * Date limit start.
     */
    $giftCardDateStart.daterangepicker({
        parentEl: '#bookly-gift-card-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyL10nGlobal.datePicker
    }, function(start) {
        $giftCardDateStart.val(start.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $giftCardDateStart.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $giftCardDateStart.on('apply.daterangepicker', function(ev, picker) {
        $giftCardDateStart.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $giftCardDateStart.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clearDateStart.on('click', function () {
        $giftCardDateStart.val('');
        $giftCardDateStart.next('input:hidden').val(null);
    });

    /**
     * Date limit end.
     */
    $giftCardDateEnd.daterangepicker({
        parentEl: '#bookly-gift-card-modal',
        singleDatePicker: true,
        showDropdowns: true,
        autoUpdateInput: false,
        locale: BooklyL10nGlobal.datePicker
    }, function (start) {
        $giftCardDateEnd.val(start.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $giftCardDateEnd.next('input:hidden').val(start.format('YYYY-MM-DD'))
    });
    $giftCardDateEnd.on('apply.daterangepicker', function(ev, picker) {
        $giftCardDateEnd.val(picker.startDate.format(BooklyL10nGlobal.datePicker.format)).trigger('change');
        $giftCardDateEnd.next('input:hidden').val(picker.startDate.format('YYYY-MM-DD'))
    });
    $clearDateEnd.on('click', function () {
        $giftCardDateEnd.val('');
        $giftCardDateEnd.next('input:hidden').val(null);
    });

    /**
     * Customers list.
     */
    if (BooklyGiftCardsL10n.customers.remote) {
        $giftCardCustomer.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: false,
            placeholder: '',
            language: {
                noResults: function() {
                    return BooklyGiftCardsL10n.noResultFound;
                },
                searching: function() {
                    return BooklyGiftCardsL10n.searching;
                }
            },
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    params.page = params.page || 1;
                    return {
                        action: 'bookly_get_customers_list',
                        filter: params.term,
                        page: params.page,
                        csrf_token: BooklyL10nGlobal.csrf_token
                    };
                },
                processResults: function(data, params) {
                    var customers = [];
                    params.page = params.page || 1;
                    data.results.forEach(function (customer) {
                        BooklyGiftCardsL10n.customers.collection[customer.id] = customer;
                        customers.push({
                            id: customer.id,
                            text: customer.name
                        });
                    });
                    return {
                        results: customers,
                        pagination: data.pagination
                    };
                }
            },
        });
    } else {
        $giftCardCustomer.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-tbs',
            allowClear: false,
            placeholder: '',
            language: {
                noResults: function() {
                    return BooklyGiftCardsL10n.noResultFound;
                }
            }
        });
    }

    /**
     * Services.
     */
    $giftCardServices.booklyDropdown();

    /**
     * Providers (staff).
     */
    $giftCardProviders.booklyDropdown();

    /**
     * Save gift.
     */
    $saveButton.on('click', function(e) {
        e.preventDefault();
        let data = booklySerialize.form($(this).parents('form')),
            ladda = Ladda.create(this);
        if (row && !duplicate) {
            data.id = row.data().id;
        }
        if (series) {
            data.create_series = 1;
        }
        if (data.customer_id == '') {
            data.customer_id = null;
        }
        data.send_notification = (data.customer_id != null && $('#bookly-send-notifications').is(':checked')) ? 1 : 0;
        ladda.start();
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: booklySerialize.buildRequestData('bookly_pro_save_gift_card', data),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    dt.ajax.reload();
                    if (!series && $createAnother.prop('checked')) {
                        row = null;
                        $giftNewTitle.show();
                        $giftEditTitle.hide();
                        $giftCode.val('');
                        $createAnother.prop('checked', false);
                    } else {
                        $giftCardModal.booklyModal('hide');
                    }
                    if (data.send_notification && response.data.queue.length) {
                        BooklyNotificationsQueueDialog.showDialog({queue: response.data.queue});
                    }
                } else {
                    alert(response.data.message);
                }
                ladda.stop();
            }
        });
    });

    /**
     * Delete gifts.
     */
    $deleteButton.on('click', function() {
        if (confirm(BooklyGiftCardsL10n.areYouSure)) {
            let ladda = Ladda.create(this),
                data = [],
                $checkboxes = $giftCardsList.find('tbody input:checked')
            ;
            ladda.start();
            $checkboxes.each(function() {
                data.push(this.value);
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bookly_pro_delete_gift_cards',
                    csrf_token: BooklyL10nGlobal.csrf_token,
                    data: data
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        dt.ajax.reload();
                    } else {
                        alert(response.data.message);
                    }
                    ladda.stop();
                }
            });
        }
    });

    $exportSelectAll
        .on('click', function() {
            let checked = this.checked;
            $('.bookly-js-columns input', $exportDialog).each(function() {
                $(this).prop('checked', checked);
            });
        });

    $('.bookly-js-columns input', $exportDialog)
        .on('change', function() {
            $exportSelectAll.prop('checked', $('.bookly-js-columns input:checked', $exportDialog).length == $('.bookly-js-columns input', $exportDialog).length);
        });

    if (BooklyGiftCardsL10n.customers.remote) {
        $giftCardCustomer.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-gift-card-modal .modal-content',
            allowClear: true,
            placeholder: '',
            language: {
                noResults: function() {
                    return BooklyGiftCardsL10n.noResultFound;
                },
                searching: function() {
                    return BooklyGiftCardsL10n.searching;
                }
            },
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    params.page = params.page || 1;
                    return {
                        action: 'bookly_get_customers_list',
                        filter: params.term,
                        page: params.page,
                        csrf_token: BooklyL10nGlobal.csrf_token
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results.map(function(item) {
                            return {id: item.id, text: item.name}
                        }),
                        pagination: data.pagination
                    };
                }
            },
        }).on('select2:select', function () {
            $(this).trigger('change');
        });
    } else {
        $giftCardCustomer.booklySelect2({
            width: '100%',
            theme: 'bootstrap4',
            dropdownParent: '#bookly-gift-card-modal .modal-content',
            allowClear: true,
            placeholder: '',
            language: {
                noResults: function() { return BooklyGiftCardsL10n.noResultFound; }
            }
        });
    }

    $giftCardCustomer.on('change', function() {
        this.value != ''
            ? $('.bookly-gift-card-cards-customer-expander').show()
            : $('.bookly-gift-card-cards-customer-expander').hide();
    }).trigger('change');
})