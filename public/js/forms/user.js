"use strict";
var KTModalusersAdd = (function () {
    var t, e, o, n, r, i, z;
    return {
        init: function () {
            (i = new bootstrap.Modal(document.querySelector("#userModal"))),
                (r = document.querySelector("#user_Form")),
                (t = r.querySelector("#userModalSubmit")),
                (e = r.querySelector("#userModalCancel")),
                (z = r.querySelector("input[name='method']")),
                (n = FormValidation.formValidation(r, {
                    fields: {
                        name: { validators: { notEmpty: { message: "name is required" } } },
                        type: { validators: { notEmpty: { message: "role is required" } } },
                        assignment: { validators: { notEmpty: { message: "assignment is required" } } },
                        username: { validators: { notEmpty: { message: "username is required" } } },
                        email: { 
                            validators: { 
                                emailAddress: {
                                    message: 'The value is not a valid email address',
                                },
                                notEmpty: { 
                                    message: "email is required" 
                                } 
                            } 
                        },
                        password: { validators: { notEmpty: { message: "password is required" } } },
                        confirm_password: { 
                            validators: { 
                                identical: {
                                    compare: function () {
                                        return r.querySelector('[name="password"]').value;
                                    },
                                    message: 'password does not matched',
                                },
                            } 
                        },
                        secret_question_id: { validators: { notEmpty: { message: "secret question is required" } } },
                        secret_password: { validators: { notEmpty: { message: "secret password is required" } } },
                    },
                    plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
                })),
                $(r.querySelector('[name="type"]')).on("change", function () {
                    n.revalidateField("type");
                }),
                $(r.querySelector('[name="assignment"]')).on("change", function () {
                    n.revalidateField("assignment");
                }),
                $(r.querySelector('[name="secret_question_id"]')).on("change", function () {
                    n.revalidateField("secret_question_id");
                }),
                t.addEventListener("click", function (e) {
                    var formUrl = ($('#userModal').find('input[name="method"]').val() == 'add') ? r.getAttribute('action') : base_url + 'auth/components/users/update/' + $('#userModal').find('input[name="id"]').val(); 
                    var formMethod = ($('#userModal').find('input[name="method"]').val() == 'add') ? 'POST' : 'PUT'; 
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
                                                data: $('#user_Form').serialize(),
                                                success: function(response) {
                                                    var data = $.parseJSON(response);   
                                                    console.log(data);
                                                    if (data.type == 'success') {
                                                            Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                                function (e) {
                                                                    i.hide();
                                                                    t.disabled = !1;
                                                                    $.user.load_contents(1);
                                                                }
                                                            );
                                                    } else {
                                                        $('#userModal').find('input[name="'+ data.field +'"]').next().text(data.text);
                                                        Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                                            function (e) {
                                                                t.disabled = !1;
                                                                // e.isConfirmed && (i.hide(), (t.disabled = !1));
                                                                $.user.load_contents(1);
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
    KTModalusersAdd.init();
});
