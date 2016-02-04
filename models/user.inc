<?php

/**
 * Class user_active_record
 * @var $uid INTEGER
 * @var $name VARCHAR(60)
 * @var $pass VARCHAR(128)
 * @var $mail VARCHAR(254)
 * @var $theme VARCHAR(255)
 * @var $signature VARCHAR(255)
 * @var $signature_format VARCHAR(255)
 * @var $created INT(11)
 * @var $access INT(11)
 * @var $login INT(11)
 * @var $status INT(4)
 * @var $timezone VARCHAR(32)
 * @var $language VARCHAR(12)
 * @var $picture INT(11)
 * @var $init VARCHAR(254)
 * @var $data LONGBLOB
 */
class user_active_record extends \Thru\ActiveRecord\ActiveRecord
{
    protected $_table = "users";

    public $uid;
    public $name;
    public $pass;
    public $mail;
    public $theme;
    public $signature;
    public $signature_format;
    public $created;
    public $access;
    public $login;
    public $status;
    public $timezone;
    public $language;
    public $picture;
    public $init;
    public $data;

    /**
     * Get the currently logged in user.
     * Will return anonymous account if fail.
     *
     * @return user_active_record
     */
    static public function current($force_load = false)
    {
        if(!isset($GLOBALS['user_ar']) || $force_load){
          if (!$GLOBALS['user']->uid > 0) {
            $GLOBALS['user_ar'] = self::search()->where('uid', 0)->execOne();
          }
          $GLOBALS['user_ar'] = self::search()->where('uid', $GLOBALS['user']->uid)->execOne();
        }
        return $GLOBALS['user_ar'];
    }

    /**
     * Decide if a user has a given role.
     *
     * @param $role_name string
     *
     * @return bool
     */
    public function has_role($role_name)
    {
        $role = role_active_record::search()
            ->where('name', $role_name)
            ->execOne();
        $user_role = user_role_active_record::search()
            ->where('uid', $this->uid)
            ->where('rid', $role->rid)
            ->execOne();
        if ($user_role instanceof user_role_active_record) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Decide if the user is an Administrator or not.
     *
     * @return bool
     */
    public function is_admin()
    {
        $drupal_user = user_load($this->uid);
        if (in_array('administrator', array_values($drupal_user->roles))) {
            return true;
        }
        return false;
    }

    /**
     * Get the roles of the user
     * @return Array of role_active_record
     */
    public function get_roles()
    {
        $users_roles = user_role_active_record::search()->where('uid', $this->uid)->exec();
        $roles = array();
        foreach ($users_roles as $users_role) {
            $roles[] = role_active_record::search()->where('rid', $users_role->rid)->execOne();
        }
        return $roles;
    }

    /**
     * Decide if the current user has a given permission
     *
     * @param $permission
     *
     * @return bool
     */
    public function has_permission($permission)
    {
      $drupal_user = user_load($this->uid);
      return user_access($permission, $drupal_user);
    }

    public function save($automatic_reload = true){
        if($this->uid == null){
            $maxUid = db_query("SELECT MAX(uid) FROM users")->fetchField();
            $this->uid = $maxUid + 1;
        }
        parent::save($automatic_reload);
    }
}