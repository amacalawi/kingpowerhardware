!function($) {
    "use strict";

    var user = function() {
        this.$body = $("body");
    };

    var track_page = 1; 

    user.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#components-users #keywords').val() + '&perPage=' + $('#components-users #perPage').val();
        if (segment == 'inactive') {
            var urls = base_url + 'auth/components/users/all-inactive';
        } else {
            var urls = base_url + 'auth/components/users/all-active';
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

    user.prototype.datatables = function()
    {
        Dropzone.autoDiscover = false;
        var accept = ".csv";

        $('#import-user-dropzone').dropzone({
            acceptedFiles: accept,
            maxFilesize: 209715200,
            timeout: 0,
            init: function () {
            this.on("processing", function(file) {
                this.options.url = base_url + 'auth/components/users/import';
                console.log(this.options.url);
            }).on("queuecomplete", function (file, response) {
                // console.log(response);
            }).on("success", function (file, response) {
                console.log(response);
                var data = $.parseJSON(response);
                if (data.message == 'success') {
                    $.user.load_contents(1);
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

    user.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.user.load_contents(1);

        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#keywords', function (e) {
            $.user.load_contents(1);
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#userModal', function (e) {
            var modal = $(this);
            modal.find('form')[0].reset();
            modal.find('.modal-header h2').html('Add a user');
            modal.find('input[name="method"]').val('add');
            modal.find('.invalid-feedback').text('');
            modal.find('select').select2().val('').trigger('change');
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#userTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-user');
            var modal  = $('#userModal');
            var urlz   = base_url + 'auth/components/users/find/' + id;
            console.log(urlz);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        modal.find('input[name='+k+']').val(v);
                        modal.find('textarea[name='+k+']').val(v);
                        modal.find('select[name='+k+']').select2().val(v).trigger('change');
                        if (k == 'assignment') {
                            modal.find('select[name="assignment[]"]').select2().val(v).trigger('change');
                        }
                    });
                    modal.find('.modal-header h2').html('Edit user (<span>' + code + '</span>)');
                    modal.find('input[name="method"]').val('edit');
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
        this.$body.on('click', '#userTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-user');
            var urlz   = base_url + 'auth/components/users/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the user with username ("+ code +")<br/>will be removed.",
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
                                    $.user.load_contents(1);
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
        this.$body.on('click', '#userTable .restore-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-user');
            var urlz   = base_url + 'auth/components/users/restore/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the user with user ("+ code +")<br/>will be restored.",
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
                                    $.user.load_contents(1);
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
            $.user.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.user.load_contents(page);
            }
        });
        
        this.$body.on('click', 'label[for="password"] span', function (e) {
            e.preventDefault();
            var self = $(this);
            var formGroup = self.closest('.col-xl-6');
            var icon = formGroup.find('i');
            var input = formGroup.find('input');
            icon.toggleClass("la-eye la-eye-slash");
            if (input.attr('type') == 'password') {
                input.attr('type', 'text');
            } else {
                input.attr('type', 'password');
            }
        });

        
    }

    //init user
    $.user = new user, $.user.Constructor = user

}(window.jQuery),

//initializing user
function($) {
    "use strict";
    $.user.init();
    $.user.datatables();
}(window.jQuery);