<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class StaffRepository extends AbstractRepository{

    public function getByID($id){
        $params = [
            '_EMPLID' => $id,
        ];
        if(!$staff = $this->sp('bn_JKA_GetDetailStaff', $params, self::$JKA_DB)){   
            return null;
        }
        return $this->handleReturn($staff);
    }

    public function isStaffLRC($employeeID){
        $params = [
            '_EMPLID' => $employeeID,
        ];
        if(!$staff = $this->sp('bn_JKA_ProxyOnlyLRC', $params, self::$JKA_DB)){   
            return null;
        }
        return $this->handleReturn($staff);
    }
}