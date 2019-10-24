<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');

require_once('requests/GeneralRequest.php');

require_once('repos/StaffRepository.php');

class Staff extends BaseController {

    static protected $JKA_DB = 'JKA_DB';

    public function __construct(){
    	parent::__construct();
    }

    public function proxy_lrc(){
    	return $this->restURIs(__FUNCTION__);
    }

    public function proxy_lrc_post(){
        return;
    	$request = new GeneralRequest();
    	$staffRepo = new StaffRepository();

    	$data = $request->data();
    	if(!$staff = $staffRepo->isStaffLRC($_SESSION['employeeID'])){
    		http_response_code(401);
    		return;
    	}
    }

}