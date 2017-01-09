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
            a .ui-icon-circle-plus{
                float:right;
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
                    "sAjaxSource": 'index.php?cont=stat&act=statdetailstable&start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>&campaign_id=<?php echo urlencode($campaign_id); ?>&type=<?php echo urlencode($type); ?>',
                    "aaSorting": [[ 0, "desc" ]]
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
                            
            function addToexeption(campain_id, country_code, isp_name){
// http://rtest/admin/index.php?act=addisp&id=56&country_code=US&isp_name=Internet%20Assigned%20Numbers%20Authority&status=added                
                $.ajax({
                    url: 'index.php', 
                    success: function(res) {
                        alert(res);
                    },
                    data:{act:'addisp',
                            id:campain_id,
                            country_code:country_code,
                            isp_name:isp_name, 
                            status:'saved'
                         }
                });

//                $.ajax({
//                    url: 'index.php', 
//                    success: function(res) {
//                        alert(res);
//                    },
//                    data:{act:'updateip',
//                          isp_name:isp_name, 
//                          ip:ip}
//                });
            
            }                
                 
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
            
            <h1><?php echo $type; ?></h1>
            <table id="table1" class="display" >
                <thead>
                    <tr>
                        <!--th>LogID</th-->
                        <th>Time</th>
                        <th>IP</th>
                        <th>ISP</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        
    </body>
</html>

