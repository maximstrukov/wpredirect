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
                width:170px;
                float: left;
            }
            #edit_dialog input,#edit_dialog textarea{
                width:310px;
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
            
            .ui-icon-cancel, .ui-icon-close {
                cursor: pointer;
            }
        </style>
        
        <script>
            function showResponse(responseText, statusText, xhr, $form)  { 
                var oTable = $('#table1').dataTable(); 
                oTable.fnReloadAjax();
            }             
                    
            $(document).ready(function() {
                $('#table1').dataTable( {
                    "bJQueryUI": true,
                    "bProcessing": true,
                    "sAjaxSource": 'index.php?cont=stat&act=stattable&start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>&campaign_id=<?php echo urlencode($campaign_id); ?>'
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
                            
                            
                            
        </script>        
    </head>
    <body>
        <div class="content">
            <?php include '_menu.php'; ?>
            <?php if (!empty($message)) :?>
            <div class="message <?php echo $messageStyle; ?>" >
                <?php echo $message; ?>                
            </div>
            <?php endif; ?>

            <div>
                <form id="date_form">
                    <input type="hidden" name="cont" value="stat">
                    <input type="hidden" name="act" value="stat">
                    <label for="start">Start date: </label>
                    <select id="start" name="start">
                        <?php foreach($dates as $date):?>
                        <option value="<?php echo $date; ?>" <?php if($date==$start) echo 'selected="selected"'; ?>><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="end">End date: </label>
                    <select id="end" name="end">
                        <?php foreach($dates as $date):?>
                        <option value="<?php echo $date; ?>" <?php if($date==$end) echo 'selected="selected"'; ?>><?php echo $date; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="end">Campaign name: </label>
                    <select id="campaign_id" name="campaign_id">
                        <option value="-1">Summary</option>
                        <?php foreach($campaigns as $campaign):?>
                        <option value="<?php echo $campaign['id']; ?>" <?php if($campaign_id==$campaign['id']) echo 'selected="selected"'; ?>><?php echo $campaign['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" name="submit" value="refresh">
                </form>
            </div>
            
            <table id="table1" class="display" >
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Redirect</th>
                        <th>Exception</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        
    </body>
</html>

