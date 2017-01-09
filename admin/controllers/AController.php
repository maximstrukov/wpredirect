<?php

/**
 * Description of AController
 *
 * @author Ivan T.
 */
abstract class AController {
    const POS_HEAD = 1;
    const POS_END = 2;
    
    protected $layout = 'main';
    protected $aMetaTagsStack = array();
    protected $aScriptsStack = array();
    protected $aScriptFilesStack = array();
    protected $aStyleSheetStack = array();
    
    protected function render($view, array $aData = array(), $return = false){
        $viewPath = $this->getViewPath().DIRECTORY_SEPARATOR.$view.'.phtml';
        
        $content = $this->renderInternal($viewPath, $aData);
        if ($return) echo $content; // return $content;
        else {
            # render layout
            $content = str_replace('{{content}}', $content, $this->getLayout());
            $this->injectCode($content);
            echo $content;
        }
    }
    
    public function viewPartial($partialFile, $aData = array())
    {
        $path = ROOT_DIR.'views'.DIRECTORY_SEPARATOR.'partials';
        $viewPath = $path.DIRECTORY_SEPARATOR.$partialFile.'.phtml';
        echo $this->renderInternal($viewPath, $aData);
    }
    
    private function getViewPath(){
        $viewDir = strtolower(str_replace('Controller', '', get_called_class()));
        return $path = ROOT_DIR.'views'.DIRECTORY_SEPARATOR.$viewDir;
    }
    
    private function getLayout(){
        $path = ROOT_DIR.'views'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$this->layout.'.phtml';
        
        ob_start();
        ob_implicit_flush(false);
        require($path);
        
        return ob_get_clean();
    }
    
    private function renderInternal($_viewFile_,$_data_=null){
		// we use special variable names here to avoid conflict when extracting data
		if(is_array($_data_))
			extract($_data_,EXTR_PREFIX_SAME,'data');
		else
			$data=$_data_;
        
        ob_start ();
        ob_implicit_flush(false);
        require($_viewFile_);
        
        return ob_get_clean();
    }
    
    /**
     * This JS code will be injected into $position position
     * 
     * @param string $name
     * @param string $js
     * @param int $position 
     */
    public function registerScript($name, $js, $position = self::POS_HEAD){
        $this->aScriptsStack[$position][$name] = $js;
    }
    
    public function registerScriptFile($file, $position = self::POS_HEAD){
        $this->aScriptFilesStack[$position][$file] = $file;
    }
    
    public function registerStyleSheetFile($file){
        $this->aStyleSheetStack[self::POS_HEAD][$file] = $file;
    }    
    
    private function injectCode(&$html){
        if (empty($this->aMetaTagsStack) && empty($this->aScriptsStack) && empty($this->aScriptFilesStack) && empty($this->aStyleSheetStack)) return;
        
        $posHead = stripos($html, '</head>');
        $posBody = stripos($html, '</body>');
        $_html = substr($html, 0, $posHead-1);
        
        if (!empty($this->aMetaTagsStack)){
            $_html .= PHP_EOL;
            foreach($this->aMetaTagsStack as $meta){
                $_html .= $meta.PHP_EOL;
            }
        }
        
        if (isset($this->aStyleSheetStack[self::POS_HEAD])){
            foreach($this->aStyleSheetStack[self::POS_HEAD] as $file){
                $_html .= "\t<link rel=\"stylesheet\" href=\"$file\" type=\"text/css\" media=\"screen\" />\n";
            }
        }        
        
        if (isset($this->aScriptFilesStack[self::POS_HEAD])){
            foreach($this->aScriptFilesStack[self::POS_HEAD] as $file){
                $_html .= "\t<script type=\"text/javascript\" src=\"$file\"></script>\n";
            }
        }
        
        if (isset($this->aScriptsStack[self::POS_HEAD])){
            foreach($this->aScriptsStack[self::POS_HEAD] as $script){
                $_html .= "\t<script type=\"text/javascript\"><!-- \n".$script."\n //--></script>\n";
            }
        }
        
        $_html .= substr($html, $posHead, $posBody - $posHead);
        
        if (isset($this->aScriptFilesStack[self::POS_END])){
            foreach($this->aScriptFilesStack[self::POS_END] as $file){
                $_html .= "\t<script type=\"text/javascript\" src=\"$file\"></script>\n";
            }
        }
        if (isset($this->aScriptsStack[self::POS_END])){
            foreach($this->aScriptsStack[self::POS_END] as $script) $_html .= "\t<script type=\"text/javascript\"><!-- \n".$script."\n //--></script>\n";
        }
        
        $_html .= substr($html, $posBody, strlen($html) - $posBody);
        $html = $_html;
    }
}
