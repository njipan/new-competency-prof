<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once(__DIR__.'../../../contracts/Downloadable.php');

class AzureDownload implements Downloadable {

	private $fullpath;
	private $downloaded_name;

	public function download(){
		if(empty($this->fullpath) || empty($this->downloaded_name)) return null;
		$ci = get_instance();
		$ci->load->config('apps');
		$azure = new AzureAPI();
        return $azure->generateLinkDownload($this->fullpath, $this->downloaded_name);
	}

	public function prepare($fullpath, $downloaded_name){
		$this->fullpath = $fullpath;
		$this->downloaded_name = $downloaded_name;
		return $this;
	}

}