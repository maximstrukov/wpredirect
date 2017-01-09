function showResponse(responseText, statusText, xhr, $form)  { 
    var oTable = $('#table1').dataTable(); 
    oTable.fnReloadAjax();
}             

jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "num-html-pre": function ( a ) {
        if ( typeof a != 'string' ) {
            a = (a !== null && a.toString) ? a.toString() : '0';
        }       
        var x = a.replace( /<.*?>/g, "" );
        return parseFloat( x );
    },

    "num-html-asc": function ( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "num-html-desc": function ( a, b ) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
} );

$(document).ready(function() {
    $('#table1').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "bServerSide": true,                 //@statcamptable - old version @statcamptableoptimized - from buff table @statcamptablebuff
        "sAjaxSource": 'index.php?cont=stat&act=statcamptablebuff&start='+$('#start').val()+'&end='+$('#end').val()+'&campaign_id='+$('#campaign_id option:selected').val()+'&site_id='+$('#sites option:selected').val(),
        "aoColumns": [
        null,
        null,
        {
            "sType": "num-html"
        },
        {
            "sType": "num-html"
        },
        ]
    } );



    var options = { 
        success: showResponse  // post-submit callback 

    // other available options: 
    //url:       url         // override for form's 'action' attribute 
    //type:      type        // 'get' or 'post', override for form's 'method' attribute 
    //dataType:  null        // 'xml', 'script', or 'json' (expected server response type) 
    //clearForm: true        // clear all form fields after successful submit 
    // resetForm: true        // reset the form after successful submit 

    // $.ajax options can be used here too, for example: 
    //timeout:   3000 
    }; 

// bind form using 'ajaxForm' 
//  $('#date_form').ajaxForm(options);                           

} ); //end of  $(document).ready(function() {