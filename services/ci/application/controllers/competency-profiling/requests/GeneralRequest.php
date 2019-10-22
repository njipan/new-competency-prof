<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

class GeneralRequest extends AbstractRequest {

    public function validate(){
        return [];
    }

}