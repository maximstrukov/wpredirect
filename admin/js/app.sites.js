
$(document).ready(function(){  
    
    $('#set_magic').click(function(){
        
        var option_value = $('input[name=magic_name]').val();
        var site_id = $('#id').val();
        showLoader('updating parametrize urls ..');
            $.ajax({
                type: 'POST',
                url: 'index.php?cont=site&act=setmagic',

                data:{
                    option_value: option_value,
                    site_id: site_id,
                    mp2: false
                },

                dataType:'json',

                success:function(result) {
                    
                    if(!jQuery.isEmptyObject(result)) {

                        if(result.status == "set")
                            alert('Done');
                        else alert('Were got some errors on the remote server. Changes was not apply.');
                    }
                },

                complete: function() {
                    hideLoader();
                }
            });         
    });
    /*
     *set magic param for TU2
     **/
    $('#set_magic2').click(function(){
        
        var option_value = $('input[name=magic_name2]').val();
        var site_id = $('#id').val();
        showLoader('updating magic param. for TU2 ..');
            $.ajax({
                type: 'POST',
                url: 'index.php?cont=site&act=setmagic',

                data:{
                    option_value: option_value,
                    site_id: site_id,
                    mp2: true
                    
                },

                dataType:'json',

                success:function(result) {
                    
                    if(!jQuery.isEmptyObject(result)) {

                        if(result.status == "set")
                            alert('Done');
                        else alert('Were got some errors on the remote server. Changes was not apply.');
                    }
                    else alert('Get some problem. Server response is "'+result+'"');
                },

                complete: function() {
                    hideLoader();
                }
            });         
    });
    
    // Run Categories manager
    $('#cat_manag').click(function(){
        
        var site_id = $('#id').val();
        openEditCategoriesDialog(site_id); 
    });
    
    //Set permalink mask 
    $('#permalink_button').click(function(){
        
        var site_id = $('#id').val();
        var permalink_struct = $('#permalink_set').val();
        setPermalink(site_id, permalink_struct);
    });
    
    $('#custom_permalink_button').click(function(){
        
        var site_id = $('#id').val();
        var permalink_struct = $('#permalink_structure').val();
        setPermalink(site_id, permalink_struct);
    });
    
});

function setPermalink(site_id, permalink_struct) {
    
    showLoader('updating parametrize urls ..');
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=site&act=setpermalink',

        data:{
            permalink_struct: permalink_struct,
            site_id: site_id
        },

        dataType:'json',

        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {

                if(result.status == "Done")
                    alert('Done.');
                else alert('Were got some errors on the remote server. Changes was not apply.');
            }
        },

        complete: function() {
            hideLoader();
        }
    });    
}

function ShowObjProperties(obj) {
    var property, propCollection = "";	
    for(property in obj) {
            propCollection += (property + "=> " + obj[property] + "\n");
    }
    alert(propCollection);
}

function openEditDialog(id)
{
    showLoader('edit');
    $('#category_manager').hide();
    $('#set_mp').hide();
    
    $('#logo_width').val('');
    $('#logo_height').val('');
    $('#permalink_set_wrap').hide();
    $('#permalink_structure').val('');
    
    $('#ftp_host').val('');
    $('#ftp_port').val('21');
    $('#ftp_login').val('');
    $('#ftp_pass').val('');  
    $('#ftp_path').val(''); 
    $('#ftp_path_cforder').val('');

    $.getJSON('index.php?cont=site&act=getinfo&id='+id, function(data) {
        
        // reset blacklist select options
        $("#blacklist option[value='']").attr('selected', 'selected');
        
        $('#id').val(data.id);
        
        $('#magic_name').val(data.magic);
        $('#magic_name2').val(data.magic2);

        $('#domain').val(data.domain);
        $('#wp_login').val(data.wp_login);
        $('#wp_pass').val(data.wp_pass);
        $('#country').val(data.country);
        
        // set ftp data 
        $('#ftp_host').val(data.ftp_host);
        if(data.ftp_port == '') data.ftp_port = 21; 
        $('#ftp_port').val(data.ftp_port);
        $('#ftp_login').val(data.ftp_login);
        $('#ftp_pass').val(data.ftp_pass);
        $('#ftp_path').val(data.ftp_path);
        $('#ftp_path_cforder').val(data.ftp_path_cforder);
        
        $("#country option[value='"+data.country+"']").attr('selected', 'selected');
        
        if (data.project==undefined) data.project = 2;
        $("#project option[value='"+data.project+"']").attr('selected', 'selected');

        $( "#edit_dialog" ).dialog( "open" );
        
        // clear email param 
        $('#set_email').val(''); 
        
        if(data.domain && data.id) {
           
           // set blacklist
           $("#blacklist option[value='"+data.blacklist_id+"']").attr('selected', 'selected');
            
           
           // set email param 
           if(data.email)
            $('#set_email').val(data.email);
           
            $('#logo_width').val(data.logo_width);
            $('#logo_height').val(data.logo_height);
           
            $('#set_mp').show();

            $('#category_manager').show(); 
            
            //Show permalink dropdown list
            $('#permalink_set').html('')
                .append($("<option></option>")
                .attr("value",'').text('http://'+data.domain+'/?p=123'))
                .append($("<option></option>")
                .attr("value",'/%year%/%monthnum%/%day%/%postname%/').text('http://'+data.domain+'/2012/10/05/sample-post/'))
                .append($("<option></option>")
                .attr("value",'/%year%/%monthnum%/%postname%/').text('http://'+data.domain+'/2012/10/sample-post/'))
                .append($("<option></option>")
                .attr("value",'/archives/%post_id%').text('http://'+data.domain+'/archives/123'))                
                .append($("<option></option>")
                .attr("value",'/%postname%/').text('http://'+data.domain+'/sample-post/'));
                
                var fromList = false; 
                $('#permalink_set option').each(function() {
                    var value = $(this).val();
                    if(value == data.permalink_struct) {
                        $(this).attr('selected','selected');
                        fromList = true; 
                    }
                });
                
                if(!fromList && 
                    data.permalink_struct!='' && 
                        data.permalink_struct!=undefined) {
                    $('#permalink_structure').val(data.permalink_struct);
                }
                
            $('#permalink_set_wrap').show();                
            
            if(data.use_bl == 1) 
                $('#use_bl').attr('checked', 'checked'); 
            else $('#use_bl').removeAttr('checked');
            
            // less strict settings
            if(data.less_strict == 1)
                $('#less_strict').attr('checked', 'checked'); 
            else $('#less_strict').removeAttr('checked');
            
            // less strict settings
            if(data.show_iframe == 1)
                $('#show_iframe').attr('checked', 'checked'); 
            else $('#show_iframe').removeAttr('checked');            
            
        }
        
        hideLoader(); 
        
        return false;
    });
}

function clearEditForm()
{
    $('#id').val(null);

    $('#domain').val(null);
    $('#wp_loin').val(null);
    $('#wp_pass').val(null);
    $('#country').val(null);
}
function clearErrorsEditForm()
{
    $('#id').removeClass('error');
    $('#domain').removeClass('error');
    $('#wp_login').removeClass('error');
    $('#wp_pass').removeClass('error');
    $('#country').removeClass('error');
    $('#set_email').removeClass('error');
    
    $('#domain_errors').hide();
    $('#wp_login_errors').hide();
    $('#wp_pass_errors').hide();
}

function open_edit(el)
{
    var url_id = $(el).attr('url_id');
    openEditDialog(url_id);
}

function resave_posts(el)
{
    var site_id = $(el).attr('site_id');
    showLoader('reposting..');
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=site&act=resaveposts',

        data:{
            site_id: site_id
        },

        dataType:'json',

        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {

                if(result.status == "Done")
                    alert('Done.');
                else alert('Were got some errors on the remote server. Changes was not apply.');
            }
        },

        complete: function() {
            hideLoader();
        }
    });
}

function fetching_posts(el) 
{
    var site_id = $(el).attr('site_id');
    showLoader('fetching..');
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=site&act=fetchingposts',

        data:{
            site_id: site_id
        },

        dataType:'json',

        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {

                if(result.status == "Done")
                    alert('Done.');
                else alert('Were got some errors on the remote server. Changes was not apply.');
            }
        },

        complete: function() {
            hideLoader();
        }
    });     
}

function fetching_cat(el) 
{
    var site_id = $(el).attr('site_id');
    showLoader('fetching..');
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=site&act=fetchingcategories',

        data:{
            site_id: site_id
        },

        dataType:'json',

        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {

                if(result.status == "Done")
                    alert('Done.');
                else alert('Were got some errors on the remote server. Changes was not apply.');
            }
        },

        complete: function() {
            hideLoader();
        }
    });     
}

function open_delete(el)
{
    var id = $(el).attr('url_id');
    $('#del_url_id').val(id);
    $( "#delete_dialog" ).dialog( "open" );
    return false;
}

function showResponseEdit(responseText, statusText, xhr, $form)  
{
    $('#domain_errors').hide();
    $('#email_errors').hide();
    $('#wp_login_errors').hide();
    $('#wp_pass_errors').hide();
    
    if(responseText.errors !== undefined){
        
        if(responseText.errors.email !== undefined) {
            $('#set_email').addClass('error');
            $('#email_errors').html(responseText.errors.email);
            $('#email_errors').show();
        }
        if(responseText.errors.domain !== undefined) {
            $('#domain').addClass('error');
            $('#domain_errors').html(responseText.errors.domain);
            $('#domain_errors').show(); 
        }
        if(responseText.errors.wp_login !== undefined) {
            $('#wp_login').addClass('error');
            $('#wp_login_errors').html(responseText.errors.wp_login);
            $('#wp_login_errors').show();
        }
        if(responseText.errors.wp_pass !== undefined) {
            $('#wp_pass').addClass('error');
            $('#wp_pass_errors').html(responseText.errors.wp_pass);
            $('#wp_pass_errors').show();
        }
    }
    else {
        
        $("#edit_dialog").dialog( "close" );
        var oTable = $('#table1').dataTable();
        oTable.fnReloadAjax();
    }
}

function showResponse(responseText, statusText, xhr, $form)  {
    var oTable = $('#table1').dataTable();
    oTable.fnReloadAjax();
}

function _init() {
    $('#table1').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 50,
        "sAjaxSource": 'index.php?cont=site&act=list',
        "aoColumns": [
            null,
            null,
            null,
            null,
            null,
            { "sClass": "check_cell" }
        ]
    } );

    $( "#edit_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:538,
        modal: true,
        buttons: {
            "Save": function() {
                //$('#edit_form').submit();
                clearErrorsEditForm();
            },
            "Cancel": function() {
                $( this ).dialog( "close" );
                clearErrorsEditForm();
            }
        },
        close: function() {
            clearErrorsEditForm();
        }
    });
    var screen_width = $(window).width();
    $( "#sites_stat" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:1250,
        modal: true,
    });

    $( "#delete_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        height:140,
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

    $( "#add_button" ).click(function() {
        openEditDialog(0);
        return false;
    });


    var options = {
        success: showResponseEdit  // post-submit callback

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
    $('#edit_form').ajaxForm(options);

    var options_del = {
        success: showResponse,  // post-submit callback
        resetForm: true        // reset the form after successful submit
    };
    $('#delete_form').ajaxForm(options_del);

    $("#enter_url").focus(function(){
        // Select field contents
        this.select();
    });

    $('span.ui-icon-close').click(function(){
        var parent = $(this).parent();
        parent.find('input').val('');
        parent.find('textarea').val('');
        parent.find('option[value="00:00"]').attr('selected', 'selected');
    });

    $('#exception_country').change(function(){
        refreshISPTable();
    });


    $('#button_clear_isp').click(function(){
        var id = $('#url_id').val();
        $.getJSON('index.php?act=clear&id='+id,function(){
            refreshISPTable();
        });

    });

    $('#button_unselect_isp').click(function(){
        var id = $('#url_id').val();

        var search = $("#exception_isp_table_filter input").val();

        $.getJSON('index.php?act=clear&id='+id+'&search='+encodeURI(search),function(){
            refreshISPTable();
        });

    });

    $('#button_select_isp').click(function(){
        var id = $('#url_id').val();
        var country_code = $('#exception_country').val();

        var search = $("#exception_isp_table_filter input").val();

        $.getJSON('index.php?act=addisp&id='+id
            +'&search='+encodeURI(search)
            +'&country_code='+encodeURI(country_code)
            +'&status=added',function(){
            refreshISPTable();
        });

    });


} //end of  function _init() {

function refreshISPTable(){
    var countryCode = $('#exception_country').val();

    var oTable = $('#exception_isp_table').dataTable();
    oTable.fnDraw();

}

function populateTextarea(data) {
    var isp_ids = $('#exception_ips').val().split(',');


    for(i in data){
        if($.inArray(data[i].id, isp_ids)==-1) isp_ids.push(data[i].id);
    }
    //$('.result').html(data);

    $('#exception_ips').val(isp_ids.join(','));

    alert('Load was performed.');
}


function saveprovider(el)
{
    var country_code = $('#exception_country').val();
    var isp_name = $(el).attr('isp_name');
    var url_id = $('#url_id').val();

    var status = 'deleted';

    if($(el).hasClass('ui-icon-plus')){
        status = 'added';
        $(el).addClass('ui-icon-check').removeClass('ui-icon-plus');
    }
    else{
        $(el).removeClass('ui-icon-check').addClass('ui-icon-plus');
    }

    $.getJSON('index.php?act=addisp&id='+url_id+'&country_code='+country_code+'&isp_name='+encodeURIComponent(isp_name)+'&status='+status,function(){});

}

function show_sites_stat() {
    if ($("table.site_stat_grid").length > 0) {
        $("#sites_stat").dialog("open");
    } else {
        showLoader('Loading data ..');
        $.getJSON('index.php?cont=site&act=getsitestat', function(data) {
            var countries = data.countries;
            
            var stats = data.data;
            for (p in stats) {
                content = '<h2>'+p+'</h2>';
                content += '<table class="sites_table site_stat_grid '+p+'">';
                content += '<tr class="header">';
                content += '<th>Site</th>';
                content += '</tr>';
                content += '</table>';
                $("#sites_stat").append(content);
                var total_country = Array();
                for (k in countries) {
                    $("."+p+" tr.header").append('<th><a href="javascript:void(0)" title="'+countries[k].name+'">'+countries[k].code+'</a></th>');
                    total_country[countries[k].code] = {'ad':0,'pl':0,'shown':0,'hidden':0};
                }
                total_country['total'] = {'ad':0,'pl':0,'shown':0,'hidden':0};

                $("."+p+" tr.header").append('<th><a href="javascript:void(0)">Totals</a></th>');
                $("."+p+" tr.header").append('<th><a href="javascript:void(0)">Advs</a></th>');
                $("."+p+" tr.header").append('<th><a href="javascript:void(0)">Plhs</a></th>');
                $("."+p+" tr.header").append('<th><a href="javascript:void(0)">Shown</a></th>');
                $("."+p+" tr.header").append('<th><a href="javascript:void(0)">Hidden</a></th>');

                var counter = 0;
                for (k in stats[p]) {
                    counter++;
                    if (counter % 2 == 0) row_class = 'even';
                    else row_class = 'odd';
                    var row = '<tr class="'+row_class+'"><td>'+k+'</td>';
                    for (c in countries) {
                        row += '<td class="stat_data">';
                        if (stats[p][k][countries[c]['code']] != undefined) {
                            ads = Number(stats[p][k][countries[c]['code']]['ad']);
                            pls = Number(stats[p][k][countries[c]['code']]['pl']);
                            shown = Number(stats[p][k][countries[c]['code']]['shown']);
                            hidden = Number(stats[p][k][countries[c]['code']]['hidden']);
                            title = 'Advertisers - '+ads+'\nPlaceholders - '+pls;
                            title += '\nShown - '+shown+'\nHidden - '+hidden;
                            row += '<a href="javascript:void(0)" title="'+title+'">';
                            row += (ads+pls);
                            row += '</a>';
                            total_country[countries[c]['code']]['ad'] += ads;
                            total_country[countries[c]['code']]['pl'] += pls;
                            total_country[countries[c]['code']]['shown'] += shown;
                            total_country[countries[c]['code']]['hidden'] += hidden;
                        }
                        row += '</td>';
                    }
                    total_ads = total_pls = total_shown = total_hidden = 0;
                    for (n in stats[p][k]) {
                        total_ads += Number(stats[p][k][n]['ad']);
                        total_pls += Number(stats[p][k][n]['pl']);
                        total_shown += Number(stats[p][k][n]['shown']);
                        total_hidden += Number(stats[p][k][n]['hidden']);
                    }
                    title = 'Advertisers - '+total_ads+'\nPlaceholders - '+total_pls;
                    title += '\nShown - '+total_shown+'\nHidden - '+total_hidden;

                    row += '<td class="stat_data"><a href="javascript:void(0)" title="'+title+'">'+(total_ads+total_pls)+'</a></td>';
                    row += '<td class="stat_data"><a href="javascript:void(0)">'+total_ads+'</a></td>';
                    row += '<td class="stat_data"><a href="javascript:void(0)">'+total_pls+'</a></td>';
                    row += '<td class="stat_data"><a href="javascript:void(0)">'+total_shown+'</a></td>';
                    row += '<td class="stat_data"><a href="javascript:void(0)">'+total_hidden+'</a></td>';

                    row += '</tr>';
                    total_country['total']['ad'] += total_ads;
                    total_country['total']['pl'] += total_pls;
                    total_country['total']['shown'] += total_shown;
                    total_country['total']['hidden'] += total_hidden;
                    $("."+p).append(row);
                }
                total_row = '<tr class="totals"><td><b>Totals</b></td>';
                for (t in total_country) {
                    title = 'Advertisers - '+total_country[t]['ad']+'\nPlaceholders - '+total_country[t]['pl'];
                    title += '\nShown - '+total_country[t]['shown']+'\nHidden - '+total_country[t]['hidden'];
                    total_row += '<td class="stat_data"><a href="javascript:void(0)" title="'+title+'">'+(total_country[t]['ad']+total_country[t]['pl'])+'</a></td>';
                }
                total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['ad']+'</a></td>';
                total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['pl']+'</a></td>';
                total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['shown']+'</a></td>';
                total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['hidden']+'</a></td>';
                total_row += '</tr>';
                $("."+p).append(total_row);

                total_row = '<tr class="odd"><td><b>Advs</b></td>';
                for (t in total_country) {
                    total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['ad']+'</a></td>';
                }
                total_row += '<td colspan="4"></td>';
                total_row += '</tr>';
                $("."+p).append(total_row);

                total_row = '<tr class="even"><td><b>Plhs</b></td>';
                for (t in total_country) {
                    total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['pl']+'</a></td>';
                }
                total_row += '<td colspan="4"></td>';
                total_row += '</tr>';
                $("."+p).append(total_row);

                total_row = '<tr class="odd"><td><b>Shown</b></td>';
                for (t in total_country) {
                    total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['shown']+'</a></td>';
                }
                total_row += '<td colspan="4"></td>';
                total_row += '</tr>';
                $("."+p).append(total_row);

                total_row = '<tr class="even"><td><b>Hidden</b></td>';
                for (t in total_country) {
                    total_row += '<td class="stat_data"><a href="javascript:void(0)">'+total_country[t]['hidden']+'</a></td>';
                }
                total_row += '<td colspan="4"></td>';
                total_row += '</tr>';
                $("."+p).append(total_row);
            
            }
            
            $("#sites_stat").dialog("open");
            hideLoader();
            return false;
        });
    }
}

function set_check_advs(check) {
    var action = 0;
    if ($(check).attr('checked')=='checked') action = 1;
    var site_id = $(check).attr('site_id');
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=site&act=setcheckadvs',
        data:{
            site_id: site_id,
            action: action,
        },
        dataType:'json',
        success:function(result) {
            if(result==true) {
                /*if (action=='Hide') {
                    $(link).html('Show');
                    $(link).prev().html('Hidden');
                } else {
                    $(link).html('Hide');
                    $(link).prev().html('Shown');
                }*/
            }
            else alert('Some errors occured on the remote server. Changes failed to apply.');
        }
    });
    
    return false;
}