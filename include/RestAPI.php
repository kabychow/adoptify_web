<?php

class RestAPI
{

    public function found($array, ...$vars)
    {
        foreach ($vars as $var) {
            if (!isset($array[$var]) || empty($array[$var])) {
                return false;
            }
        }
        return true;
    }


    public function response($code, $array = null)
    {
        http_response_code($code);

        if ($array) {
            header('Content-type: application/json;');
            return json_encode($array);
        }
        return null;
    }


    public function getBearerToken()
    {
        $authorization = $_SERVER['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        return (preg_match('/Bearer\s(\S+)/', $authorization, $matches)) ? $matches[1] : null;
    }

}