<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/PeriodRepository.php');

class UpdatePeriodRequest  extends AbstractRequest {

    public function validate(){
        $periodRepository = new PeriodRepository();
        $errors = [];
        if(empty($this->request['period_id']) || empty($periodRepository->getByID($this->request['period_id']))){
            return [ 
                "message" => "Data not found" 
            ];
        }

        if(empty($this->request['institution'])){
            $errors['institution'] = 'Must be chosen';
        }

        if(empty($this->request['start_date'])){
            $errors['startDate'] = 'Must be selected';
        } else{
            $start_date = DateTime::createFromFormat('d-m-Y', $this->request['start_date']);
        }
        
        if(empty($this->request['end_date'])){
            $errors['endDate'] = 'Must be selected';
        } else {
            $end_date = DateTime::createFromFormat('d-m-Y', $this->request['end_date']);    
        }
        if(empty($errors['startDate']) && empty($start_date)) $errors['startDate'] = 'Date is invalid';
        if(empty($errors['endDate']) && empty($end_date)) $errors['endDate'] = 'Date is invalid';

        if(empty($errors['startDate']) && empty($errors['endDate'])){
            $interval = date_diff($start_date, $end_date);
            if($interval->format('%R') == '-' || $interval->format('%a') == 0){
                $errors['endDate'] = 'Must be after start date';
            }    
        }
        
        if(empty($this->request['eff_date'])){
            $errors['effdate'] = 'Must be selected';
        }
        return $errors;
    }

    public function transform(){
        
        return [
            '_User' => $_SESSION['UserID'],
            '_PeriodID' => $this->request['period_id'],
            '_Institution' => $this->request['institution'],
            '_StartDt' => date("Y-m-d", strtotime($this->request['start_date'])),
            '_EndDt' => date("Y-m-d", strtotime($this->request['end_date'])),
            '_Effdt' => date("Y-m-d", strtotime($this->request['eff_date'])),
        ];
    }

}