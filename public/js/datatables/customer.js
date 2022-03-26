!function($) {
    "use strict";

    var customer = function() {
        this.$body = $("body");
    };

    var track_page = 1; 

    customer.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#components-customers #keywords').val() + '&perPage=' + $('#components-customers #perPage').val();
        var urls = base_url + 'auth/components/customers/all-active';
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

    customer.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.customer.load_contents(1);

        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#keywords', function (e) {
            $.customer.load_contents(1);
        });

        /*
        | ---------------------------------
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#customerModal', function (e) {
            var modal = $(this);
            modal.find('.modal-header h2').html('Add a Customer');
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('input[name="method"]').val('add');
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#customerTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var modal  = $('#customerModal');
            var urlz   = base_url + 'auth/components/customers/find/' + id;
            console.log(urlz);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        modal.find('input[name='+k+']').val(v);
                        modal.find('select[name='+k+']').select2().val(v).trigger('change');
                        modal.find('textarea[name='+k+']').val(v);
                    });
                    modal.find('.modal-header h2').html('Edit Customer (<span>' + code + '</span>)');
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
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#customerTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/components/customers/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the customer with code ("+ code +") will be removed.",
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
                                    $.customer.load_contents(1);
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
            $.customer.load_contents(1);
        });
        
        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.customer.load_contents(page);
            }
        }); 

        
    }

    //init customer
    $.customer = new customer, $.customer.Constructor = customer

}(window.jQuery),

//initializing customer
function($) {
    "use strict";
    $.customer.init();
}(window.jQuery);