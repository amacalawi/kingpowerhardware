!function($) {
    "use strict";

    var purchase_order_type = function() {
        this.$body = $("body");
    };

    var track_page = 1; 

    purchase_order_type.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#components-purchase-order-types #keywords').val() + '&perPage=' + $('#components-purchase-order-types #perPage').val();
        if (segment == 'inactive') {
            var urls = base_url + 'auth/components/purchase-order-types/all-inactive';
        } else {
            var urls = base_url + 'auth/components/purchase-order-types/all-active';
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

    purchase_order_type.prototype.datatables = function()
    {
        Dropzone.autoDiscover = false;
        var accept = ".csv";

        $('#import-purchase-order-type-dropzone').dropzone({
            acceptedFiles: accept,
            maxFilesize: 209715200,
            timeout: 0,
            init: function () {
            this.on("processing", function(file) {
                this.options.url = base_url + 'auth/components/purchase-order-types/import';
                console.log(this.options.url);
            }).on("queuecomplete", function (file, response) {
                // console.log(response);
            }).on("success", function (file, response) {
                console.log(response);
                var data = $.parseJSON(response);
                if (data.message == 'success') {
                    $.purchase_order_type.load_contents(1);
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

    purchase_order_type.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.purchase_order_type.load_contents(1);

        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#keywords', function (e) {
            $.purchase_order_type.load_contents(1);
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#purchaseOrderTypeModal', function (e) {
            var modal = $(this);
            modal.find('form')[0].reset();
            modal.find('.modal-header h2').html('Add a purchase order type');
            modal.find('input[name="method"]').val('add');
            modal.find('.invalid-feedback').text('');
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#purchaseOrderTypeTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var modal  = $('#purchaseOrderTypeModal');
            var urlz   = base_url + 'auth/components/purchase-order-types/find/' + id;
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
                    });
                    modal.find('.modal-header h2').html('Edit purchase order type (<span>' + code + '</span>)');
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
        this.$body.on('click', '#purchaseOrderTypeTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/purchase-order-types/remove/' + id;
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
                                    $.purchase_order_type.load_contents(1);
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
        this.$body.on('click', '#purchaseOrderTypeTable .restore-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/purchase-order-types/restore/' + id;
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
                                    $.purchase_order_type.load_contents(1);
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
            $.purchase_order_type.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.purchase_order_type.load_contents(page);
            }
        }); 

        
    }

    //init purchase_order_type
    $.purchase_order_type = new purchase_order_type, $.purchase_order_type.Constructor = purchase_order_type

}(window.jQuery),

//initializing purchase_order_type
function($) {
    "use strict";
    $.purchase_order_type.init();
    $.purchase_order_type.datatables();
}(window.jQuery);