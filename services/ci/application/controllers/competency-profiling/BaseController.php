<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
    Created By : Panji Kurnia Nugroho
    Used for this module
*/
class BaseController extends BM_Controller {

    public function __construct(){
        parent::__construct();
    }

    public function getCurrentUser(){
        return $_SESSION['employeeID'] ?: $_SESSION['UserID'];
    }

    public function getLecturerCode(){
        if(empty($_SESSION['UserID'])) return null;
        $lecturer_code = $_SESSION['UserID'];
        if(strpos($lecturer_code, 'D') !== 0) return null;
        return $lecturer_code;
    }

    protected function uploadFile($file, $user_id, $allowed_types=[], $path="/uploads/"){
        $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        $filename = date("d-m-Y_h-m-s.u")."_".$user_id.".".$ext;
        $_FILES["temp_upload_file"] = $file;
        $uploadConfig["remove_space"] = false;
        $uploadConfig["upload_path"] = __DIR__.$path;
        $uploadConfig["file_name"] = trim($filename);
        $uploadConfig["allowed_types"] = is_array($allowed_types) ? implode("|", $allowed_types) : $allowed_types;
        $this->load->library('upload', $uploadConfig);
        $this->upload->initialize($uploadConfig);
        if(!$this->upload->do_upload('temp_upload_file')){
            var_dump($this->upload->display_errors());
            return null;
        }
        $result = $this->upload->data('temp_upload_file');
        unset($_FILES["temp_upload_file"]);
        
        return $path.$result["file_name"];
    }

    protected function restURIs($prefix, $json=false){
    	if($json) $_POST = $this->getBody();
        $type = strtoupper($_SERVER['REQUEST_METHOD']);
        $func_name = $prefix."_".strtolower($type);
        if(!method_exists($this, $func_name)){
            return $this->httpRequestInvalid();
        }
        return $this->{$func_name}();
    }

    protected function httpRequestInvalid($message=null){
        http_response_code(422);
        return $this->load->view("json_view",[
            'json' => [
                'message' => $message ?: 'Invalid request' ,
            ],
        ]);
    }

    protected function getBody(){
        return json_decode(file_get_contents("php://input"), true);
    }

}