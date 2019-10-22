<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('requests/GeneralRequest.php');
require_once('requests/UpdatePeriodRequest.php');
require_once('repos/PeriodRepository.php');
class Period extends BaseController {
    
    static protected $JKA_DB = 'JKA_DB';

    public function __construct(){
        parent::__construct();

        $this->lecturer_code = $this->getLecturerCode();
    }

    public function update(){
        return $this->restURIs(__FUNCTION__);
    }

    public function update_post(){
        $periodRepository = new PeriodRepository();
        $request = new UpdatePeriodRequest();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }

        $param_update = $request->transform();        
        $updated_period = $periodRepository->update($param_update);
        if(empty($updated_period)){
            return $this->httpRequestInvalid('Error occured when updating data');
        }

        return $this->load->view("json_view",[
            'json' => $updated_period,
        ]);
    }

    public function delete(){
        return $this->restURIs(__FUNCTION__);
    }

    public function delete_post(){
        $request = new GeneralRequest();
        $periodRepository = new PeriodRepository();
        $data = $request->data();
        if(empty($data['id']) || !$period = $periodRepository->getByID($data['id'])){
            return $this->httpRequestInvalid('Request is invalid');
        }
        
        $params = [ 
            '_PeriodID' => $data['id'] ,
            '_User' => $_SESSION['employeeID'],
        ];
        $deleted_rows = $periodRepository->delete($params);
        if(empty($deleted_rows)){
            return $this->httpRequestInvalid('Error occured when deleting data');
        }
        return $this->load->view('json_view', [
            'json' => $deleted_rows,
        ]);
    }

    public function effdates(){
        return $this->restURIs(__FUNCTION__);
    }

    public function effdates_get(){
        $dates = $this->sp('bn_JKA_GetEffdtHOP', [], self::$JKA_DB);
        if(empty($dates)){
            return $this->httpRequestInvalid('Error occured when getting data');    
        }
        $dates = $dates->result();
        return $this->load->view("json_view",[
            'json' => $dates,
        ]);
    }

    public function all(){
        return $this->restURIs(__FUNCTION__);
    }

    public function all_get(){
        $periods = $this->sp('bn_JKA_GetPeriods', [], self::$JKA_DB);
        if(empty($periods)){
            return $this->httpRequestInvalid();    
        }
        $periods = $periods->result();
        return $this->load->view("json_view",[
            'json' => $periods,
        ]);
    }

    public function get(){
        if(empty($_GET['id'])){
            http_response_code(422);
            return $this->load->view("json_view",[
                'json' => [
                    'status' => false,
                ]
            ]);
        }
        $id = $_GET['id'];
        $period = reset($this->sp('bn_JKA_GetPeriodById', [
            '_PeriodID' => $id,
        ], self::$JKA_DB)->result());

        return $this->load->view("json_view",[
            'json' => $period,
        ]);
    }

    public function candidate(){
        return $this->restURIs(__FUNCTION__);
    }

    public function candidate_get(){
        $params = [
            '_LecturerCode' => $this->lecturer_code,
        ];
        try{
            $periods = $this->sp('bn_JKA_GetPeriodsByLecturerCode',$params , self::$JKA_DB)->result();

            return $this->load->view("json_view",[
                'json' => $periods,
            ]);
        }catch(\Exception $e){
            return $this->httpRequestInvalid();
        }
    }

}