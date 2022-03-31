!function($) {
    "use strict";

    var purchase_order = function() {
        this.$body = $("body");
    };

    var track_page = 1; 
    var t, n;
    var o2, n2, r2, t2;
    var t3, n3, z3, postingValidation = 0;

    purchase_order.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#purchase-order #keywords').val() + '&perPage=' + $('#purchase-order #perPage').val();
        var urls = base_url + 'auth/purchase-order/all-active';
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

    purchase_order.prototype.load_purchase_order_contents = function(track_page, purchase_orderID) 
    {   
        var keywords = '?id='+ purchase_orderID + '&keywords=' + $('#purchaseOrderModal .keywords').val() + '&perPage=7';
        var urls = base_url + 'auth/purchase-order/all-active-lines';
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

    purchase_order.prototype.truncate = function(num) {
        var with2Decimals = num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0]
        return with2Decimals
    },

    purchase_order.prototype.compute = function(form) {
        var qty = form.find('#qty');
        var total_amount = form.find('#total_amountx');
        var srp = form.find('#srp').val();
        var totalAmt = 0;
        if (qty.val() != '') {
            totalAmt = parseFloat(srp) * parseFloat(qty.val());
        } else {
            totalAmt = 0;
        }
        total_amount.val($.purchase_order.truncate(totalAmt));
    },

    purchase_order.prototype.posting_validation = function()
    {   
        z3  = document.querySelector("#purchaseOrderPostingForm");
        t3 = document.querySelector("#purchaseOrderPostingModalSubmit");
        n3 = FormValidation.formValidation(z3, {
            fields: {
                qty_to_post: { 
                    validators: { 
                        notEmpty: { 
                            message: "Quantity is required" 
                        }
                    } 
                },
                date_recieved: { validators: { notEmpty: { message: "Date Received is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
    },

    purchase_order.prototype.item_detail_validation = function()
    {
        t = document.querySelector("#purchaseOrderLineForm .add-item-btn");
        n = FormValidation.formValidation(document.querySelector("#purchaseOrderLineForm"), {
            fields: {
                item_id: { validators: { notEmpty: { message: "Item is required" } } },
                uom_id: { validators: { notEmpty: { message: "UOM is required" } } },
                qty: { validators: { notEmpty: { message: "Quantity is required" } } },
                srp: { validators: { notEmpty: { message: "SRP is required" } } }
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
    },

    purchase_order.prototype.purchase_order_detail_validation = function()
    {
        o2 = document.querySelector("#prev-next-btn-holder");
        t2 = o2.querySelector("#next-btn");
        r2 = document.querySelector("#purchaseOrderForm");
        n2 = FormValidation.formValidation(document.querySelector("#purchaseOrderForm"), {
            fields: {
                branch_id: { validators: { notEmpty: { message: "Branch is required" } } },
                purchase_order_type_id: { validators: { notEmpty: { message: "PO type is required" } } },
                supplier_id: { validators: { notEmpty: { message: "Supplier is required" } } },
                contact_no: { 
                    validators: { 
                        notEmpty: { message: "Contact no is required" },
                        numeric: {
                            message: 'The value is not a valid number'
                        },
                        stringLength: {
                            max: 11,
                            min: 11,
                            message: 'The mobile no must be 11 characters',
                        },
                    } 
                },
                payment_terms_id: { validators: { notEmpty: { message: "Payment Terms is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        $(r2.querySelector('[name="branch_id"]')).on("change", function () {
            n2.revalidateField("branch_id");
            if ($('#purchase_order_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/purchase-order/update/' + $('#purchase_order_id').val(),
                    data: $('#purchaseOrderForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
        $(r2.querySelector('[name="supplier_id"]')).on("change", function () {
            n2.revalidateField("supplier_id");
            if ($('#purchase_order_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/purchase-order/update/' + $('#purchase_order_id').val(),
                    data: $('#purchaseOrderForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
        $(r2.querySelector('[name="payment_terms_id"]')).on("change", function () {
            n2.revalidateField("payment_terms_id");
            if ($('#purchase_order_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/purchase-order/update/' + $('#purchase_order_id').val(),
                    data: $('#purchaseOrderForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
    },

    purchase_order.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.purchase_order.load_contents(1);
        $.purchase_order.load_purchase_order_contents(1, $('#purchase_order_id').val());

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
            $.purchase_order.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when purchaseOrderModal keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#purchaseOrderModal .keywords', function (e) {
            var modal = $(this).closest('.modal');
            $.purchase_order.load_purchase_order_contents(1, modal.find('#purchase_order_id').val());
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#purchaseOrderModal', function (e) {
            var modal = $(this);
            modal.find('.modal-header h5').html('New purchase_order');
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('#next-btn').removeClass('hidden');
            modal.find('#prev-next-btn-holder button.prev').addClass('hidden');
            modal.find('div[data-kt-stepper-element="content"][steps="1"]').addClass('current');
            modal.find('div[data-kt-stepper-element="content"][steps="2"]').removeClass('current');
            modal.find('div.stepper-nav div[steps="1"].stepper-item').addClass('current');
            modal.find('div.stepper-nav div[steps="2"].stepper-item').removeClass('current');
            $.purchase_order.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when modal is shown
        | ---------------------------------
        */
        this.$body.on('shown.bs.modal', '#purchaseOrderModal', function (e) {
            var modal = $(this);
            $.purchase_order.load_purchase_order_contents(1, modal.find('#purchase_order_id').val());
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#purchaseOrderTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var po     = $(this).closest('tr').attr('data-row-po');
            var total  = $(this).closest('tr').attr('data-row-amount');
            var modal  = $('#purchaseOrderModal');
            var urlz   = base_url + 'auth/purchase-order/find/' + id;
            console.log(urlz);
            modal.find('.modal-header h5').html('Edit Purchase Order ('+po+')');
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        if (k == 'branch_id') {
                            modal.find('#purchaseOrderForm select[name='+k+']').select2().val(v).prop('disabled', true).trigger('change');
                        }
                        modal.find('#purchaseOrderForm textarea[name='+k+']').val(v);
                        modal.find('#purchaseOrderForm select[name='+k+']').select2().val(v).trigger('change');
                        modal.find('#purchaseOrderForm input[name='+k+']').val(v);
                        if (k == 'contact_no') {
                            setTimeout(function() {
                                modal.find('#purchaseOrderForm input[name='+k+']').val(v);
                            }, 1000);
                        }
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
        this.$body.on('click', '#purchase_orderTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/purchase-orders/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the purchase_order with code ("+ code +") will be removed.",
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
                                    $.purchase_order.load_contents(1);
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
            $.purchase_order.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.purchase_order.load_contents(page);
            }
        });

        /*
        | ---------------------------------
        | # when purchase_order paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#purchaseOrderModal .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            var modal = $('#purchaseOrderModal')
            if (page > 0) {
                $.purchase_order.load_purchase_order_contents(page, modal.find('#purchase_order_id').val());
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
            var form = $('#purchaseOrderForm');
            n2 &&
                n2.validate().then(function (e) {
                    if ("Valid" == e) {
                        if ($('#purchase_order_id').val() == '') {
                            (t2.setAttribute("data-kt-indicator", "on"),
                            (t2.disabled = !0),
                            setTimeout(function () {
                                t2.removeAttribute("data-kt-indicator"),
                                $.ajax({
                                    type: form.attr('method'),
                                    url: form.attr('action') + '?due_date=' + form.find('input[name="due_date"]').val(),
                                    data: form.serialize(),
                                    success: function(response) {
                                        var data = $.parseJSON(response); 
                                        $('#po_no').val(data.po_no);
                                        $('#branch_id').prop('disabled', true).select2().trigger('change');
                                        $('#purchase_order_id').val(data.purchase_order_id);
                                        $.purchase_order.load_purchase_order_contents(1, data.purchase_order_id);
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
                            self.addClass('hidden');
                            $('#prev-next-btn-holder button.prev').removeClass('hidden');
                            $('div[data-kt-stepper-element="content"][steps="1"]').removeClass('current');
                            $('div[data-kt-stepper-element="content"][steps="2"]').addClass('current');
                            $('div.stepper-nav div[steps="1"].stepper-item').removeClass('current');
                            $('div.stepper-nav div[steps="2"].stepper-item').addClass('current');
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
            var urlz   = base_url + 'auth/purchase-order/get-po-no/' + self.val();
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
            var urlz   = base_url + 'auth/purchase-order/get-supplier-info/' + self.val();
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
            var urlz   = base_url + 'auth/purchase-order/get-item-info/' + self.val() + '/' + modal.find("#branch_id").val();
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
                $.purchase_order.compute($('#purchaseOrderLineForm'));
            }
        });

        /*
        | ---------------------------------
        | # when purchase_orderLineForm onkeyup blur
        | ---------------------------------
        */
        this.$body.on('change', '#purchaseOrderLineForm #uom_id', function (e) {
            $.purchase_order.compute($('#purchaseOrderLineForm'));
        });
        this.$body.on('keyup', '#purchaseOrderLineForm #qty, #purchaseOrderLineForm #srp', function (e) {
            $.purchase_order.compute($('#purchaseOrderLineForm'));
        });
        this.$body.on('blur', '#purchaseOrderLineForm #qty, #purchaseOrderLineForm #srp', function (e) {
            $.purchase_order.compute($('#purchaseOrderLineForm'));
        });

        /*
        | ---------------------------------
        | # when add item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#purchaseOrderLineForm .add-item-btn', function (e) {
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
            var modal = $('#purchaseOrderModal');
            var keywords =  $('#purchase_order_id').val() + '?total_amount=' + $('#purchaseOrderLineForm #total_amountx').val(); 
            var $lineID = $('#purchaseOrderLineForm').find('input[name="purchase_order_line_id"]').val();
            var $url = ($lineID != '') ? base_url + 'auth/purchase-order/update-line-item/' + $lineID + '?total_amount=' + $('#purchaseOrderLineForm #total_amountx').val() : $('#purchaseOrderLineForm').attr('action') + '/' + keywords; 
            var $method = ($lineID != '') ? 'PUT' : 'POST';
            n &&
            n.validate().then(function (e) {
                if ("Valid" == e) {
                    (t.setAttribute("data-kt-indicator", "on"),
                    (t.disabled = !0),
                    setTimeout(function () {
                        t.removeAttribute("data-kt-indicator"),
                        console.log($url),
                        $.ajax({
                            type: $method,
                            url: $url,
                            data: $('#purchaseOrderLineForm').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $.purchase_order.load_purchase_order_contents(1, $('#purchase_order_id').val());
                                t.removeAttribute("data-kt-indicator");
                                $('#purchaseOrderLineForm')[0].reset();
                                $('#purchaseOrderLineForm select').select2().val('').trigger('change');
                                t.disabled = !1;
                                $('#purchaseOrderModal').find('button.add-item-btn').html('<span class="indicator-label">' +
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
        | # when purchase_order line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#PurchaseOrderLineTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var posted = $(this).closest('tr').attr('data-row-posted');
            var urlz   = base_url + 'auth/purchase-order/remove-line-item/' + id;
            var modal  = $('#purchaseOrderModal');
            console.log(urlz);
            if (posted <= 0) {
                Swal.fire({
                    html: "Are you sure you? <br/>the line item ("+ item +") will be removed.",
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
                                        modal.find('#totalAmount').text(data.total_amount);
                                        $.purchase_order.load_purchase_order_contents(1, $('#purchase_order_id').val());
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
        | # when purchase_order line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#PurchaseOrderLineTable .post-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var posted = $(this).closest('tr').attr('data-row-posted');
            var quantity = $(this).closest('tr').attr('data-row-qty');
            var urlz   = base_url + 'auth/purchase-order/find-line-item/' + id;
            var modal  = $('#purchaseOrderPostingModal');
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
        this.$body.on('keyup', '#purchaseOrderPostingModal #qty_to_post', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var onHand = modal.find('input[name="available_qty"]');
            var forPosting = modal.find('input[name="for_posting"]');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(forPosting.val())) {
                    postingValidation = 0;
                    self.next().text('The quantity should not be higher than (For Posting)');
                } else {
                    postingValidation = 1;
                    self.next().text('');
                }
            } else {
                postingValidation = 0;
            }
        });
        this.$body.on('blur', '#purchaseOrderPostingModal #qty_to_post', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var onHand = modal.find('input[name="available_qty"]');
            var forPosting = modal.find('input[name="for_posting"]');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(forPosting.val())) {
                    postingValidation = 0;
                    self.next().text('The quantity should not be higher than (For Posting)');
                } else {
                    postingValidation = 1;
                    self.next().text('');
                }
            } else {
                postingValidation = 0;
            }
        });

        /*
        | ---------------------------------
        | # when purchase_order posting modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#purchaseOrderPostingModal', function (e) {
            var modal = $(this);
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
        });

        /*
        | ---------------------------------
        | # when post item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#purchaseOrderPostingModal #purchaseOrderPostingModalSubmit', function (e) {
            e.preventDefault();
            var modal = $(this).closest('.modal');
            var purchase_order_line_id = modal.find('input[name="purchase_order_line_id"]').val();
            var purchase_order_id = modal.find('input[name="purchase_order_idx"]').val();
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
                        console.log($('#purchaseOrderPostingForm').attr('action') + '/' + purchase_order_line_id);
                        $.ajax({
                            type: $('#purchaseOrderPostingForm').attr('method'),
                            url: $('#purchaseOrderPostingForm').attr('action') + '/' + purchase_order_line_id,
                            data: $('#purchaseOrderPostingForm').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $.purchase_order.load_purchase_order_contents(1, purchase_order_id);
                                t.removeAttribute("data-kt-indicator");
                                $('#purchaseOrderPostingForm')[0].reset();
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
            var url = base_url + 'auth/purchase-order/preview?document=preparation&dr_no=' + $('#purchase_order_doc_no').val();
            window.open(url, "_blank");
        });

        /*
        | ---------------------------------
        | # when preview posting button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#preview-posting-btn', function (e) {
            e.preventDefault();
            var url = base_url + 'auth/purchase-order/preview?document=posting&dr_no=' + $('#purchase_order_doc_no').val();
            window.open(url, "_blank");
        });

        /*
        | ---------------------------------
        | # when edit item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#purchaseOrderModal .edit-item-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var modal  = $(this).closest('.modal');
            var urlz   = base_url + 'auth/purchase-order/find-line/' + id;
            console.log(urlz);
            var posted = $(this).closest('tr').attr('data-row-posted');
            if (posted <= 0) {
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        console.log(response);
                        $.each(response.data, function (k, v) {
                            modal.find('#purchaseOrderLineForm input[name='+k+']').val(v);
                            modal.find('#purchaseOrderLineForm textarea[name='+k+']').val(v);
                            modal.find('#purchaseOrderLineForm select[name='+k+']').select2().val(v).trigger('change');
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
    }

    //init purchase_order
    $.purchase_order = new purchase_order, $.purchase_order.Constructor = purchase_order

}(window.jQuery),

//initializing purchase_order
function($) {
    "use strict";
    $.purchase_order.item_detail_validation();
    $.purchase_order.purchase_order_detail_validation();
    $.purchase_order.posting_validation();
    $.purchase_order.init();
}(window.jQuery);