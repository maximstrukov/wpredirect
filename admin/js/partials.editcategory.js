/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/* 
 * [example]
 * html:
 *      <input id="cat_manag" type="button" value="Category manager" ></input>
 * js:      
 *   // Run Category manager 
 *   $('#cat_manag').click(function(){  
 *       var site_id = $('#id').val(); // need value of site id
 *       openEditCategoriesDialog(site_id); 
 *   });
 */

    $(document).ready(function() {
        _edit_cat_init();
        
        // delete category
        $('#del_category').click(function(){
            //deleteCategory(); 
        }); 
        
        //save category
        $('#edit_category').val('');
        $('#save_category').click(function() {
            /*var old_name = $('#manag_site_category').text();
            var new_name = $.trim($('#edit_category').val());
            var site_id = $('#site_id').val();            
            saveCategory(old_name, new_name, site_id);*/
        });
        
        //create category
        $('#create_category').click(function() {
            
            /*var parent_id = null;
            var category_name = $('input[name=root_name]').val();
            var site_id = $('#site_id').val();            
            createCategory(category_name, site_id, parent_id);*/
        }); 
        
        //add sub category add_sub
        $('#add_sub').click(function() {
            
            /*var parent_id = $('#manag_site_category').attr('native_id');
            var category_name = $('input[name=add_sub]').val();
            var site_id = $('#site_id').val();
            
            if(parent_id == '' ||
                parent_id == undefined) {

                alert('No category was chosen.'); 
                return false;                 
            }
            
            createCategory(category_name, site_id, parent_id);*/
        });
    });
    
    // category managment dialog (RUN DIALOG WIN)

    function openEditCategoriesDialog(id)
    { 
        showLoader();
        
        $( "#edit_categories_dialog" ).dialog( "open" );

        $('#site_id').val(id);
        
        $('.site_edit_category_caption').show();
        $('#set_mp').show();

        $('#categories').show(); 
        getCategoriesBySiteId(id);
            
        hideLoader();    
        
        return false;
    }    
    
    function _edit_cat_init() 
    {
        $( "#edit_categories_dialog" ).dialog({
            autoOpen: false,
            resizable: false,
            //  height:240,
            width:506,
            modal: true,
            buttons: {
//                "Save": function() {
//                    $('#edit_category_form').submit();
//                    clearErrorsEditForm();
//                },
                "Ok": function() {
                    $( this ).dialog( "close" );
                    //clearErrorsEditForm();
                }
            },
            close: function() {
                clearErrorsEditForm();
            }
        });        
    }
    
    function getCategoriesBySiteId(site_id)
    {
        $('#part_category_indicator').show();
        $('#manag_site_category').attr('disabled', 'disabled');    

        $('.treeview-gray').html('');

        $.ajax({
            type: 'POST',
            url: 'index.php?cont=campaign&act=getcategory',

            data:{
                site_id: site_id
            },

            dataType:'json',

            success:function(result) {

                $('#manag_site_category').html('None');

                if(!jQuery.isEmptyObject(result)) {

                    drawTree(0,result);

                    // fourth example
                    $("#black, #gray").treeview({
                            control: "#treecontrol",
                            persist: "cookie",
                            cookieId: "treeview-black"
                    });
                    
                    $("ul").find("span").css('cursor', 'pointer');
                    $("ul").find("li").css('cursor', 'pointer');                   
                }
            },

            complete: function() {
                $('#manag_site_category').removeAttr('disabled');
                $('#part_category_indicator').hide();            
            }
        });
        $('#edit_category').val('');
    }
    
    // event for each categiry in list
    function categoryHandler(el) {

        //$('#manag_site_category').html('');

        var parent_id = $(el).attr('parent_id');
        var id = $(el).attr('id');
        var name = $(el).text();

        $('#manag_site_category').attr('parent_id', parent_id);
        $('#manag_site_category').attr('native_id', id);
        $('#manag_site_category').text(name);
        $('#manag_site_category').css('color','#2D68AE');
        
        $("#edit_category").val(name);
        
    }    

    //check whatever id has a parant_id 
    function is_parent(id, data) 
    {
        for (key in data) {
            
            // for old response
//            var parentId = data[key].parentId;

            // for new response
            var parentId = data[key].parent_id;
            
            if(id == parentId)
                return true; 
        }
        return false;
    }
    
    function drawTree(sParentID, data) 
    {
        for (key in data) {
            
            // for old response
//            var id = data[key].categoryId; 
//            var name = data[key].categoryName; 
//            var parentId = data[key].parentId;            
//            
            // for new response
            var id = data[key].category_id; 
            var name = data[key].name; 
            var parentId = data[key].parent_id;     
            
            //console.log('id = '+id+', name = '+name+', parentId = '+parentId);
            
            if (sParentID == parentId) {
                
                // Добавили категорию
                $('ul[parent="'+sParentID+'"]').append($('<li onclick="categoryHandler(this);"></li>')
                    .attr("id", id)
                    .attr("parent_id", parentId)
                    .text(name)
                );
                
                if(is_parent(id, data)) {
                    
                    $('li[id="'+id+'"]').html($('<span onclick="categoryHandler(this);"></span>').attr('id', id).attr('parent_id', parentId).text(name));
                    $('li[id="'+id+'"]').removeAttr('onclick');
                    $('li[id="'+id+'"]').append($('<ul></ul>')
                        .attr("parent", id));
                    
                    drawTree(id, data);
                }
            }
        }       
    }

    function deleteCategory() 
    {
        var category_id = $('#manag_site_category').attr('native_id');
        var site_id = $('#site_id').val();

        $('#category_indicator').show();
        $('#manag_site_category').attr('disabled', 'disabled');

        if(category_id &&
            site_id) {
            if (confirm('Are you sure you want to delete this category?')) {
                $.ajax({
                    type: 'POST',
                    url: 'index.php?cont=site&act=delcategory',

                    data:{
                        category_id: category_id,
                        site_id: site_id
                    },

                    dataType:'json',

                    success:function(result) {

                        if(!jQuery.isEmptyObject(result)) {

                            getCategoriesBySiteId(site_id);
                            $('#manag_site_category').css('color','#000').html('None');
                        }

                    },

                    complete: function() {
                        $('#manag_site_category').removeAttr('disabled');
                        $('#category_indicator').hide();       
                    }
                });
            }
        }    
    }
    
    function createCategory(category_name, site_id, parent_id)
    {   
        if(category_name == '' ||
            category_name == undefined) {

            alert('Please enter a new category name.'); 
            return false; 
        }        
        
        if(site_id == '' ||
            site_id == undefined)        
            return false;
        
        if(!parent_id ||
            parent_id == undefined)
                parent_id = '';
        
        if(category_name &&
            site_id) {

            $('#create_category_indicator').show();
            $('input[name=root_name]').attr('disabled', 'disabled').val('');            

            $.ajax({
                type: 'POST',
                url: 'index.php?cont=site&act=createcategory',

                data:{
                    category_name: category_name,
                    site_id: site_id,
                    parent_id: parent_id
                },

                dataType:'json',

                success:function(result) {

                    if(!jQuery.isEmptyObject(result))
                        getCategoriesBySiteId(site_id);
                },

                complete: function() {
                    
                    $('#create_category_indicator').hide();
                    $('input[name=root_name]').removeAttr('disabled');
                }
            });                
        }         
    }

    function saveCategory(old_name, new_name, site_id)
    {   

        if (old_name == 'None') {
            alert('Please choose the category.');
            return false;
        }

        if (new_name == '' || new_name == undefined) {
            alert('Please enter a new category name.');
            return false;
        }
        
        if (site_id == '' || site_id == undefined) return false;
        
        if (old_name == new_name) return false;
        
        if (confirm("Are you sure you want to change the category name?\r\nIt will be changed only in redirect database, NOT on the remote website!")) {

            $('#create_category_indicator').show();
            $('#edit_category').attr('disabled', 'disabled').val('');

            $.ajax({
                type: 'POST',
                url: 'index.php?cont=site&act=savecategory',

                data:{
                    old_name: old_name,
                    new_name: new_name,
                    site_id: site_id,
                },

                dataType:'json',

                success:function(result) {

                    if(!jQuery.isEmptyObject(result))
                        getCategoriesBySiteId(site_id);
                },

                complete: function() {

                    $('#create_category_indicator').hide();
                    $('#edit_category').removeAttr('disabled');
                }
            });
        }
   
    }