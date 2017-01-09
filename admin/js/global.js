/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

var global_obj = {};
global_obj.site_id = false;
global_obj.thumb_width = false;
global_obj.thumb_height = false;
global_obj.default_logo_src = ''; 
global_obj.set_logo_preview = false;
global_obj.IMAGE_SCALE_WIDTH = 450;

/* LOADER SCRIPTS */
//invisible page at loaded time
function loader(form_id, action) {
    showLoader();
    $('#'+form_id).attr('action', action);
    setTimeout("$('#"+form_id+"').submit()",1000);
}  
    
// hide lodare 
function hideLoader() {
    $('#loader_box').remove();
}

function showLoader(custom_message)
{
    custom_message = custom_message || '';
    $('body').append('<div id="loader_box" class="win_blackside" >&nbsp;<div id="loader_icon" class="innerloaderimg" >&nbsp;<img src="/images/loader.gif" alt="" /></div><div class="innerloadermsg"><h2>'+custom_message+'</h2></div></div>');
    $('div#loader_box').css('height', $(document).height());
    $('div.win_blackside').css('background', 'url(/images/tooltip.png) repeat 0 0');
}
/* END LOADER SCRIPTS */

/* GET LOGO DATA (height, width) */
function getLogoData(site_id) 
{
    $('#dimensions').hide();
    
    $.ajax({
        type: 'POST',
        url: 'index.php?cont=campaign&act=getlogodata',
        
        data:{
            site_id: site_id
        },
        
        dataType:'json',
        
        success:function(result) {
            
            if(!jQuery.isEmptyObject(result)) {
               
                $('#logo_height').html(result.logo_height);
                $('#logo_width').html(result.logo_width);
                
                global_obj.thumb_width = parseInt(result.logo_width);
                global_obj.thumb_height = parseInt(result.logo_height);
                
                $('#label_width').html(global_obj.thumb_width);
                $('#label_height').html(global_obj.thumb_height);     
                
                setLogoHW(); 
                
                $('#dimensions').show();
                
                _ajax_fileuploader_init(); 
            }
        },
        
        complete: function() {

        }
    });   
}

// clear hidden image data 

function clearCropImageData()
{
    $('#logo_image').attr('src','');
    
    $('#x1').val('');
    $('#y1').val('');
    $('#w').val('');
    $('#h').val('');
    $('#scale').val('');
    $('#changedSize').val('');
    $('#img_raw').val('');    
}

//clear edit_image dialog win
function clearEditImage() {
    
        $('#file-url').val('');
        $('#image-preview-thumb').removeClass('image_tool_boreder');
        $('#image-preview').removeClass('image_tool_boreder');
    
        $('#image-preview-thumb').html('');
        $('#image-preview').html('');
        $('.qq-upload-list').html('');    
}

function revertDefaultLogo() {

    $('#logo_image_wrap').removeAttr('style');                     
    $('#logo_image_wrap').css('width', global_obj.thumb_width);
    $('#logo_image_wrap').css('height', global_obj.thumb_height);                    

    $('#logo_image').removeAttr('style'); 
    $('#logo_image').css('width', global_obj.thumb_width);
    $('#logo_image').css('height', global_obj.thumb_height);                    

    $("#logo_image").attr('src', global_obj.default_logo_src);                         
}

function setLogoHW() {
    
    $('#logo_image_wrap').css('width', global_obj.thumb_width);
    $('#logo_image_wrap').css('height', global_obj.thumb_height);

    $('#logo_image').css('width', global_obj.thumb_width);
    $('#logo_image').css('height', global_obj.thumb_height);              
}




/* setting dialog window */ 

$(document).ready(function(){
    _ginit();
   $('#settings').click(function(){
       openSettingsDialog(); 
   });
}); 

function openSettingsDialog()
{
    showLoader('Settings');
    
    $('#ui-dialog-title-settings_dialog').html('General settings');
    
    $('#admin_email').val('');
    $('#system_email').val('');
    
    $.getJSON('index.php?cont=site&act=settings', function(data) {
        
        $('#admin_email').val(data.admin_email);
        $('#system_email').val(data.system_email);
        
        $( "#settings_dialog" ).dialog( "open" );
        
        hideLoader();
        
        return false;
    });
}

function _ginit() {
    
    $( "#settings_dialog" ).dialog({
        autoOpen: false,
        resizable: false,
        //  height:240,
        width:538,
        modal: true,
        buttons: {
            "Save": function() {
                $('#settings_form').submit();
                clearErrorsSettingsForm();
                
            },
            "Cancel": function() {
                $( this ).dialog( "close" ); 
                clearErrorsSettingsForm();
            }
        },
        close: function() {
            clearErrorsSettingsForm();
        }
    }); 
    
    var gOptions = {
        success: responseHandler  // post-submit callback
    };    
    
    // bind form using 'ajaxForm'
    $('#settings_form').ajaxForm(gOptions);
}

function clearErrorsSettingsForm()
{
    $('#admin_email').removeClass('error');
    $('#system_email').removeClass('error');

    $('#admin_email_errors').hide(); 
    $('#system_email_errors').hide(); 
}

function responseHandler (responseText, statusText, xhr, $form) 
{
    $('#admin_email_errors').hide();
    
    if(responseText.errors !== undefined) {
        
        if(responseText.errors.admin_email !== undefined) {
            $('#admin_email').addClass('error');
            $('#admin_email_errors').html(responseText.errors.admin_email);
            $('#admin_email_errors').show();
        }
        
        if(responseText.errors.system_email !== undefined) {
            $('#system_email').addClass('error');
            $('#system_email_errors').html(responseText.errors.system_email);
            $('#system_email_errors').show();
        }        
    }
    else $("#settings_dialog").dialog( "close" );
}

/* end setting dialog window */ 