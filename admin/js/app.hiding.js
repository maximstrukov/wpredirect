/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
    _init(); 
    _events();
});

function _events() {
    
    $('#show_edit_accounts_dialog').click(function(){
        $('#ui-dialog-title-edit_accounts_dialog').html('Account manager');
        $( "#edit_accounts_dialog" ).dialog( "open" );
    });
}

function openEditDialog(site_id, site)
{
    
    $('#site_id').val('');
    $('#account_list').html('');
    $('#account_list').hide(); 
    
    $.getJSON('index.php?cont=hiding&act=getaccountinfo', function(data) {
        
        if(!jQuery.isEmptyObject(data)) {

            for(key in data) {
                
                var a_id = data[key].id; 
                var a_name = data[key].name;
                var a_rule = data[key].rule;
                
                var li = $('<li></li>');
                var input = $('<input></input>')
                                .attr('type', 'radio')
                                .attr('name', 'account_id')
                                .val(a_id);

                
                var wrap_div = $('<div></div>');
                    wrap_div.css('padding', '8px');
                input.appendTo(wrap_div);
                
                var account_name = $('<div></div>')
                                        .css('padding', '0 8px')
                                        .css('display', 'inline')
                                        .html(a_name); 
                account_name.appendTo(wrap_div);
                
                wrap_div.appendTo(li);
                li.appendTo($('#account_list'));
            }        
            
            $('#account_list').show();
        }
    });

    $('#site_id').val(site_id);

    $('#ui-dialog-title-edit_dialog').html('Hide or show advertisers for "'+site+'" mini site');
    $( "#edit_dialog" ).dialog( "open" );
    
}

function account_edit(el)
{
    $('#name').val('');
    $('#rule').html('');
    $('#edit_account_id').val('');
    
    if(el != undefined) {
        
        $('#ui-dialog-title-account_edit').html('Edit "'+el.name+'" account');
        var account_id = $(el).attr('account_id');
        $.getJSON('index.php?cont=hiding&act=getaccountinfo&account_id='+account_id, function(data) {
            $('#edit_account_id').val(data.id);
            $('#name').val(data.name);
            $('#rule').html(data.rule);
        });
    } 
    else {
        $('#ui-dialog-title-account_edit').html('Adding a new account');
    }
    
    $( "#account_edit" ).dialog( "open" );
}

function account_delete(el) {
    
    if(el != undefined) {
        
        $('#account_name').html(el.name); 
        
        var account_id = $(el).attr('account_id');
        
        $('#account_id').val(account_id);
        
        $('#ui-dialog-title-delete_account').html('Remove?'); 
        $( "#delete_account" ).dialog( "open" );
        
        return true;
    }
}

function _init() {
    // init main table
    $('#site_list_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 50
    });
    
    $('#account_manager_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 25,
        "sAjaxSource": 'index.php?cont=hiding&act=getaccount'
    });
    
    //init dialogs
    
    $( "#account_edit" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:500,
        modal: true,
        buttons: {
            "Save": function() {
                //document.getElementById('account_edit_form').submit();
                $('#account_edit_form').submit(); 
                $( this ).dialog( "close" );
            },
            "Cancel": function() {                
                $( this ).dialog( "close" );
            }
        },
        close: function(){
        }
    }); 
    
    $( "#delete_account" ).dialog({
        autoOpen: false,
        resizable: false,
        height:140,
        modal: true,
        buttons: {
            "Ok": function() {
                $('#delete_account_form').submit();
                $( this ).dialog( "close" );
            },
            "Cancel": function() {
                $( this ).dialog( "close" );
            }
        }
    });       
    
    $( "#dry-mode-data" ).dialog({
        autoOpen: false,
        resizable: false,
        height:500,
        width:500,
        modal: true,
        buttons: {
            "Ok": function() {
                $( this ).dialog( "close" );
            }
        }
    });    
    
    $( "#edit_accounts_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:558,
        modal: true,
        buttons: {
            "Ok": function() {                
                $( this ).dialog( "close" );
            }
        },
        close: function(){
        }
    });    
    
    $( "#edit_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:558,
        modal: true,
        buttons: {
            "Dry-mode": function() {
                
                var type = $('<input></input>').attr('name', 'type').attr('type', 'hidden').val('dry-mode');
                type.appendTo('#edit_form');
                
                $('#edit_form').submit();
            },
            "Hide": function() {
                
                showLoader('Hiding ...');
                var type = $('<input></input>').attr('name', 'type').attr('type', 'hidden').val('hide');
                type.appendTo('#edit_form');
                
                $('#edit_form').submit();                
            },                   
            "Show": function() {
                
                showLoader('Setting "show" ...');
                var type = $('<input></input>').attr('name', 'type').attr('type', 'hidden').val('show');
                type.appendTo('#edit_form');
                
                $('#edit_form').submit();
            },                 
            "Cancel": function() {                
                $( this ).dialog( "close" );
            }
        },
        close: function(){
        }
    });  
    
    var default_options = {
        resetForm: true        // reset the form after successful submit
    };
    
    var options_account_edit = {
        success: refreshAccountTable,
        resetForm: true
    }; 
    
    var options_edit_dialog = {
        success: showResponseEdit  // post-submit callback
    };    
    
    // bind form using 'ajaxForm'
    $('#account_edit_form').ajaxForm(options_account_edit); 
    $('#delete_account_form').ajaxForm(options_account_edit); 
    $('#edit_accounts_form').ajaxForm(default_options);
    $('#edit_form').ajaxForm(options_edit_dialog);
    
}

function refreshAccountTable () {
    
    var oTable = $('#account_manager_table').dataTable();
    oTable.fnReloadAjax();
}

function showResponseEdit (responseText) {
    
    if(responseText.type == 'dry-mode') {
        
        $('#dry-mode-data_list').html(''); 
        $('#adv_list_info').html('');
        
        var cnt = 0; 
        for(key in responseText.data) {
            
            var li = $('<li></li>').html(responseText.data[key].name); 
            li.appendTo('#dry-mode-data_list'); 
            cnt++; 
        }
        
        if(cnt == 0) $('#adv_list_info').html('None.');
        
        $('#ui-dialog-title-dry-mode-data').html('Dry-mode result:'); 
        
        $( "#dry-mode-data" ).dialog( "open" );
    }
    else {
        alert('Done');
        hideLoader();
        //$( "#edit_dialog" ).dialog( "close" );
    }

   return true;  
}