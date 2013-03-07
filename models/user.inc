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
	
	/**
	 * Get the roles of the user
	 * @return Array of role_active_record
	 */
	public function get_roles(){
		$users_roles = user_role_active_record::search()->where('uid', $this->uid)->exec();
		$roles = array();
		foreach($users_roles as $users_role){
			$roles[] = role_active_record::search()->where('rid', $users_role->rid)->execOne();
		} 
		return $roles;
	}
}