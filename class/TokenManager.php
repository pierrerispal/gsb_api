<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TokenManager
 *
 * @author usersio
 */

use RedBeanPHP\Facade as R;
class TokenManager {

    static function json_encode_token($data) {
        $data['token'] = self::generateToken($GLOBALS['user']['id']);
        $json = json_encode($data);
        return $json;
    }

    static function checkToken($token, $time) {
        $limit = time() - $time;
        $t = R::exportAll(R::find('tokens', 'token = ? AND time > ?', [$token, $limit]));
        if (sizeof($t) !== 0) {
            $user = API::readVisitorById($t[0]['employee_id']);
            $userInfo = array();
            $userInfo['label'] = $user[0]['label'];
            $userInfo['id'] = $user[0]['id'];
            return $userInfo;
        } else {
            return null;
        }
    }

    static function removeTokens($empId) {
        $tokens = R::findAll('tokens', 'employee_id = ?', [$empId]);
        R::trashAll($tokens);
    }

    static function getToken($empId, $time) {
        $limit = time() - $time;
        $token = R::find('tokens', 'employee_id = ? AND time > ?', [$empId, $limit]);

        return R::exportAll($token)[0]['token'];
    }

    static function hasToken($empId, $time) {
        $limit = time() - $time;
        $token = R::find('tokens', 'employee_id = ? AND time > ?', [$empId, $limit]);
        if (sizeof($token) == 0) {
            return false;
        } else {
            return true;
        }
    }

    static function generateToken($userId) {
        self::removeTokens($userId);
        $tokens = R::dispense('tokens');
        $tokens->employee_id = $userId;
        $token = openssl_random_pseudo_bytes(16);
        $token = bin2hex($token);
        $tokens->token = $token;
        $tokens->time = time();
        $tokens->count = 1;
        R::store($tokens);
        return $token;
    }

}
