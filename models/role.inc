<?php

/**
 * Class role_active_record
 * @var $rid INTEGER
 * @var $name VARCHAR(64)
 * @var $weight INT(11)
 */
class role_active_record extends \Thru\ActiveRecord\ActiveRecord
{
    protected $_table = "role";

    public $rid;
    public $name;
    public $weight;

    /**
     * Get Users with this Role.
     * @return Array of user_active_record
     */
    public function get_users()
    {
        $users_roles = user_role_active_record::search()->where('rid', $this->rid)->exec();
        $uids = array();
        foreach ($users_roles as $users_role) {
            $uids[] = $users_role->uid;
        }

        $users = user_active_record::search()->where('uid', $uids, "IN")->exec();
        //var_dump($users); exit;
        return $users;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function get_role_permissions()
    {
        $role_permissions = role_permission_active_record::factory()->search()->where('rid', $this->rid)->exec();
        return $role_permissions;
    }
}
