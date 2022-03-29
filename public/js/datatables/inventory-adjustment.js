!function($) {
    "use strict";

    var inventory_adjustment = function() {
        this.$body = $("body");
    };

    var track_page = 1;
    var i2, n2, r2, t2, e2, validated = 0;

    inventory_adjustment.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#items-inventory-adjustment #keywords').val() + '&perPage=' + $('#items-inventory-adjustment #perPage').val();
        if (segment == 'inactive') {
            var urls = base_url + 'auth/items/inventory-adjustment/all-inactive';
        } else {
            var urls = base_url + 'auth/items/inventory-adjustment/all-active';
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

    inventory_adjustment.prototype.datatables = function()
    {
        Dropzone.autoDiscover = false;
        var accept = ".csv";

        $('#import-purchase-order-type-dropzone').dropzone({
            acceptedFiles: accept,
            maxFilesize: 209715200,
            timeout: 0,
            init: function () {
            this.on("processing", function(file) {
                this.options.url = base_url + 'auth/items/inventory-adjustment/import';
                console.log(this.options.url);
            }).on("queuecomplete", function (file, response) {
                // console.log(response);
            }).on("success", function (file, response) {
                console.log(response);
                var data = $.parseJSON(response);
                if (data.message == 'success') {
                    $.inventory_adjustment.load_contents(1);
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

    inventory_adjustment.prototype.get_item_info = function(modal)
    {   
        var item = modal.find('#item_id').val();
        var branch = modal.find('#branch_id').val();
        if (item != '' && branch != '') {
        var urlz = base_url + 'auth/items/inventory-adjustment/get-item-info/' + item + '/' + branch ;
        console.log(urlz);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    var data = $.parseJSON(response);
                    $.each(data, function (k, v) {
                        modal.find('input[name='+k+']').val(v);
                        modal.find('textarea[name='+k+']').val(v);
                        modal.find('select[name='+k+']').select2().val(v).trigger('change');
                    });
                },
                complete: function() {
                    window.onkeydown = null;
                    window.onfocus = null;
                }
            });
        }
    },

    inventory_adjustment.prototype.validation = function()
    {   
        i2 = new bootstrap.Modal(document.querySelector("#inventoryAdjustmentModal"));
        r2 = document.querySelector("#inventoryAdjustment_Form");
        t2 = r2.querySelector("#inventoryAdjustmentModalSubmit");
        e2 = r2.querySelector("#inventoryAdjustmentModalCancel");
        n2 = FormValidation.formValidation(document.querySelector("#inventoryAdjustment_Form"), {
            fields: {
                category: { validators: { notEmpty: { message: "category is required" } } },
                item_id: { validators: { notEmpty: { message: "item is required" } } },
                branch_id: { validators: { notEmpty: { message: "branch is required" } } },
                quantity: { validators: { notEmpty: { message: "quantity is required" } } },
                remarks: { validators: { notEmpty: { message: "remarks is required" } } },
            },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: ".fv-row", eleInvalidClass: "", eleValidClass: "" }) },
        });
        $(r2.querySelector('[name="category"]')).on("change", function () {
            n2.revalidateField("category");
            document.querySelector('#quantity').value = '';
        });
        $(r2.querySelector('[name="item_id"]')).on("change", function () {
            n2.revalidateField("item_id");
        });
        $(r2.querySelector('[name="branch_id"]')).on("change", function () {
            n2.revalidateField("branch_id");
        });
    },

    inventory_adjustment.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.inventory_adjustment.load_contents(1);
        
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
            $.inventory_adjustment.load_contents(1);
        });

        /*
        | ---------------------------------
        | # branch and item onchange
        | ---------------------------------
        */
        this.$body.on('change', '#branch_id, #item_id', function (e) {
            var modal = $(this).closest('.modal');
            modal.find('input[name="quantity"]').val('');
            $.inventory_adjustment.get_item_info(modal);
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#inventoryAdjustmentModal', function (e) {
            var modal = $(this);
            modal.find('form')[0].reset();
            modal.find('input').val('');
            modal.find('textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('.invalid-feedback').text('');
            validated = 0;
        });

        /*
        | ---------------------------------
        | # when qty to post onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#inventoryAdjustmentModal #quantity', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var onHand = modal.find('input[name="based_quantity"]');
            var category = modal.find('select[name="category"]');
            if (self.val() != '') {
                if (category.val() == 'Deduction Inventory') {
                    if (parseFloat(self.val()) > parseFloat(onHand.val())) {
                        validated = 0;
                        self.next().text('The quantity should not be higher than (based quantity)');
                    } else {
                        validated = 1;
                        self.next().text('');
                    }
                } else {
                    validated = 1;
                    self.next().text('');
                }
            } else {
                validated = 0;
                self.next().text('');
            }
        });
        this.$body.on('blur', '#inventoryAdjustmentModal #quantity', function (e) {
            var self = $(this);
            var modal = $(this).closest('.modal');
            var onHand = modal.find('input[name="based_quantity"]');
            var category = modal.find('select[name="category"]');
            if (self.val() != '') {
                if (category.val() == 'Deduction Inventory') {
                    if (parseFloat(self.val()) > parseFloat(onHand.val())) {
                        validated = 0;
                        self.next().text('The quantity should not be higher than (based quantity)');
                    } else {
                        validated = 1;
                        self.next().text('');
                    }
                } else {
                    validated = 1;
                    self.next().text('');
                }
            } else {
                validated = 0;
                self.next().text('');
            }
        });

        /*
        | ---------------------------------
        | # when modal submit is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#inventoryAdjustmentModal #inventoryAdjustmentModalSubmit', function (e) {
            e.preventDefault();
            n2 &&
            n2.validate().then(function (e) {
                if ("Valid" == e && validated > 0) {
                    (t2.setAttribute("data-kt-indicator", "on"),
                    (t2.disabled = !0),
                    setTimeout(function () {
                        t2.removeAttribute("data-kt-indicator"),
                        $.ajax({
                            type: r2.getAttribute('method'),
                            url:  r2.getAttribute('action'),
                            data: $('#inventoryAdjustment_Form').serialize(),
                            success: function(response) {
                                var data = $.parseJSON(response); 
                                if (data.type == 'success') {
                                    Swal.fire({ title: data.title, text: data.text, icon: data.type, buttonsStyling: !1, confirmButtonText: "Ok, got it!", customClass: { confirmButton: "btn btn-primary" } }).then(
                                        function (e) {
                                            i2.hide();
                                            t2.disabled = !1;
                                            $.inventory_adjustment.load_contents(1);
                                        }
                                    );
                                } 
                            },
                        });
                    }, 2e3))
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
            var urlz   = base_url + 'auth/items/inventory-adjustment/remove/' + id;
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
                                    $.inventory_adjustment.load_contents(1);
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
            var urlz   = base_url + 'auth/items/inventory-adjustment/restore/' + id;
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
                                    $.inventory_adjustment.load_contents(1);
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
            $.inventory_adjustment.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.inventory_adjustment.load_contents(page);
            }
        }); 

        
    }

    //init inventory_adjustment
    $.inventory_adjustment = new inventory_adjustment, $.inventory_adjustment.Constructor = inventory_adjustment

}(window.jQuery),

//initializing inventory_adjustment
function($) {
    "use strict";
    $.inventory_adjustment.init();
    $.inventory_adjustment.datatables();
    $.inventory_adjustment.validation();
}(window.jQuery);