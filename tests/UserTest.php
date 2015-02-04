<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 04/02/2015
 * Time: 15:47
 */

class UserTest extends PHPUnit_Framework_TestCase {
  public function testCreateObject(){
    $user = new user_active_record();
    $this->assertEquals("user_active_record", get_class($user));
  }
}
