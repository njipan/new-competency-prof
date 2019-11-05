<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('requests/DeleteMaterialRequest.php');
require_once('requests/GeneralRequest.php');

require_once('constants/Subtype.php');

require_once('repos/MaterialRepository.php');

require_once('strategies/upload/AzureUpload.php');
require_once('strategies/download/AzureDownload.php');
class Material extends BaseController {

    static protected $JKA_DB = 'JKA_DB';
    static protected $CMS_DB = 'CMS_DB';
    static protected $PERPAGE = 5;
    static protected $STORAGE_DIR = "Competency Profiling\\Application\\";

    public function __construct(){
        parent::__construct();
    }

    public function get(){
        return $this->restUris(__FUNCTION__);
    }

    public function get_get(){
        if(empty($_POST['id']) || empty($_POST['subitem'])){
            http_response_code(422);
            return $this->httpRequestInvalid('Subtype record is not exist');
        }
        $params = [
            '_SubTypeID' => $_POST['id'],
	        '_N_SUBITEM_ID' => $_POST['subitem'],
        ];
        $materials = $this->sp('bn_JKA_GetMaterialsBySubType', $params, self::$JKA_DB);
        if(empty($materials)){
            return $this->httpRequestInvalid('Error when deleting data');
        }
        return $this->load->view('json_view', [
            'json' => $materials->result()
        ]);
    }

    public function delete(){
        return $this->restUris(__FUNCTION__);
    }

    public function delete_post(){
        $request = new DeleteMaterialRequest();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        $_POST = $request->data();
        $params = [
            '_MaterialID' => $_POST['material_id'],
            '_UserUp' => $this->getLecturerCode(),
        ];
        $deleted_material = $this->sp('bn_JKA_DeleteMaterialByID', $params, self::$JKA_DB);
        if(empty($deleted_material)){
            return $this->httpRequestInvalid('Error when deleting data');
        }
        return $this->load->view('json_view', [
            'json' => $deleted_material->result()[0]
        ]);
    }

    public function download(){
        return $this->restUris(__FUNCTION__);
    }

    public function download_post(){
        $request = new GeneralRequest();
        $repo = new MaterialRepository();
        $data = $request->data();
        if(empty($data['id']) || !$material = $repo->get($data['id'])){
            http_response_code(422);
            return $this->httpRequestInvalid('Invalid parameter request');
        }
        $azureDownload = new AzureDownload();
        return $azureDownload
                ->prepare(self::$STORAGE_DIR.$material->LocationFile, $material->LocationFile)
                ->download();
        
    }

}