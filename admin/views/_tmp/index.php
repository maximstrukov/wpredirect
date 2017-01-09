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

        <link rel="stylesheet" href="css/index.css" type="text/css" media="screen" />
        <script type="text/javascript" language="javascript" src="js/index.js"></script>

        <script>

            $(document).ready(function() {
                _init();
                <?php if($openDialogId>0):?>
                openEditDialog(<?php echo $openDialogId?>);
                <?php endif; ?>
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
            <input type="button" id="add_button" value="CREATE NEW CAMPAIGN">
            
            <table id="table1" class="display" >
                <thead>
                    <tr>
                        <th>Campaign name</th>
                        <th>Country</th>
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
                    <input type="text" id="name"  name="name" class="edit_dialog">
                    <span class="ui-icon ui-icon-close" style="float: right;"></span>
                </div>
                <div>
                    <label for="enter_url">Campaign URL: </label>
                    <input type="text" id="enter_url"  name="enter_url" readonly="readonly" class="edit_dialog">
                </div>
                <hr/> 
                <div>
                    <label for="redirect_url">Redirect URL: </label>
                    <input type="text" id="redirect_url"  name="redirect_url" class="edit_dialog">
                    <span class="ui-icon ui-icon-close" style="float: right;"></span>
                </div>
                <hr/>
                <div>
                    <label for="exception_country">Country: </label>
                    <select id="exception_country" name="exception_country" class="edit_dialog">
                        <option value="-1">Select Country</option>
                        <?php foreach ($countryCodes as $code):?>
                        <option value="<?php echo $code['country_code'];?>"><?php echo $code['country_code'];?> - <?php echo $code['country_name'];?></option>
                        <?php endforeach;?>
                    </select>
                </div>
                <div>
                    <table id="exception_isp_table" class="display"  class="edit_dialog">
                        <thead>
                        <tr>
                            <th>ISP name</th>
                            <th>IPs</th>
                            <th>&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div >
                    <div style="float:left;">
                        Show:
                        <input type="checkbox" id="show_unselected" value="unselected" checked="checked" title="Show available" onchange="refreshISPTable()"/> <span  class="ui-icon ui-icon-plus"  style="float: right;"></span>
                    </div>
                    <div style="float:left;">
                        <input type="checkbox" id="show_selected" value="selected" checked="checked" title="Show selected" onchange="refreshISPTable()"/> <span  class="ui-icon ui-icon-check" style="float: right;"></span>
                    </div>
                    <div style="float:right;">
                        <input type="button" id="button_unselect_isp" value="Un-select All Searched"/>
                        <input type="button" id="button_select_isp" value="Select All Searched"/>
                        <input type="button" id="button_clear_isp" value="Clear All"/>
                    </div>
                    <div style="clear:both;"></div>
                </div>
                <div>
                    <label for="exception_ips">Exception IPs: </label>
                    <textarea id="exception_ips" name="ips" placeholder="Add IPs" class="edit_dialog"></textarea>
                    <span class="ui-icon ui-icon-close" style="float: right;"></span>
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
                    <span class="ui-icon ui-icon-close" style="float: right;"></span>
                </div>
                <div>
                    <label for="exception_url">Exception URL: </label>
                    <input type="text" id="exception_url"  name="exception_url" class="edit_dialog">
                    <span class="ui-icon ui-icon-close" style="float: right;"></span>
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

