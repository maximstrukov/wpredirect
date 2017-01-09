<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Statistic
 *
 * @author dmitry
 */
class Statistic extends BaseModel
{
    public $id; 
    public $urls_logs_id;
    public $added_date;
    public $name;
    public $url_id;
    public $redirect;
    public $exception;
    
    protected $table = 'statistic';
    
    public function getStartPoint() 
    {
        $sql = "SELECT 	
                    MAX(urls_logs_id) AS start_point
                FROM ".$this->table;
        
        //echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute();
        $result = $smtm->fetch();
        if(isset($result['start_point']) && !empty($result['start_point'])) {
            return $result['start_point'];
        }
        else return false; 
    }
    
    public function getTotalRowCnt($start, $end, $url_id = false, $site_id = false, $search = false) 
    {
        $result = array();
        
        $sWhere = "";
        if($search) {    
            //the columns are to be searched
            $aColumns = array('added_date', 'name');
            
            $sWhere = ' AND (';
            for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
                $sWhere .= $aColumns[$i]." LIKE '%".$search."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }
        
        $filter = array(); 
        $campaignIdSQL = ''; 
        if($url_id) {
            $campaignIdSQL = ' AND url_id = :url_id';
            $filter[':url_id'] = intval($url_id); 
        }
        
        $siteFiltesSQL = ''; 
        if($site_id && $site_id>0) {
            
            $siteFiltesSQL = ' AND site_id = :site_id';
            $filter[':site_id'] = $site_id; 
        }
        
        $sql = "SELECT 	
                    COUNT(*) AS cnt
                FROM ".$this->table."
                WHERE added_date BETWEEN :start AND :end ".$campaignIdSQL." ".$siteFiltesSQL." ".$sWhere." ";
        
        $filter[':start'] = $start; 
        $filter[':end'] = $end;
        //echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        $result = $smtm->fetch(); 
        
        return ($result['cnt']) ? $result['cnt'] : 0;
    }
    
    public function getStatisticData($start, $end, $url_id = false, $site_id = false, $search = false, $sOrder = "", $sLimit = false) 
    {
        $result = array();
        
        $sWhere = "";
        if($search) {    
            //the columns are to be searched
            $aColumns = array('added_date', 'name');
            
            $sWhere = ' AND (';
            for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
                $sWhere .= $aColumns[$i]." LIKE '%".$search."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }
        
        $filter = array(); 
        $campaignIdSQL = ''; 
        if($url_id) {
            $campaignIdSQL = ' AND url_id = :url_id';
            $filter[':url_id'] = intval($url_id); 
        }
        
        $siteFiltesSQL = ''; 
        if($site_id && $site_id>0) {
            
            $siteFiltesSQL = ' AND site_id = :site_id';
            $filter[':site_id'] = $site_id; 
        }
        
        $sql = "SELECT 	
                    *
                FROM ".$this->table."
                WHERE added_date BETWEEN :start AND :end ".$campaignIdSQL." ".$siteFiltesSQL." ".$sWhere." ".$sOrder." ".$sLimit;
        
        $filter[':start'] = $start; 
        $filter[':end'] = $end;
        //echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        return $smtm->fetchAll(); 
    }
    

    /**
     * getVisitsCnt 
     * @desc return count of visits any of advertisers from mini-site
     * @param type $start
     * @param type $end
     * @param int $url_id - advertiser id 
     * @param int $site_id - site id
     * @param int $type - visits type ("redirect" or "exception")
     * @return mixed 
     */
    public function getVisitsCnt($start, $end, $url_id = false, $site_id = false, $type=false, $group_by_day = false, $group_by_month = false, $count_one = false)
    {
        $filter = array(); 
        $siteFiltesSQL = ''; 
        if($site_id && $site_id>0) {
            
            $siteFiltesSQL = ' AND site_id = :site_id';
            $filter[':site_id'] = $site_id;
        }
        
        $campaignIdSQL = ''; 
        if($url_id) {
            $campaignIdSQL = ' AND url_id = :url_id';
            $filter[':url_id'] = intval($url_id); 
        }
        
        $groupSQL = '';
        if($group_by_day) {
            $groupSQL = 'GROUP BY added_date ORDER BY added_date ASC'; 
        } else if($group_by_month) {
            $groupSQL = ' GROUP BY EXTRACT(MONTH FROM added_date) ORDER BY added_date ASC'; 
        }
        
        $sql = "SELECT 	
                    SUM(redirect) AS redirect, 
                    SUM(exception) AS exception,
                    (SUM(redirect) + SUM(exception)) AS cnt, 
                    added_date
                FROM ".$this->table."
                WHERE added_date BETWEEN :start AND :end ".$campaignIdSQL." ".$siteFiltesSQL." ".$groupSQL;
        
        $filter[':start'] = $start; 
        $filter[':end'] = $end;
        
        //if($group_by_month) {echo '<pre>'; print_r($filter); die($sql);}
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        
        // return array for rGraph data 
        if($group_by_day || $group_by_month || $count_one) {
            if ($count_one) return $smtm->fetchColumn(2);
            else return $smtm->fetchAll();
        }
        else $result = $smtm->fetch();
        
        if(!empty($result)) {
            
            $value = ($type == 'redirect') ? $result['redirect'] : (($type == 'exception') ? $result['exception'] : 0);
        } else $value = 0;
        
        return $value;
    }        
}

?>
