"use strict";
var KTModalsuppliersAdd = (function () {
    var t, e, o, n, r, i, z;
    return {
        init: function () {
            (i = new bootstrap.Modal(document.querySelector("#supplierModal"))),
                (r = document.querySelector("#supplier_Form")),
                (t = r.querySelector("#supplierModalSubmit")),
                (e = r.querySelector("#supplierModalCancel")),
                (z = r.querySelector("input[name='method']")),
                (n = FormValidation.formValidation(r, {
                    fields: {
                        code: { validators: { notEmpty: { message: "code is required" } } },
                        name: { validators: { notEmpty: { message: "name is required" } } },
                        payment_terms_id: { validators: { notEmpty: { message: "payment terms is required" } } },
                        contact_person: { validators: { notEmpty: { message: "contact persion is required" } } },
                        contact_no: {
                            validators: {
                                notEmpty: { message: "contact no is required" },
                                numeric: {
                                    message: 'The value is not a valid number'
                                },
                            }
                        }
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r.querySelector('[name="payment_terms_id"]')).on("change", function () {
                    n.revalidateField("payment_terms_id");
                }),
                t.addEventListener("click", function (e) {
                    var formUrl = ($('#supplierModal').find('input[name="method"]').val() == 'add') ? r.getAttribute('action') : base_url + 'auth/components/suppliers/update/' + $('#supplierModal').find('input[name="id"]').val(); 
                    var formMethod = ($('#supplierModal').find('input[name="method"]').val() == 'add') ? 'POST' : 'PUT'; 
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
                                                data: $('#supplier_Form').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    if (data.type == 'success') {
                                                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                                function (e) {
                                                                    i.hide();
                                                                    t.disabled = !1;
                                                                    $.supplier.load_contents(1);
                                                                }
                                                            );
                                                    } else {
                                                        $('#supplierModal').find('input[name="code"]').next().text('This is an existing code.');
                                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                            function (e) {
                                                                t.disabled = !1;
                                                                // e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                $.supplier.load_contents(1);
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
    };
})();
KTUtil.onDOMContentLoaded(function () {
    KTModalsuppliersAdd.init();
});
