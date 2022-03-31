!function($) {
    "use strict";

    var delivery = function() {
        this.$body = $("body");
    };

    var track_page = 1; 
    var t, n;
    var o2, n2, r2, t2;
    var t3, n3, z3, postingValidation = 0;

    delivery.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#delivery #keywords').val() + '&perPage=' + $('#delivery #perPage').val();
        var urls = base_url + 'auth/delivery/all-active';
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

    delivery.prototype.load_delivery_contents = function(track_page, deliveryID) 
    {   
        var keywords = '?id='+ deliveryID + '&keywords=' + $('#deliveryModal .keywords').val() + '&perPage=7';
        var urls = base_url + 'auth/delivery/all-active-lines';
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

    delivery.prototype.truncate = function(num) {
        var with2Decimals = num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0]
        return with2Decimals
    },

    delivery.prototype.compute = function(form) {
        var qty = form.find('#qty');
        var total_amount = form.find('#total_amountx');
        var disc1 = form.find('#disc1');
        var disc2 = form.find('#disc2');
        var srp = (form.find('#srp_special').val() != '') ? form.find('#srp_special').val() : form.find('#srp').val();
        var plus = form.find('#plus');
        var totalAmt = 0;
        if (qty.val() != '') {
            if (disc1.val() != '') {
                var disc1x = parseFloat(disc1.val() / 100);
                console.log(disc1x);
                srp = parseFloat(srp) - parseFloat(parseFloat(srp) * parseFloat(disc1x)); 
            }
            if (disc2.val() != '') {
                var disc2 = parseFloat(disc2.val() / 100);
                console.log(disc2);
                srp = parseFloat(srp) - parseFloat(parseFloat(srp) * parseFloat(disc2)); 
            }
            if (plus.val() != '') {
                var plusx = parseFloat(plus.val() / 100);
                console.log(plusx);
                srp = parseFloat(parseFloat(srp) * parseFloat(plusx)) + parseFloat(srp); 
            }
            totalAmt = parseFloat(srp) * parseFloat(qty.val());
        } else {
            totalAmt = 0;
        }
        total_amount.val($.delivery.truncate(totalAmt));
    },

    delivery.prototype.posting_validation = function()
    {   
        z3  = document.querySelector("#deliveryPostingForm");
        t3 = document.querySelector("#deliveryPostingModalSubmit");
        n3 = FormValidation.formValidation(z3, {
            fields: {
                date_delivered: { 
                    validators: { 
                        notEmpty: { 
                            message: "Date Delivered is required" 
                        }
                    } 
                },
                qty_to_post: { 
                    validators: { 
                        notEmpty: { 
                            message: "Quantity is required" 
                        }
                    } 
                }
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
    },

    delivery.prototype.item_detail_validation = function()
    {
        t = document.querySelector("#deliveryLineForm .add-item-btn");
        n = FormValidation.formValidation(document.querySelector("#deliveryLineForm"), {
            fields: {
                plus: { 
                    validators: { 
                        lessThan: { 
                            max: 100,
                            message: "The maximum plus value is 100" 
                        } 
                    } 
                },
                disc1: { 
                    validators: { 
                        lessThan: { 
                            max: 100,
                            message: "The maximum discount 1 value is 100" 
                        } 
                    } 
                },
                disc2: { 
                    validators: { 
                        lessThan: { 
                            max: 100,
                            message: "The maximum discount 2 value is 100" 
                        } 
                    } 
                },
                item_id: { validators: { notEmpty: { message: "Item is required" } } },
                qty: { validators: { notEmpty: { message: "Quantity is required" } } }
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
    },

    delivery.prototype.delivery_detail_validation = function()
    {
        o2 = document.querySelector("#prev-next-btn-holder");
        t2 = o2.querySelector("#next-btn");
        r2 = document.querySelector("#deliveryForm");
        n2 = FormValidation.formValidation(document.querySelector("#deliveryForm"), {
            fields: {
                branch_id: { validators: { notEmpty: { message: "Branch is required" } } },
                customer_id: { validators: { notEmpty: { message: "Customer is required" } } },
                agent_id: { validators: { notEmpty: { message: "Agent is required" } } },
                payment_terms_id: { validators: { notEmpty: { message: "Payment Terms is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        $(r2.querySelector('[name="branch_id"]')).on("change", function () {
            n2.revalidateField("branch_id");
            if ($('#delivery_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/delivery/update/' + $('#delivery_id').val(),
                    data: $('#deliveryForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
        $(r2.querySelector('[name="customer_id"]')).on("change", function () {
            n2.revalidateField("customer_id");
            if ($('#delivery_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/delivery/update/' + $('#delivery_id').val(),
                    data: $('#deliveryForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
        $(r2.querySelector('[name="agent_id"]')).on("change", function () {
            n2.revalidateField("agent_id");
            if ($('#delivery_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/delivery/update/' + $('#delivery_id').val(),
                    data: $('#deliveryForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
        $(r2.querySelector('[name="payment_terms_id"]')).on("change", function () {
            n2.revalidateField("payment_terms_id");
            if ($('#delivery_id').val() !== '') {
                $.ajax({
                    type: 'PUT',
                    url: base_url + 'auth/delivery/update/' + $('#delivery_id').val(),
                    data: $('#deliveryForm').serialize(),
                    success: function(response) {
                        var data = $.parseJSON(response); 
                    },
                });
            }
        });
    },

    delivery.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.delivery.load_contents(1);
        $.delivery.load_delivery_contents(1, $('#delivery_id').val());
        
        $("#date_delivered").flatpickr({
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
            $.delivery.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when deliveryModal keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#deliveryModal .keywords', function (e) {
            var modal = $(this).closest('.modal');
            $.delivery.load_delivery_contents(1, modal.find('#delivery_id').val());
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#deliveryModal', function (e) {
            var modal = $(this);
            modal.find('.modal-header h5').html('New delivery');
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('#next-btn').removeClass('hidden');
            modal.find('#prev-next-btn-holder button.prev').addClass('hidden');
            modal.find('div[data-kt-stepper-element="content"][steps="1"]').addClass('current');
            modal.find('div[data-kt-stepper-element="content"][steps="2"]').removeClass('current');
            modal.find('div.stepper-nav div[steps="1"].stepper-item').addClass('current');
            modal.find('div.stepper-nav div[steps="2"].stepper-item').removeClass('current');
            $.delivery.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when modal is shown
        | ---------------------------------
        */
        this.$body.on('shown.bs.modal', '#deliveryModal', function (e) {
            var modal = $(this);
            $.delivery.load_delivery_contents(1, modal.find('#delivery_id').val());
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var dr     = $(this).closest('tr').attr('data-row-dr');
            var total  = $(this).closest('tr').attr('data-row-amount');
            var modal  = $('#deliveryModal');
            var urlz   = base_url + 'auth/delivery/find/' + id;
            console.log(urlz);
            modal.find('.modal-header h5').html('Edit delivery ('+dr+')');
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        if (k == 'branch_id') {
                            modal.find('#deliveryForm select[name='+k+']').select2().val(v).prop('disabled', true).trigger('change');
                        }
                        modal.find('#deliveryForm input[name='+k+']').val(v);
                        modal.find('#deliveryForm textarea[name='+k+']').val(v);
                        modal.find('#deliveryForm select[name='+k+']').select2().val(v).trigger('change');
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
        this.$body.on('click', '#deliveryTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/deliverys/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the delivery with code ("+ code +") will be removed.",
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
                                    $.delivery.load_contents(1);
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
            $.delivery.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.delivery.load_contents(page);
            }
        });

        /*
        | ---------------------------------
        | # when delivery paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryModal .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            var modal = $('#deliveryModal')
            if (page > 0) {
                $.delivery.load_delivery_contents(page, modal.find('#delivery_id').val());
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
            n2 &&
                n2.validate().then(function (e) {
                    if ("Valid" == e) {
                        if ($('#delivery_id').val() == '') {
                            (t2.setAttribute("data-kt-indicator", "on"),
                            (t2.disabled = !0),
                            setTimeout(function () {
                                t2.removeAttribute("data-kt-indicator"),
                                $.ajax({
                                    type: $('#deliveryForm').attr('method'),
                                    url: $('#deliveryForm').attr('action'),
                                    data: $('#deliveryForm').serialize(),
                                    success: function(response) {
                                        var data = $.parseJSON(response); 
                                        $('#delivery_doc_no').val(data.doc_no);
                                        $('#branch_id').prop('disabled', true).select2().trigger('change');
                                        $('#delivery_id').val(data.delivery_id);
                                        $.delivery.load_delivery_contents(1, data.delivery_id);
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
            var urlz   = base_url + 'auth/delivery/get-delivery-doc-no/' + self.val();
            console.log(urlz);
            if (modal.find('.modal-header h5').text() == 'New Delivery') {
                if (self.val() > 0) {
                    $.ajax({
                        type: 'GET',
                        url: urlz,
                        success: function(response) {
                            console.log(response);
                            modal.find('input[name="delivery_doc_no"]').val(response);
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
        this.$body.on('change', '#customer_id', function (e) {
            var self = $(this);
            var modal = self.closest('.modal');
            var urlz   = base_url + 'auth/delivery/get-customer-info/' + self.val();
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
            var urlz   = base_url + 'auth/delivery/get-item-srp/' + self.val() + '/' + modal.find("#branch_id").val();
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
        | # when deliveryLineForm onkeyup blur
        | ---------------------------------
        */
        this.$body.on('keyup', '#deliveryLineForm #qty, #deliveryLineForm #plus, #deliveryLineForm #disc1, #deliveryLineForm #disc2, #deliveryLineForm #srp_special', function (e) {
            $.delivery.compute($('#deliveryLineForm'));
        });
        this.$body.on('blur', '#deliveryLineForm #qty, #deliveryLineForm #plus, #deliveryLineForm #disc1, #deliveryLineForm #disc2, #deliveryLineForm #srp_special', function (e) {
            $.delivery.compute($('#deliveryLineForm'));
        });

        /*
        | ---------------------------------
        | # when add item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryLineForm .add-item-btn', function (e) {
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
            var modal = $('#deliveryModal');
            var keywords =  $('#delivery_id').val() + '?uom='+ $('#deliveryLineForm #uom').val() +'&srp='+ $('#deliveryLineForm #srp').val() + '&total_amount=' + $('#deliveryLineForm #total_amountx').val(); 
            var $lineID = $('#deliveryLineForm').find('input[name="delivery_line_id"]').val();
            var $url = ($lineID != '') ? base_url + 'auth/delivery/update-line-item/' + $lineID + '?uom='+ $('#deliveryLineForm #uom').val() +'&srp='+ $('#deliveryLineForm #srp').val() + '&total_amount=' + $('#deliveryLineForm #total_amountx').val() : $('#deliveryLineForm').attr('action') + '/' + keywords; 
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
                            data: $('#deliveryLineForm').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $.delivery.load_delivery_contents(1, $('#delivery_id').val());
                                t.removeAttribute("data-kt-indicator");
                                $('#deliveryLineForm')[0].reset();
                                $('#deliveryLineForm select').select2().val('').trigger('change');
                                t.disabled = !1;
                                $('#deliveryModal').find('button.add-item-btn').html('<span class="indicator-label">' +
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
        | # when delivery line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryLineTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var posted = $(this).closest('tr').attr('data-row-posted');
            var urlz   = base_url + 'auth/delivery/remove-line-item/' + id;
            var modal  = $('#deliveryModal');
            console.log(urlz);
            if (posted <= 0) {
                Swal.fire({
                    html: "Are you sure you? <br/>the delivery line item ("+ item +") will be removed.",
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
                                        $.delivery.load_delivery_contents(1, $('#delivery_id').val());
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
        | # when delivery line remove is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryLineTable .post-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var posted = $(this).closest('tr').attr('data-row-posted');
            var quantity = $(this).closest('tr').attr('data-row-qty');
            var urlz   = base_url + 'auth/delivery/find-line-item/' + id;
            var modal  = $('#deliveryPostingModal');
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
        this.$body.on('keyup', '#deliveryPostingModal #qty_to_post', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var onHand = modal.find('input[name="available_qty"]');
            var forPosting = modal.find('input[name="for_posting"]');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(onHand.val())) {
                    var value = self.val();
                    self.val(value.slice(0, (parseFloat(onHand.length) + 1)));
                    postingValidation = 0;
                    self.next().text('There is no available stock on hand');
                } else if (parseFloat(self.val()) > parseFloat(forPosting.val())) {
                    var value = self.val();
                    self.val(value.slice(0, (parseFloat(forPosting.length) + 1)));
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
        this.$body.on('blur', '#deliveryPostingModal #qty_to_post', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var onHand = modal.find('input[name="available_qty"]');
            var forPosting = modal.find('input[name="for_posting"]');
            if (self.val() != '') {
                if (parseFloat(self.val()) > parseFloat(onHand.val())) {
                    var value = self.val();
                    postingValidation = 0;
                    self.next().text('Theres no available stock on hand');
                } else if (parseFloat(self.val()) > parseFloat(forPosting.val())) {
                    var value = self.val();
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
        | # when delivery posting modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#deliveryPostingModal', function (e) {
            var modal = $(this);
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
        });

        /*
        | ---------------------------------
        | # when post item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryPostingModal #deliveryPostingModalSubmit', function (e) {
            e.preventDefault();
            var modal = $(this).closest('.modal');
            var delivery_line_id = modal.find('input[name="delivery_line_id"]').val();
            var delivery_id = modal.find('input[name="delivery_idx"]').val();
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
                        console.log($('#deliveryPostingForm').attr('action') + '/' + delivery_line_id);
                        $.ajax({
                            type: $('#deliveryPostingForm').attr('method'),
                            url: $('#deliveryPostingForm').attr('action') + '/' + delivery_line_id,
                            data: $('#deliveryPostingForm').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $.delivery.load_delivery_contents(1, delivery_id);
                                t.removeAttribute("data-kt-indicator");
                                $('#deliveryPostingForm')[0].reset();
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
            var url = base_url + 'auth/delivery/preview?document=preparation&dr_no=' + $('#delivery_doc_no').val();
            window.open(url, "_blank");
        });

        /*
        | ---------------------------------
        | # when preview posting button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#preview-posting-btn', function (e) {
            e.preventDefault();
            var url = base_url + 'auth/delivery/preview?document=posting&dr_no=' + $('#delivery_doc_no').val();
            window.open(url, "_blank");
        });

        /*
        | ---------------------------------
        | # when edit item button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#deliveryModal .edit-item-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var modal  = $(this).closest('.modal');
            var urlz   = base_url + 'auth/delivery/find-line/' + id;
            console.log(urlz);
            var posted = $(this).closest('tr').attr('data-row-posted');
            if (posted <= 0) {
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        console.log(response);
                        $.each(response.data, function (k, v) {
                            modal.find('#deliveryLineForm input[name='+k+']').val(v);
                            modal.find('#deliveryLineForm textarea[name='+k+']').val(v);
                            modal.find('#deliveryLineForm select[name='+k+']').select2().val(v).trigger('change');
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

    //init delivery
    $.delivery = new delivery, $.delivery.Constructor = delivery

}(window.jQuery),

//initializing delivery
function($) {
    "use strict";
    $.delivery.item_detail_validation();
    $.delivery.delivery_detail_validation();
    $.delivery.posting_validation();
    $.delivery.init();
}(window.jQuery);