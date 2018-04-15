<?php
/**
 * Created by PhpStorm.
 * User: khaibin
 * Date: 15/04/2018
 * Time: 5:43 PM
 */

class Adoptify
{
    private $con;


    public function __construct(mysqli $con)
    {
        if (!$con->errno) {
            $this->con = $con;
        } else {
            http_response_code(500);
            die();
        }
    }


    public function auth($user_id, $password) {

        $query = "
          SELECT password
          FROM users
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($user && password_verify($password, $user['password']));
    }


    public function getUserDetails($user_id) {

        $query = "
          SELECT user_id, name, email, country_code, created_at
          FROM users
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user;
    }


    public function addUser($name, $email, $password, $country_code, $fcm_token) {

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $name = trim($name);
            $password = password_hash($password, PASSWORD_DEFAULT);
            $country_code = strtoupper($country_code);

            $query = "
              INSERT INTO users (name, email, password, country_code, fcm_token)
              VALUES (?, ?, ?, ?, ?)
            ";
            $stmt = $this->con->prepare($query);
            $stmt->bind_param('sssss', $name, $email, $password, $country_code, $fcm_token);
            $stmt->execute();
            $stmt->store_result();
            $affected_rows = $stmt->affected_rows;
            $user_id = $stmt->insert_id;
            $stmt->close();

            if ($affected_rows > 0) {
                return $user_id;
            }
        }

        return null;
    }


    public function updateUserDetails($user_id, $name, $email, $country_code) {

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $name = trim($name);

            $query = "
              UPDATE users
              SET name = ?, email = ?, country_code = ?
              WHERE user_id = ? AND is_disabled = 0
            ";
            $stmt = $this->con->prepare($query);
            $stmt->bind_param('sssi', $name, $email, $country_code, $user_id);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        }

        return false;
    }


    public function updateUserPassword($user_id, $new_password) {

        $new_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "
          UPDATE users
          SET password = ?
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ai', $new_password, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


    public function updateUserFcmToken($user_id, $fcm_token) {

        $query = "
          UPDATE users
          SET fcm_token = ?
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('si', $fcm_token, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function disableUser($user_id) {

        $query = "
          UPDATE users
          SET is_disabled = 1
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


    public function getUserId($email) {

        $query = "
          SELECT user_id
          FROM users
          WHERE email = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ? $user['user_id'] : null;
    }


    public function isEmailExists($email, $exclude_user_id = 0) {

        $query = "
          SELECT COUNT(*) AS count
          FROM users
          WHERE email = ? AND email != (
            SELECT email
            FROM users
            WHERE user_id = ? AND is_disabled = 0
          )
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('si', $email, $exclude_user_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        return $count > 0;
    }


    public function getAccessToken($user_id) {

        $query = "
          SELECT MD5(CONCAT(user_id, password)) AS access_token
          FROM users
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ? $user['access_token'] : null;
    }


    public function verifyAccessToken($user_id, $access_token) {

        $query = "
          SELECT COUNT(*) AS count
          FROM users
          WHERE user_id = ? AND MD5(CONCAT(user_id, password)) = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('is', $user_id, $access_token);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        return $count > 0;
    }


    public function getDog($dog_id) {

        $query = "
          SELECT
            d.dog_id, d.breed, d.gender, (((YEAR(NOW()) * 12) + MONTH(NOW())) - ((YEAR(d.dob) * 12) + MONTH(d.dob))) AS age_month,
            d.description, d.country_code, u.user_id, u.name AS user_name, d.contact_name, d.contact_phone, d.contact_latitude,
            d.contact_longitude, d.views, DATEDIFF(d.expiry_date, DATE(NOW())) AS day_left, d.updated_at, d.created_at
          FROM dogs AS d
          INNER JOIN users AS u ON d.user_id = u.user_id
          WHERE d.dog_id = ? AND DATEDIFF(d.expiry_date, DATE(NOW())) > 0 AND d.is_deleted = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $dog_id);
        $stmt->execute();
        $dog = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $dog;
    }


    public function updateDogIncrementViews($dog_id) {

        $query = "
          UPDATE dogs
          SET views = views + 1
          WHERE dog_id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $dog_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


}