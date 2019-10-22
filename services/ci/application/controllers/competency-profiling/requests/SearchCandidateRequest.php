<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

class SearchCandidateRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
        if(empty($_GET['institution'])){
            $errors['institution'] = "Must be selected";
        }
        if(empty($_GET['organization'])){
            $errors['organization'] = "Must be selected";
        }
        if(empty($_GET['department'])){
            $errors['department'] = "Must be selected";
        }
        if(empty($_GET['period_id'])){
            $errors['period'] = "Must be selected";
        }
        else if($_GET['period_id'] != '*' && !is_numeric($_GET['period_id'])){
            $errors['period'] = "Period is not valid";
        }

        return $errors;
    }

}