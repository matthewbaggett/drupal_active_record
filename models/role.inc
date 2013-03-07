<?php 
class role_active_record extends active_record{
	protected $_table = "role";
	
	/**
	 * Get Users with this Role.
	 * @return Array of user_active_record
	 */
	public function get_users(){
		$users_roles = user_role_active_record::search()->where('rid', $this->rid)->exec();
		$uids = array();
		foreach($users_roles as $users_role){
			$uids[] = $users_role->uid;
		}
		
		$users = user_active_record::search()->where('uid',$uids,"IN")->exec();
		//var_dump($users); exit;
		return $users;
	}
	
	public function __toString(){
		return $this->name;
	}
}