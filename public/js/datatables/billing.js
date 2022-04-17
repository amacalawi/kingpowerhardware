!function($) {
    "use strict";

    var billing = function() {
        this.$body = $("body");
    };

    var track_page = 1; 
    var t, n;
    var o2, n2, r2, t2;
    var t3, n3, z3, i3, e3, paymentValidation = 0, edit = 0;
    var postingID = [];

    billing.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#billing #keywords').val() + '&perPage=' + $('#billing #perPage').val();
        var urls = base_url + 'auth/billing/all-active';
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

    billing.prototype.load_payment_contents = function(track_page, billingID) 
    {   
        var keywords = '?billing='+ billingID + '&keywords=' + $('#billingModal #search-payment').val() + '&perPage=7';
        var urls = base_url + 'auth/billing/all-active-payment-lines';
        var me = $(this);
        var $portlet = $('#datatable-result3');

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

    billing.prototype.load_billing_contents = function(track_page, billingID) 
    {   
        var keywords = '?billing='+ billingID + '&keywords=' + $('#billingModal #search-billing').val() + '&perPage=7';
        var urls = base_url + 'auth/billing/all-active-billing-lines';
        var me = $(this);
        var $portlet = $('#datatable-result2');

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

    billing.prototype.load_unbilled_contents = function(track_page, billingID) 
    {   
        var keywords = '?billing='+ billingID + '&keywords=' + $('#billingLineModal #search-billing-line').val() + '&perPage=5';
        var urls = base_url + 'auth/billing/all-active-unbilled-lines';
        var me = $(this);
        var $portlet = $('#datatable-result4');

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

        return true;
    },

    billing.prototype.get_invoice_no = function() 
    {
        if($('#branch_id').val() !== '' && $('#invoice_id').val() !== '') {
            var keywords = $('#branch_id').val() + '/' + $('#invoice_id').val();
            var urls = base_url + 'auth/billing/get-invoice-no';
            var me = $(this);

            if ( me.data('requestRunning') ) {
                return;
            }
            
            console.log(urls + '/' + keywords);
            $.ajax({
                type: 'GET',
                url:  urls + '/' + keywords,
                success: function (data) {
                    $('#invoice_no').val(data);
                },
                complete: function() {
                    me.data('requestRunning', false);
                }
            });
        }
    },

    billing.prototype.get_customer_info = function ($customer)
    {   
        var form = $('#billingForm');
        var urls = base_url + 'auth/billing/get-customer-info';
        var me = $(this);

        if ( me.data('requestRunning') ) {
            return;
        }
        
        console.log(urls + '/' + $customer);
        $.ajax({
            type: 'GET',
            url:  urls + '/' + $customer,
            success: function (response) {
                var data = $.parseJSON(response); 
                $.each(data, function (k, v) {
                    form.find('input[name='+k+']').val(v);
                    form.find('textarea[name='+k+']').val(v);
                    form.find('select[name='+k+']').select2().val(v).trigger('change');
                });
            },
            complete: function() {
                me.data('requestRunning', false);
            }
        });
    }

    billing.prototype.get_due_date = function () 
    {
        if($('#payment_terms_id').val() !== '' && $('#invoice_date').val() !== '') {
            var keywords = $('#payment_terms_id').val() + '/' + $('#invoice_date').val();
            var urls = base_url + 'auth/billing/get-due-date';
            var me = $(this);

            if ( me.data('requestRunning') ) {
                return;
            }
            
            console.log(urls + '/' + keywords);
            $.ajax({
                type: 'GET',
                url:  urls + '/' + keywords,
                success: function (data) {
                    $('#due_date').val(data);
                },
                complete: function() {
                    me.data('requestRunning', false);
                }
            });
        }
    }

    billing.prototype.truncate = function(num) {
        var with2Decimals = num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0]
        return with2Decimals
    },

    billing.prototype.compute = function(form) {
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
        total_amount.val($.billing.truncate(totalAmt));
    },

    billing.prototype.invoice_detail_validation = function()
    {   
        t2 = document.querySelector(".save-billing-btn");
        r2 = document.querySelector("#billingForm");
        n2 = FormValidation.formValidation(document.querySelector("#billingForm"), {
            fields: {
                branch_id: { validators: { notEmpty: { message: "Branch is required" } } },
                invoice_id: { validators: { notEmpty: { message: "Invoice Type is required" } } },
                customer_id: { validators: { notEmpty: { message: "Customer is required" } } },
                agent_id: { validators: { notEmpty: { message: "Agent is required" } } },
                payment_terms_id: { validators: { notEmpty: { message: "Payment Terms is required" } } },
                invoice_date: { validators: { notEmpty: { message: "Invoice date is required" } } }
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        $(r2.querySelector('[name="branch_id"]')).on("change", function () {
            n2.revalidateField("branch_id");
        });
        $(r2.querySelector('[name="invoice_id"]')).on("change", function () {
            n2.revalidateField("invoice_id");
        });
        $(r2.querySelector('[name="customer_id"]')).on("change", function () {
            n2.revalidateField("customer_id");
        });
        $(r2.querySelector('[name="agent_id"]')).on("change", function () {
            n2.revalidateField("agent_id");
        });
        $(r2.querySelector('[name="payment_terms_id"]')).on("change", function () {
            n2.revalidateField("payment_terms_id");
        });
    },

    billing.prototype.payment_validation = function()
    {   
        i3 = new bootstrap.Modal(document.querySelector("#paymentLineModal"));
        z3 = document.querySelector("#paymentLine_form");
        t3 = document.querySelector("#paymentLineModalSubmit");
        e3 = document.querySelector("#paymentLineModalCancel")
        n3 = FormValidation.formValidation(z3, {
            fields: {
                payment_type_id: { validators: { notEmpty: { message: "Payment type is required" } } },
                bank_id: { validators: { notEmpty: { message: "Bank is required" } } },
                amount: { validators: { notEmpty: { message: "Amount is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        $(z3.querySelector('[name="payment_type_id"]')).on("change", function () {
            var self = $(this);
            var form = $(this).closest('form');
            n3.revalidateField("payment_type_id");
            if (self.val() == 2) {
                var layer1 = form.find('label[for="cheque_no"]').closest('.col-sm-6');
                form.find('label[for="cheque_no"]').addClass('required').next().prop('disabled', false);
                if (!(layer1.find('div').length > 0)) {
                    layer1.append('<div class="fv-plugins-message-container invalid-feedback">Cheque no is required</div>');
                }
                var layer2 = form.find('label[for="cheque_date"]').closest('.col-sm-6');
                form.find('label[for="cheque_date"]').addClass('required').next().prop('disabled', false);
                if (!(layer2.find('div').length > 0)) {
                    layer2.append('<div class="fv-plugins-message-container invalid-feedback">Cheque date is required</div>');
                }
                paymentValidation = 0;
            } else {
                var layer1 = form.find('label[for="cheque_no"]').closest('.col-sm-6');
                var layer2 = form.find('label[for="cheque_date"]').closest('.col-sm-6');
                form.find('label[for="cheque_no"]').removeClass('required').next().val('').attr('disabled', 'disabled').next().text('');
                form.find('label[for="cheque_date"]').removeClass('required').next().val('').attr('disabled', 'disabled').next().text('');
                layer1.find('div').remove(); layer2.find('div').remove();
                paymentValidation = 1;
            }
        });
        $(z3.querySelector('[name="bank_id"]')).on("change", function () {
            n3.revalidateField("bank_id");
        });
        e3.addEventListener("click", function (t3) {
            t3.preventDefault();
            z3.reset();
            i3.hide();
        });
        $(z3.querySelector('[name="cheque_no"]')).on("blur", function () {
            var self = $(this);
            var form = self.closest('form');
            var chequeDate = form.find('input[name="cheque_date"]');
            var layer1 = form.find('label[for="cheque_no"]').closest('.col-sm-6');
            if (self.val() !== '') {
                layer1.find('div').remove();
            } else {
                layer1.find('div').remove();
                layer1.append('<div class="fv-plugins-message-container invalid-feedback">Cheque no is required</div>');
            }
            if (self.val() !== '' && chequeDate.val() !== '') {
                paymentValidation = 1;
            } else {
                paymentValidation = 0;
            }
        });
        $(z3.querySelector('[name="cheque_date"]')).on("change", function () {
            var self = $(this);
            var form = self.closest('form');
            var chequeNo = form.find('input[name="cheque_no"]');
            var layer2 = form.find('label[for="cheque_date"]').closest('.col-sm-6');
            if (self.val() !== '') {
                layer2.find('div').remove();
            } else {
                layer2.find('div').remove();
                layer2.append('<div class="fv-plugins-message-container invalid-feedback">Cheque no is required</div>');
            }
            if (self.val() !== '' && chequeNo.val() !== '') {
                paymentValidation = 1;
            } else {
                paymentValidation = 0;
            }
        });
    },

    billing.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.billing.load_contents(1);
        $.billing.load_payment_contents(1, $('#billing_id').val());
        $.billing.load_billing_contents(1, $('#billing_id').val());
        
        $("#countered_date, #cheque_date, #invoice_date").flatpickr({
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
            $.billing.load_contents(1);
        });

        /*
        | ---------------------------------
        | # when perPage is changed
        | ---------------------------------
        */
        this.$body.on('change', '#perPage', function (e) {
            $.billing.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.billing.load_contents(page);
            }
        });

        /*
        | ---------------------------------
        | # get invoice no when branch onChange
        | ---------------------------------
        */
        this.$body.on('change', '#branch_id', function (e) {
            if (edit == 0) {
                $.billing.get_invoice_no();
            }
        });
        this.$body.on('change', '#invoice_id', function (e) {
            if (edit == 0) {
                $.billing.get_invoice_no();
            }
        });
        /*
        | ---------------------------------
        | # get customer info when customer onChange
        | ---------------------------------
        */
        this.$body.on('change', '#customer_id', function (e) {
            var self = $(this); 
            if ($(this).val() !== '' && edit == 0) {
                $.billing.get_customer_info($(this).val());
            }
        });

        /*
        | ---------------------------------
        | # get duedate when payment terms & invoice date onChange
        | ---------------------------------
        */
        this.$body.on('change', '#payment_terms_id', function (e) {
            if (edit == 0) {
                $.billing.get_due_date();
            }
        });
        this.$body.on('change', '#invoice_date', function (e) {
            if (edit == 0) {
                $.billing.get_due_date();
            }
        });

        /*
        | ---------------------------------
        | # when save button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.save-billing-btn', function (e) {
            e.preventDefault();
            var self = $(this);
            n2 &&
                n2.validate().then(function (e) {
                    if ("Valid" == e) {
                        if ($('#billing_id').val() == '') {
                            (t2.setAttribute("data-kt-indicator", "on"),
                            (t2.disabled = !0),
                            setTimeout(function () {
                                t2.removeAttribute("data-kt-indicator"),
                                $.ajax({
                                    type: 'POST',
                                    url: $('#billingForm').attr('action') + '?due_date=' + $('#due_date').val(),
                                    data: $('#billingForm').serialize(),
                                    success: function(response) {
                                        var data = $.parseJSON(response); 
                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                            function (e) {
                                                e.isConfirmed && ((t2.disabled = !1));
                                                $('#invoice_id').prop('disabled', true).select2().trigger('change');
                                                $('#branch_id').prop('disabled', true).select2().trigger('change');
                                                $('#billing_id').val(data.billing_id);
                                                $.billing.load_contents(1, data.billing_id);
                                                t2.removeAttribute("data-kt-indicator");
                                                t2.disabled = !1;
                                            }
                                        );
                                    },
                                });
                            }, 2e3))
                        } else {
                            (t2.setAttribute("data-kt-indicator", "on"),
                            (t2.disabled = !0),
                            setTimeout(function () {
                                t2.removeAttribute("data-kt-indicator"),
                                $.ajax({
                                    type: 'PUT',
                                    url: base_url + 'auth/billing/update/' + $('#billing_id').val() + '?customer=' + $('#customer_id').val() + '&due_date=' + $('#due_date').val(),
                                    data: $('#billingForm').serialize(),
                                    success: function(response) {
                                        var data = $.parseJSON(response); 
                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                            function (e) {
                                                e.isConfirmed && ((t2.disabled = !1));
                                                $('#billing_count').val(data.billing_count);
                                                $.billing.load_contents(1, $('#billing_id').val());
                                                t2.removeAttribute("data-kt-indicator");
                                                t2.disabled = !1;
                                            }
                                        );
                                    },
                                });
                            }, 2e3))
                        }
                    }
            });
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#billingTable .edit-btn', function (e) {
            var self    = $(this);
            var id      = $(this).closest('tr').attr('data-row-id');
            var invoice = $(this).closest('tr').attr('data-row-invoice');
            var total   = $(this).closest('tr').attr('data-row-amount');
            var modal   = $('#billingModal');
            var urlz    = base_url + 'auth/billing/find/' + id;
            console.log(urlz);
            edit = 1; self.prop('disabled', true);
            modal.find('.modal-header h5').html('Edit Invoice ('+invoice+')');
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        modal.find('#billingForm input[name='+k+']').val(v);
                        modal.find('#billingForm textarea[name='+k+']').val(v);
                        modal.find('#billingForm select[name='+k+']').select2().val(v).trigger('change');
                        if (k == 'branch_id' || k == 'invoice_id') {
                            modal.find('#billingForm select[name='+k+']').select2().val(v).prop('disabled', true).trigger('change');
                        }
                        if (k == 'billing_amount') {
                            $('#billingTotalAmount').text(v);
                        }
                        if (k == 'billing_paid') {
                            $('#paymentTotalAmount').text(v);
                        }
                    });
                    if (parseFloat(total) > 0) {
                        modal.find('#billingForm select[name="customer_id"]').select2().prop('disabled', true).trigger('change');
                    }
                    $.billing.load_payment_contents(1, $('#billing_id').val());
                    $.billing.load_billing_contents(1, $('#billing_id').val());
                    modal.modal('show');
                    edit = 0;
                },
                complete: function() {
                    window.onkeydown = null;
                    window.onfocus = null;
                    self.prop('disabled', false);
                }
            });
        }); 

        /*
        | ---------------------------------
        | # when billing modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#billingModal', function (e) {
            var modal = $(this);
            modal.find('.modal-header h5').html('New Invoice');
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('#invoice_id').prop('disabled', false).select2().trigger('change');
            modal.find('#branch_id').prop('disabled', false).select2().trigger('change');
            modal.find('#customer_id').prop('disabled', false).select2().trigger('change');
            modal.find('#billingTotalAmount').text('0.00');
            modal.find('#paymentTotalAmount').text('0.00');
            $.billing.load_contents(1);
            $.billing.load_payment_contents(1, $('#billing_id').val());
            $.billing.load_billing_contents(1, $('#billing_id').val());
            edit = 0;
        });

        /*
        | ---------------------------------
        | # when add billing is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#add-billing-btn', function (e) {
            var modal = $('#billingLineModal');
            modal.addClass('bg-shadow');
            if ($('#billing_id').val() !== '') {
                var d1 = $.billing.load_unbilled_contents(1, $('#billing_id').val());
                $.when( d1 ).done(function ( v1 ) 
                {   
                    modal.modal('show');
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    html: "Oops!<br/>Please save an invoice details first.",
                    customClass: { confirmButton: "btn btn-warning", cancelButton: "btn btn-active-light" }
                });
            }
        });
        /*
        | ---------------------------------
        | # when keywords onkeyup on unbilled
        | ---------------------------------
        */
        this.$body.on('keyup', '#search-billing-line', function (e) {
            $.billing.load_unbilled_contents(1, $('#billing_id').val());
        });
        /*
        | ---------------------------------
        | # when paginate is clicked on unbilled
        | ---------------------------------
        */
        this.$body.on('click', '#billingLineModal .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.billing.load_unbilled_contents(page, $('#billing_id').val());
            }
        }); 

        /*
        | ---------------------------------
        | # when unbilled line table is checked value all
        | ---------------------------------
        */
        this.$body.on('click', '#unbilledLineTable input[type="checkbox"][value="all"]', function (e) {
            var self = $(this);
            var _Table = self.closest('table');
            if (self.is(':checked')) {
                _Table.find('input[type="checkbox"]').prop('checked', true);
                $.each(_Table.find('input[type="checkbox"][value!="all"]'), function(){
                    var checkbox = $(this);
                    var postingId = checkbox.val();
                    if (checkbox.is(":checked")) {
                        var found = false;
                        for (var i = 0; i < postingID.length; i++) {
                            if (postingID[i] == postingId) {
                                found == true;
                                return;
                            }
                        } 
                        if (found == false) {
                            postingID.push(postingId);
                        }
                    } 
                });
                console.log(postingID);
            } else {
                _Table.find('input[type="checkbox"]').prop('checked', false);
                $.each(_Table.find("input[type='checkbox'][value!='all']"), function(){
                    var checkbox = $(this);
                    var postingId = checkbox.val();
                    for (var i = 0; i < postingID.length; i++) {
                        if (postingID[i] == postingId) {
                            postingID.splice(i, 1);
                        }
                    }
                });
                console.log(postingID);
            }
        });

        /*
        | ---------------------------------
        | # when unbilled line table is checked value all
        | ---------------------------------
        */
        this.$body.on('click', '#unbilledLineTable input[type="checkbox"][value!="all"]', function (e) {
            var checkbox = $(this);
            var postingId = checkbox.val();
            var found = false;
            if (checkbox.is(":checked")) {
                for (var i = 0; i < postingID.length; i++) {
                    if (postingID[i] == postingId) {
                        found == true;
                    }
                }
                if (found == false) {
                    postingID.push(postingId);
                }
            } else {
                for (var i = 0; i < postingID.length; i++) {
                    if (postingID[i] == postingId) {
                        postingID.splice(i, 1);
                    }
                }
            }
            console.log(postingID);
        });

        /*
        | ---------------------------------
        | # when unbilled line table is checked value all
        | ---------------------------------
        */
        this.$body.on('click', '.attach-billing-btn', function (e) {
            e.preventDefault();
            var d2 = document.querySelector('.attach-billing-btn');
            var modal = $('#billingModal');
            var d3 = $('#billingLineModal');
            if (postingID.length > 0) {
                d2.disabled = !0;
                d2.setAttribute("data-kt-indicator", "on");
                setTimeout(function () {
                    $.ajax({
                        type: 'POST',
                        url: base_url + 'auth/billing/attach/' + $('#billing_id').val(),
                        data: { postingID },
                        success: function(response) {
                            var data = $.parseJSON(response); 
                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                function (e) {
                                    $.billing.load_billing_contents(1, $('#billing_id').val());
                                    d2.removeAttribute("data-kt-indicator");
                                    d2.disabled = !1;
                                    d3.modal('hide');
                                    $('#billingTotalAmount').text(data.totalAmt);
                                    if (parseFloat(data.totalAmt) > 0) { 
                                        modal.find('#billingForm select[name="customer_id"]').select2().prop('disabled', true).trigger('change');
                                    }
                                }
                            );
                        },
                    });
                }, 2e3);
            } else {
                Swal.fire({
                    icon: 'error',
                    html: "Oops!<br/>Please select first a billing to attach.",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" }
                });
            }
        });
        /*
        | ---------------------------------
        | # when billing line modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#billingLineModal', function (e) {
            postingID = [];
        });

        /*
        | ---------------------------------
        | # when billing line remove btn is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#billingLineTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var item   = $(this).closest('tr').attr('data-row-item');
            var urlz   = base_url + 'auth/billing/remove-billing-line/' + id;
            var modal  = $('#billingModal');
            console.log(urlz);
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
                                    modal.find('#billingTotalAmount').text(data.totalAmt);
                                    $.billing.load_billing_contents(1, $('#billing_id').val());
                                    if (!(parseFloat(data.totalAmt)) > 0) { 
                                        modal.find('#billingForm select[name="customer_id"]').select2().prop('disabled', false).trigger('change');
                                    }
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
        | # when add payment is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#add-payment-btn', function (e) {
            var modal = $('#paymentLineModal');
            modal.addClass('bg-shadow');
            if ($('#billing_id').val() !== '') {
                var d1 = $.billing.load_unbilled_contents(1, $('#billing_id').val());
                $.when( d1 ).done(function ( v1 ) 
                {   
                    modal.modal('show');
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    html: "Oops!<br/>Please save an invoice details first.",
                    customClass: { confirmButton: "btn btn-warning", cancelButton: "btn btn-active-light" }
                });
            }
        });

        /*
        | ---------------------------------
        | # when bank on change
        | ---------------------------------
        */
        this.$body.on('change', '#bank_id', function (e) {
            var self = $(this);
            var form = $('#paymentLine_form');
            var urls = base_url + 'auth/billing/get-bank-info/' + self.val();
            
            if (self.val() > 0) {
                console.log(urls);
                $.ajax({
                    type: 'GET',
                    url:  urls,
                    success: function (response) {
                        var data = $.parseJSON(response); 
                        $.each(data, function (k, v) {
                            form.find('input[name='+k+']').val(v);
                            form.find('textarea[name='+k+']').val(v);
                            form.find('select[name='+k+']').select2().val(v).trigger('change');
                        });
                    },
                });
            }
        });

        /*
        | ---------------------------------
        | # when payment line submit btn is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#paymentLineModalSubmit', function (e) {
            e.preventDefault();
            var modal = $(this).closest('.modal');
            var paymentID = modal.find('input[name="payment_id"]').val();
            var billingID = $('#billingModal input[name="billing_id"]').val();
            var keywords = '?cheque_no=' + modal.find('input[name="cheque_no"]').val() + '&cheque_date=' + modal.find('input[name="cheque_date"]').val() + '&bank_no=' + modal.find('input[name="bank_no"]').val() + '&bank_account=' + modal.find('input[name="bank_account"]').val() + '&bank_name=' + modal.find('input[name="bank_name"]').val();
            var form = modal.find('form');
            var formUrl = (paymentID !== '') ? base_url + 'auth/billing/update-payment-line/' + paymentID : base_url + 'auth/billing/store-payment-line/' + billingID;
            var formMethod = (paymentID !== '') ? 'PUT' : 'POST';
            n3 &&
            n3.validate().then(function (e) {
                if ("Valid" == e && paymentValidation > 0) {
                    (t3.setAttribute("data-kt-indicator", "on"),
                    (t3.disabled = !0),
                    setTimeout(function () {
                        t3.removeAttribute("data-kt-indicator"),
                        console.log(formUrl);
                        $.ajax({
                            type: formMethod,
                            url: formUrl + '' + keywords,
                            data: form.serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                    function (e) {
                                        e.isConfirmed && ((t3.disabled = !1));
                                        $.billing.load_payment_contents(1, billingID);
                                        t3.removeAttribute("data-kt-indicator");
                                        z3.reset();
                                        i3.hide();
                                        t3.disabled = !1;
                                        $('#paymentTotalAmount').text(data.totalAmt);
                                    }
                                );
                            },
                        });
                    }, 2e3))
                }
            });
        });

        /*
        | ---------------------------------
        | # when payment line edit btn is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#paymentLineTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var status = $(this).closest('tr').attr('data-row-status');
            var modal  = $('#paymentLineModal');
            var urlz   = base_url + 'auth/billing/find-payment-line/' + id;
            if (status == 'draft') {
                modal.addClass('bg-shadow');
                console.log(urlz);
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        console.log(response);
                        $.each(response.data, function (k, v) {
                            modal.find('input[name='+k+']').val(v);
                            modal.find('select[name='+k+']').select2().val(v).trigger('change');
                            modal.find('textarea[name='+k+']').val(v);
                        });
                        modal.find('.modal-header h2').html('Edit Payment');
                        modal.find('input[name="method"]').val('edit');
                        modal.modal('show');
                    },
                    complete: function() {
                        paymentValidation = 1;
                        window.onkeydown = null;
                        window.onfocus = null;
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    html: "Oops!<br/>Unable to edit, the payment is already been posted.",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" }
                });
            }
        }); 

        /*
        | ---------------------------------
        | # when payment modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#paymentLineModal', function (e) {
            var modal = $(this);
            modal.find('.modal-header h5').html('Add Payment');
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('input[name="cheque_no"]').attr('disabled', 'disabled');
            modal.find('input[name="cheque_date"]').attr('disabled', 'disabled');
            var layer1 = modal.find('label[for="cheque_no"]').closest('.col-sm-6');
            layer1.find('div').remove();
            var layer2 = modal.find('label[for="cheque_date"]').closest('.col-sm-6');
            layer2.find('div').remove();
            paymentValidation = 0;
        });

        /*
        | ---------------------------------
        | # when payment line remove button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#paymentLineTable .remove-btn', function (e) {
            var id        = $(this).closest('tr').attr('data-row-id');
            var amt       = $(this).closest('tr').attr('data-row-amount');
            var billingID = $(this).closest('tr').attr('data-row-billing-id');
            var status    = $(this).closest('tr').attr('data-row-status');
            var urlz      = base_url + 'auth/billing/remove-payment-line/' + id;
            if (status == 'draft') {
                console.log(urlz);
                Swal.fire({
                    html: "Are you sure you? <br/>the payment line with amount ("+ amt +") will be removed.",
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
                                        $.billing.load_payment_contents(1, billingID);
                                        $('#paymentTotalAmount').text(data.totalAmt);
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
                    html: "Oops!<br/>Unable to remove, the payment is already been posted.",
                    customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-active-light" }
                });
            }
        }); 

        /*
        | ---------------------------------
        | # when payment line remove button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#paymentLineTable .post-btn', function (e) {
            var id        = $(this).closest('tr').attr('data-row-id');
            var amt       = $(this).closest('tr').attr('data-row-amount');
            var billingID = $(this).closest('tr').attr('data-row-billing-id');
            var status    = $(this).closest('tr').attr('data-row-status');
            var urlz      = base_url + 'auth/billing/post-payment-line/' + id;
            if (status == 'draft') {
                console.log(urlz);
                Swal.fire({
                    html: "Are you sure you? <br/>the payment line with amount ("+ amt +") will be postd.",
                    icon: "info",
                    showCancelButton: !0,
                    buttonsStyling: !1,
                    confirmButtonText: "Yes, post it!",
                    cancelButtonText: "No, return",
                    customClass: { confirmButton: "btn btn-info", cancelButton: "btn btn-active-light" },
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
                                        $.billing.load_payment_contents(1, billingID);
                                        $('#paymentTotalAmount').text(data.totalAmt);
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
            }
        });
        
        /*
        | ---------------------------------
        | # when keywords onkeyup on billing lne
        | ---------------------------------
        */
        this.$body.on('keyup', '#search-billing', function (e) {
            $.billing.load_billing_contents(1, $('#billing_id').val());
        });
        /*
        | ---------------------------------
        | # when paginate is clicked on billing line
        | ---------------------------------
        */
        this.$body.on('click', '#datatable-result2 .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.billing.load_billing_contents(page, $('#billing_id').val());
            }
        }); 

        /*
        | ---------------------------------
        | # when keywords onkeyup on payment line
        | ---------------------------------
        */
        this.$body.on('keyup', '#search-payment', function (e) {
            $.billing.load_payment_contents(1, $('#billing_id').val());
        });
        /*
        | ---------------------------------
        | # when paginate is clicked on payment line
        | ---------------------------------
        */
        this.$body.on('click', '#datatable-result3 .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.billing.load_payment_contents(page, $('#billing_id').val());
            }
        }); 
    }

    //init billing
    $.billing = new billing, $.billing.Constructor = billing

}(window.jQuery),

//initializing billing
function($) {
    "use strict";
    $.billing.payment_validation();
    $.billing.invoice_detail_validation();
    $.billing.init();
}(window.jQuery);