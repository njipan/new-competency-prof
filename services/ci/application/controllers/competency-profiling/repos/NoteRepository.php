<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class NoteRepository extends AbstractRepository{

	public function createOrUpdate($params){
        if(!$candidate_note = $this->sp('bn_JKA_InsertCandidateNote', $params, self::$JKA_DB)){   
            return null;
        }
        return $this->handleReturn($candidate_note);
	}

}