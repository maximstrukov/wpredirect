/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var missTable = {};
var equalTable = {};

$(document).ready(function() {
   equalTable = _init_equal();
   missTable = _init_missing(); 
    
    _init_logs();
    _init_logsData();
    _init_onemainorsubcat();
    
    // buttons handlers
    $('#update_logs').click(function() {
        updateLogs(); 
        return false; 
    });
    $("#check_all").click(function(){
        if ($(this).attr("checked")=="checked") $("input[name='check_adv[]']").attr("checked","checked");
        else $("input[name='check_adv[]']").removeAttr("checked");
    });
    $("#set_checked_role").click(function(){
        var role = $(".multi_set select").val();
        var ids = '';
        var checks = $("input[name='check_adv[]']");
        for (i=0; i<checks.length; i++) {
            if ($(checks[i]).attr("checked")=="checked") {
                if (ids != '') ids += ",";
                ids += $(checks[i]).attr("_id");
            }
        }
        if (ids != '') setRole(role, ids);
    });
});

function _init_onemainorsubcat() 
{
    $('#onemainorsubcat_table').dataTable( {
//        "bJQueryUI": true,
//        "bProcessing": true,
        "iDisplayLength": 25
    });        
}

function _init_equal() {
    
    var oTable = $('#equal_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 25,
        "sAjaxSource": 'index.php?cont=troubleshooting&act=equallist'
    });
    return oTable;
}

function _init_missing() {
    
    var oTable = $('#missing_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 25,
        "sAjaxSource": 'index.php?cont=troubleshooting&act=missinglist'
    });    
    return oTable;
}

function _init_logs() {
    
    $('#logs_table').dataTable( {
//        "bJQueryUI": true,
//        "bProcessing": true,
        "iDisplayLength": 25
//        "sAjaxSource": 'index.php?cont=troubleshooting&act=equallist'
    });    
}

function _init_logsData() {
    
    $('#logsData_table').dataTable( {
        "bJQueryUI": true,
//        "bProcessing": true,
        "iDisplayLength": 25
//        "sAjaxSource": 'index.php?cont=troubleshooting&act=equallist'
    });    
}

// refreshing missing_table; 
function refreshMissTable() {
    
    missTable.fnReloadAjax();
}

// refreshing equals_table; 
function refreshEqualTable() {
    
    equalTable.fnReloadAjax();
}

// set post role by urls_id
function setRole (role, urls_id) {
    
    showLoader("Setting role ...");
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=troubleshooting&act=setrole',
        
        data:{
            role: role, 
            urls_id: urls_id
        },
        
        dataType:'json',
        
        success:function(result) {
            if(!jQuery.isEmptyObject(result)) {    
            }
        },
        
        complete: function() {
            $("#check_all").removeAttr("checked");
            if (document.location.href.indexOf('act=equalurls') > -1) refreshEqualTable();
            if (document.location.href.indexOf('act=missingurls') > -1) refreshMissTable();
            hideLoader();
        }
    });      
}

function updateLogs() {

    $('#update_logs').attr('disabled', 'disabled');

    var checksite = $('#checksite').val();
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=troubleshooting&act=runscan',
        
        data:{
            site: checksite
        },
        
        dataType:'json',
        
        success:function(result) {
            if(!jQuery.isEmptyObject(result)) {
            }
        },
        
        complete: function() {
            //$('#update_logs').removeAttr('disabled'); 
        }
    }); 
}