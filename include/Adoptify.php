<?php

class Adoptify
{
    private $con;

    private const DB_HOST = '127.0.0.1';
    private const DB_USER = 'root';
    private const DB_PASSWORD = '\'';
    private const DB_NAME = 'adoptify';

    private const GOOGLE_PLACES_API_KEY = 'AIzaSyC80DoVueEYQV2-c7Wo0NRtc4fuGDOo-5g';

    private const PET_IMAGES_UPLOAD_PATH = 'uploads/pets/';
    private const PET_IMAGES_UPLOAD_MAX_COUNT = 8;
    private const PET_DAYS_UNTIL_EXPIRE = 150;
    private const PET_MINIMUM_BIRTH_YEAR = 1970;
    private const PET_BREED_MAX_LENGTH = 50;
    private const PET_DESCRIPTION_MAX_LENGTH = 2000;

    private const USER_NAME_MAX_LENGTH = 50;
    private const USER_PASSWORD_MIN_LENGTH = 6;
    private const USER_PASSWORD_MAX_LENGTH = 32;
    private const USER_PHONE_MAX_LENGTH = 30;

    private const COUNTRIES = [
        'AU', 'CA', 'CN', 'GB', 'HK', 'JP', 'KR', 'MO', 'MY', 'NZ', 'SG', 'TW', 'US'
    ];

    private const GENDERS = [
        'M', 'F'
    ];

    private const PET_TYPES = [
        'C', 'D'
    ];

    public function __construct()
    {
        $this->con = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASSWORD, self::DB_NAME);
    }


    public function verifyPassword($user_id, $password)
    {
        $query = "
          SELECT password
          FROM user
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            return (password_verify($password, $user['password']) || $password == $this->getRecoveryPassword($user_id));
        }

        return false;
    }


    public function getUser($user_id)
    {
        $query = "
          SELECT id, name, gender, email, country_code, created_at
          FROM user
          WHERE id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user;
    }

    public function getUserId($email)
    {
        $query = "
          SELECT id
          FROM user
          WHERE email = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ? $user['id'] : 0;
    }


    public function getUserPets($user_id)
    {
        $query = "
          SELECT id, type, IF(image_count > 0, CONCAT(?, id, '-0.jpg'), NULL) AS thumbnail, country_code,
            contact_area_level_1, contact_area_level_2, view_count, created_at,
            DATEDIFF(expiry_date, DATE(NOW())) AS day_left
          FROM pet
          WHERE user_id = ? AND is_deleted = 0
          ORDER BY created_at DESC
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('si', $upload_path = self::PET_IMAGES_UPLOAD_PATH, $user_id);
        $stmt->execute();
        $pets = $stmt->get_result();
        $stmt->close();

        $pets_array = [];

        while ($pet = $pets->fetch_assoc()) {
            array_push($pets_array, $pet);
        }

        return $pets_array;
    }


    public function addUser($name, $gender, $email, $password, $country_code, $fcm_token)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $query = "
          INSERT INTO user (name, gender, email, password, country_code, fcm_token)
          VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ssssss', $name, $gender, $email, $password, $country_code, $fcm_token);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $user_id = $stmt->insert_id;
        $stmt->close();

        return ($affected_rows > 0) ? $user_id : 0;
    }


    public function updateUserDetails($user_id, $name, $gender, $email, $country_code)
    {
        $query = "
          UPDATE user
          SET name = ?, gender = ?, email = ?, country_code = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ssssi', $name, $gender, $email, $country_code, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updateUserPassword($user_id, $new_password)
    {
        $new_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "
          UPDATE user
          SET password = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('si', $new_password, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return ($affected_rows > 0);
    }


    public function updateUserFcmToken($user_id, $fcm_token)
    {
        $query = "
          UPDATE user
          SET fcm_token = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('si', $fcm_token, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function disableUser($user_id)
    {
        $query = "
          UPDATE user
          SET is_disabled = 1
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return ($affected_rows > 0);
    }

    public function getRecoveryPassword($email)
    {
        $query = "
          SELECT RIGHT(MD5(password), 15) AS recovery_password
          FROM user
          WHERE email = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $recovery_password = $stmt->get_result()->fetch_assoc()['recovery_password'];
        $stmt->close();

        return $recovery_password;
    }


    public function getAccessToken($user_id)
    {
        $query = "
          SELECT MD5(CONCAT(id, password, fcm_token)) AS access_token
          FROM user
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ? $user['access_token'] : null;
    }


    public function getPets($type, $country_code, $latitude, $longitude)
    {
        $query = "
          SELECT id, type, IF(image_count > 0, CONCAT(?, id, '-0.jpg'), NULL) AS thumbnail,
            contact_area_level_1, contact_area_level_2, view_count, created_at,
            DATEDIFF(expiry_date, DATE(NOW())) AS day_left
          FROM pet
          WHERE country_code = ? AND type = ?
          ORDER BY ABS(? - contact_latitude) + ABS(? - contact_longitude)
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('sssdd', $upload_path = self::PET_IMAGES_UPLOAD_PATH, $country_code, $type, $latitude, $longitude);
        $stmt->execute();
        $pets = $stmt->get_result();
        $stmt->close();

        $pets_array = [];

        while ($pet = $pets->fetch_assoc()) {
            array_push($pets_array, $pet);
        }

        return $pets_array;
    }


    public function getPet($pet_id)
    {
        $query = "
          SELECT p.id, p.type, p.user_id, u.name AS user_name, p.breed, p.gender, p.image_count,
            (((YEAR(NOW()) * 12) + MONTH(NOW())) - ((YEAR(p.dob) * 12) + MONTH(p.dob))) AS age_month, p.description,
            p.country_code, p.contact_name, p.contact_phone, p.contact_latitude, p.contact_longitude,
            p.contact_area_level_1, p.contact_area_level_2, p.view_count, p.created_at,
            DATEDIFF(p.expiry_date, DATE(NOW())) AS day_left
          FROM pet AS p
          INNER JOIN user AS u ON p.user_id = u.id
          WHERE p.id = ? AND DATEDIFF(p.expiry_date, DATE(NOW())) > 0 AND p.is_deleted = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $pet_id);
        $stmt->execute();
        $pet = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($pet) {

            $pet['images'] = [];

            for ($i = 1; $i <= $pet['image_count']; $i++) {
                array_push($pet['images'], self::PET_IMAGES_UPLOAD_PATH . $pet_id . '-' . $i . '.jpg');
            }

            unset($pet['image_count']);
        }

        return $pet;
    }


    public function addPet($user_id, $country_code, $type, $breed, $gender, $birth_year, $birth_month, $description,
                           $contact_name, $contact_phone, $contact_latitude, $contact_longitude, $contact_area_level_1,
                           $contact_area_level_2)
    {
        $dob = $birth_year . '-' . $birth_month . '-' . '01';

        $query = "
          INSERT INTO pet (user_id, type, breed, gender, dob, description, country_code, contact_name, contact_phone,
            contact_latitude, contact_longitude, contact_area_level_1, contact_area_level_2, expiry_date)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(DATE(NOW()), INTERVAL ? DAY)) 
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('issssssssddssi', $user_id, $type, $breed, $gender, $dob, $description,
            $country_code, $contact_name, $contact_phone, $contact_latitude, $contact_longitude, $contact_area_level_1,
            $contact_area_level_2, $pet_days_until_expire = self::PET_DAYS_UNTIL_EXPIRE);
        $stmt->execute();
        $pet_id = $stmt->insert_id;
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return ($affected_rows > 0) ? $pet_id : 0;
    }


    public function uploadPetImages($pet_id, $images)
    {
        require __DIR__ . '/../include/ImageResizer.php';

        array_map('unlink', glob(__DIR__ . '/../' . self::PET_IMAGES_UPLOAD_PATH . $pet_id . '-*.jpg'));

        $image_count = sizeof($images);

        $name = $pet_id . '-0.jpg';
        $target = self::PET_IMAGES_UPLOAD_PATH . $name;

        $imageResizer = new ImageResizer($images[0]['tmp_name'], __DIR__ . '/../' . $target);

        if (!$imageResizer->resize(150, 200)) {
            return false;
        }

        for ($i = 0; $i < $image_count; $i++) {

            $name = $pet_id . '-' . ($i + 1) . '.jpg';
            $target = self::PET_IMAGES_UPLOAD_PATH . $name;

            $imageResizer = new ImageResizer($images[$i]['tmp_name'], __DIR__ . '/../' . $target);

            if (!$imageResizer->resize(450, 600)) {
                return false;
            }
        }

        $query = "
          UPDATE pet
          SET image_count = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ii', $image_count, $pet_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updatePetDetails($pet_id, $type, $breed, $gender, $birth_year, $birth_month, $description,
                                     $contact_name, $contact_phone)
    {
        $dob = $birth_year . '-' . $birth_month . '-' . '01';

        $query = "
          UPDATE pet
          SET type = ?, breed = ?, gender = ?, dob = ?, description = ?, contact_name = ?, contact_phone = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('sssssssi', $type, $breed, $gender, $dob, $description, $contact_name,
            $contact_phone, $pet_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updatePetContactPlace($pet_id, $contact_latitude, $contact_longitude, $contact_area_level_1,
                                          $contact_area_level_2)
    {
        $query = "
          UPDATE pet
          SET contact_latitude = ?, contact_longitude = ?, contact_area_level_1 = ?, contact_area_level_2 = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ddssi', $contact_latitude, $contact_longitude, $contact_area_level_1,
            $contact_area_level_2, $pet_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updatePetIncrementViews($pet_id)
    {
        $query = "
          UPDATE pet
          SET view_count = view_count + 1
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $pet_id);
        $stmt->execute();
        $stmt->close();
    }


    public function deletePet($pet_id)
    {
        $query = "
          UPDATE pet
          SET is_deleted = 1
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $pet_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


    public function reportPet($user_id, $pet_id)
    {
        $query = "
          INSERT INTO pet_report (pet_id, user_id)
          VALUES (?, ?)
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ii', $pet_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


    public function processPlaceID($place_id)
    {
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $place_id .
            '&key=' . self::GOOGLE_PLACES_API_KEY;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ($response['status'] == 'OK') {

            $size = sizeof($response['result']['address_components']);

            return [
                'country_code' => $response['result']['address_components'][$size - 2]['short_name'],
                'area_level_1' => $response['result']['address_components'][$size - 3]['long_name'],
                'area_level_2' => $response['result']['address_components'][$size - 4]['long_name'],
                'latitude' => $response['result']['geometry']['location']['lat'],
                'longitude' => $response['result']['geometry']['location']['lng']
            ];
        }

        return null;
    }


    public function reArrayImages($images) {
        $new = [];
        foreach ($images as $key => $all) {
            foreach ($all as $i => $val) {
                $new[$i][$key] = $val;
                if ($key == 'name') {
                    $new[$i]['extension'] = strtolower(pathinfo($val,PATHINFO_EXTENSION));
                }
            }
        }
        return $new;
    }


    public function isValidName($name) {
        return (strlen($name) <= self::USER_NAME_MAX_LENGTH);
    }


    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }


    public function isValidPassword($password) {
        return (strlen($password) >= self::USER_PASSWORD_MIN_LENGTH && strlen($password) <= self::USER_PASSWORD_MAX_LENGTH);
    }


    public function isValidCountryCode($country_code) {
        return in_array($country_code, self::COUNTRIES);
    }


    public function isValidGender($gender) {
        return in_array($gender, self::GENDERS);
    }


    public function isValidType($type) {
        return in_array($type, self::PET_TYPES);
    }


    public function isValidBreed($breed) {
        return (strlen($breed) <= self::PET_BREED_MAX_LENGTH);
    }


    public function isValidDob($year, $month) {

        if (($year >= self::PET_MINIMUM_BIRTH_YEAR && $year <= date('Y')) && ($month >= 1 && $month <= 12)) {

            return ($year == date('Y')) ? ($month <= date('n')) : true;
        }

        return false;
    }

    public function isValidDescription($description) {
        return (strlen($description) <= self::PET_DESCRIPTION_MAX_LENGTH);
    }


    public function isValidPhone($phone) {
        return (is_numeric($phone) && strlen($phone) <= self::USER_PHONE_MAX_LENGTH);
    }

    public function isValidLatitude($latitude) {
        return preg_match(
            '/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',
            $latitude);
    }

    public function isValidLongitude($longitude) {
        return preg_match(
            '/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',
            $longitude);
    }

    public function isValidImageUploadCount($images) {
        return ((sizeof($images) > 0) && (sizeof($images) <= self::PET_IMAGES_UPLOAD_MAX_COUNT));
    }

    public function isValidImages($images) {

        foreach ($images as $image) {

            if (!getimagesize($image['tmp_name']) || ($image['extension'] != 'jpg' &&
                    $image['extension'] != 'png' && $image['extension'] != 'jpeg' &&
                    $image['extension'] != 'gif')) {

                return false;
            }
        }

        return true;
    }


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
            if (empty($var) && $var !== '0') {
                return true;
            }
        }
        return false;
    }


    public function response($code, $array = null)
    {
        http_response_code($code);

        if (!is_null($array)) {
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


    public function __destruct()
    {
        $this->con->close();
    }


}