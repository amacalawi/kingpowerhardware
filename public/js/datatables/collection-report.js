!function($) {
    "use strict";

    var collection_report = function() {
        this.$body = $("body");
    };

    var track_page = 1; 
    var t, n;
    var o2, n2, r2, t2;
    var t3, n3, z3, postingValidation = 0;

    collection_report.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?dateFrom=' + $('#dateFrom').val() + '&dateTo=' + $('#dateTo').val() + '&type=' + $('#type').val() + '&branch=' + $('#branch_id').val() + '&customer=' + $('#customer_id').val() + '&agent=' + $('#agent_id').val() + '&status=' + $('#status').val() + '&orderby=' + $('#order_by').val() + '&keywords=' + $('#keywords').val();
        var urls = base_url + 'auth/reports/collection-reports/search';
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

    collection_report.prototype.truncate = function(num) {
        var with2Decimals = num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0]
        return with2Decimals
    },

    collection_report.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.collection_report.load_contents(1);

        $("#dateFrom, #dateTo").flatpickr({
            dateFormat: "d-M-Y"
        });
        
        /*
        | ---------------------------------
        | # when search button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.btn-search', function (e) {
            var t2 = document.querySelector('.btn-search');
            (t2.setAttribute("data-kt-indicator", "on"),
            (t2.disabled = !0),
            setTimeout(function () {
                t2.removeAttribute("data-kt-indicator"),
                $.collection_report.load_contents(1);
                t2.disabled = !1;
            }, 2e3))
        });

        $(document).keypress(function(event){
            var keycode = (event.keyCode ? event.keyCode : event.which);
            if(keycode == '13'){
                $.collection_report.load_contents(1);  
            }
        });

        /*
        | ---------------------------------
        | # when paginate is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.pagination li:not([class="disabled"],[class="active"])', function (e) {
            var page  = $(this).attr('p');   
            if (page > 0) {
                $.collection_report.load_contents(page);
            }
        });

        /*
        | ---------------------------------
        | # when export button is clicked
        | ---------------------------------
        */
        this.$body.on('click', '.btn-export', function (e) {
            e.preventDefault();
            var count = $('body #collectionReportTable').attr('data-row-count');
            var form = $('#collectionReportForm');

            if (count > 0) {
                form.submit();
            } else {
                Swal.fire({
                    icon: 'warning',
                    html: "Oops!<br/>Theres no record can be export.",
                    customClass: { confirmButton: "btn btn-warning", cancelButton: "btn btn-active-light" }
                });
            }
        });
    }

    //init collection_report
    $.collection_report = new collection_report, $.collection_report.Constructor = collection_report

}(window.jQuery),

//initializing collection_report
function($) {
    "use strict";
    // $.collection_report.item_detail_validation();
    $.collection_report.init();
}(window.jQuery);