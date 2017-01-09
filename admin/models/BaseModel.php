<?php

/**
 * Description of BaseModel
 *
 * @author ivan
 */
abstract class BaseModel {
    
    private $aErrors = array();
    private static $instance;
    protected $primaryKey = 'id';


    private $aMessages = array(
        'required'=>'Is required',
        'unique'=>'Already exists',
    );
    
    public static function model($class = __CLASS__){
        if (empty(self::$instance))
            self::$instance = new $class;
        
        return self::$instance;
    }

    public function rules(){
        return array();
    }
    
    public function validate(){
        $this->aErrors = array();
        if ($this->rules()){
            foreach($this->rules() as $aRule){
                $fields = array_map('trim', explode(',', $aRule[0]));
                $method = $aRule[1];
                if (in_array($method, array_keys($this->aMessages)))
                    $method = 'validator'.ucfirst($method);
                
                foreach($fields as $field) call_user_func(array($this, $method), $field);
            }
        }
        return empty($this->aErrors);
    }
    
    public function addError($key, $val){
        $this->aErrors[$key] = $val;
    }
    
    public function getErrors(){
        return $this->aErrors;
    }
    
    public function __set($name, $value) {
        if ($name == 'attributes'){
            foreach($value as $k=>$v) $this->$k = $v;
        } else $this->$name = $value;
    }
    
    public function __get($name){
        if ($name == 'attributes'){
            $attr = array();
            foreach(get_class_methods($this) as $k=>$v) $attr[$k] = $v;
            return $attr;
        } else return $this->$name;
    }
    
    public function validatorRequired($field){
        if (empty($this->$field)) $this->addError($field, $this->aMessages['required']);
    }
    
    public function validatorUnique($field){
        $isNew = empty($this->{$this->primaryKey});
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$field} = :{$field}";
        if (!$isNew)
            $sql .= ' AND '.$this->{$this->primaryKey}.' != :id';
        
        $stmt = app::inst()->db->prepare($sql);
        $stmt->bindValue(":{$field}", $this->$field);
        if (!$isNew)
            $stmt->bindValue(':id', $this->{$this->primaryKey});
        
        if ($stmt->execute()){
            $result = array_filter($stmt->fetch(PDO::FETCH_NUM));
            
            if (!empty($result))
                $this->addError($field, $this->aMessages['unique']);
        }
        
    }
    
    /**
     * method getDataByFields
     * @param array $whereFields - fields for where conditional
     * @param boolean $allRows - fetching all rows or not 
     * @param array $orderFields - those fields in which the procedure is carried out data
     * @param array $fetchingRows - fields are extracted
     * @param array $groupFields
     * @param int $limit
     * @return array 
     */
    
    public function getDataByFields($whereFields = array(), $allRows = false, $orderFields = array(), $fetchingRows = array('*'), $groupFields = array(), $limitData = false)
    {
        $result = array(); 
        
        // set where conditional 
        $filter = array(); 
        $where = '';
        if(!empty($whereFields)) {
            foreach($whereFields as $fieldName => $value) {

                $filter[":{$fieldName}"] = $value;
                $where .= empty($where) ? " WHERE `{$fieldName}` = :{$fieldName}" : " AND `{$fieldName}` = :{$fieldName}";
            }
        }

        // set group 
        $group = '';
        if(!empty($groupFields)) {

            $cnt = 1; 
            foreach($groupFields as $groupVal) {

                $group .= ($cnt === 1) ? ' GROUP By '.$groupVal : ' ,'.$groupVal;
                $cnt++; 
            }
        }        
        
        // set order 
        $order = '';
        if(!empty($orderFields)) {

            $cnt = 1; 
            foreach($orderFields as $orderVal) {

                $order .= ($cnt === 1) ? ' ORDER By '.$orderVal : ' ,'.$orderVal;
                $cnt++; 
            }
        }
        
        //set limit
        $limit = ''; 
        if(intval($limitData) && 
            $limitData>0 &&
                !empty($limitData)) {
            
                $limit = ' LIMIT '.$limitData;
        }
        
        // set fetching fields
        $fFields = ''; 
        foreach($fetchingRows as $fRow)
            $fFields .= (empty($fFields)) ? $fRow : ' ,'.$fRow;
        
        $sql = "SELECT ".$fFields." FROM ".$this->table." " . $where . $group . $order . $limit;
        
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);

        if($allRows)
            $result = $smtm->fetchAll();
        else $result = $smtm->fetch();
            
        return $result; 
    }
    
    /**
     * method insertData
     * @param array $data - include key:fields, value:value of insered fields. For example: array('field' => 'valueOfInsredField');
     * @return last insert id or result of execution
     */
    
    public function insertData($data) 
    {
        if(!empty($data)) {
            
            $set = ''; 
            $upData = array(); 
            $cnt = 0;
            foreach($data as $field => $value) {
                
                $placeholder = ':'.$field;
                $upData[$placeholder] = $value; 

                if($field!='id')
                    if($cnt!=0)
                        $set .= ',`'.$field.'` = '.$placeholder;
                    else $set .= '`'.$field.'` = '.$placeholder;
                
                $cnt++;                 
            }
            
            // insert 
            $sql = 'INSERT INTO `'.$this->table.'` SET '.$set.' ';
            $smtm = app::inst()->db->prepare($sql);
            $execRes = $smtm->execute($upData);
            
            $lastID = false; 
            $lastID = @app::inst()->db->lastInsertId();
            
            return ($lastID) ? $lastID : $execRes;
        }        
    }
    
    /**
     * method updateData
     * @param array $data
     * @param array $whereFields 
     * @return int 
     */
    
    public function updateData($data, $whereFields)
    {
        if(!empty($data)) {
            
            $set = ''; 
            $upData = array(); 
            $cnt = 0;
            foreach($data as $field => $value) {
                
                $placeholder = ':'.$field;
                $upData[$placeholder] = $value; 

                if($field!='id')
                    if($cnt!=0)
                        $set .= ',`'.$field.'` = '.$placeholder;
                    else $set .= '`'.$field.'` = '.$placeholder;
                
                $cnt++;                 
            }
            
            // set where conditional 
            $where = '';
            if(!empty($whereFields)) {
                foreach($whereFields as $fieldName => $value) {

                    $upData[":{$fieldName}"] = $value;
                    $where .= empty($where) ? " WHERE `{$fieldName}` = :{$fieldName}" : " AND `{$fieldName}` = :{$fieldName}";
                }
            }            
            
            // updating 
            $sql = 'UPDATE `'.$this->table.'` SET '.$set.' '.$where;
            $smtm = app::inst()->db->prepare($sql);
            return $smtm->execute($upData);
        }        
    }
    
    /**
     * method deleteData
     * @param array $whereClause
     * @return int 
     */
    
    public function deleteData($whereClause)
    {
        $upData = array(); 
        // set where conditional 
        $where = '';
        if(!empty($whereClause)) {
            foreach($whereClause as $fieldName => $value) {

                $upData[":{$fieldName}"] = $value;
                $where .= empty($where) ? " WHERE {$fieldName} = :{$fieldName}" : " AND {$fieldName} = :{$fieldName}";
            }
        }
        
        // deleting 
        $sql = 'DELETE FROM `'.$this->table.'` '.$where;
        $smtm = app::inst()->db->prepare($sql);
        return $smtm->execute($upData);
    }
    
    /**
     * method getCustomData
     * @desc it is advisable not to use, can be used in extremely rare cases possible to use getDataByFields
     * @param string $whereClause - custom where clause 
     * @param array $fetchingRows - fields are extracted
     * @param bool $fetchAll - if set true fetching all rows if false fetching only one row
     * @return array 
     */
    
    public function getCustomData($whereClause = '', $fetchingRows = array('*'),  $fetchAll = true)
    {
        $uData = array();

        if(!empty($whereClause)) {

            // set fetching fields
            $fFields = ''; 
            foreach($fetchingRows as $fRow)
                $fFields .= (empty($fFields)) ? $fRow : ' ,'.$fRow;            
            
            $sql = 'SELECT '.$fFields.' FROM '.$this->table.' WHERE '.$whereClause;
            //die($sql);
            $smtm = app::inst()->db->prepare($sql);
            $smtm->execute();
            if($fetchAll)
                $uData = $smtm->fetchAll();
            else $uData = $smtm->fetch();
        }

        return $uData;
    }    
}