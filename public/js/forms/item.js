"use strict";
var basedQuantity = 0, validation = 0;
var KTModalitemsAdd = (function () {
    var t, e, n, r, i, z; 
    var t2, e2, n2, r2, i2, z2; 
    var t3, e3, n3, r3, i3, z3; 
    return {
        init: function () {
            (i = new bootstrap.Modal(document.querySelector("#itemModal"))),
                (r = document.querySelector("#item_form")),
                (t = r.querySelector("#itemModalSubmit")),
                (e = r.querySelector("#itemModalCancel")),
                (z = r.querySelector("input[name='method']")),
                (n = FormValidation.formValidation(r, {
                    fields: {
                        name: { validators: { notEmpty: { message: "item name is required" } } },
                        reorder_level: {
                            validators: {
                                numeric: {
                                    message: 'The value is not a valid number'
                                },
                            },
                        },
                        srp: { 
                            validators: { 
                                numeric: {
                                    message: 'The value is not a valid number'
                                },
                            } 
                        },
                        item_category_id: { validators: { notEmpty: { message: "Product Category is required" } } },
                        uom_id: { validators: { notEmpty: { message: "UOM is required" } } },
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r.querySelector('[name="uom_id"]')).on("change", function () {
                    n.revalidateField("uom_id");
                }),
                $(r.querySelector('[name="item_category_id"]')).on("change", function () {
                    n.revalidateField("item_category_id");
                }),
                t.addEventListener("click", function (e) {
                    var formUrl = ($('#itemModal').find('input[name="method"]').val() == 'add') ? r.getAttribute('action') : base_url + 'auth/components/items/update/' + $('#itemModal').find('input[name="id"]').val(); 
                    var formMethod = ($('#itemModal').find('input[name="method"]').val() == 'add') ? 'POST' : 'PUT'; 
                    e.preventDefault(),
                        n &&
                            n.validate().then(function (e) {
                                console.log("validated!"),
                                    "Valid" == e
                                        ? (t.setAttribute("data-kt-indicator", "on"),
                                          (t.disabled = !0),
                                          setTimeout(function () {
                                            t.removeAttribute("data-kt-indicator"),
                                            console.log(formUrl);
                                            $.ajax({
                                                type: formMethod,
                                                url: formUrl,
                                                data: $('#item_form').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    if (data.type == 'success') {
                                                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                                function (e) {
                                                                    e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                    $.item.load_contents(1);
                                                                }
                                                            );
                                                    } else {
                                                        $('#itemModal').find('input[name="code"]').next().text('This is an existing code.');
                                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                            function (e) {
                                                                t.disabled = !1;
                                                                // e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                $.item.load_contents(1);
                                                            }
                                                        );
                                                    }
                                                }, 
                                                complete: function() {
                                                    window.onkeydown = null;
                                                    window.onfocus = null;
                                                }
                                            });

                                          }, 2e3))
                                        : Swal.fire({
                                              text: "Sorry, looks like there are some errors detected, please try again.",
                                              icon: "error",
                                              buttonsStyling: !1,
                                              confirmButtonText: "Ok, got it!",
                                              customClass: { confirmButton: "btn btn-primary" },
                                          });
                            });
                }),
                e.addEventListener("click", function (t) {
                    var formMethod = (z.value == 'add') ? 1 : 0; 
                    t.preventDefault(),
                        (formMethod > 0) ?
                            Swal.fire({
                                text: "Are you sure you would like to cancel?",
                                icon: "warning",
                                showCancelButton: !0,
                                buttonsStyling: !1,
                                confirmButtonText: "Yes, cancel it!",
                                cancelButtonText: "No, return",
                                customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-active-light" },
                            }).then(function (t) {
                                t.value
                                    ? (r.reset(), i.hide())
                                    : "cancel" === t.dismiss && Swal.fire({ text: "Your form has not been cancelled!.", icon: "error", buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } });
                            })
                        : i.hide();
                        
                });
        },
        init2: function () {
            (i2 = new bootstrap.Modal(document.querySelector("#itemWithdrawalModal"))),
                (r2 = document.querySelector("#itemWithdrawalForm")),
                (t2 = r2.querySelector("#itemWithdrawalModalSubmit")),
                (e2 = r2.querySelector("#itemWithdrawalModalCancel")),
                (n2 = FormValidation.formValidation(r2, {
                    fields: {
                        transaction: { validators: { notEmpty: { message: "Transaction is required" } } },
                        branch_id: { validators: { notEmpty: { message: "Branch is required" } } },
                        issued_by: { validators: { notEmpty: { message: "Issued By is required" } } },
                        received_by: { validators: { notEmpty: { message: "Received By is required" } } },
                        issued_quantity: { 
                            validators: { 
                                notEmpty: { message: "Quantity is required" },
                                numeric: {
                                    message: 'The value is not a valid number'
                                }
                            } 
                        },
                        remarks: { validators: { notEmpty: { message: "Remarks is required" } } },
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r2.querySelector('[name="issued_by"]')).on("change", function () {
                    n2.revalidateField("issued_by");
                }),
                $(r2.querySelector('[name="received_by"]')).on("change", function () {
                    n2.revalidateField("received_by");
                }),
                $(r2.querySelector('[name="branch_id"]')).on("change", function () {
                    n2.revalidateField("branch_id");
                    var self = $(this);
                    var itemId = $('#itemWithdrawalModal').find('.item-id').text(); 
                    var urlz = base_url + 'auth/components/items/find-item-quantity/' + itemId + '/' + self.val();
                    console.log(urlz);
                    if (self.val() > 0) {
                        $.ajax({
                            type: 'GET',
                            url: urlz,
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $('#itemWithdrawalModal').find('#based_quantity').val(data.quantity);
                                $('#itemWithdrawalModal').find('#srp').val(data.srp);
                                basedQuantity = parseFloat(data.quantity);
                                console.log(basedQuantity);
                                if ($('#itemWithdrawalModal input[name="issued_quantity"]').val() != '') {
                                    if ($('#itemWithdrawalModal input[name="issued_quantity"]').val() > basedQuantity) {
                                        $('#itemWithdrawalModal input[name="issued_quantity"], #itemWithdrawalModal input[name="total_amount"]').val('');
                                    } else {
                                        var total = parseFloat($('#itemWithdrawalModal input[name="issued_quantity"]').val()) * parseFloat($('#itemWithdrawalModal input[name="srp"]').val());
                                        $('#itemWithdrawalModal input[name="total_amount"]').val(total)
                                    }
                                }
                            }, 
                            complete: function() {
                                window.onkeydown = null;
                                window.onfocus = null;
                            }
                        });
                    }
                }),
                $(r2.querySelector('[name="issued_quantity"]')).on("keyup", function () {
                    var self = $(this);
                    if ($.trim(self.val()).length > 0) {
                        console.log(basedQuantity);
                        if (self.val() == 0) {
                            self.next().text('The minimum quantity must be higher or equal to 1');
                            validation = 0;
                        } else if (self.val() > basedQuantity) {
                            self.next().text('The quantity must not be higher than based quantity');
                            validation = 0;
                        } else {
                            self.next().text('');
                            validation = 1;
                        }
                        console.log($('#itemWithdrawalModal input[name="srp"]').val());
                        $('#itemWithdrawalModal input[name="total_amount"]').val(parseFloat(self.val()) * parseFloat($('#itemWithdrawalModal input[name="srp"]').val()));
                    } else {
                        self.next().text('');
                        validation = 1;
                    }
                }),
                $(r2.querySelector('[name="issued_quantity"]')).on("blur", function () {
                    var self = $(this);
                    if ($.trim(self.val()).length > 0) {
                        console.log(basedQuantity);
                        if (self.val() == 0) {
                            self.next().text('The minimum quantity must be higher or equal to 1');
                            validation = 0;
                        } else if (self.val() > basedQuantity) {
                            self.next().text('The quantity must not be higher than based quantity');
                            validation = 0;
                        } else {
                            self.next().text('');
                            validation = 1;
                        }
                        console.log($('#itemWithdrawalModal input[name="srp"]').val());
                        $('#itemWithdrawalModal input[name="total_amount"]').val(parseFloat(self.val()) * parseFloat($('#itemWithdrawalModal input[name="srp"]').val()));
                    } else {
                        self.next().text('');
                        validation = 1;
                    }
                }),
                $(r2.querySelector('[name="transaction"]')).on("change", function () {
                    var self  = $(this);
                    var layer = self.closest('.fv-row').next();
                    var inputs = layer.find('select');
                    if (self.val() == 'Transfer Item') {
                        layer.removeClass('hidden');
                        if (inputs.val() == '') {
                            layer.append('<div class="fv-plugins-message-container invalid-feedback">Transfer to is required</div>');
                            validation = 0;
                        }
                    } else {
                        layer.addClass('hidden');
                        layer.find('.fv-plugins-message-container').remove();
                        validation = 1;
                    }
                }),
                $(r2.querySelector('[name="transfer_to"]')).on("change", function () {
                    var self  = $(this);
                    var branch = $('#itemWithdrawalModal [name="branch_id"]').val();
                    var layer  = self.closest('.fv-row');
                    if (self.val() > 0) {
                        if (self.val() == branch) {
                            layer.find('.fv-plugins-message-container').text('Please select another branch');
                            validation = 0;
                        } else {
                            layer.find('.fv-plugins-message-container').text('');
                            validation = 1;
                        }
                    } else {
                        layer.find('.fv-plugins-message-container').text('Transfer to is required');
                        validation = 0;
                    }
                }),
                t2.addEventListener("click", function (e) {
                   e.preventDefault(),
                        n2 &&
                            n2.validate().then(function (e) {
                                console.log("validated!"),
                                    ("Valid" == e && validation > 0)
                                        ? (t2.setAttribute("data-kt-indicator", "on"),
                                          (t2.disabled = !0),
                                          setTimeout(function () {
                                            t2.removeAttribute("data-kt-indicator"),
                                            z2 = '?itemId=' + $('#itemWithdrawalForm').find('.item-id').text() + '&srp=' + $('#itemWithdrawalForm input[name="srp"]').val() + '&based_quantity=' + $('#itemWithdrawalForm input[name="based_quantity"]').val() + '&total_amount=' + $('#itemWithdrawalForm input[name="total_amount"]').val();
                                            console.log($('#itemWithdrawalForm').attr('action'));
                                            $.ajax({
                                                type: r2.getAttribute('method'),
                                                url: r2.getAttribute('action') + '' + z2,
                                                data: $('#itemWithdrawalForm').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                        function (e) {
                                                            e.isConfirmed && (i2.hide(), (t2.disabled = !1));
                                                            $.item.load_contents(1);
                                                        }
                                                    );
                                                }, 
                                                complete: function() {
                                                    window.onkeydown = null;
                                                    window.onfocus = null;
                                                }
                                            });
                                          }, 2e3))
                                        : Swal.fire({
                                              text: "Sorry, looks like there are some errors detected, please try again.",
                                              icon: "error",
                                              buttonsStyling: !1,
                                              confirmButtonText: "Ok, got it!",
                                              customClass: { confirmButton: "btn btn-primary" },
                                          });
                            });
                }),
                e2.addEventListener("click", function (t) {
                    t.preventDefault(),
                    i2.hide(),
                    validation = 0, 
                    basedQuantity = 0
                });
        },
        init3: function () {
            (i3 = new bootstrap.Modal(document.querySelector("#itemReceivingModal"))),
                (r3 = document.querySelector("#itemReceivingForm")),
                (t3 = r3.querySelector("#itemReceivingModalSubmit")),
                (e3 = r3.querySelector("#itemReceivingModalCancel")),
                (n3 = FormValidation.formValidation(r3, {
                    fields: {
                        transaction: { validators: { notEmpty: { message: "Transaction is required" } } },
                        branch_id: { validators: { notEmpty: { message: "Branch is required" } } },
                        issued_by: { validators: { notEmpty: { message: "Issued By is required" } } },
                        received_by: { validators: { notEmpty: { message: "Received By is required" } } },
                        issued_quantity: { 
                            validators: { 
                                notEmpty: { message: "Quantity is required" },
                                numeric: {
                                    message: 'The value is not a valid number'
                                }
                            } 
                        },
                        remarks: { validators: { notEmpty: { message: "Remarks is required" } } },
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r3.querySelector('[name="issued_by"]')).on("change", function () {
                    n3.revalidateField("issued_by");
                }),
                $(r3.querySelector('[name="received_by"]')).on("change", function () {
                    n3.revalidateField("received_by");
                }),
                $(r3.querySelector('[name="issued_quantity"]')).on("keyup", function () {
                    var self = $(this);
                    if ($.trim(self.val()).length > 0) {
                        console.log(basedQuantity);
                        $('#itemReceivingModal input[name="total_amount"]').val(parseFloat(self.val()) * parseFloat($('#itemReceivingModal input[name="srp"]').val()));
                    } else {
                        $('#itemReceivingModal input[name="total_amount"]').val('');
                    }
                }),
                $(r3.querySelector('[name="issued_quantity"]')).on("blur", function () {
                    var self = $(this);
                    if ($.trim(self.val()).length > 0) {
                        console.log(basedQuantity);
                        $('#itemReceivingModal input[name="total_amount"]').val(parseFloat(self.val()) * parseFloat($('#itemReceivingModal input[name="srp"]').val()));
                    } else {
                        $('#itemReceivingModal input[name="total_amount"]').val('');
                    }
                }),
                $(r3.querySelector('[name="branch_id"]')).on("change", function () {
                    n3.revalidateField("branch_id");
                    var self = $(this);
                    var itemId = $('#itemReceivingModal').find('.item-id').text(); 
                    var urlz = base_url + 'auth/components/items/find-item-quantity/' + itemId + '/' + self.val();
                    console.log(urlz);
                    if (self.val() > 0) {
                        $.ajax({
                            type: 'GET',
                            url: urlz,
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                $('#itemReceivingModal').find('#based_quantity').val(data.quantity);
                                $('#itemReceivingModal').find('#srp').val(data.srp);
                                basedQuantity = parseFloat(data.quantity);
                                console.log(basedQuantity);
                                if ($('#itemReceivingModal input[name="issued_quantity"]').val() != '') {
                                    if ($('#itemReceivingModal input[name="issued_quantity"]').val() > basedQuantity) {
                                        $('#itemReceivingModal input[name="issued_quantity"], #itemReceivingModal input[name="total_amount"]').val('');
                                    } else {
                                        var total = parseFloat($('#itemReceivingModal input[name="issued_quantity"]').val()) * parseFloat($('#itemReceivingModal input[name="srp"]').val());
                                        $('#itemReceivingModal input[name="total_amount"]').val(total)
                                    }
                                }
                            }, 
                            complete: function() {
                                window.onkeydown = null;
                                window.onfocus = null;
                            }
                        });
                    }
                }),
                $(r3.querySelector('[name="transaction"]')).on("change", function () {
                    n3.revalidateField("transaction");
                }),
                t3.addEventListener("click", function (e) {
                   e.preventDefault(),
                        n3 &&
                            n3.validate().then(function (e) {
                                console.log("validated!"),
                                    "Valid" == e
                                        ? (t3.setAttribute("data-kt-indicator", "on"),
                                          (t3.disabled = !0),
                                          setTimeout(function () {
                                            t3.removeAttribute("data-kt-indicator"),
                                            z3 = '?itemId=' + $('#itemReceivingForm').find('.item-id').text() + '&srp=' + $('#itemReceivingForm input[name="srp"]').val() + '&based_quantity=' + $('#itemReceivingForm input[name="based_quantity"]').val() + '&total_amount=' + $('#itemReceivingForm input[name="total_amount"]').val();
                                            console.log($('#itemReceivingForm').attr('action') + '' + z3);
                                            $.ajax({
                                                type: r3.getAttribute('method'),
                                                url: r3.getAttribute('action') + '' + z3,
                                                data: $('#itemReceivingForm').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                        function (e) {
                                                            e.isConfirmed && (i3.hide(), (t3.disabled = !1));
                                                            $.item.load_contents(1);
                                                        }
                                                    );
                                                }, 
                                                complete: function() {
                                                    window.onkeydown = null;
                                                    window.onfocus = null;
                                                }
                                            });
                                          }, 2e3))
                                        : Swal.fire({
                                              text: "Sorry, looks like there are some errors detected, please try again.",
                                              icon: "error",
                                              buttonsStyling: !1,
                                              confirmButtonText: "Ok, got it!",
                                              customClass: { confirmButton: "btn btn-primary" },
                                          });
                            });
                }),
                e3.addEventListener("click", function (t) {
                    t.preventDefault(),
                    i3.hide(),
                    basedQuantity = 0
                });
        },
    };
})();
KTUtil.onDOMContentLoaded(function () {
    KTModalitemsAdd.init();
    KTModalitemsAdd.init2();
    KTModalitemsAdd.init3();
});
