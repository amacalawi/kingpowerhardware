!function($) {
    "use strict";

    var purchased_report = function() {
        this.$body = $("body");
    };

    var track_page = 1; 
    var t, n;
    var o2, n2, r2, t2;
    var t3, n3, z3, postingValidation = 0;

    purchased_report.prototype.load_contents = function(track_page) 
    {   
        var keywords = '?dateFrom=' + $('#dateFrom').val() + '&dateTo=' + $('#dateTo').val() + '&type=' + $('#type').val() + '&branch=' + $('#branch_id').val() + '&supplier=' + $('#supplier_id').val() + '&po_type=' + $('#purchase_order_type_id').val() + '&status=' + $('#status').val() + '&orderby=' + $('#order_by').val() + '&keywords=' + $('#keywords').val();
        var urls = base_url + 'auth/reports/purchased-reports/search';
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

    purchased_report.prototype.truncate = function(num) {
        var with2Decimals = num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0]
        return with2Decimals
    },

    purchased_report.prototype.init = function()
    {   
        /*
        | ---------------------------------
        | # load initial content
        | ---------------------------------
        */
        $.purchased_report.load_contents(1);

        $("#dateFrom, #dateTo").flatpickr({
            dateFormat: "d-M-Y"
        });
        
        this.$body.on('click', '.btn-search', function (e) {
            var t2 = document.querySelector('.btn-search');
            (t2.setAttribute("data-kt-indicator", "on"),
            (t2.disabled = !0),
            setTimeout(function () {
                t2.removeAttribute("data-kt-indicator"),
                $.purchased_report.load_contents(1);
                t2.disabled = !1;
            }, 2e3))
        });

        $(document).keypress(function(event){
            var keycode = (event.keyCode ? event.keyCode : event.which);
            if(keycode == '13'){
                $.purchased_report.load_contents(1);  
            }
        });
    }

    //init purchased_report
    $.purchased_report = new purchased_report, $.purchased_report.Constructor = purchased_report

}(window.jQuery),

//initializing purchased_report
function($) {
    "use strict";
    // $.purchased_report.item_detail_validation();
    $.purchased_report.init();
}(window.jQuery);