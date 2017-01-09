<?php
    if (!empty($pageTitle)) {
        $pageTitle = ' - ' . $pageTitle;
    } else {
        $pageTitle = '' ;
    }
    
    $message = '';
    if (!empty($_SESSION['message'])) {
        $message = join('<br />', $_SESSION['message']['text']);
        $messageStyle = $_SESSION['message']['type'];
        unset($_SESSION['message']);
    }
    
   
?>
<html>
    <head>
        <title>Redirect Tool</title> 
        <script type="text/javascript" src="js/jquery.min.js"></script>
        <link type="text/css" href="css/redmond/jquery-ui-1.8.19.custom.css" rel="Stylesheet" />
        
        <link type="text/css" href="css/demo_page.css" rel="Stylesheet" />
        <link type="text/css" href="css/demo_table.css" rel="Stylesheet" />
        
        <script type="text/javascript" src="js/jquery-ui.min.js"></script>
        <script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>
        <link rel="stylesheet" href="css/ui.css" type="text/css" media="screen" />
        
        <script type="text/javascript" src="js/jquery.form.js"></script>
        <script type="text/javascript" language="javascript" src="js/ReloadAjax.js"></script>
        
        <style>
            #edit_dialog label{
                width:150px;
                float: left;
            }
            #edit_dialog input,#edit_dialog textarea{
                width:300px;
            }
            .error {
                background-color: #FCC;
            }
            
            #edit_form div{
                padding-bottom: 5px;
            }
            
            hr{
                border: 0;
                background-color:  #A6C9E2;
                height: 1px;
            }
            
            .ui-icon-cancel{
                cursor: pointer;
            }
        </style>
        
        <script>

            function openEditDialog(id)
            {
                $.getJSON('index.php?act=getinfo&id='+id, function(data) {
                    
                    $('#url_id').val(data.id);

                    $('#name').val(data.name);
                    $('#redirect_url').val(data.redirect_url);
                    $('#exception_url').val(data.exception_url);
                    $('#exception_ips').val(data.ips);
                    
                    
                    $("#start option[value='"+data.start+"']").attr('selected', 'selected');
                    $("#end option[value='"+data.end+"']").attr('selected', 'selected');
//                    $('#start').val(data.start);
  //                  $('#end').val(data.end);
                    $('#enter_url').val(data.enter_url);


                    $( "#edit_dialog" ).dialog( "open" );
                        return false;
                    
                });
            }
            
            function clearEditForm()
            {
                    $('#url_id').val(null);

                    $('#name').val(null);
                    $('#redirect_url').val(null);
                    $('#exception_url').val(null);
                    $('#exception_ips').val(null);
                    $('#start').val(null);
                    $('#end').val(null);
                    $('#enter_url').val(randomPassword(32));                
            }
            function clearErrorsEditForm()
            {
                    $('#url_id').removeClass('error');

                    $('#name').removeClass('error');
                    $('#redirect_url').removeClass('error');
                    $('#exception_url').removeClass('error');
                    $('#exception_ips').removeClass('error');
                    $('#start').removeClass('error');
                    $('#end').removeClass('error');
                    $('#enter_url').removeClass('error');                
            }
            
            function open_edit(el)
            {
                var url_id = $(el).attr('url_id');
                openEditDialog(url_id)
            }
            
            function open_delete(el)
            {
                var url_id = $(el).attr('url_id');
                
                $('#del_url_id').val(url_id);
                
                $( "#delete_dialog" ).dialog( "open" );
                return false;
            }

            function showResponseEdit(responseText, statusText, xhr, $form)  { 
                
                
                if(responseText.errors !== undefined){
                    //alert(responseText.errors);
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
                    if(responseText.errors.enter_url !== undefined) {
                         $('#enter_url').addClass('error');
                    }
                    if(responseText.errors.start !== undefined) {
                         $('#start').addClass('error');
                    }                
                    if(responseText.errors.ips !== undefined) {
                         $('#exception_ips').addClass('error');
                    }           
                }
                else
                {
                    $( this ).dialog( "close" );
                    var oTable = $('#table1').dataTable(); 
                    oTable.fnReloadAjax();
                }
            } 

            function showResponse(responseText, statusText, xhr, $form)  { 
                var oTable = $('#table1').dataTable(); 
                oTable.fnReloadAjax();
            } 
                    
            $(document).ready(function() {
                $('#table1').dataTable( {
                    "bJQueryUI": true,
                    "bProcessing": true,
                    "sAjaxSource": 'index.php?act=table'
                } );
                
                
                $( "#edit_dialog" ).dialog({
                    autoOpen: false,
                    resizable: false,
                  //  height:240,
                    width:500,
                    modal: true,
                    buttons: {
                        "Save": function() {
                            $('#edit_form').submit();
                            clearErrorsEditForm();
                        },
                        "Cancel": function() {
                            $( this ).dialog( "close" );
                        }
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
                    
                    openEditDialog(0);
                    return false;
                });          
                
                
                var options = { 
                        success: showResponseEdit,  // post-submit callback 

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

                    $('span.ui-icon-cancel').click(function(){
                        var parent = $(this).parent();
                        parent.find('input').val('');
                        parent.find('textarea').val('');
                        parent.find('option[value="00:00"]').attr('selected', 'selected');
                    });

                  
            } ); //end of  $(document).ready(function() {
                            
                            
                            
        </script>        
    </head>
    <body>
        <div class="content">
            <?php if (!empty($message)) :?>
            <div class="message <?php echo $messageStyle; ?>" >
                <?php echo $message; ?>                
            </div>
            <?php endif; ?>
            <input type="button" id="add_button" value="CREATE NEW CAMPAIGN">
            
            <table id="table1" class="display" >
                <thead>
                    <tr>
                        <th>Campaign name</th>
                        <th>Exception IPs</th>
                        <th>Exception Time</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        
        <div id="edit_dialog">
            <form action="index.php?act=save" method="post" id="edit_form">
                <input type="hidden" id="url_id" name="id" value="">
                <div>
                    <label for="name">Campaign name: </label>
                    <input type="text" id="name"  name="name">
                    <span class="ui-icon ui-icon-cancel" style="float: right;"></span>
                </div>
                <div>
                    <label for="enter_url">Campaign URL: </label>
                    <input type="text" id="enter_url"  name="enter_url" readonly="readonly">
                </div>
                <hr/> 
                <div>
                    <label for="redirect_url">Redirect URL: </label>
                    <input type="text" id="redirect_url"  name="redirect_url">
                    <span class="ui-icon ui-icon-cancel" style="float: right;"></span>
                </div>
                <div>
                    <label for="exception_ips">Exception IPs: </label>
                    <textarea id="exception_ips" name="ips" placeholder="Add IPs"></textarea>
                    <span class="ui-icon ui-icon-cancel" style="float: right;"></span>
                </div>
                <div>
                    <label for="exception_ips">Exception time (GMT): </label>
                    <select id="start" name="start" >
                        <?php foreach ($time_options as $time):?>
                            <option value="<?php echo $time;?>"><?php echo $time;?></option>
                        <?php endforeach;?>
                    </select>
                    -
                    <select id="end" name="end" >
                        <?php foreach ($time_options as $time):?>
                            <option value="<?php echo $time;?>"><?php echo $time;?></option>
                        <?php endforeach;?>
                    </select>
                    <span class="ui-icon ui-icon-cancel" style="float: right;"></span>
                </div>
                <div>
                    <label for="exception_url">Exception URL: </label>
                    <input type="text" id="exception_url"  name="exception_url">
                    <span class="ui-icon ui-icon-cancel" style="float: right;"></span>
                </div>
            </form>
        </div>

        <div id="delete_dialog">
            <form action="index.php?act=delete" method="post" id="delete_form">
                <input type="hidden" id="del_url_id" name="id" value="">
                <div>
                    Are you sure?
                </div>
            </form>
        </div>
        
    </body>
</html>

