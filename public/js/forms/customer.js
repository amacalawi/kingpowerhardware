"use strict";
var KTModalCustomersAdd = (function () {
    var t, e, o, n, r, i, z;
    return {
        init: function () {
            (i = new bootstrap.Modal(document.querySelector("#customerModal"))),
                (r = document.querySelector("#customer_form")),
                (t = r.querySelector("#customerModalSubmit")),
                (e = r.querySelector("#customerModalCancel")),
                (z = r.querySelector("input[name='method']")),
                (n = FormValidation.formValidation(r, {
                    fields: {
                        code: { validators: { notEmpty: { message: "Customer code is required" } } },
                        name: { validators: { notEmpty: { message: "Customer name is required" } } },
                        email: {
                            validators: {
                                emailAddress: {
                                    message: 'The value is not a valid email address',
                                },
                                notEmpty: { message: "Customer email is required" }
                            },
                        },
                        mobile_no: { 
                            validators: { 
                                notEmpty: { message: "Customer mobile no is required" },
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
                        agent_id: { validators: { notEmpty: { message: "Agent is required" } } },
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r.querySelector('[name="agent_id"]')).on("change", function () {
                    n.revalidateField("agent_id");
                }),
                t.addEventListener("click", function (e) {
                    var formUrl = ($('#customerModal').find('input[name="method"]').val() == 'add') ? r.getAttribute('action') : base_url + 'auth/components/customers/update/' + $('#customerModal').find('input[name="id"]').val(); 
                    var formMethod = ($('#customerModal').find('input[name="method"]').val() == 'add') ? 'POST' : 'PUT'; 
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
                                                data: $('#customer_form').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    if (data.type == 'success') {
                                                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                                function (e) {
                                                                    e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                    $.customer.load_contents(1);
                                                                }
                                                            );
                                                    } else {
                                                        $('#customerModal').find('input[name="code"]').next().text('This is an existing code.');
                                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                            function (e) {
                                                                t.disabled = !1;
                                                                // e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                $.customer.load_contents(1);
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
    KTModalCustomersAdd.init();
});
