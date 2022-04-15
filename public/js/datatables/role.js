!function($) {
    "use strict";

    var role = function() {
        this.$body = $("body");
    };

    var track_page = 1;
    var t3, n3, z3, i3, e3;

    role.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#components-roles #keywords').val() + '&perPage=' + $('#components-roles #perPage').val();
        if (segment == 'inactive') {
            var urls = base_url + 'auth/components/roles/all-inactive';
        } else {
            var urls = base_url + 'auth/components/roles/all-active';
        }
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

    role.prototype.datatables = function()
    {
        Dropzone.autoDiscover = false;
        var accept = ".csv";

        $('#import-role-dropzone').dropzone({
            acceptedFiles: accept,
            maxFilesize: 209715200,
            timeout: 0,
            init: function () {
            this.on("processing", function(file) {
                this.options.url = base_url + 'auth/components/roles/import';
                console.log(this.options.url);
            }).on("queuecomplete", function (file, response) {
                // console.log(response);
            }).on("success", function (file, response) {
                console.log(response);
                var data = $.parseJSON(response);
                if (data.message == 'success') {
                    $.role.load_contents(1);
                }
            }).on("totaluploadprogress", function (progress) {
                var progressElement = $("[data-dz-uploadprogress]");
                progressElement.width(progress + '%');
                progressElement.find('.progress-text').text(progress + '%');
            });
            this.on("error", function(file){if (!file.accepted) this.removeFile(file);});            
            }
        });
    },

    role.prototype.role_validation = function()
    {   
        i3 = new bootstrap.Modal(document.querySelector("#roleModal"));
        z3 = document.querySelector("#roleForm");
        t3 = document.querySelector("#roleSubmit");
        e3 = document.querySelector("#roleCancel")
        n3 = FormValidation.formValidation(z3, {
            fields: {
                code: { validators: { notEmpty: { message: "code is required" } } },
                name: { validators: { notEmpty: { message: "name is required" } } }
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        e3.addEventListener("click", function (t3) {
            t3.preventDefault();
            z3.reset();
            i3.hide();
        });
    },

    role.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.role.load_contents(1);

        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#keywords', function (e) {
            $.role.load_contents(1);
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#roleModal', function (e) {
            var modal = $(this);
            modal.find('form')[0].reset();
            modal.find('.modal-header h2').html('Add Role');
            modal.find('.invalid-feedback').text('');
            modal.find('div.accordion-collapse').removeClass('show');
            modal.find('button.accordion-buttons').removeClass('bg-secondary');
            modal.find('span[data-bs-toggle="collapse"]').empty().append('<i class="la fs-2 la-plus"></i>');
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#roleTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var modal  = $('#roleModal');
            var urlz   = base_url + 'auth/components/roles/find/' + id;
            console.log(urlz);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data['roles'], function (k, v) {
                        modal.find('input[name='+k+']').val(v);
                        modal.find('textarea[name='+k+']').val(v);
                        modal.find('select[name='+k+']').select2().val(v).trigger('change');
                    });
                    $.each(response.data['modules'], function (k, v) {
                        var moduleID = 0;
                        $.each(v, function (k2, v2) {
                            if (k2 == 'module_id') {
                                modal.find('input[name="modules[]"][value="' + v2 + '"]').prop("checked", true);
                                moduleID = v2;
                            }
                            if (k2 == 'permissions') {
                                var permission = v2.split(',');
                                $.each(permission, function (z, x) {
                                    if (parseFloat(x) == 1) {
                                        modal.find('input[name="crudx['+moduleID+']['+ z +']"]').prop("checked", true);
                                    }
                                });
                            }
                        });
                    });
                    $.each(response.data['sub_modules'], function (k, v) {
                        var subModuleID = 0;
                        $.each(v, function (k2, v2) {
                            if (k2 == 'sub_module_id') {
                                modal.find('input[name="sub_modules[]"][value="' + v2 + '"]').prop("checked", true);
                                subModuleID = v2;
                            }
                            if (k2 == 'permissions') {
                                var permission = v2.split(',');
                                $.each(permission, function (z, x) {
                                    if (parseFloat(x) == 1) {
                                        modal.find('input[name="crud['+subModuleID+']['+ z +']"]').prop("checked", true);
                                    }
                                });
                            }
                        });
                    });
                    modal.find('.modal-header h2').html('Edit role (<span>' + code + '</span>)');
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
        | # when remove button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#roleTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/roles/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the purchase order type with code ("+ code +")<br/>will be removed.",
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
                                    $.role.load_contents(1);
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
        | # when restore button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#roleTable .restore-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/roles/restore/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the purchase order type with code ("+ code +")<br/>will be restored.",
                icon: "warning",
                showCancelButton: !0,
                buttonsStyling: !1,
                confirmButtonText: "Yes, restore it!",
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
                                    $.role.load_contents(1);
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
            $.role.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.role.load_contents(page);
            }
        }); 
        
        /*
        | ---------------------------------
        | # when uncheck/check all checkbox is tick
        | ---------------------------------
        */
        this.$body.on('click', '.check-roles', function (e){
            var self = $(this);
            var modal = self.closest('.modal');

            if (self.attr('value') == 'checkall') {
                modal.find('input[type="checkbox"]').prop('checked', true);
            } else {
                modal.find('input[type="checkbox"]').prop('checked', false);
            }
        });

        /*
        | ---------------------------------
        | # when module checkbox is tick
        | ---------------------------------
        */
        this.$body.on('click', 'input[type="checkbox"][name="modules[]"]', function (e){
            var self = $(this);
            var submodules = $('input[type="checkbox"][module="' + self.val() + '"]');
            
            if (self.is(":checked")) {
                $.each(submodules, function(){
                    var submodule = $(this);
                    submodule.prop('checked', true);
                    $('input[type="checkbox"][submodule="' + submodule.val() + '"]').prop('checked', true);
                });
            } else {
                $.each(submodules, function(){
                    var submodule = $(this);
                    submodule.prop('checked', false);
                    $('input[type="checkbox"][submodule="' + submodule.val() + '"]').prop('checked', false);
                });
            }
        });

        /*
        | ---------------------------------
        | # when sub module checkbox is tick
        | ---------------------------------
        */
        this.$body.on('click', 'input[type="checkbox"][name="sub_modules[]"]', function (e){
            var self = $(this);

            if (self.is(":checked")) {
                $('input[type="checkbox"][submodule="' + self.val() + '"]').prop('checked', true);
            } else {
                $('input[type="checkbox"][submodule="' + self.val() + '"]').prop('checked', false);
            }
        });
        

        this.$body.on('click', '.toggle-crud', function (e){
            e.preventDefault();
            var $self = $(this);
            var $icon = $self.find('i.la');
            var $parent = $self.closest('.d-flex').next();
                $icon.toggleClass("la-plus la-minus");
                $parent.toggleClass('hidden display');
           
        });

        this.$body.on('click', 'span[data-bs-toggle="collapse"]', function (e){
            var $self = $(this);
            var $icon = $self.find('i.la');
            var $parents = $self.closest('button');
            if ($self.hasClass('collapsed')) {
                $icon.removeClass('la-minus').addClass('la-plus');
                $parents.removeClass('bg-secondary');
            } else {
                $icon.removeClass('la-plus').addClass('la-minus');
                $parents.addClass('bg-secondary');
            }
        });

        /*
        | ---------------------------------
        | # when payment line submit btn is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#roleSubmit', function (e) {
            e.preventDefault();
            var modal = $(this).closest('.modal');
            var form = modal.find('form');
            var roleID = form.find('input[name="role_id"]').val();
            var formUrl = (roleID !== '') ? base_url + 'auth/components/roles/update/' + roleID : base_url + 'auth/components/roles/store';
            var formMethod = (roleID !== '') ? 'PUT' : 'POST';
            n3 &&
            n3.validate().then(function (e) {
                if ("Valid" == e) {
                    (t3.setAttribute("data-kt-indicator", "on"),
                    (t3.disabled = !0),
                    setTimeout(function () {
                        t3.removeAttribute("data-kt-indicator"),
                        console.log(formUrl);
                        $.ajax({
                            type: formMethod,
                            url: formUrl,
                            data: form.serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                    function (e) {
                                        e.isConfirmed && ((t3.disabled = !1));
                                        $.role.load_contents(1);
                                        t3.removeAttribute("data-kt-indicator");
                                        z3.reset();
                                        i3.hide();
                                        t3.disabled = !1;
                                    }
                                );
                            },
                        });
                    }, 2e3))
                }
            });
        });
    }

    //init role
    $.role = new role, $.role.Constructor = role

}(window.jQuery),

//initializing role
function($) {
    "use strict";
    $.role.init();
    $.role.datatables();
    $.role.role_validation();
}(window.jQuery);