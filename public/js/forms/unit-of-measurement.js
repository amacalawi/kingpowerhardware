"use strict";
var KTModalUOMAdd = (function () {
    var t, e, o, n, r, i, z;
    return {
        init: function () {
            (i = new bootstrap.Modal(document.querySelector("#uomModal"))),
                (r = document.querySelector("#uomForm")),
                (t = r.querySelector("#uomModalSubmit")),
                (e = r.querySelector("#uomModalCancel")),
                (z = r.querySelector("input[name='method']")),
                (n = FormValidation.formValidation(r, {
                    fields: {
                        code: { validators: { notEmpty: { message: "uom code is required" } } },
                        name: { validators: { notEmpty: { message: "uom name is required" } } }
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                // $(r.querySelector('[name="agent_id"]')).on("change", function () {
                //     n.revalidateField("agent_id");
                // }),
                t.addEventListener("click", function (e) {
                    var formUrl = ($('#uomModal').find('input[name="method"]').val() == 'add') ? r.getAttribute('action') : base_url + 'auth/components/unit-of-measurements/update/' + $('#uomModal').find('input[name="id"]').val(); 
                    var formMethod = ($('#uomModal').find('input[name="method"]').val() == 'add') ? 'POST' : 'PUT'; 
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
                                                data: $('#uomForm').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    if (data.type == 'success') {
                                                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                                function (e) {
                                                                    e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                    $.uom.load_contents(1);
                                                                }
                                                            );
                                                    } else {
                                                        $('#uomModal').find('input[name="code"]').next().text('This is an existing code.');
                                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                            function (e) {
                                                                t.disabled = !1;
                                                                // e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                $.uom.load_contents(1);
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
    KTModalUOMAdd.init();
});
