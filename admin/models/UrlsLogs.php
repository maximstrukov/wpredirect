<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlsLogs
 *
 * @author dmitry
 */
class UrlsLogs extends BaseModel 
{
    public $id;
    public $added;        
    public $added_date;  
    public $url_id;      
    public $type;        
    public $remote_ip;
    public $user_agent;  
    public $isp_name;    
    public $country_code;
    public $start_ip;
    public $end_ip;
    
    protected $table = 'urls_logs';
    
    /**
     * getVisitsCnt 
     * @desc return count of visits any of advertisers from mini-site
     * @param type $start
     * @param type $end
     * @param int $url_id - advertiser id 
     * @param int $site_id - site id
     * @param int $type - visits type ("redirect" or "exception")
     */
    public function getVisitsCnt($start, $end, $url_id = false, $site_id = false, $type=false, $group_by_added_date = false)
    {
        $filter = array(); 
        $siteFiltesSQL = ''; 
        if($site_id && $site_id>0) {
            
            $siteFiltesSQL = 'INNER JOIN urls ON urls.id = urls_logs.url_id AND urls.site_id = :site_id';   
            $filter[':site_id'] = $site_id; 
        }
        
        $campaignIdSQL = ''; 
        if($url_id) {
            $campaignIdSQL = ' AND urls_logs.url_id = :url_id';
            $filter[':url_id'] = intval($url_id); 
        }
        
        $typeFilterSQL = ''; 
        if($type) {
            $typeFilterSQL = ' AND urls_logs.type = :type ';
            $filter[':type'] = $type; 
        }
        
        $groupOrderSQL = ''; 
        if($group_by_added_date) {
            $groupOrderSQL = " GROUP BY urls_logs.added_date ORDER BY urls_logs.added_date ASC "; 
        }
        
        $sql = "SELECT 	
                    COUNT(urls_logs.id) AS cnt,
                    urls_logs.added_date
                FROM urls_logs
                ".$siteFiltesSQL."
                WHERE urls_logs.added_date BETWEEN :start AND :end ".$campaignIdSQL." ".$typeFilterSQL." ".$groupOrderSQL;
        
        $filter[':start'] = $start; 
        $filter[':end'] = $end;
        //echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        
        return $smtm->fetchAll();
    }
    
    public function getTotalRowCnt($start, $end, $url_id = false, $site_id = false, $search = false)
    {
        $result = array();
        
        $sWhere = "";
        $urlsSQL = ""; 
        if($search) {    
            //the columns are to be searched
            $aColumns = array('urls_logs.added_date', 'urls.name');
            
            $sWhere = ' AND (';
            for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
                $sWhere .= $aColumns[$i]." LIKE '%".$search."%' OR ";
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
            
            if(!$site_id)
                $urlsSQL = " INNER JOIN urls ON urls.id = urls_logs.url_id ";
        }        
        
        $filter = array(); 
        $campaignIdSQL = ''; 
        if($url_id) {
            $campaignIdSQL = ' AND urls_logs.url_id = :url_id';
            $filter[':url_id'] = intval($url_id); 
        } 
        
        $siteFiltesSQL = ''; 
        if($site_id && $site_id>0) {
            
            $siteFiltesSQL = 'INNER JOIN urls ON urls.id = urls_logs.url_id AND urls.site_id = :site_id';   
            $filter[':site_id'] = $site_id; 
        }
        
        $sql = "SELECT 	
                    COUNT(urls_logs.id) AS cnt
                FROM urls_logs
                ".$urlsSQL."
                ".$siteFiltesSQL."
                WHERE urls_logs.added_date BETWEEN :start AND :end ".$campaignIdSQL." ".$sWhere." GROUP BY urls_logs.added_date, urls_logs.url_id ";
        
        $filter[':start'] = $start; 
        $filter[':end'] = $end;
        //echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        $result = $smtm->fetchAll();        
        
        return count($result); 
    }
    
    /**
     * method getStatisticData
     * @desc get full statistic data from db
     * @param string $start
     * @param string $end
     * @param int $url_id
     * @param int $site_id
     * @param string $search
     * @param string $sLimit
     * @return array 
     */
    public function getStatisticData($start, $end, $url_id = false, $site_id = false, $search = false, $sLimit = '')
    {
        $result = array();
        
        $sWhere = "";
        if($search) {    
            //the columns are to be searched
            $aColumns = array('urls_logs.added_date', 'urls.name');
            
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
            $campaignIdSQL = ' AND urls_logs.url_id = :url_id';
            $filter[':url_id'] = intval($url_id); 
        } 
        
        $siteFiltesSQL = ''; 
        if($site_id && $site_id>0) {
            
            $siteFiltesSQL = ' AND urls.site_id = :site_id';   
            $filter[':site_id'] = $site_id; 
        }        
        
        $sql = "SELECT 
                    urls_logs.added_date,
                    COUNT(urls_logs.url_id) AS cnt, 
                    urls.name,
                    urls_logs.type,
                    urls.id as url_id
                FROM urls_logs 
                INNER JOIN urls ON urls.id = urls_logs.url_id ".$siteFiltesSQL."
                WHERE urls_logs.added_date BETWEEN :start AND :end ".$campaignIdSQL." ".$sWhere."
                GROUP BY urls_logs.added_date, urls_logs.url_id, urls_logs.type ".
                $sLimit;
        
        $filter[':start'] = $start; 
        $filter[':end'] = $end;    
        
        //echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        $result = $smtm->fetchAll();
        
        return $result;
    }
    
    public function getAddedDates($minDate = false)
    {
        $filter = array();
        $minDateSQL = ''; 
        if($minDate) {
            $minDateSQL = 'WHERE  urls_logs.added_date >= :added_date ';
            $filter[':added_date'] = $minDate;
        }    
        
        $sql = 'SELECT 
                    urls_logs.added_date
                FROM urls_logs 
                '.$minDateSQL.'
                GROUP BY urls_logs.added_date';    
        
//echo '<pre>'; print_r($filter); die($sql);
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        $result = $smtm->fetchAll();

        return $result;
    }
    
    public function getStatisticByDate($added_date, $urls_logs_id = false) 
    {
        $filter = array();
        $startIdSQL = ''; 
        if($urls_logs_id) {
            $startIdSQL = 'AND urls_logs.id > :id ';
            $filter[':id'] = $urls_logs_id;
        }
        
        $sql = 'SELECT 
                    urls_logs.added_date,
                    COUNT(urls_logs.url_id) AS cnt, 
                    urls.name,
                    urls_logs.type,
                    urls.id as url_id,
                    urls_logs.id as urls_logs_id,
                    urls.site_id
                FROM urls_logs 
                INNER JOIN urls ON urls.id = urls_logs.url_id
                WHERE urls_logs.added_date = :added_date '.$startIdSQL.'
                GROUP BY urls_logs.added_date, urls_logs.url_id, urls_logs.type';
        $filter[':added_date'] = $added_date;
        
//echo '<pre>'; print_r($filter); die($sql);      
        $smtm = app::inst()->db->prepare($sql);
        $smtm->execute($filter);
        $result = $smtm->fetchAll();
        
        return $result;
    }
    
}

?>
