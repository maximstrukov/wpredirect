function showResponse(responseText, statusText, xhr, $form)  { 
    var oTable = $('#table1').dataTable(); 
    oTable.fnReloadAjax();
}             
                    
$(document).ready(function() {
    
    $('#table1').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 50,
        "sAjaxSource": 'index.php?cont=stat&act=stattable&start='+$('#start').val()+'&end='+$('#end').val()+'&campaign_id='+$('#campaign_id option:selected').val()
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