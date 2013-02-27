<?php 
class user_active_record extends active_record{
	protected $_table = "users";
	
	public function has_role($role_name){
		$role = role_active_record::search()
						->where('name', $role_name)
						->execOne();
		$user_role = user_role_active_record::search()
						->where('uid', $this->uid)
						->where('rid', $role->rid)
						->execOne();
		if($user_role instanceof user_role_active_record){
			return TRUE;
		}else{
			return FALSE;
		}
	}
}