<?php

class RestAPI
{

    public function isset($array, ...$vars)
    {
        foreach ($vars as $var) {
            if (!isset($array[$var])) {
                return false;
            }
        }
        return true;
    }


    public function empty(...$vars)
    {
        foreach ($vars as $var) {
            if (empty($array[$var]) && $var !== '0') {
                return true;
            }
        }
        return false;
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


    public function getBasicToken()
    {
        $authorization = $_SERVER['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        return (preg_match('/Basic\s(\S+)/', $authorization, $matches)) ? $matches[1] : null;
    }

}