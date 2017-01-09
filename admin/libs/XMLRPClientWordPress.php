<?php


class XMLRPClientWordPress
{
    var $XMLRPCURL = "";
    var $UserName  = "";
    var $PassWord = "";

    // constructor
    public function __construct($xmlrpcurl, $username, $password) 
    {
        $this->XMLRPCURL = $xmlrpcurl;
        $this->UserName  = $username;
        $this->PassWord = $password;

    }

    function send_request($requestname, $params) 
    {
        $request = xmlrpc_encode_request($requestname, $params, array( 'escaping' => array(
                                                                                        'cdata', 
                                                                                        //'non-ascii', 
                                                                                        //'non-print', 
                                                                                        //'markup'
                                                                                                ), 
                                                                        'encoding'=> 'UTF-8'));  //'escaping'=>'markup', //'escaping'=>'non-ascii'
        $ch = curl_init();
//        echo '<pre>';
//        var_dump($request); 
//        var_dump($this->XMLRPCURL);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        // set curl header
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));        
        curl_setopt($ch, CURLOPT_URL, $this->XMLRPCURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $results = curl_exec($ch);
//        var_dump($results);
//        die();
        curl_close($ch);
        return $results;
    }

    function create_post($title, $body, $category, $keywords='', $customfields = array(), $encoding='UTF-8', $mediaId = '', $mt_excerpt = '', $wp_page_order = 2147483647)
    {
        $title = htmlentities($title,ENT_NOQUOTES,$encoding);
        $keywords = htmlentities($keywords,ENT_NOQUOTES,$encoding);
        
        $content = array(
                'title'=>$title,
                'description'=>$body,
                'mt_allow_comments'=>0,  // 1 to allow comments
                'mt_allow_pings'=>0,  // 1 to allow trackbacks
                'post_type'=>'post',
                'mt_keywords'=>$keywords,
                'categories'=>(array)$category,
                'custom_fields' => $customfields, 
                'mt_excerpt' => $mt_excerpt,
                'post_status' => 'publish',
                'wp_page_order' => $wp_page_order
        );

        if(!empty($mediaId))
            $content['wp_post_thumbnail'] = $mediaId; 
        //echo json_encode($content).PHP_EOL;
        $params = array(0,$this->UserName,$this->PassWord,$content,true);

        return $this->send_request('metaWeblog.newPost',$params);	
    }

    function edit_post($post_id, $title, $body, $category, $keywords='', $customfields = array(), $encoding='UTF-8', $mediaId = '', $mt_excerpt = '', $wp_page_order = 2147483647)
    {
        $title = htmlentities($title,ENT_NOQUOTES,$encoding);
        $keywords = htmlentities($keywords,ENT_NOQUOTES,$encoding);

        $content = array(
                'title'=>$title,
                'description'=>$body,
                'mt_allow_comments'=>0,  // 1 to allow comments
                'mt_allow_pings'=>0,  // 1 to allow trackbacks
                'post_type'=>'post',
                'mt_keywords'=>$keywords,
                'categories'=>(array)$category,
                'custom_fields' => $customfields,
                'mt_excerpt' => $mt_excerpt,
                'post_status' => 'publish',
                'wp_page_order' => $wp_page_order
        );

        if(!empty($mediaId))
            $content['wp_post_thumbnail'] = $mediaId; 
        //echo json_encode($content).PHP_EOL;
        $params = array($post_id,$this->UserName,$this->PassWord,$content,true);

        return $this->send_request('metaWeblog.editPost',$params);
    } 

    function update_categories($post_id, $category)
    {
        $content = array(
            'categories'=>(array)$category,
        );

        $params = array($post_id,$this->UserName,$this->PassWord,$content,true);

        return $this->send_request('metaWeblog.editPost',$params);
    }
    
    public function set_published($post_id, $state = true)
    {
        $content = array();

        $params = array($post_id, $this->UserName, $this->PassWord, $content, $state);

        return $this->send_request('metaWeblog.editPost',$params);        
    }

    function delete_post($post_id) 
    {
        $params = array(0, $post_id, $this->UserName, $this->PassWord);
        return $this->send_request('metaWeblog.deletePost',$params);
    }
    
    /**
     * get_post
     * @desc uses "metaWeblog.getPost" wordpress xmlrpc method
     * @param int $post_id 
     * @return array 
     */
    function get_post($post_id) 
    {    
        $params = array($post_id, $this->UserName, $this->PassWord);      
        return $this->send_request('metaWeblog.getPost',$params);
    }
    
    function getAllPosts() 
    {    
        $params = array(0, $this->UserName, $this->PassWord);
        return $this->send_request('wp.getPosts',$params);
    }    
    
    /**
     * getPost
     * @desc uses wp.getPost wordpress xmlrpc method
     * @param int $post_id 
     * @return array 
     */
    function getPost($post_id)
    {    
        $params = array(0, $this->UserName, $this->PassWord, $post_id);
        return $this->send_request('wp.getPost',$params);
    }    
    
    function getRecentPosts() 
    {   
        $params = array(0, $this->UserName, $this->PassWord);
        return $this->send_request('metaWeblog.getRecentPosts',$params);
    }

    function create_mediaobject($filename, $filedata)
    {
        xmlrpc_set_type($filedata,'base64');
        $data = array(
            'name'  => $filename,
            'type'  => 'image/jpg',
            'bits'  => $filedata,
            true // overwrite
        );

        $params = array(0,$this->UserName,$this->PassWord, $data);
        return $this->send_request('metaWeblog.newMediaObject',$params);
    }

    function create_page($title,$body,$encoding='UTF-8')
    {
        $title = htmlentities($title,ENT_NOQUOTES,$encoding);

        $content = array(
                'title'=>$title,
                'description'=>$body
        );
        $params = array(0,$this->UserName,$this->PassWord,$content,true);

        return $this->send_request('wp.newPage',$params);
    }

    function display_authors()
    {
        $params = array(0,$this->UserName,$this->PassWord);
        return $this->send_request('wp.getAuthors',$params);
    }

    function sayHello()
    {
        $params = array();
        return $this->send_request('demo.sayHello',$params);
    }

    function getUserInfo()
    {
        $params = array(0,$this->UserName,$this->PassWord);
        return $this->send_request('blogger.getUserInfo',$params);
    }

    function getCategories() 
    {
        $params = array(0,$this->UserName,$this->PassWord);
        return $this->send_request('metaWeblog.getCategories',$params);
    }

    function deleteCategory($category_id)
    {
        $params = array(0, $this->UserName, $this->PassWord, $category_id);
        return $this->send_request('wp.deleteCategory',$params);
    }    

    function newCategory($category_name, $slug, $description, $parent_id = 0) 
    {
        $content = array(
            'name' => $category_name,
            'slug' => $slug,
            'parent_id' => $parent_id,
            'description' => $description
        );

        $params = array(0,$this->UserName,$this->PassWord,$content);

        return $this->send_request('wp.newCategory', $params);
    }

    function getOptions($option_name)
    {
        $content = array(
            'option' => $option_name
        );

        $params = array(0, $this->UserName, $this->PassWord, $option_name);

        return $this->send_request('wp.getOptions', $params);
    }

    function setOptions($option_name, $option_value) 
    {            
        $content = array(
            "$option_name" => "$option_value"
        );            

        $params = array(0, $this->UserName, $this->PassWord, $content);

        return $this->send_request('wp.setOptions', $params);
    }
}

?>