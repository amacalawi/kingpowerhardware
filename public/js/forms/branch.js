"use strict";
var KTModalbranchsAdd = (function () {
    var t, e, o, n, r, i, z;
    return {
        init: function () {
            (i = new bootstrap.Modal(document.querySelector("#branchModal"))),
                (r = document.querySelector("#branch_form")),
                (t = r.querySelector("#branchModalSubmit")),
                (e = r.querySelector("#branchModalCancel")),
                (z = r.querySelector("input[name='method']")),
                (n = FormValidation.formValidation(r, {
                    fields: {
                        code: { validators: { notEmpty: { message: "branch code is required" } } },
                        name: { validators: { notEmpty: { message: "branch name is required" } } },
                        activation_code: { validators: { notEmpty: { message: "branch activation code is required" } } },
                        agent_id: { validators: { notEmpty: { message: "Agent is required" } } },
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r.querySelector('[name="agent_id"]')).on("change", function () {
                    n.revalidateField("agent_id");
                }),
                t.addEventListener("click", function (e) {
                    var checkboxes = $('#branchModal').find('input[type="checkbox"]').is(":checked") ? 1 : 0;
                    var formUrl = ($('#branchModal').find('input[name="method"]').val() == 'add') ? r.getAttribute('action') + '?is_srp=' + checkboxes : base_url + 'auth/components/branches/update/' + $('#branchModal').find('input[name="id"]').val() + '?is_srp=' + checkboxes 
                    var formMethod = ($('#branchModal').find('input[name="method"]').val() == 'add') ? 'POST' : 'PUT'; 
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
                                                data: $('#branch_form').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    if (data.type == 'success') {
                                                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                                function (e) {
                                                                    e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                    $.branch.load_contents(1);
                                                                }
                                                            );
                                                    } else {
                                                        if (data.field == 'code') {
                                                            $('#branchModal').find('input[name="code"]').next().text('This is an existing code.');
                                                        } else {
                                                            $('#branchModal').find('textarea[name="activation_code"]').next().text('This is an invalid activation code.');
                                                        }
                                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                            function (e) {
                                                                t.disabled = !1;
                                                                // e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                $.branch.load_contents(1);
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
    KTModalbranchsAdd.init();
});
