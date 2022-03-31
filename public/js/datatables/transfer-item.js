!function($) {
    "use strict";

    var transfer_item = function() {
        this.$body = $("body");
    };

    var track_page = 1; 
    var t, n, itemValidation = 0;
    var o2, n2, r2, t2, detailValidation = 0;;
    var t3, n3, z3, postingValidation = 0;

    transfer_item.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#items-transfer-items #keywords').val() + '&perPage=' + $('#items-transfer-items #perPage').val();
        var urls = base_url + 'auth/items/transfer-items/all-active';
        var me = $(this);
        var $portlet = $('#datatable-result');

        if ( me.data('requestRunning') ) {
            return;
        }
        
        console.log(urls + '' + keywords);
        $.ajax({
            type: 'GET',
            url:  urls + '' + keywords,
            data: {'page': track_page},
            success: function (data) {
                if(data.trim().length == 0)
                {                    
                    return;
                }
                $portlet.html(data);
            },
            complete: function() {
                me.data('requestRunning', false);
            }
        });
    },

    transfer_item.prototype.load_transfer_item_contents = function(track_page, transfer_itemID) 
    {   
        var keywords = '?id='+ transfer_itemID + '&keywords=' + $('#transferItemModal .keywords').val() + '&perPage=7';
        var urls = base_url + 'auth/items/transfer-items/all-active-lines';
        var me = $(this);
        var $portlet = $('#datatable-lines-result');

        if ( me.data('requestRunning') ) {
            return;
        }
        
        console.log(urls + '' + keywords);
        $.ajax({
            type: 'GET',
            url:  urls + '' + keywords,
            data: {'page': track_page},
            success: function (data) {
                if(data.trim().length == 0)
                {                    
                    return;
                }
                $portlet.html(data);
            },
            complete: function() {
                me.data('requestRunning', false);
            }
        });
    },

    transfer_item.prototype.truncate = function(num) {
        var with2Decimals = num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0]
        return with2Decimals
    },

    transfer_item.prototype.compute = function(form) {
        var qty = form.find('#qty');
        var total_amount = form.find('#total_amountx');
        var srp = form.find('#srp').val();
        var totalAmt = 0;
        if (qty.val() != '') {
            totalAmt = parseFloat(srp) * parseFloat(qty.val());
        } else {
            totalAmt = 0;
        }
        total_amount.val($.transfer_item.truncate(totalAmt));
    },

    transfer_item.prototype.posting_validation = function()
    {   
        z3  = document.querySelector("#transferItemPostingForm");
        t3 = document.querySelector("#transferItemPostingModalSubmit");
        n3 = FormValidation.formValidation(z3, {
            fields: {
                qty_to_post: { 
                    validators: { 
                        notEmpty: { 
                            message: "quantity is required" 
                        }
                    } 
                },
                date_recieved: { validators: { notEmpty: { message: "date received is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
    },

    transfer_item.prototype.transfer_item_detail_validation = function()
    {
        t = document.querySelector("#transferItemLineForm .add-item-btn");
        n = FormValidation.formValidation(document.querySelector("#transferItemLineForm"), {
            fields: {
                item_id: { validators: { notEmpty: { message: "Item is required" } } },
                uom_id: { validators: { notEmpty: { message: "UOM is required" } } },
                qty: { validators: { notEmpty: { message: "Quantity is required" } } }
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
    },

    transfer_item.prototype.transfer_detail_validation = function()
    {
        o2 = document.querySelector("#prev-next-btn-holder");
        t2 = o2.querySelector("#next-btn");
        r2 = document.querySelector("#transferItemForm");
        n2 = FormValidation.formValidation(document.querySelector("#transferItemForm"), {
            fields: {
                transfer_from: { validators: { notEmpty: { message: "transfer from is required" } } },
                transfer_to: { validators: { notEmpty: { message: "transfer to is required" } } },
                remarks: { validators: { notEmpty: { message: "remarks is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        $(r2.querySelector('[name="transfer_from"]')).on("change", function () {
            n2.revalidateField("transfer_from");
        });
        $(r2.querySelector('[name="transfer_to"]')).on("change", function () {
            n2.revalidateField("transfer_to");
        });
    },

    transfer_item.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.transfer_item.load_contents(1);
        $.transfer_item.load_transfer_item_contents(1, $('#transfer_item_id').val());

        $("#date_received").flatpickr({
            dateFormat: "d-M-Y"
        });
        /*
        | ---------------------------------
        | # numeric text
        | ---------------------------------
        */
        this.$body.on("keypress", ".numeric-only", function (e) {

            var verified = (e.which == 8 || e.which == undefined || e.which == 0) ? null : String.fromCharCode(e.which).match(/[^0-9]/);
            if (verified) {
                e.preventDefault();
            }
    
        });
        this.$body.on("keypress", ".numeric", function (event) {

            var $this = $(this);
            if ((event.which != 46 || $this.val().indexOf('.') != -1) &&
                ((event.which < 48 || event.which > 57) &&
                    (event.which != 0 && event.which != 8))) {
                event.preventDefault();
            }
    
            var text = $(this).val();
            if ((event.which == 46) && (text.indexOf('.') == -1)) {
                setTimeout(function () {
                    if ($this.val().substring($this.val().indexOf('.')).length > 3) {
                        $this.val($this.val().substring(0, $this.val().indexOf('.') + 3));
                    }
                }, 1);
            }
    
            if ((text.indexOf('.') != -1) &&
                (text.substring(text.indexOf('.')).length > 2) &&
                (event.which != 0 && event.which != 8) &&
                ($(this)[0].selectionStart >= text.length - 2)) {
                event.preventDefault();
            }
    
        });

        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#keywords', function (e) {
            $.transfer_item.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when transferItemModal keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#transferItemModal .keywords', function (e) {
            var modal = $(this).closest('.modal');
            $.transfer_item.load_transfer_item_contents(1, modal.find('#transfer_item_id').val());
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#transferItemModal', function (e) {
            var modal = $(this);
            modal.find('.modal-header h5').html('New Transfer Item');
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('#next-btn').removeClass('hidden');
            modal.find('#prev-next-btn-holder button.prev').addClass('hidden');
            modal.find('div[data-kt-stepper-element="content"][steps="1"]').addClass('current');
            modal.find('div[data-kt-stepper-element="content"][steps="2"]').removeClass('current');
            modal.find('div.stepper-nav div[steps="1"].stepper-item').addClass('current');
            modal.find('div.stepper-nav div[steps="2"].stepper-item').removeClass('current');
            $.transfer_item.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when modal is shown
        | ---------------------------------
        */
        this.$body.on('shown.bs.modal', '#transferItemModal', function (e) {
            var modal = $(this);
            $.transfer_item.load_transfer_item_contents(1, modal.find('#transfer_item_id').val());
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var trans  = $(this).closest('tr').attr('data-row-trans');
            var total  = $(this).closest('tr').attr('data-row-amount');
            var modal  = $('#transferItemModal');
            var urlz   = base_url + 'auth/items/transfer-items/find/' + id;
            console.log(urlz);
            modal.find('.modal-header h5').html('Edit Transfer Item ('+trans+')');
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        modal.find('#transferItemForm textarea[name='+k+']').val(v);
                        modal.find('#transferItemForm select[name='+k+']').select2().val(v).prop('disabled', true).trigger('change');
                        modal.find('#transferItemForm input[name='+k+']').val(v);
                    });
                    modal.find('#next-btn').addClass('hidden');
                    modal.find('#prev-next-btn-holder button.prev').removeClass('hidden');
                    modal.find('div[data-kt-stepper-element="content"][steps="1"]').removeClass('current');
                    modal.find('div[data-kt-stepper-element="content"][steps="2"]').addClass('current');
                    modal.find('div.stepper-nav div[steps="1"].stepper-item').removeClass('current');
                    modal.find('div.stepper-nav div[steps="2"].stepper-item').addClass('current');
                    modal.find('#totalAmount').text(total);
                    modal.modal('show');
                },
                complete: function() {
                    window.onkeydown = null;
                    window.onfocus = null;
                }
            });
        }); 

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transfer_itemTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/items-transfer-itemss/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the transfer_item with code ("+ code +") will be removed.",
                icon: "warning",
                showCancelButton: !0,
                buttonsStyling: !1,
                confirmButtonText: "Yes, remove it!",
                cancelButtonText: "No, return",
                customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" },
            }).then(function (t) {
                t.value
                    ? 
                    $.ajax({
                        type: 'PUT',
                        url: urlz,
                        success: function(response) {
                            var data = $.parseJSON(response);
                            console.log(data);
                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                function (e) {
                                    e.isConfirmed && ((t.disabled = !1));
                                    $.transfer_item.load_contents(1);
                                }
                            );
                        },
                        complete: function() {
                            window.onkeydown = null;
                            window.onfocus = null;
                        }
                    })
                    : "cancel" === t.dismiss 
            });
            
        }); 

        /*
        | ---------------------------------
        | # when perPage is changed
        | ---------------------------------
        */
        this.$body.on('change', '#perPage', function (e) {
            $.transfer_item.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.transfer_item.load_contents(page);
            }
        });

        /*
        | ---------------------------------
        | # when transfer_item paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemModal .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            var modal = $('#transferItemModal')
            if (page > 0) {
                $.transfer_item.load_transfer_item_contents(page, modal.find('#transfer_item_id').val());
            }
        });
        

        /*
        | ---------------------------------
        | # when back button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#prev-btn', function (e) {
            e.preventDefault();
            var self = $(this);
            self.addClass('hidden');
            $('#prev-next-btn-holder button.next').removeClass('hidden');
            $('div[data-kt-stepper-element="content"][steps="2"]').removeClass('current');
            $('div[data-kt-stepper-element="content"][steps="1"]').addClass('current');
            $('div.stepper-nav div[steps="2"].stepper-item').removeClass('current');
            $('div.stepper-nav div[steps="1"].stepper-item').addClass('current');
        });
        /*
        | ---------------------------------
        | # when continue button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#next-btn', function (e) {
            e.preventDefault();
            var self = $(this);
            var form = $('#transferItemForm');
            n2 &&
                n2.validate().then(function (e) {
                    if ("Valid" == e && detailValidation > 0) {
                        if ($('#transfer_item_id').val() == '') {
                            (t2.setAttribute("data-kt-indicator", "on"),
                            (t2.disabled = !0),
                            setTimeout(function () {
                                t2.removeAttribute("data-kt-indicator"),
                                $.ajax({
                                    type: form.attr('method'),
                                    url: form.attr('action'),
                                    data: form.serialize(),
                                    success: function(response) {
                                        var data = $.parseJSON(response); 
                                        $('#transfer_no').val(data.transfer_no);
                                        $('#transfer_item_id').val(data.transfer_item_id);
                                        $('#transfer_from, #transfer_to').prop('disabled', true).select2().trigger('change');
                                        $.transfer_item.load_transfer_item_contents(1, data.transfer_item_id);
                                        t2.removeAttribute("data-kt-indicator");
                                        t2.disabled = !1;
                                        self.addClass('hidden');
                                        $('#prev-next-btn-holder button.prev').removeClass('hidden');
                                        $('div[data-kt-stepper-element="content"][steps="1"]').removeClass('current');
                                        $('div[data-kt-stepper-element="content"][steps="2"]').addClass('current');
                                        $('div.stepper-nav div[steps="1"].stepper-item').removeClass('current');
                                        $('div.stepper-nav div[steps="2"].stepper-item').addClass('current');
                                    },
                                });
                            }, 2e3))
                        } else {
                            $.ajax({
                                type: 'PUT',
                                url: base_url + 'auth/items/transfer-items/update/' + $('#transfer_item_id').val(),
                                data: form.serialize(),
                                success: function(response) {
                                    var data = $.parseJSON(response); 
                                    self.addClass('hidden');
                                    $('#prev-next-btn-holder button.prev').removeClass('hidden');
                                    $('div[data-kt-stepper-element="content"][steps="1"]').removeClass('current');
                                    $('div[data-kt-stepper-element="content"][steps="2"]').addClass('current');
                                    $('div.stepper-nav div[steps="1"].stepper-item').removeClass('current');
                                    $('div.stepper-nav div[steps="2"].stepper-item').addClass('current');
                                },
                            });
                        }
                    }
            });
        });
        
        /*
        | ---------------------------------
        | # when branch is changed
        | ---------------------------------
        */
        this.$body.on('change', '#branch_id', function (e) {
            var self = $(this);
            var modal = self.closest('.modal');
            var urlz   = base_url + 'auth/items/transfer-items/get-po-no/' + self.val();
            console.log(urlz);
            if (modal.find('.modal-header h5').text() == 'New Purchase Order') {
                if (self.val() > 0) {
                    $.ajax({
                        type: 'GET',
                        url: urlz,
                        success: function(response) {
                            console.log(response);
                            modal.find('input[name="po_no"]').val(response);
                        },
                    });
                }
            }
        });

        /*
        | ---------------------------------
        | # when customer is changed
        | ---------------------------------
        */
        this.$body.on('change', '#supplier_id', function (e) {
            var self = $(this);
            var modal = self.closest('.modal');
            var urlz   = base_url + 'auth/items/transfer-items/get-supplier-info/' + self.val();
            console.log(urlz);
            if (self.val() > 0) {
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        var data = $.parseJSON(response);
                        $.each(data, function (k, v) {
                            modal.find('input[name='+k+']').val(v);
                            modal.find('textarea[name='+k+']').val(v);
                            modal.find('select[name='+k+']').select2().val(v).trigger('change');
                        });
                    },
                });
            }
        });

        /*
        | ---------------------------------
        | # when item is changed
        | ---------------------------------
        */
        this.$body.on('change', '#item_id', function (e) {
            var self = $(this);
            var modal = self.closest('.modal');
            var urlz   = base_url + 'auth/items/transfer-items/get-item-info/' + self.val() + '/' + modal.find("#transfer_from").val();
            console.log(urlz);
            if (self.val() > 0) {
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        var data = $.parseJSON(response);
                        $.each(data, function (k, v) {
                            modal.find('input[name='+k+']').val(v);
                            modal.find('textarea[name='+k+']').val(v);
                            modal.find('select[name='+k+']').select2().val(v).trigger('change');
                        });
                    },
                });
                $.transfer_item.compute($('#transferItemLineForm'));
            }
        });

        /*
        | ---------------------------------
        | # when transfer_itemLineForm onkeyup blur
        | ---------------------------------
        */
        this.$body.on('change', '#transferItemLineForm #uom_id', function (e) {
            $.transfer_item.compute($('#transferItemLineForm'));
        });
        this.$body.on('keyup', '#transferItemLineForm #qty', function (e) {
            var self = $(this);
            var based_quantity = $('#based_quantity');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(based_quantity.val())) {
                    itemValidation = 0;
                    self.next().text('The quantity should not be higher than (inventory)');
                } else {
                    itemValidation = 1;
                    self.next().text('');
                }
            } else {
                itemValidation = 0;
            }
            $.transfer_item.compute($('#transferItemLineForm'));
        });
        this.$body.on('blur', '#transferItemLineForm #qty', function (e) {
            var self = $(this);
            var based_quantity = $('#based_quantity');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(based_quantity.val())) {
                    itemValidation = 0;
                    self.next().text('The quantity should not be higher than (inventory)');
                } else {
                    itemValidation = 1;
                    self.next().text('');
                }
            } else {
                itemValidation = 0;
            }
            $.transfer_item.compute($('#transferItemLineForm'));
        });

        /*
        | ---------------------------------
        | # when transfer from and to on change
        | ---------------------------------
        */
        this.$body.on('change', '#transfer_from', function (e) {
            var self = $(this);
            var trans = $('#transfer_to');
            if (self.val() != '') {
                if (self.val() == trans.val()) {
                    detailValidation = 0;
                    self.closest('.col-sm-6').find('.invalid-feedback').text('transfer from should not be equal to transfer to');
                } else {
                    detailValidation = 1;
                    self.closest('.col-sm-6').find('.invalid-feedback').text('');
                }
            } else {
                detailValidation = 0;
            }
            $.transfer_item.compute($('#transferItemLineForm'));
        });
        this.$body.on('change', '#transfer_to', function (e) {
            var self = $(this);
            var trans = $('#transfer_from');
            if (self.val() != '') {
                if (self.val() == trans.val()) {
                    detailValidation = 0;
                    self.closest('.col-sm-6').find('.invalid-feedback').text('transfer to should not be equal to transfer from');
                } else {
                    detailValidation = 1;
                    self.closest('.col-sm-6').find('.invalid-feedback').text('');
                }
            } else {
                detailValidation = 0;
            }
        });


        /*
        | ---------------------------------
        | # when add item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemLineForm .add-item-btn', function (e) {
            e.preventDefault();
            var options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": false,
                "progressBar": false,
                "positionClass": "toast-bottom-left",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };
            var modal = $('#transferItemModal');
            var keywords =  $('#transfer_item_id').val() + '?total_amount=' + $('#transferItemLineForm #total_amountx').val() + '&srp=' + $('#transferItemLineForm #srp').val(); 
            var $lineID = $('#transferItemLineForm').find('input[name="transfer_item_line_id"]').val();
            var $url = ($lineID != '') ? base_url + 'auth/items/transfer-items/update-line-item/' + $lineID + '?total_amount=' + $('#transferItemLineForm #total_amountx').val() + '&srp=' + $('#transferItemLineForm #srp').val() : $('#transferItemLineForm').attr('action') + '/' + keywords; 
            var $method = ($lineID != '') ? 'PUT' : 'POST';
            n &&
            n.validate().then(function (e) {
                if ("Valid" == e && itemValidation > 0) {
                    (t.setAttribute("data-kt-indicator", "on"),
                    (t.disabled = !0),
                    setTimeout(function () {
                        t.removeAttribute("data-kt-indicator"),
                        console.log($url),
                        $.ajax({
                            type: $method,
                            url: $url,
                            data: $('#transferItemLineForm').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $.transfer_item.load_transfer_item_contents(1, $('#transfer_item_id').val());
                                t.removeAttribute("data-kt-indicator");
                                $('#transferItemLineForm')[0].reset();
                                $('#transferItemLineForm select').select2().val('').trigger('change');
                                t.disabled = !1;
                                $('#transferItemModal').find('button.add-item-btn').html('<span class="indicator-label">' +
                                '<span class="svg-icon svg-icon-4 ms-1 me-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">' +
                                '<rect fill="#000000" x="4" y="11" width="16" height="2" rx="1"/>' +
                                '<rect fill="#000000" opacity="0.5" transform="translate(12.000000, 12.000000) rotate(-270.000000) translate(-12.000000, -12.000000) " x="4" y="11" width="16" height="2" rx="1"/>' +
                                '</svg></span>' +
                                ' Add Item</span>' +
                                '<span class="indicator-progress">Please wait...' +
                                '<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>');
                                toastr.success(data.text, data.title, options);
                                modal.find('#totalAmount').text(data.total_amount);
                            },
                        });
                    }, 2e3))
                }
            }); 
        });

        /*
        | ---------------------------------
        | # when transfer_item line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemLineTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var posted = $(this).closest('tr').attr('data-row-posted');
            var quantity = $(this).closest('tr').attr('data-row-qty');
            var urlz   = base_url + 'auth/items/transfer-items/remove-line-item/' + id;
            var modal  = $('#transferItemModal');
            console.log(urlz);
            if (quantity > posted) {
                Swal.fire({
                    html: "Are you sure you? <br/>the remaining quantity from line item<br/>("+ item +")<br/>will be retracted.",
                    icon: "warning",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "Yes, retract it!",
                    cancelButtonText: "No, return",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" },
                }).then(function (t) {
                    t.value
                        ? 
                        $.ajax({
                            type: 'PUT',
                            url: urlz,
                            success: function(response) {
                                var data = $.parseJSON(response);
                                console.log(data);
                                Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                    function (e) {
                                        e.isConfirmed && ((t.disabled = !1));
                                        modal.find('#totalAmount').text(data.total_amount);
                                        $.transfer_item.load_transfer_item_contents(1, $('#transfer_item_id').val());
                                    }
                                );
                            },
                            complete: function() {
                                window.onkeydown = null;
                                window.onfocus = null;
                            }
                        })
                        : "cancel" === t.dismiss 
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    html: "Oops!<br/>This item is already been posted.",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" }
                });
            }
        }); 

        /*
        | ---------------------------------
        | # when transfer_item line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemLineTable .post-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var posted = $(this).closest('tr').attr('data-row-posted');
            var quantity = $(this).closest('tr').attr('data-row-qty');
            var urlz   = base_url + 'auth/items/transfer-items/find-line-item/' + id;
            var modal  = $('#transferItemPostingModal');
            if (posted != quantity) {
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        console.log(response);
                        $.each(response.data, function (k, v) {
                            modal.find('input[name='+k+']').val(v);
                            modal.find('textarea[name='+k+']').val(v);
                            modal.find('select[name='+k+']').select2().val(v).trigger('change');
                        });
                        modal.modal('show');
                        setTimeout(function() {
                            modal.find('input[name="qty_to_post"]').focus();
                        }, 500);
                    },
                    complete: function() {
                        window.onkeydown = null;
                        window.onfocus = null;
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    html: "Oops!<br/>This item cannot be post,<br/>the item quantity has already been posted.",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" }
                });
            }
        });
        
        /*
        | ---------------------------------
        | # when qty to post onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#transferItemPostingModal #qty_to_post', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var date_recieved = modal.find('input[name="date_received"]');
            var forPosting = modal.find('input[name="for_posting"]');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(forPosting.val())) {
                    postingValidation = 0;
                    self.next().text('The quantity should not be higher than (For Posting)');
                } else {
                    if (date_recieved.val() != '') {
                        postingValidation = 1;
                    } else {
                        postingValidation = 0;
                    }
                    self.next().text('');
                }
            } else {
                postingValidation = 0;
            }
        });
        this.$body.on('blur', '#transferItemPostingModal #qty_to_post', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var date_recieved = modal.find('input[name="date_received"]');
            var forPosting = modal.find('input[name="for_posting"]');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(forPosting.val())) {
                    postingValidation = 0;
                    self.next().text('The quantity should not be higher than (For Posting)');
                } else {
                    if (date_recieved.val() != '') {
                        postingValidation = 1;
                    } else {
                        postingValidation = 0;
                    }
                    self.next().text('');
                }
            } else {
                postingValidation = 0;
            }
        });
        this.$body.on('blur', '#transferItemPostingModal #date_received', function (e) {
            var self = $(this);
            if (self.val() != '') {
                postingValidation = 1;
            } else {
                postingValidation = 0;
            }
        });

        /*
        | ---------------------------------
        | # when transfer_item posting modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#transferItemPostingModal', function (e) {
            var modal = $(this);
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
        });

        /*
        | ---------------------------------
        | # when post item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemPostingModal #transferItemPostingModalSubmit', function (e) {
            e.preventDefault();
            var modal = $(this).closest('.modal');
            var transfer_item_line_id = modal.find('input[name="transfer_item_line_id"]').val();
            var transfer_item_id = modal.find('input[name="transfer_item_idx"]').val();
            var options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": false,
                "progressBar": false,
                "positionClass": "toast-bottom-left",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };
            n3 &&
            n3.validate().then(function (e) {
                if ("Valid" == e && postingValidation > 0) {
                    (t3.setAttribute("data-kt-indicator", "on"),
                    (t3.disabled = !0),
                    setTimeout(function () {
                        t3.removeAttribute("data-kt-indicator"),
                        console.log($('#transferItemPostingForm').attr('action') + '/' + transfer_item_line_id);
                        $.ajax({
                            type: $('#transferItemPostingForm').attr('method'),
                            url: $('#transferItemPostingForm').attr('action') + '/' + transfer_item_line_id,
                            data: $('#transferItemPostingForm').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $.transfer_item.load_transfer_item_contents(1, transfer_item_id);
                                t.removeAttribute("data-kt-indicator");
                                $('#transferItemPostingForm')[0].reset();
                                modal.modal('hide');
                                t3.disabled = !1;
                                toastr.success(data.text, data.title, options);
                            },
                        });
                    }, 2e3))
                }
            });
        });

        /*
        | ---------------------------------
        | # when preview preparation button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#preview-preparation-btn', function (e) {
            e.preventDefault();
            var url = base_url + 'auth/items/transfer-items/preview?document=preparation&dr_no=' + $('#transfer_item_doc_no').val();
            window.open(url, "_blank");
        });

        /*
        | ---------------------------------
        | # when preview posting button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#preview-posting-btn', function (e) {
            e.preventDefault();
            var url = base_url + 'auth/items/transfer-items/preview?document=posting&dr_no=' + $('#transfer_item_doc_no').val();
            window.open(url, "_blank");
        });

        /*
        | ---------------------------------
        | # when edit item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#transferItemModal .edit-item-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var modal  = $(this).closest('.modal');
            var urlz   = base_url + 'auth/items/transfer-items/find-line/' + id;
            console.log(urlz);
            var posted = $(this).closest('tr').attr('data-row-posted');
            if (posted <= 0) {
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        console.log(response);
                        $.each(response.data, function (k, v) {
                            modal.find('#transferItemLineForm input[name='+k+']').val(v);
                            modal.find('#transferItemLineForm textarea[name='+k+']').val(v);
                            modal.find('#transferItemLineForm select[name='+k+']').select2().val(v).trigger('change');
                        });
                        modal.find('button.add-item-btn').html('<span class="indicator-label">' +
                        '<span class="svg-icon svg-icon-4 ms-1 me-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">' +
                        '<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">' +
                        '<polygon points="0 0 24 0 24 24 0 24"/>' +
                        '<path d="M17,4 L6,4 C4.79111111,4 4,4.7 4,6 L4,18 C4,19.3 4.79111111,20 6,20 L18,20 C19.2,20 20,19.3 20,18 L20,7.20710678 C20,7.07449854 19.9473216,6.94732158 19.8535534,6.85355339 L17,4 Z M17,11 L7,11 L7,4 L17,4 L17,11 Z" fill="#000000" fill-rule="nonzero"/>' +
                        '<rect fill="#000000" opacity="0.3" x="12" y="4" width="3" height="5" rx="0.5"/>' +
                        '</g>' +
                        '</svg></span> ' +
                        'Update Item</span>' +
                        '<span class="indicator-progress">Please wait...' +
                        '<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>');
                    },
                    complete: function() {
                        window.onkeydown = null;
                        window.onfocus = null;
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    html: "Oops!<br/>This item is already been posted.",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" }
                });
            }
        }); 

        /*
        | ---------------------------------
        | # when transfer_item line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.add-new-btn', function (e) {
            var self   = $(this);
            var urlz   = base_url + 'auth/items/transfer-items/generate-trans-no';
            var modal  = $('#transferItemModal');
            self.prop('disabled', true);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    modal.find('#transfer_no').val(response);
                    modal.modal('show');
                },
                complete: function() {
                    self.prop('disabled', false);
                },
            });
        });
    }

    //init transfer_item
    $.transfer_item = new transfer_item, $.transfer_item.Constructor = transfer_item

}(window.jQuery),

//initializing transfer_item
function($) {
    "use strict";
    $.transfer_item.transfer_detail_validation();
    $.transfer_item.transfer_item_detail_validation();
    $.transfer_item.posting_validation();
    $.transfer_item.init();
}(window.jQuery);