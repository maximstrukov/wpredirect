/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function() {
    _init();
});

function open_edit(el)
{
    var blacklist_id = $(el).attr('blacklist_id');
    openEditDialog(blacklist_id);
}

function open_delete(el)
{
    var id = $(el).attr('blacklist_id');
    var name = $(el).attr('name');
    $('#del_blacklist_id').val(id);
    $('#blacklist_name').html(name);
    $( "#delete_dialog" ).dialog( "open" );
    return false;
}

function openEditDialog(id)
{
    showLoader('edit');
    
    //hide textarea with a tracking urls 
    $('#tu_data_wrap').hide();
    $("#type option[value='public']").attr('selected', 'selected');
    $('#ips_data').val('');
    $('#tu_data').val('');
    
    $.getJSON('index.php?cont=blacklists&act=getinfo&id='+id, function(data) {
        
        $('#id').val(data.id);
        $('#name').val(data.name);
        $('#ips_data').val(data.ips_data);
        $("#type option[value='"+data.type+"']").attr('selected', 'selected');
        
        if(data.type == 'private_tu') {
            
            $('#tu_data').val(data.tu_data);
            $('#tu_data_wrap').show();
        }
        
        $( "#edit_dialog" ).dialog( "open" );
        
        hideLoader(); 
        
        return false;
    });
}

function _init() {
    
    $('#blacklist_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 50,        
        "sAjaxSource": 'index.php?cont=blacklists&act=list'
    });    
    
    $( "#add_button" ).click(function() {
        openEditDialog(0);
        return false;
    });    
    
    $( "#type" ).change(function() {
        
        var value = $(this).val(); 
        if(value == 'private_tu')
            $('#tu_data_wrap').show();
        else $('#tu_data_wrap').hide();
        
        return false;
    });       
    
    $( "#edit_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:538,
        modal: true,
        buttons: {
            "Save": function() {
                $('#edit_form').submit();
                //clearErrorsEditForm();
            },
            "Cancel": function() {
                $( this ).dialog( "close" );
                //clearErrorsEditForm();
            }
        },
        close: function() {
            //clearErrorsEditForm();
        }
    }); 
    
    $( "#delete_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        height:160,
        width:440,
        modal: true,
        buttons: {
            "Ok": function() {
                $('#delete_form').submit();
                $( this ).dialog( "close" );
            },
            "Cancel": function() {
                $( this ).dialog( "close" );
            }
        }
    });   
    
    var options = {
        success: showResponseEdit  // post-submit callback
    };    
    
    var options_del = {
        success: showResponse,  // post-submit callback
        resetForm: true        // reset the form after successful submit
    };
    
    // bind form using 'ajaxForm'
    $('#edit_form').ajaxForm(options);
    $('#delete_form').ajaxForm(options_del);
    
    // for close (hidden) fileds 
    $('span.ui-icon-close').click(function(){
        var parent = $(this).parent();
        parent.find('input').val('');
        parent.find('textarea').val('');
        parent.find('option[value="00:00"]').attr('selected', 'selected');
    });    
}

function showResponse(responseText, statusText, xhr, $form)  {
    var oTable = $('#blacklist_table').dataTable();
    oTable.fnReloadAjax();
}

function showResponseEdit(responseText, statusText, xhr, $form) {
    
    $('#name_errors').hide();
    $('#ips_data_errors').hide();
    
    if(responseText.errors !== undefined) {
        
        if(responseText.errors.name !== undefined) {
            $('#name').addClass('error');
            $('#name_errors').html(responseText.errors.name);
            $('#name_errors').show();
        }
        
        if(responseText.errors.ips_data !== undefined) {
            $('#ips_data').addClass('error');
            $('#ips_data_errors').html(responseText.errors.ips_data);
            $('#ips_data_errors').show();
        }
    }
    else {
        
        $("#edit_dialog").dialog( "close" );
        var oTable = $('#blacklist_table').dataTable();
        oTable.fnReloadAjax();
    }
}