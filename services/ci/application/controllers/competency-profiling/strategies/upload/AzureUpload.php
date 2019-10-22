<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(__DIR__.'../../../contracts/Uploadable.php');


class AzureUpload implements Uploadable {

	public $file;
	public $filename;

	public function __construct($file){
		$this->file = $file;
		$this->filename = $this->generateFilename();
	}

	public function upload(){
		if(empty($this->file)){
			return new \Exception('File is required');
		} 
		$ci = get_instance();
		$ci->load->config('apps');
		$directory = $ci->config->item('AzureCloudStorage');
		$filepath = $this->directory();
		
		$fullpath = $filepath."\\".$this->filename;
		try{
            $content = fopen($this->file["tmp_name"], "r");
            $azure = new AzureAPI();
            $azure->uploadFile($filepath, $this->filename, $content);
            return $this->filename;
        }catch(Exception $e){
            return null;
        }
	}

	public function generateFilename(){
		return $_SESSION['employeeID'].'_'.date('d-m-Y.H_i_s').'.'.pathinfo($this->file['name'], PATHINFO_EXTENSION);
	}
    
    public function directory(){
    	return 'Competency Profiling\\Application';
    }

}