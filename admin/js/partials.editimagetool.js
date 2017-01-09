/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * [example]
 * html:
 *  <input type="button" value="image tool" id="image_tool"/>
 * js: 
 *  // run image tool win     
 *   $('#image_tool').click(function(){ 
 *        // set default logo sourses
 *       global_obj.set_logo_preview = false;
 *       global_obj.default_logo_src = $('#logo_image').attr('src');       
 *       clearEditImage();
 *       $( "#edit_image" ).dialog( "open" );
 *       return false;        
 *   });
 */
    $(document).ready(function() {
        
        aspectRatio = "0.75"; 
        global_obj.thumb_width = "160";
        global_obj.thumb_height = "120";
        global_obj.IMAGE_SCALE_WIDTH = 450;
        
        var site_id = global_obj.site_id;
        if(site_id) getLogoData(site_id); // get height and width of Logo
        
        _edit_image_tool_init();
        //_ajax_fileuploader_init();
        _url_fileuploader_init(); 
        
        //initResize();
    });
    
    function _ajax_fileuploader_init() {
        aspectRatio = global_obj.thumb_height/global_obj.thumb_width; 
        //alert(aspectRatio);          
        new qq.FileUploader({
            multiple: false,
            element: document.getElementById('file-uploader'),
            action: 'index.php?cont=campaign&act=prefileuploud',
            params: {
                width: global_obj.thumb_width,
                height: global_obj.thumb_height
            },
            allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
            onComplete: function(id, fileName, data){
                if (data.success == 1){
                    $('#image-preview').html('<img src="'+data.type + data.raw+'" />');
                    
                    // set size of div 
                    $('#image-preview-thumb').css('position', 'relative');
                    $('#image-preview-thumb').css('overflow', 'hidden');
                    $('#image-preview-thumb').css('width', global_obj.thumb_width);
                    $('#image-preview-thumb').css('height', global_obj.thumb_height);
                    // end set size of div 
                    
                    $('#image-preview-thumb').html('<img src="'+data.type + data.raw+'" />');
                    initResize();
                    setBorders();
                    $('.qq-upload-failed-text').html('');
                    $('#img_raw').val(data.raw);
                } else {
                    error = data.errors;
                }
            }
        });    
    }
    
    function _url_fileuploader_init() {
        aspectRatio = global_obj.thumb_height/global_obj.thumb_width;
        $('#file-url').on('blur', function(){
            if ($(this).val() != ''){
                $.post('index.php?cont=campaign&act=prefileuploud', 
                    {
                        url:$(this).val(), 
                        width: global_obj.thumb_width, 
                        height: global_obj.thumb_height
                    }, 
                    function(data){                        
                        if (data.success == 1) {
                            $('#image-preview').html('');
                            $('#image-preview').html('<img src="'+data.type + data.raw+'" />');
                            
                            // set size of div 
                            $('#image-preview-thumb').css('position', 'relative');
                            $('#image-preview-thumb').css('overflow', 'hidden');
                            $('#image-preview-thumb').css('width', global_obj.thumb_width);
                            $('#image-preview-thumb').css('height', global_obj.thumb_height);
                            // end set size of div                             
                            
                            $('#image-preview-thumb').html('<img src="'+data.type + data.raw+'" />');
                            initResize();
                            setBorders();
                            $('#img_raw').val(data.raw);
                        } else {
                            error = data.errors;
                        }
                }, 'json');
            }
        });
    }
    
    function _force_init_by_url(url) {
        aspectRatio = global_obj.thumb_height/global_obj.thumb_width;
        if (url != '' && 
                url !=undefined) {
                
            $.post('index.php?cont=campaign&act=prefileuploud', 
                {
                    url: url,
                    width: global_obj.thumb_width, 
                    height: global_obj.thumb_height
                }, 
                function(data){                        
                    if (data.success == 1) {
                        $('#image-preview').html('');
                        $('#image-preview').html('<img src="'+data.type + data.raw+'" />');

                        // set size of div 
                        $('#image-preview-thumb').css('position', 'relative');
                        $('#image-preview-thumb').css('overflow', 'hidden');
                        $('#image-preview-thumb').css('width', global_obj.thumb_width);
                        $('#image-preview-thumb').css('height', global_obj.thumb_height);
                        // end set size of div                             

                        $('#image-preview-thumb').html('<img src="'+data.type + data.raw+'" />');
                        initResize();
                        setBorders();
                        $('#img_raw').val(data.raw);
                    } else {
                        error = data.errors;
                    }
            }, 'json');
        }        
    }
    
    function setBorders() {
        
        $('#image-preview-thumb').addClass('image_tool_boreder');
        $('#image-preview').addClass('image_tool_boreder');
    }
    
    // image jCorp scripts 

    function initResize(){
        //var img = $('#target');
        var img = $('#image-preview > img');
        img.load(function(){
            $(this).Jcrop({
                setSelect:[0,0,img.width(),img.height()],
                onChange: showPreview,
                //onSelect: updateCoords / showPreview,
                onSelect: showPreview,
                aspectRatio:1 / aspectRatio,
                addClass: 'jcrop-light'
            })
        });
    }

    function updateCoords(c)
    {
        $('#x').val(c.x);
        $('#y').val(c.y);
        $('#w').val(c.w);
        $('#h').val(c.h);
        showPreview(c);
    };

    function checkCoords()
    {
        var x1 = parseInt($('#x1').val());
        if (x1 || x1==0) return true;
        alert('Please select a crop region then press submit.'+x1);
        return false;
    };

    // Our simple event handler, called from onChange and onSelect
    // event handlers, as per the Jcrop invocation above
    function showPreview(coords)
    {
//        var rx = 100 / coords.w;
//        var ry = 100 / coords.h;
//
//        $('#preview').css({
//            width: Math.round(rx * 500) + 'px',
//            height: Math.round(ry * 370) + 'px',
//            marginLeft: '-' + Math.round(rx * coords.x) + 'px',
//            marginTop: '-' + Math.round(ry * coords.y) + 'px'
//        });
        
	var rx = global_obj.thumb_width / coords.w;
	var ry = global_obj.thumb_height / coords.h;

	$('#image-preview-thumb img').css({
		width: Math.round(rx * global_obj.IMAGE_SCALE_WIDTH) + 'px',
		height: Math.round(ry * (global_obj.IMAGE_SCALE_WIDTH/global_obj.thumb_width * global_obj.thumb_height)) + 'px',
		marginLeft: '-' + Math.round(rx * coords.x) + 'px',
		marginTop: '-' + Math.round(ry * coords.y) + 'px'
	});
       
       // for maine image 
        $("#logo_image").attr('src', $('#image-preview-thumb > img').attr('src')).load(function(){$('#scale').val(this.width/global_obj.IMAGE_SCALE_WIDTH);});
        $('#logo_image_wrap').css('position', 'relative');
        $('#logo_image_wrap').css('overflow', 'hidden');
        $('#logo_image_wrap').css('width', global_obj.thumb_width);
        $('#logo_image_wrap').css('height', global_obj.thumb_height);
	$('#logo_image').css({
		width: Math.round(rx * global_obj.IMAGE_SCALE_WIDTH) + 'px',
		height: Math.round(ry * (global_obj.IMAGE_SCALE_WIDTH/global_obj.thumb_width * global_obj.thumb_height)) + 'px',
		marginLeft: '-' + Math.round(rx * coords.x) + 'px',
		marginTop: '-' + Math.round(ry * coords.y) + 'px'
	});        
        
        $('#x1').val(coords.x);
        $('#y1').val(coords.y);
        $('#w').val(coords.w);
        $('#h').val(coords.h);
        $('#changedSize').val(1);
        $("<img/>").attr('src', $('#image-preview > img').attr('src')).load(function(){$('#scale').val(this.width/global_obj.IMAGE_SCALE_WIDTH);});    
    }    
    // end image jCorp scripts     

    function _edit_image_tool_init() 
    {        
        $( "#edit_image" ).dialog({
            autoOpen: false,
            resizable: true,
            //height:1000,
            width:540,
            modal: true,
            buttons: {
                "Crop Image": function() {
                    checkCoords();
                    
                    //$('#image_crop_form').submit();
                    // add link
                    //$("#logo_image").attr('src', $('#image-preview-thumb > img').attr('src')).load(function(){$('#scale').val(this.width/global_obj.IMAGE_SCALE_WIDTH);});
                    
                    $('#logo_image').show();
                    $('#logo_image_wrap').show();                    
                    
                    global_obj.set_logo_preview = true;
                    $( this ).dialog( "close" );
                },
                "Cancel": function() {
                    
                    revertDefaultLogo();
                    $( this ).dialog( "close" );
                }
            },
            close: function(){
                if(!global_obj.set_logo_preview) {
                    
                    revertDefaultLogo();
                }
            }            
        }); 
    }