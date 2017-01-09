<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Log
 *
 * set level consol: phpunit ...  -d log_level=INFO
 * get print_r($_SERVER['argv']);
 * 
 * @author Scratch
 */

require_once 'Zend/Log.php';
require_once 'Zend/Log/Writer/Stream.php';

class Log {

    protected static $instance;
    private $testName;
    private $timeStart;
    private $timeEnd;

    private $fileDate;

    private $fileName;
    
    static $levels = array(
        'INFO'=>Zend_Log::INFO,
        'WARN'=>Zend_Log::WARN,
        'ERR'=>Zend_Log::ERR,
        'DEBUG'=>Zend_Log::DEBUG
    );

    public static function l($message, $level = Zend_Log::INFO) 
    {
        if(in_array($level, Log::$levels))
            Log::getInstance()->log($message, $level);
    }

    public static function start($message) 
    {
        Log::getInstance()->logStart($message);
    }

    public static function end() 
    {
        Log::getInstance()->logEnd();
    }


    public function __construct() 
    {
        $this->fileDate = @date('Y-m-d_',time()); //Y-m-d_H.i.s
    }

    public static function init($filename)
    {
        Log::getInstance()->setFileName($filename);
    }

    static function getInstance() 
    {
        if (!self::$instance) {
            self::$instance = new Log();
        }
        
        return self::$instance;
    }


    public function setFileName($filename) 
    {
        $this->fileName = $filename;
    }

    private function _log($message, $level = Zend_Log::INFO)
    {
        $logger = new Zend_Log();
        $path = dirname(__FILE__).'/../logs/'; 
        @chmod($path, 0777);
        $writer = new Zend_Log_Writer_Stream($path.$this->fileDate.$this->fileName.'.log'); //$path.$this->fileDate.'_'.$this->fileName.'.log'
        $logger->addWriter($writer);
        $logger->log($message, $level);

        return true;
    }

    public function log($message, $level = Zend_Log::INFO) 
    {
        if ($this->testName) {
            
            $message = $this->testName . " : " . $message;
        }
        
        $this->_log($message, $level);
    }

    public function logStart($test) 
    {
        if (isset($this->timeStart) && $this->timeStart) {
            
            $this->logEnd();
        }
        
        $this->testName = $test;
        $this->timeStart = microtime(true);
        $this->log('started at ' . $this->timeStart);
    }

    public function logEnd() 
    {
        $this->timeEnd = microtime(true);
        $this->log('ended at ' . $this->timeEnd . ', totally '. sprintf("%.4f", ($this->timeEnd - $this->timeStart)));
        $this->timeStart = false;
        $this->testName = false;
    }

}