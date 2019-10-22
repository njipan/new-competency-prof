<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

class SearchPeriodRequest  extends AbstractRequest {

    public function validate(){
        $errors = [];
        if(empty($this->request['inst'])){
            $errors['institution'] = 'Must be chosen';
        }
        if(empty($this->request['start_date'])){
            $errors['startDate'] = 'Must be selected';
        }
        else if(empty(DateTime::createFromFormat('d-m-Y', $this->request['start_date']))){
            $errors['startDate'] = 'Date is not valid';
        }
        if(empty($this->request['end_date'])){
            $errors['endDate'] = 'Must be selected';
        }
        else if(empty(DateTime::createFromFormat('d-m-Y', $this->request['end_date']))){
            $errors['endDate'] = 'Date is not valid';
        }

        if(!empty($errors['endDate']) || !empty($errors['startDate'])) return $errors;

        $start_date = DateTime::createFromFormat('d-m-Y', $this->request['start_date']);
        $end_date = DateTime::createFromFormat('d-m-Y', $this->request['end_date']);
        $interval = date_diff($start_date, $end_date);
        if($interval->format('%R') == '-' || $interval->format('%a') == 0){
            $errors['endDate'] = 'Must be after start date';
        }

        return $errors;
    }

    public function transform(){
        
        return [
            '_InstitutionID' => $this->request['inst'],
            '_StartDt' => date("Y-m-d", strtotime($this->request['start_date'])),
            '_EndDt' => date("Y-m-d", strtotime($this->request['end_date'])),
        ];
    }

}