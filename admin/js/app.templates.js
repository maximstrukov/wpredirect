/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function() {
    _init();
    
    getinfoCallback = {};
    getinfoCallback.isp_data = false;
});

function open_edit(el)
{
    var templates_id = $(el).attr('template_id');
    openEditDialog(templates_id);
}

function open_delete(el)
{
    var id = $(el).attr('template_id');
    var name = $(el).attr('name');
    $('#del_templates_id').val(id);
    $('#templates_name').html(name);
    $( "#delete_dialog" ).dialog( "open" );
    return false;
}

function openEditDialog(id)
{
    showLoader('edit');
    
    $.getJSON('index.php?cont=templates&act=getinfo&id='+id, function(data) {

        var oTable = $('#exception_isp_table').dataTable();
        
        if(!jQuery.isEmptyObject(data)) {

            for(key in data) {

                tempalte_id = data[key].tempalte_id;
                name = data[key].name;
                isp_data_id = data[key].isp_data_id;
                country = data[key].country;
                isp_data = data[key].isp_data;

                $('#id').val(tempalte_id);
                $('#name').val(name);
                $("#exception_country option[value='"+country+"']").attr('selected', 'selected');            
                
                // set global isp_data;
                getinfoCallback.isp_data = isp_data;
                oTable.fnDraw();
            }            
        } 
        else {
            
            $('#id').val('');
            $('#name').val('');
            $("#exception_country option[value='-1']").attr('selected', 'selected');
            // clear global isp_data;
            getinfoCallback.isp_data = false;
            oTable.fnDraw();
        }
        
        $( "#edit_dialog" ).dialog( "open" );
        
        hideLoader(); 
        
        setEditDialogTitle();
        
        return false;
    });
}

function setEditDialogTitle()
{
    var name  = $('#name').val();
    if(name != '')
        $('#ui-dialog-title-edit_dialog').html('Template: "'+name+'"');
    else 
        $('#ui-dialog-title-edit_dialog').html('New template');
}

function is_object( mixed_var ) {
    
    if(mixed_var instanceof Array) {
        return false;
    } 
    else {
        return (mixed_var !== null) && (typeof( mixed_var ) == 'object');
    }
}


function checkedISPs(id)
{
    isp_data = getinfoCallback.isp_data;
    
    if(is_object(isp_data)) {
        
        var arr = [];

        for (var key in isp_data) {
            if (isp_data.hasOwnProperty(key)) {
                arr.push(isp_data[key]);
            }
        }       
        
        isp_data = arr; 
    }
    
    if(isp_data) {
        
        $('#'+id).find('tbody tr').each(function(el) {

            $(this).find('td').each(function(elem){
                
                if(elem == 0)
                    isp_name = $(this).text();
                if(elem == 2)
                    sEl = this; 
            });
            
            if(jQuery.inArray(isp_name, isp_data)!=-1) {                
                
                var saveproviderElem = $(sEl).find('span');
                if($(saveproviderElem).hasClass('ui-icon-plus')) {
                    $(saveproviderElem).addClass('ui-icon-check').removeClass('ui-icon-plus');
                }
            }
        });    
    }
}

function selectISPbyCount (count, _clear)
{
    var country_code = $('#exception_country').val();
    var clear = (_clear) ? true : false; 
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=templates&act=setispbycount',
        
        data:{
            country_code: country_code, 
            count: count,
            clear: clear
        },
        
        dataType:'json',
        
        success:function(result) {
            
            // set global isp_data;
            getinfoCallback.isp_data = result;    
            $('#select_ips_count').val('');
            refreshISPTable();            
        },
        
        complete: function() {
            
            $('#select_ips_count').val('');
            //refreshISPTable();
        }
    });     
}

function getAllSearched(isp_name, select) 
{
    var country_code = $('#exception_country').val();
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=templates&act=selsearchedisp',
        
        data:{
            country_code: country_code, 
            isp_name: isp_name,
            select: select
        },
        
        dataType:'json',
        
        success:function(result) {
            
            // set global isp_data;
            getinfoCallback.isp_data = result;    
            $('#select_ips_count').val('');
            refreshISPTable();            
        },
        
        complete: function() {
            
            $('#select_ips_count').val('');
            //refreshISPTable();
        }
    });    
}

function _init() {
    
    $('#templates_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 50,
        "sAjaxSource": 'index.php?cont=templates&act=list'
    });
    
    $( "#add_button" ).click(function() {
        openEditDialog(0);
        return false;
    });   
    
    $('#exception_country').change(function(){
        refreshISPTable();
        clearSession();
    });
    
    // select ISP by count 
    $('#select_ips_button').click(function(){ 
        var count = $('#select_ips_count').val(); 
        selectISPbyCount(count);
    });    
    
    // select all ISP
    $('#select_all_isp').click(function(){ 
        selectISPbyCount(false);
    });     
    
    // clear all ISP
    $('#button_clear_isp').click(function(){ 
        var clear = true;
        selectISPbyCount(false, clear);
    });     
    
    // select all searched
    $('#button_select_isp').click(function(){ 
        var isp_name = $("input[aria-controls='exception_isp_table']").val();
        getAllSearched(isp_name, true);
    });     
    
    // un-select all searched
    $('#button_unselect_isp').click(function(){ 
        var isp_name = $("input[aria-controls='exception_isp_table']").val();
        getAllSearched(isp_name, false);
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
                clearSession();
            }
        },
        close: function() {
            //clearErrorsEditForm();
            clearSession();
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
    
    // for isp list
    $('#exception_isp_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "bServerSide": true,
        "bSort": false,
        "sAjaxSource": 'index.php',
        "fnServerParams": function ( aoData ) {

            var country_code = $('#exception_country').val();
            var url_id = '';//$('#id').val();
            
            var selected = false;
            if($('#show_selected').attr("checked")=="checked") selected = true;
            var unselected = false;
            if($('#show_unselected').attr("checked")=="checked") unselected = true;

            aoData.push( {"name":"act", "value":"getcurrentisptable"});
            //aoData.push( {"name":"cont", "value":"templates"});
            
            aoData.push( {"name":"country_code", "value":country_code});
            aoData.push( {"name":"id", "value":url_id} );
            aoData.push( {"name":"selected", "value":selected} );
            aoData.push( {"name":"unselected", "value":unselected} );
        },
        "fnDrawCallback" : function() {
            checkedISPs('exception_isp_table');
        }        
    } );    
    
    // for close (hidden) fileds 
    $('span.ui-icon-close').click(function(){
        var parent = $(this).parent();
        parent.find('input').val('');
        parent.find('textarea').val('');
        parent.find('option[value="00:00"]').attr('selected', 'selected');
    });    
}

function clearSession()
{
    var id = $('#id').val();
    $.getJSON('index.php?cont=templates&act=cancel&id='+id,function(){
        //clearErrorsEditForm();
    });
}

function showResponse(responseText, statusText, xhr, $form)  {
    
    var oTable = $('#templates_table').dataTable();
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
        var oTable = $('#templates_table').dataTable();
        oTable.fnReloadAjax();
    }
}

function refreshISPTable() {
    
    var oTable = $('#exception_isp_table').dataTable();
    oTable.fnDraw();
}

function saveprovider(el)
{
    var country_code = $('#exception_country').val();
    var isp_name = $(el).attr('isp_name');
    var template_id = $('#id').val();

    var status = 'deleted';

    if($(el).hasClass('ui-icon-plus')){
        status = 'added';
        $(el).addClass('ui-icon-check').removeClass('ui-icon-plus');
    }
    else{
        $(el).removeClass('ui-icon-check').addClass('ui-icon-plus');
    }

    $.getJSON('index.php?cont=templates&act=addisp&id='+template_id+'&country_code='+country_code+'&isp_name='+encodeURIComponent(isp_name)+'&status='+status,function(){});

}

