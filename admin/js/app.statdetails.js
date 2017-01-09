function showResponse(responseText, statusText, xhr, $form)  { 
    var oTable = $('#table1').dataTable(); 
    oTable.fnReloadAjax();
}             
                    
$(document).ready(function() {
    $('#table1').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "sAjaxSource": 'index.php?cont=stat&act=statdetailstable&start='+start+'&end='+end+'&campaign_id='+campaign_id+'&type='+type,
        "aaSorting": [[ 0, "desc" ]]
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
                            
function addToexeption(campain_id, country_code, isp_name){
    // http://rtest/admin/index.php?act=addisp&id=56&country_code=US&isp_name=Internet%20Assigned%20Numbers%20Authority&status=added                
    $.ajax({
        url: 'index.php', 
        success: function(res) {
            alert(res);
        },
        data:{
            act:'addisp',
            id:campain_id,
            country_code:country_code,
            isp_name:isp_name, 
            status:'saved'
        }
    });

//                $.ajax({
//                    url: 'index.php', 
//                    success: function(res) {
//                        alert(res);
//                    },
//                    data:{act:'updateip',
//                          isp_name:isp_name, 
//                          ip:ip}
//                });
            
}    