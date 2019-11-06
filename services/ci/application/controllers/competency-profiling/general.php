<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('requests/SearchPeriodRequest.php');
require_once('requests/CreatePeriodRequest.php');
require_once('requests/UpdatePeriodRequest.php');
require_once('repos/PeriodRepository.php');
class General extends BaseController {

    static protected $JKA_DB = 'JKA_DB';
    static protected $CMS_DB = 'CMS_DB';
    static protected $PERPAGE = 5;
    static protected $TTL_ONE_DAY = 86400;

    public function __construct(){
        parent::__construct();
    }

    public function institutions(){
        $query_institutions = $this->sp("bn_JKA_GetInstitutions",[], self::$JKA_DB);
        $institutions = $query_institutions->result();        
		$payload = [ 
            'json' => $institutions 
        ];
		return $this->load->view('json_view', $payload);
    }

    public function statuses(){
        return $this->restUris(__FUNCTION__);
    }

    public function statuses_get(){
        $rows = $this->sp('bn_JKA_GetAllStatus', [], self::$JKA_DB);
        if(empty($rows)){
            return $this->httpRequestInvalid('Error occured when getting data');
        }
        return $this->load->view('json_view', [
            'json' => $rows->result(),
        ]);
        
    }

    public function leveldescs(){
        return $this->restUris(__FUNCTION__);
    }

    public function leveldescs_get(){
        $rows = $this->sp('bn_JKA_GetLevelDescs', [], self::$JKA_DB);
        if(empty($rows)){
            return $this->httpRequestInvalid('Error occured when getting data');
        }
        return $this->load->view('json_view', [
            'json' => $rows->result(),
        ]);
    }

    public function reasons(){
        return $this->restUris(__FUNCTION__);
    }

    public function reasons_get(){
        $rows = $this->sp('bn_JKA_GetReasons', [], self::$JKA_DB);
        if(empty($rows)){
            return $this->httpRequestInvalid('Error occured when getting data');
        }
        return $this->load->view('json_view', [
            'json' => $rows->result(),
        ]);
    }

    public function periods(){
        $type = strtoupper($_SERVER['REQUEST_METHOD']);
        return $this->{__FUNCTION__."_".strtolower($type)}();
    }

    public function periods_delete(){
        $_POST = $this->getBody();
        if(empty($_POST['id'])){
            return $this->httpRequestInvalid('Period record not exist');
        }
        $id = $_POST['id'];
        $params = [ 
            '_PeriodID' => $id ,
            '_User' => $_SESSION['employeeID'],
        ];
        $deleted_rows = $this->sp('bn_JKA_DeletePeriodByID', $params, self::$JKA_DB);
        if(empty($deleted_rows)){
            return $this->httpRequestInvalid('Error occured when deleting data');
        }
        return $this->load->view('json_view', [
            'json' => $deleted_rows->result()[0],
        ]);
    }

    public function getAllPeriods($param = []){
        return $this->sp('bn_JKA_GetPeriods', $param,'JKA_DB')->result();
    }

    public function periods_get(){
        $request = new SearchPeriodRequest();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        $param = $request->transform();
        $periods = $this->sp('bn_JKA_SearchPeriods', $param, self::$JKA_DB);
        if(empty($periods)){
            return $this->httpRequestInvalid('Error occured when getting data');
        }
        $this->load->view("json_view",[
            'json' => $periods->result(),
        ]);
    }

    public function periods_post(){
        $request = new CreatePeriodRequest();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }

        $param = $request->transform();
        $param_proxy = [
            '_StartDt' => $param['_StartDt'],
            '_EndDt' => $param['_EndDt'],
            '_InstitutionID' => $param['_Institution'],
        ];
        $periodRepo = new PeriodRepository();
        $is_proxy_valid = $periodRepo->proxy_insert($param_proxy);
        if(!$is_proxy_valid) {
            return $this->httpRequestInvalid('Duplicate inputted data');
        }
        
        $post = (object)$this->getBody();
        $result = $this->sp('bn_JKA_InsertPeriod', $param, self::$JKA_DB);
        if(empty($result)){
            return $this->httpRequestInvalid('Error occured when inserting data');
        }

        return $this->load->view("json_view",[
            'json' => reset($result->result())
        ]);
    }

    protected function validatePeriod($body){
        $errors = [];
        if(empty($body['institution'])){
            $errors['institution'] = "Please choose institution";
        }
        if(empty($body['start_date'])){
            $errors['start_date'] = "Invalid date time";
        }
        if(empty($body['start_date'])){
            $errors['end_date'] = "Invalid date time";
        }
        
        return $errors;
    }

    public function getBody(){
        return json_decode(file_get_contents("php://input"), true);
    }

    public function organizations(){
        $res = $this->sp("Staff_CMS_GetAcademicOrganizationAcadCareer", array(), 'CMS_DB')->result();

        return $this->load->view("json_view",[
            'json' => $res
        ]);
    }

    public function departments(){
        $type = strtoupper($_SERVER['REQUEST_METHOD']);
        return $this->{__FUNCTION__."_".strtolower($type)}();
    }

    public function departments_get(){
        $result = $this->sp('bn_JKA_GetDepartments',[],self::$JKA_DB)->result();

        return $this->load->view("json_view",[
            'json' => $result,
        ]);
    }

    public function periodsByInstitution(){
        if(empty($_GET['institution'])){
            return $this->load->view("json_view",[
                'json' => []
            ]);
        }
        $institution = $_GET['institution'];
        $result = [];
        try{
            $result = $this->sp('sp_JKA_GetPeriodsByInstitution',[
                '_Institution' => $institution,
            ],self::$JKA_DB)->result();
        }catch(Exception $e){
            $result = [];
        }
        return $this->load->view("json_view",[
            'json' => $result,
        ]);
    }

    public function item_subtypes(){
        return $this->restUris(__FUNCTION__);
    }

    public function item_subtypes_get(){
        $result = [];
        if($result = $this->sp("bn_JKA_GetItemSubType", [], self::$JKA_DB)){
            $result = $result->result();
        }
        else return $this->httpRequestInvalid();

        return $this->load->view("json_view",[
            'json' => $result,
        ]);
    }

    public function item_types(){
        $type = strtoupper($_SERVER['REQUEST_METHOD']);
        $func_name = __FUNCTION__."_".strtolower($type);
        if(!method_exists($this, $func_name)){
            return $this->httpRequestInvalid();
        }
        return $this->{$func_name}();
    }

    public function item_types_get(){
        $result = [];
        if($result = $this->sp("bn_JKA_GetItemType", [], self::$JKA_DB)){
            $result = $result->result();
        }
        else return $this->httpRequestInvalid();

        return $this->load->view("json_view",[
            'json' => $result,
        ]);
    }

    public function httpRequestInvalid($message=null){
        http_response_code(422);
        return $this->load->view("json_view",[
            'json' => [
                'message' => $message ?: 'invalid request',
            ],
        ]);
    }
}