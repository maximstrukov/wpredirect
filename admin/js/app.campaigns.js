// starting scripts
$(function(){
    
    // global object for show current page of datatable also set less_strict value 
    dtObj = {};
    dtObj.page = false; 
    dtObj.run = false;     
    dtObj.less_strict = false;
    
    // check existence of logo data and set it if not exist
    logoUrl = {};
    logoUrl.isset = false;
    logoUrl.data = false;
    logoUrl.setData = function(n) {
        if(!logoUrl.isset && 
                logoUrl.data)
            $('#logo_link').val(logoUrl.data);
    }    
    
    $('#sites').change(
        function(){
            var site_id = $(this).val();
            // set global site_id 
            global_obj.site_id = site_id;
            getCategoryBySiteId(site_id);
            getLogoData(site_id); 
            editCategoryWinWrap(site_id);
            getlessstrict(site_id);
            getshowiframe(site_id);
        }
    );
        
    $('.domain_filter').change(
        function(){
            
            var site_id = $(this).val();
            setCategoriesFilter(site_id);
            $('.domain_filter').val(site_id);
            var oTable = $('#table1').dataTable();
            oTable.fnReloadAjax();
        }
    );    
        
    $('.categories_filter').change(
        function(){
            var category_id = $(this).val();
            $('.categories_filter').val(category_id);
            var oTable = $('#table1').dataTable();
            oTable.fnReloadAjax();
        }
    );        
        
    $('.visible_filter').change(
        function(){
            var visible_filter = $(this).val();
            $('.visible_filter').val(visible_filter);
            var oTable = $('#table1').dataTable();
            oTable.fnReloadAjax();
        }
    );        
        
    // Run Categories manager
    runCategoryObj = {};
    $('#cat_manag').click(function(){ 
        runCategoryObj.runEditWin();
    });   
    
    // open image tool win     
    $('#image_tool').click(function(){ 
        // set default logo sourses
        global_obj.set_logo_preview = false;
        global_obj.default_logo_src = $('#logo_image').attr('src');
        //clearEditImage();
        $( "#edit_image" ).dialog( "open" );
        if(global_obj.default_logo_src != '' && global_obj.default_logo_src!=undefined)
            _force_init_by_url(global_obj.default_logo_src);        
        return false;        
    });   

    // select IPS by count 
    $('#select_ips_button').click(function(){ 
        
        var count = $('#select_ips_count').val(); 
        selectIPSbyCount(count);
    });
    
    $(".add_root_cat a").click(function(){
        $(".category_set").append('<div class="category_block"></div>');
        $(".category_unit:first").clone().appendTo(".category_block:last");
        var current_select = $('.category_select:last');
        $(current_select).attr("id",'category_'+$('.category_select').length);
        $(current_select).val("");
        $(current_select).change(function(){
            onchange_category($(this));
        });
    });
    $("#project_tabs a").click(function(){
        var url = document.location.href;
        document.cookie="project="+$(this).attr('_id');
        document.location = url;
    });
    
});

// check less strict settings
function getlessstrict(site_id) {
    $('#redirect_url').removeAttr('disabled');
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getlessstrict',
        
        data:{
            site_id: site_id
        },
        
        dataType:'json',
        
        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {
                if(result == 1) {
                     //$('#redirect_url').attr('disabled', 'disabled');
                     lessStrictView(result);
                } else lessStrictView(0); 
                   
            }
        },
        
        complete: function() {

        }
    });    
}

// check less strict settings
function getshowiframe(site_id) {
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getshowiframe',
        data:{
            site_id: site_id
        },
        dataType:'json',
        success:function(result) {
            if(!jQuery.isEmptyObject(result)) {
                if(result == 1) {
                    $('#show_iframe').attr('checked', 'checked');
                } else $('#show_iframe').removeAttr("checked");
            }
        },
        complete: function() {

        }
    });    
}

// set post role by urls_id
function setRole (role, urls_id) {
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
            refreshISPTable();
        }
    });      
}

function setCategoriesFilter(site_id) {
    
    //TO DO: get categories from server by site_id 
    
    $('.categories_filter').html(''); 
    
    $('.categories_filter')
        .append($("<option></option>")
        .attr("value",'')
        .text('Show all categories'));

    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getcategory',
        
        data:{
            filter: true, 
            site_id: site_id
        },
        
        dataType:'json',
        
        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {
                
                for (key in result) {
                    $('.categories_filter')
                        .append($("<option></option>")
                        .attr("value",result[key].category_id+'#'+result[key].site_id)
                        .text('['+result[key].site_name+'] '+result[key].category_name));
                }
            }
        },
        
        complete: function() {

        }
    });      
    
    return true; 
}

function selectIPSbyCount(count) 
{
    var country_code = $('#exception_country').val();
    var url_id = $('#url_id').val();

    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=selipsbycount',
        
        data:{
            country_code: country_code, 
            count: count, 
            id:url_id
        },
        
        dataType:'json',
        
        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {
                // TO DO: something
                $('#select_ips_count').val('');
                refreshISPTable();
            }
        },
        
        complete: function() {
            
            $('#select_ips_count').val('');
            //refreshISPTable();
        }
    });     
}

function editCategoryWinWrap(site_id)
{
    if(!site_id || site_id == undefined) 
        $('#category_manager').hide();
    else {
        
        // Run Categories manager
        $('#category_manager').show();
        runCategoryObj.runEditWin = function() {openEditCategoriesDialog(site_id)};
    }
}

function lessStrictView(less_strict_val) {
    
        if(less_strict_val == 1) {
            
           //$('#redirect_url').attr('disabled', 'disabled'); 
           $('#redirect_url_block').css({ "display": "none"}); 
           $('#isp_block').css({ "display": "none"}); 
           dtObj.less_strict = true; 
        }
        else {
            
           // $('#redirect_url').removeAttr('disabled'); 
            $('#redirect_url_block').css({ "display": "block"});
            $('#isp_block').css({ "display": "block"});
            dtObj.less_strict = false; 
        }    
}

function onchange_category(elem) {
    var cat_name = $(elem).val();
    if (cat_name=="") cat_name = 999999;
    var site_id = $("#sites").val();
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getcategory',
        data:{
            site_id: site_id,
            category: cat_name,
            elem_id: $(elem).attr("id")
        },
        dataType:'json',
        success:function(result) {
            var parent_div = $("#"+result.elem_id).parent("div");
            var next_div = $(parent_div).next();
            var current_select;
            if ($(next_div).html()) {
                current_select = $(next_div).find("select");
                $(current_select).html('<option value="">Select Category</option>');
            } else {
                $(".category_unit:first").clone().insertAfter(parent_div);
                next_div = $(parent_div).next();
                $(next_div).find("label").html("SubCategory: ");
                current_select = $(next_div).find("select");
                $(current_select).html('<option value="">Select Category</option>').attr("id",'category_'+$('.category_select').length);
                $(current_select).removeClass("root_category").addClass("sub_category");
                $(current_select).change(function(){
                    onchange_category($(this));
                });
            }
            if (result.categories.length > 0) {
                for (key in result.categories) {
                    $(current_select)
                        .append($("<option></option>")
                        .attr("value",result.categories[key].name)
                        .text(result.categories[key].name));
                }
            } else {
                $(current_select).parent().remove();
            }
        }
    });
}

function openEditDialog(id)
{
    $('#dimensions').hide();
    $('#logo_link').val('');
    $('#myfile').val('');
    $('#role').html('');
    
    if (id == 0) showLoader();
    else showLoader('edit');

    // refreshing the subcategory selects
    $('.category_unit:gt(0)').remove();
    $('.category_block:gt(0)').remove();
    $('.category_select').html('<option value="">Select Category</option>');
    $('.category_select').attr('disabled', 'disabled').val('');
    $(".category_set > br").remove();
    $(".add_root_cat").hide();
    
    $("select.category_select").change(function(){
        onchange_category($(this));
    });



    $.getJSON('index.php?cont=campaign&act=getinfo&id='+id, function(data) {
        
        // check less strict settings 
        lessStrictView(data.less_strict); 
        
        // reset blacklist select options
        $("#blacklist option[value='']").attr('selected', 'selected');        
        $("#template option[value='']").attr('selected', 'selected');        
        
        //added image logo to empty field if exixst that img
        logoUrl.data = false; 
        if(data.logo_img) 
            logoUrl.data = data.logo_img;
            //$('#logo_link').val(data.logo_img);
        
        // check/uncheck desc_logo state 
        if(data.desc_logo && 
            data.desc_logo !='0')
            $('#desc_logo').attr('checked', 'checked');
        else $('#desc_logo').removeAttr("checked");
        
        // check/uncheck featured_post state 
        if(data.featured_post && 
            data.featured_post !='0')
            $('#featured_post').attr('checked', 'checked');
        else $('#featured_post').removeAttr("checked");   
        
        // add sites and categories to select box
        $('#category_1').html('');
        
        $('#sites').html('<option value="">Select a site</option>');
        for (key in data.sites) {
            $('#sites')
                .append($("<option></option>")
                .attr("value",data.sites[key].id)
                .text(data.sites[key].domain));
        }        
        
        // get all Roles  
        for (key in data.roles) {
            $('#role')
                .append($("<option></option>")
                .attr("value",data.roles[key].id)
                .text(data.roles[key].type));
        }
        // set current Role 
        $("#role option[value='"+data.role+"']").attr('selected', 'selected');
        
        $('#category_manager').hide();
        
        // set blacklist
        if(data.blacklist_id)
            $("#blacklist option[value='"+data.blacklist_id+"']").attr('selected', 'selected');
        
        // set template
        if(data.template_id)
            $("#template option[value='"+data.template_id+"']").attr('selected', 'selected');
        disabledIspData(data.template_id);
        
        if(data.site_id) {
            
            global_obj.thumb_width = data.logo_width;
            global_obj.thumb_height = data.logo_height;
            
            $("#sites option[value='"+data.site_id+"']").attr('selected', 'selected');
            if (data.site_category.length == 0 || (data.site_category.length==1 && data.site_category[0]=="")) getCategoryBySiteId(data.site_id);
            else getCategoryBySiteId(data.site_id, data.site_category);
            
            getLogoData(data.site_id);
            
            // activate category manager
            editCategoryWinWrap(data.site_id); 
        }
        // added logo image 
        $('#logo_image').hide();
        $('#logo_image_wrap').hide();
        if(data.logo_img) {
            
            $('#logo_image').attr('src', data.logo_img+'?'+Math.random()); 
            $('#logo_image').show();
            $('#logo_image_wrap').show();
        }
        
        // added email_param
        $('#email_param').val('');
        if(data.email_param) {
            $('#email_param').val(data.email_param);
        }
        
        $('#description').val(data.description);
        
        $('#url_id').val(data.id);

        $('#name').val(data.name);
        $('#redirect_url').val(data.redirect_url);
        $('#exception_url').val(data.exception_url);
        $('#exception_url2').val(data.exception_url2);
        $('#exception_ips').val(data.ips);

        $("#exception_country option[value='"+data.country+"']").attr('selected', 'selected');

        $("#start option[value='"+data.start+"']").attr('selected', 'selected');
        $("#end option[value='"+data.end+"']").attr('selected', 'selected');
//                    $('#start').val(data.start);
        //                  $('#end').val(data.end);
        $('#enter_url').val(data.enter_url);

        // check/uncheck show_iframe state 
        if(data.show_iframe && 
            data.show_iframe !='0')
            $('#show_iframe').attr('checked', 'checked');
        else $('#show_iframe').removeAttr("checked");

        if($('#exception_country').val()!="-1"){refreshISPTable();}

        tinyMCE.execCommand("mceAddControl", true, 'description');
        tinyMCE.execCommand("mceRepaint", true, 'description');

        $( "#edit_dialog" ).dialog( "open" );
        
        if (id == 0) setIPSdefaultTable(); 
        
        setLogoHW();
        
        hideLoader();
        
        return false;
    });
}

function setIPSdefaultTable()
{
    var cookie_exception_country = $.cookie("cookie_exception_country");
    $('#exception_country option[value='+cookie_exception_country+']').attr('selected', 'selected');
    refreshISPTable();
}

function getCategoryBySiteId(site_id, site_category_arr)
{

    // refreshing the subcategory selects
    $('.category_unit:gt(0)').remove();
    $('.category_block:gt(0)').remove();
    $('.category_select').html('<option value="">Select Category</option>');    
    $(".category_set > br").remove();
    $(".add_root_cat").hide();
    
    //console.log(site_category_arr);
    //outputing the tree of categories
    $('.category_indicator').css('display', 'block');
    $('.category_select').attr('disabled', 'disabled').val('');
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getcategory',
        data:{
            site_id: site_id
        },
        dataType:'json',
        success:function(result) {
            
            var parent_cat;
            if(!jQuery.isEmptyObject(result)) {
                
                //output the root categories set
                var root_cat = 0;
                var cnt = 0;
                //console.log(site_category_arr);
                if (site_category_arr==undefined) site_category_arr = new Array("roots");
                for (var root_key=0; root_key<site_category_arr.length; root_key++) {
                    //console.log(root_key + ' ' + site_category_arr[root_key]);
                    is_root = false;
                    for (key in result) {
                        if (result[key].name==site_category_arr[root_key] && result[key].parent_id==0 
                        || site_category_arr[root_key]=="roots") {
                            is_root = true;
                            if (site_category_arr[root_key]=="roots") root_cat = 999999;
                            else root_cat = result[key].category_id;
                            break;
                        }
                    }
                    if (is_root) {
                        //console.log(cnt);
                        if (cnt > 0) {
                            //console.log(root_cat);
                            $(".category_set").append('<div class="category_block"></div>');
                            $(".category_unit:first").clone().appendTo(".category_block:last");
                        }
                        var current_select = $('.category_select:last');
                        $(current_select).html('<option value="">Select Category</option>').attr("id",'category_'+$('.category_select').length);
                        for (key in result) {
                            if (result[key].parent_id==0) {
                                $(current_select)
                                    .append($("<option></option>")
                                    .attr("value",result[key].name)
                                    .text(result[key].name));
                                    if (result[key].category_id == root_cat) {
                                        $(current_select).find("option[value='"+result[key].name+"']").attr('selected', 'selected');
                                        parent_cat = result[key].category_id;
                                    }
                            }
                        }
                        if (cnt > 0) {
                            $(current_select).change(function(){
                                onchange_category($(this));
                            });
                        }
                        // output the subcategories if they exist
                        var sub_select_content;
                        do {
                            sub_select_content = '';
                            more_cat = false;
                            //console.log(parent_cat);
                            for (subkey in result) {
                                if (result[subkey].parent_id==parent_cat) {
                                    sub_select_content += '<option value="'+result[subkey].name+'"';
                                    if (site_category_arr.indexOf(result[subkey].name) > -1 && sub_select_content.indexOf('selected="selected"') < 0) {
                                        sub_select_content += ' selected="selected"';
                                        parent_cat_id = result[subkey].category_id;
                                        more_cat = true;
                                        category_arr_index = site_category_arr.indexOf(result[subkey].name);
                                        site_category_arr.splice(category_arr_index, 1);
                                        if (category_arr_index >= 0 && category_arr_index < root_key) {
                                            root_key = root_key - 1;
                                            //console.log(root_key);
                                        }
                                    }
                                    sub_select_content += '>'+result[subkey].name+'</option>';
                                }
                            }
                            if (more_cat) parent_cat = parent_cat_id;
                            if (sub_select_content!='') {
                                $(".category_unit:first").clone().appendTo(".category_block:last");
                                $(".category_unit:last label").html("SubCategory: ");
                                current_select = $('.category_select:last');
                                $(current_select).html('<option value="">Select Category</option>').attr("id",'category_'+$('.category_select').length);
                                $(current_select).removeClass("root_category").addClass("sub_category");
                                $(current_select).append(sub_select_content);
                                $(current_select).change(function(){
                                    onchange_category($(this));
                                });
                            }
                        } while (more_cat==true);
                        cnt++;
                    }
                }
            }
            $(".add_root_cat").show();
        },
        
        complete: function() {
            $('.category_select').removeAttr('disabled');
            $('.category_indicator').css('display', 'none');
        }
    });
}

function getSubCategories(category, site_id) {
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getcategory',
        
        data:{
            categoty: category,
            site_id: site_id
        },
        
        dataType:'json',
        async: false,
        success:function(result) {
            
        }
    });
}

function clearEditForm()
{
    $('#url_id').val(null);

    $('#name').val(null);
    $('#redirect_url').val(null);
    $('#exception_url').val(null);
    $('#exception_url2').val(null);
    $('#exception_ips').val(null);
    $('#start').val(null);
    $('#end').val(null);
    $('#enter_url').val(randomPassword(32));
}
function clearErrorsEditForm()
{
    $('.site_errors').hide();    
    
    $('#url_id').removeClass('error');
    
    $('#sites').removeClass('error');
    $('.category_select').removeClass('error');
    $('.sub_category').removeClass('error');
    
    $('#name').removeClass('error');
    $('#redirect_url').removeClass('error');
    $('#exception_url').removeClass('error');
    $('#exception_url2').removeClass('error');
    $('#exception_ips').removeClass('error');
    $('#start').removeClass('error');
    $('#end').removeClass('error');
    $('#enter_url').removeClass('error');
    $('#exception_country').removeClass('error');
}

function open_edit(el)
{    
    clearCropImageData(); 
    // reverting 
    revertDefaultLogo();
    var url_id = $(el).attr('url_id');
    openEditDialog(url_id);
}

function set_published(el, status) {
    
    var wp_post_id = $(el).attr('wp_post_id');
    var site_id = $(el).attr('site_id');
    var id = $(el).attr('url_id');
    
    $("a[url_id='"+id+"']").removeClass('set_published');
    
    $(el).addClass('set_published');
    
        $.ajax({
            
            type: 'POST',
            url: 'index.php?cont=campaign&act=setpublished',

            data:{
                wp_post_id: wp_post_id,
                site_id: site_id,
                status: status,
                id: id
            },

            dataType:'json',

            success:function() {
                refreshISPTable();
            },

            complete: function() {

            }
        });    
}

function disabledIspData(template_id) 
{
    if(template_id!='' && 
        template_id!=undefined) {
        
        $('#exception_country_wrap').css('visibility', 'hidden'); //visibility: visible | hidden 
        $('#exception_isp_table_wrapper').hide();
        $('#management_isp').hide();
        $('#select_ips_count_wrap').hide();
    }
    else {
        
        $('#exception_country_wrap').removeAttr('style');
        $('#exception_isp_table_wrapper').show();
        $('#management_isp').show();
        $('#select_ips_count_wrap').show();
    }
}

function open_delete(el)
{
    var url_id = $(el).attr('url_id');

    $('#del_url_id').val(url_id);

    $( "#delete_dialog" ).dialog( "open" );
    return false;
}

function ShowObjProperties(obj) {

	var property, propCollection = "";	
	
	for(property in obj) {
		
		propCollection += (property + "=> " + obj[property] + "\n");
	}
	alert(propCollection);
}

function showResponseEdit(responseText, statusText, xhr, $form)  {
    
    hideLoader();
    $('.site_errors').hide();
    
    if(responseText.errors !== undefined){
        
        if(responseText.errors.myfile_errors !== undefined) {
            $('#myfile').addClass('error');
            $('#myfile_errors').html(responseText.errors.myfile_errors);
            $('#myfile_errors').show();
        }        
        
        if(responseText.errors.post_errors !== undefined) {
            $('#post_errors').html(responseText.errors.post_errors);
            $('#post_errors').show();
        }        
        
        if(responseText.errors.sites !== undefined) {
            $('#sites').addClass('error');
            $('#sites_errors').html(responseText.errors.sites);
            $('#sites_errors').show();            
        }
        
        if(responseText.errors.site_category !== undefined) {
            $('#category_1').addClass('error');
            //$('#site_category_errors').html(responseText.errors.site_category);
            //$('#site_category_errors').show();            
        }
        
        if(responseText.errors.name !== undefined) {
            $('#name').addClass('error');
        }
        if(responseText.errors.redirect_url !== undefined) {
            $('#redirect_url').addClass('error');
        }
        if(responseText.errors.redirect_url !== undefined) {
            $('#redirect_url').addClass('error');
        }
        if(responseText.errors.exception_url !== undefined) {
            $('#exception_url').addClass('error');
        }
        if(responseText.errors.exception_url2 !== undefined) {
            $('#exception_url2').addClass('error');
        }        
        if(responseText.errors.enter_url !== undefined) {
            $('#enter_url').addClass('error');
        }
        if(responseText.errors.start !== undefined) {
            $('#start').addClass('error');
        }
        if(responseText.errors.ips !== undefined) {
            $('#exception_ips').addClass('error');
        }
        if(responseText.errors.exception_country !== undefined) {
            $('#exception_country').addClass('error');
        }
    }
    else
    {
        var oTable = $('#table1').dataTable();
        // get and storage to dtObj number of current page 
        dtObj.page = oTable.fnPagingInfo().iPage;
        //added access to auto change page
        dtObj.run = true; 
        // reload table data 
        oTable.fnReloadAjax();  
        //setTimeout("returnCurPage ()", 2000);
        $("select.sub_category").remove();
        $("#edit_dialog").dialog( "close" );
    }
}

function showResponse(responseText, statusText, xhr, $form)  {
    var oTable = $('#table1').dataTable();
    oTable.fnReloadAjax();
}




function returnCurPage () {
    var page_num = parseInt(dtObj.page); 
    
    if(page_num && page_num!='NaN') {
        
        var oTable = $('#table1').dataTable();
        dtObj.run = false;         
        oTable.fnPageChange(page_num, true);
    }   
}

function checkIspSelected() {
    
    var country_code = $('#exception_country').val();
    var url_id = $('#url_id').val();

    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=checkisp', //selipsbycount',
        
        data:{
            country_code: country_code, 
            id:url_id
        },
        
        dataType:'json',
        
        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {
                // TO DO: something
                
                if(result == 'none' && !dtObj.less_strict) {
                    var conf = confirm("Really want to save advertiser with 0 ISP selected?");
                    if(conf != true) return false; 
                } 

                var logo_link = $.trim($('#logo_link').val());                
                var myfile = $.trim($('#myfile').val());

                logoUrl.isset = false;
                if(logo_link!='' ||
                        myfile !='')
                    logoUrl.isset = true;

                logoUrl.setData();

                // add data from tynemce to textarea el [description]
                tinyContent = tinyMCE.get('description').getContent(); 
                $('#description').val(tinyContent);

                loader('edit_form', 'index.php?act=save');

                clearErrorsEditForm();
            }
            
        },
        
        complete: function() {
            
        }
    });     
    
}

function _init() {
   
$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings )
{
  return {
    "iStart":         oSettings._iDisplayStart,
    "iEnd":           oSettings.fnDisplayEnd(),
    "iLength":        oSettings._iDisplayLength,
    "iTotal":         oSettings.fnRecordsTotal(),
    "iFilteredTotal": oSettings.fnRecordsDisplay(),
    "iPage":          Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
    "iTotalPages":    Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
  };
}   
   
    var oTable = $('#table1').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "iDisplayLength": 50,
        //"bStateSave": true,
        "sAjaxSource": 'index.php?cont=campaign&act=table',
        "fnServerParams": function ( aoData ) {            
            var domain_filter = $('.domain_filter').val();
            aoData.push( {"name":"site_id", "value":domain_filter} );
            
            var visible_filter = $('.visible_filter').val();
            aoData.push( {"name":"published", "value":visible_filter} );
            
            var categories_filter = $('.categories_filter').val();
            aoData.push( {"name":"categoryData", "value":categories_filter} );
            
        },
        "fnInitComplete": function() {
            
        },
        "fnDrawCallback": function() {
            if ($("#table1 tbody td:first").html() != 'Loading...') {
                var main_table_width = $("#table1").outerWidth();
                $("#table1_wrapper > .fg-toolbar").width(main_table_width);
                $(".top_filters").width(main_table_width);
                var td1_width = $("#table1 th:first").outerWidth();
                var td2_width = $("#table1 th:eq(1)").outerWidth();
                var td3_width = $("#table1 th:eq(2)").outerWidth();
                $(".top_filters td:first div").width(td1_width);
                $(".top_filters td:first").width(td1_width+td2_width+td3_width);
                var td4_width = $("#table1 th:eq(3)").outerWidth();
                var td5_width = $("#table1 th:eq(4)").outerWidth();
                var td6_width = $("#table1 th:eq(5)").outerWidth();
                $(".top_filters td:eq(1) div").width(td4_width);
                $(".top_filters td:eq(1)").width(td4_width+td5_width+td6_width);
                var td7_width = $("#table1 th:eq(6)").outerWidth();
                $(".top_filters td:eq(2) div").width(td7_width);
            }
            if(dtObj.run)
                returnCurPage ();
        }
    } );

    $('#exception_isp_table').dataTable( {
        "bJQueryUI": true,
        "bProcessing": true,
        "bServerSide": true,
        "bSort": false,
        "sAjaxSource": 'index.php',
        "fnServerParams": function ( aoData ) {

            var country_code = $('#exception_country').val();
            var url_id = $('#url_id').val();
            var selected = false;
            if($('#show_selected').attr("checked")=="checked") selected = true;
            var unselected = false;
            if($('#show_unselected').attr("checked")=="checked") unselected = true;

            aoData.push( {"name":"act", "value":"getcurrentisptable"});
            aoData.push( {"name":"country_code", "value":country_code});
            aoData.push( {"name":"id", "value":url_id} );
            aoData.push( {"name":"selected", "value":selected} );
            aoData.push( {"name":"unselected", "value":unselected} );
        }
    } );

    $( "#edit_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:558,
        modal: true,
        buttons: {
            "Save": function() {
                //$('#edit_form').submit();
                /*var selects = $("select.category_select:visible");
                var cat_errors = false;
                for (k=0; k<selects.length; k++) {
                    if ($(selects[k]).val()=="" && $(selects[k]).hasClass("sub_category")) {
                        cat_errors = true;
                        $(selects[k]).addClass("error");
                    }
                    if ($(selects[k]).hasClass("root_category") && $(selects[k]).attr("id")!="category_1" && $(selects[k]).val()=="") $(selects[k]).parent().remove();
                }
                if (cat_errors) return false;
                $('.category_select[value=""]').remove();

                //remove duplicates
                var blocks = $(".category_block");
                var cat_block = new Array();
                var current_cats = '';
                for (k=0; k<blocks.length; k++) {
                    current_cats = '';
                    selects = $(blocks[k]).children();
                    for (n=0; n<selects.length; n++) {
                        if (current_cats != '') current_cats += '|';
                        current_cats += $(selects[n]).find('select').val();
                    }
                    if (cat_block.indexOf(current_cats) > -1) $(blocks[k]).remove();
                    else cat_block[cat_block.length] = current_cats;
                }
                
                //Check, whether the selected address
                checkIspSelected();*/
            },
            "Cancel": function() {
                var id = $('#url_id').val();
                $.getJSON('index.php?cont=campaign&act=cancel&id='+id,function(){ 
                    clearErrorsEditForm();
                });
                
                tinyMCE.execCommand("mceRemoveControl", true, 'description');
                
                $( this ).dialog( "close" );
            }
        },
        close: function(){
            var id = $('#url_id').val();
            
            tinyMCE.execCommand("mceRemoveControl", true, 'description');
            
            $.getJSON('index.php?cont=campaign&act=cancel&id='+id,function(){ 
                clearErrorsEditForm();
            });
        }
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
        
        clearCropImageData();
        clearEditImage(); 
        
        openEditDialog(0);
        
        return false;
    });
    
    var options = {
        success: showResponseEdit,  // post-submit callback

        // other available options:
        //url:       url         // override for form's 'action' attribute
        //type:      type        // 'get' or 'post', override for form's 'method' attribute
        //dataType:  null        // 'xml', 'script', or 'json' (expected server response type)
        dataType:  "json"// 'xml', 'script', or 'json' (expected server response type)
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
        
        $.cookie("cookie_exception_country", $(this).val());
        refreshISPTable();
    });


    $('#button_clear_isp').click(function(){
        var id = $('#url_id').val();
        $.getJSON('index.php?act=clear&id='+id,function(){
            refreshISPTable();
        });

    });

    $('#template').change(function(el){
        
        var template_id = $(this).val();
        disabledIspData(template_id);
    });
    
    $('#button_unselect_isp').click(function(){
        var id = $('#url_id').val();

        var search = $("#exception_isp_table_filter input").val();

        $.getJSON('index.php?cont=campaign&act=clear&id='+id+'&search='+encodeURI(search),function(){
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
    
    $('#select_all_isp').click(function(){
        var id = $('#url_id').val();
        var country_code = $('#exception_country').val();
        
        var isp_names = []; 
        var isp_name = false; 
        $('#exception_isp_table').find('tbody tr').each(function(el) {
            
            $(this).find('td').each(function(elem){
                if(elem == 0)
                    isp_name = $(this).text();
                return; 
            });
            
            if(isp_name)
                isp_names[el] = isp_name;
        });
        
        var json_isp_names = JSON.stringify(isp_names);
        
        $.ajax({
            type: 'POST',
            url: 'index.php?act=addisp&id='+id+'&status=added',

            data:{
                json_isp_names: json_isp_names
            },

            dataType:'json',

            success:function() {
                refreshISPTable();
            },

            complete: function() {

            }
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

    $.getJSON('index.php?cont=campaign&act=addisp&id='+url_id+'&country_code='+country_code+'&isp_name='+encodeURIComponent(isp_name)+'&status='+status,function(){});

}
