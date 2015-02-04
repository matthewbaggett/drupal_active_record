<?php
class role_permission_active_record extends active_record
{
    protected $_table = "role_permission";

    public $rid;
    public $permission;
    public $module;

    public function __toString(){
        return "{$this->module}: {$this->permission}";
    }

    public static function get_all_permissions(){
        $all_permissions = [];
        foreach (module_list(FALSE, FALSE, TRUE) as $module) {
            if ($permissions = module_invoke($module, 'permission')) {
                foreach($permissions as $permission_name => $permission_label){
                    $all_permissions[$permission_name] = $permission_label['title'];
                }
            }
        }
        return $all_permissions;
    }
}