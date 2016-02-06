<?php

/**
 * Class user_role_active_record
 * @var $uid INT(10)
 * @var $rid INT(10)
 */
class user_role_active_record extends \Thru\ActiveRecord\ActiveRecord
{
    protected $_table = "users_roles";

    public $uid;
    public $rid;
}
