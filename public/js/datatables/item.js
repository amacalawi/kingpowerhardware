!function($) {
    "use strict";

    var item = function() {
        this.$body = $("body");
    };

    var track_page = 1; 

    item.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?keywords=' + $('#items-listing #keywords').val() + '&perPage=' + $('#items-listing #perPage').val();
        var urls = base_url + 'auth/items/listing/all-active';
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

    item.prototype.load_iventory_contents = function(track_page, $id, $branch, $modal, $dataresult) 
    {   
        var keywords = '?itemID=' + $id + '&branchID=' + $branch + '&keywords=' + $('#branch-'+ $branch +' .keywordx').val() + '&perPage=' + $('#branch-'+ $branch +' .perPagex').val();
        var urls = base_url + 'auth/items/listing/all-active-inventory';
        var me = $(this);
        var $portlet = $modal.find('#'+ $dataresult);

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


    item.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.item.load_contents(1);

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
        | # when modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#itemModal', function (e) {
            var modal = $(this);
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('input[name="method"]').val('add');
        });

        /*
        | ---------------------------------
        | # when item inventory modal is reset
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#itemInventoryModal', function (e) {
            var modal = $(this);
            modal.find('.nav-tabs').empty();
            modal.find('.tab-content').empty();
        });

        /*
        | ---------------------------------
        | # when modal is shown
        | ---------------------------------
        */
        this.$body.on('click', '.add-item-btn', function (e) {
            var modal = $('#itemModal');
            var urlz  = base_url + 'auth/items/listing/generate-item-code';
            if (modal.find('input[name="method"]').val() == 'add') {
                console.log(urlz);
                $.ajax({
                    type: 'GET',
                    url: urlz,
                    success: function(response) {
                        console.log(response);
                        modal.find('input[name=code]').val(response);
                        modal.modal('show');
                    },
                    complete: function() {
                        window.onkeydown = null;
                        window.onfocus = null;
                    }
                });
            }
        });

        /*
        | ---------------------------------
        | # when edit button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#itemTable .edit-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var modal  = $('#itemModal');
            var urlz   = base_url + 'auth/items/listing/find/' + id;
            console.log(urlz);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response.data, function (k, v) {
                        modal.find('input[name='+k+']').val(v);
                        modal.find('select[name='+k+']').select2().val(v).trigger('change');
                    });
                    modal.find('.modal-header h2').html('Edit item (<span>' + code + '</span>)');
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
        | # when view button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#itemTable .view-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var name   = $(this).closest('tr').attr('data-row-name');
            var modal  = $('#itemInventoryModal');
            var urlz   = base_url + 'auth/items/listing/get-all-inventory/' + id;
            console.log(urlz);
            $.ajax({
                type: 'GET',
                url: urlz,
                success: function(response) {
                    console.log(response);
                    $.each(response, function (k) {
                        var status = (k > 0) ? '' : 'show';
                        var active = (k > 0) ? '' : 'active';
                        modal.find('.nav-tabs').append('' +
                            '<li class="nav-item">' +
                            '<a class="nav-link '+ active + '" data-bs-toggle="tab" href="#branch-'+ response[k].branch_id +'">' + response[k].branch + '</a>' +
                            '</li>');
                        
                        modal.find('.tab-content').append('' +
                            '<div class="tab-pane fade ' + status + ' active" id="branch-'+ response[k].branch_id +'" role="tabpanel">' +
                            '<div class="row">' +
                            '<div class="table-responsive">' +
                            '<table class="table table-row-dashed table-striped table-row-gray-300 gy-7">' +
                            '<thead>' +
                            '<tr class="fw-bolder fs-6 text-gray-800">' +
                            '<th>Code</th>' +
                            '<th>Product Category</th>' +
                            '<th>Item Description</th>' +
                            '<th class="text-center">Total Quantity</th>' +
                            '<th class="text-center">UOM</th>' +
                            '<th class="text-center">SRP</th>' +
                            '<th class="text-center">Last Modified</th>' +
                            '</tr>' +
                            '</thead>' +
                            '<tbody>' +
                            '<tr>' +
                            '<td>' + response[k].code + '</td>' +
                            '<td>' + response[k].category + '</td>' +
                            '<td>' + response[k].name + '</td>' +
                            '<td class="text-center">' + response[k].quantity + '</td>' +
                            '<td class="text-center">' + response[k].uom + '</td>' +
                            '<td class="text-center">' + response[k].srp + '</td>' +
                            '<td class="text-center">' + response[k].modified_at + '</td>' +
                            '</tr>' +
                            '</tbody>' +
                            '</table>' +
                            '</div>' +
                            '</div>' +
                            '<div data-row-item="' + id + '" data-row-branch="' + response[k].branch_id + '" class="transaction-holder mt-5">' +
                            '<h1 class="mb-3">All transactions of item ('+ response[k].code +') branch ('+ response[k].branch +')</h1>' +
                            

                            '<div class="d-flex justify-content-end bd-highlight mb-3">' +
                            '<div class="me-auto">' +
                            '<input type="text" data-kt-item-table-filter="search" data-row-item="' + id + '" data-row-branch="' + response[k].branch_id + '" class="keywordx form-control form-control-solid w-250px ps-15" placeholder="Search Transactions">' +
                            '</div>' +
                            '<div class="me-3">' +
                            '<select data-row-item="' + id + '" data-row-branch="' + response[k].branch_id + '" class="perPagex form-select form-select-solid fw-bolder" data-kt-select2="true" data-hide-search="true">' +
                            '<option value="5" selected="selected">5</option>' +
                            '<option value="10">10</option>' +
                            '<option value="25">25</option>' +
                            '<option value="50">50</option>' +
                            '<option value="100">100</option>' +
                            '</select>' +
                            '</div>' +
                            '<div class="me-3">' +
                            '<input class="date-from date-picker form-control form-control-solid text-center" placeholder="date from"/>' +
                            '</div>'+
                            '<div class="me-3">' +
                            '<input class="date-to date-picker form-control form-control-solid text-center" placeholder="date to"/>' +
                            '</div>'+
                            '</div>'+
                            '<div id="branch-'+ response[k].branch_id +'-result" class="data-result">' +
                            '</div>' +
                            '</div>' +
                            '</div>');
                        $.item.load_iventory_contents(1, id, response[k].branch_id , modal, "branch-"+ response[k].branch_id +"-result");
                    });
                    modal.find('.nav-tabs').tab();
                    modal.find('.modal-title').text(name + ' ('+code+')');
                    modal.modal('show');
                },
                complete: function() {
                    window.onkeydown = null;
                    window.onfocus = null;
                    
                    $('.date-picker').daterangepicker({
                        autoUpdateInput: false,
                        singleDatePicker: true,
                        showDropdowns: true,
                        minYear: 1901,
                        maxYear: parseInt(moment().format('YYYY'),10)
                    }, function(start, end, label) {
                        // var years = moment().diff(start, 'years');
                        // alert("You are " + years + " years old!");
                        alert(start);
                    });
                }
            });
        }); 




        /*
        | ---------------------------------
        | # when remove button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#itemTable .remove-btn', function (e) {
            var id     = $(this).closest('tr').attr('data-row-id');
            var code   = $(this).closest('tr').attr('data-row-code');
            var urlz   = base_url + 'auth/items/listing/remove/' + id;
            console.log(urlz);
            Swal.fire({
                html: "Are you sure you? <br/>the item with code ("+ code +") will be removed.",
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
                                    $.item.load_contents(1);
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
        | # when withdrawal button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.withdraw-btn', function (e) {
            var modal = $('#itemWithdrawalModal');
            var itemCode  = $(this).closest('tr').attr('data-row-code');
            var itemId  = $(this).closest('tr').attr('data-row-id');
            modal.find('.item-code').text(itemCode);
            modal.find('.item-id').text(itemId);
            modal.modal('show');
        });
        /*
        | ---------------------------------
        | # when withdrawal button is clicked
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#itemWithdrawalModal', function (e) {
            var modal = $(this);
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('.item-code, .item-id').text('');
        });
        /*
        | ---------------------------------
        | # when receiving button is clicked
        | ---------------------------------
        */
        this.$body.on('hidden.bs.modal', '#itemReceivingModal', function (e) {
            var modal = $(this);
            modal.find('input, textarea').val('');
            modal.find('select').select2().val('').trigger('change');
            modal.find('.item-code, .item-id').text('');
        });
        



        /*
        | ---------------------------------
        | # when item perPage is changed
        | ---------------------------------
        */
        this.$body.on('change', '#perPage', function (e) {
            $.item.load_contents(1);
        });/*
        | ---------------------------------
        | # when item inventory perPage is changed
        | ---------------------------------
        */
        this.$body.on('change', '.perPagex', function (e) { 
            var branch = $(this).attr('data-row-branch');
            var item   = $(this).attr('data-row-item');
            $.item.load_iventory_contents(1, item, branch , $('#itemInventoryModal'), "branch-"+ branch +"-result");
        });
       
        /*
        | ---------------------------------
        | # when item paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '#datatable-result .pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.item.load_contents(page);
            }
        }); 
        /*
        | ---------------------------------
        | # when item inventory paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.paginationx li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            var branch = $(this).attr('data-row-branch');
            var item   = $(this).attr('data-row-item');
            if (page > 0) {
                $.item.load_iventory_contents(page, item, branch , $('#itemInventoryModal'), "branch-"+ branch +"-result");
            }
        }); 

        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '#keywords', function (e) {
            $.item.load_contents(1);
        });
        /*
        | ---------------------------------
        | # when keywords onkeyup
        | ---------------------------------
        */
        this.$body.on('keyup', '.keywordx', function (e) {
            var self   = $(this);
            var branch = $(this).attr('data-row-branch');
            var item   = $(this).attr('data-row-item');
            $.item.load_iventory_contents(1, item, branch , $('#itemInventoryModal'), "branch-"+ branch +"-result");
        });


        /*
        | ---------------------------------
        | # when receiving button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.post-btn', function (e) {
            var modal = $('#itemReceivingModal');
            var itemCode  = $(this).closest('tr').attr('data-row-code');
            var itemId  = $(this).closest('tr').attr('data-row-id');
            modal.find('.item-code').text(itemCode);
            modal.find('.item-id').text(itemId);
            modal.modal('show');
        });
        
    }

    //init item
    $.item = new item, $.item.Constructor = item

}(window.jQuery),

//initializing item
function($) {
    "use strict";
    $.item.init();
}(window.jQuery);