<?php

class Adoptify
{
    private $con;

    // Database
    private const DB_HOST = '127.0.0.1';
    private const DB_USER = 'root';
    private const DB_PASSWORD = '\'';
    private const DB_NAME = 'adoptify';

    // Config
    private const PET_RESULTS_PER_PAGE = 20;
    private const PET_DAYS_UNTIL_EXPIRE = 150;
    private const PET_IMAGES_UPLOAD_MAX_COUNT = 8;
    private const PET_IMAGES_UPLOAD_PATH = 'uploads/pets/';

    // API Keys
    private const GOOGLE_PLACES_API_KEY = 'AIzaSyC80DoVueEYQV2-c7Wo0NRtc4fuGDOo-5g';

    // Validation
    private const USER_NAME_MAX_LENGTH = 50;
    private const USER_PASSWORD_MIN_LENGTH = 6;
    private const USER_PASSWORD_MAX_LENGTH = 32;
    private const USER_PHONE_NUMBER_MAX_LENGTH = 30;
    private const PET_MINIMUM_BIRTH_YEAR = 1970;
    private const PET_BREED_MAX_LENGTH = 50;
    private const PET_DESCRIPTION_MAX_LENGTH = 2000;

    // Currently Supported Countries
    private const COUNTRIES = [
        'AU', 'CA', 'CN', 'GB', 'HK', 'JP', 'KR', 'MO', 'MY', 'NZ', 'SG', 'TW', 'US'
    ];

    // Currently Supported Pet Types
    private const PET_TYPES = [
        'cat', 'dog'
    ];



    public function __construct()
    {
        $this->con = new mysqli(self::DB_HOST, self::DB_USER, self::DB_PASSWORD, self::DB_NAME);
    }


    public function __destruct()
    {
        $this->con->close();
    }



    /*..................................................................................................................
     *
     * Get User Details
     *..................................................................................................................
     *
     * Required parameters: user_id
     *
     * Returns array
     *   { id, name, gender, email, country_code, created_at }
     *
     * Note: Returns null if user not found
     * .................................................................................................................
     */

    public function getUserDetails($user_id)
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




    /*..................................................................................................................
     *
     * Get User Published Pets
     *..................................................................................................................
     *
     * Required parameters: user_id
     *
     * Returns array of array
     *   [ id, type, thumbnail, country_code, contact_area_level_1, contact_area_level_2, view_count, created_at,
     *     day_left ]
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Add A New User
     *..................................................................................................................
     *
     * Required parameters: name, gender, email, password, country_code, fcm_token
     *
     * Returns integer
     *   user_id
     *
     * Note: Returns 0 if failed
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Update User Details
     *..................................................................................................................
     *
     * Required parameters: user_id, name, gender, email, country_code
     *
     * Returns boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Update User Password
     *..................................................................................................................
     *
     * Required parameters: user_id, new_password
     *
     * Returns boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Update User FCM Token
     *..................................................................................................................
     *
     * Required parameters: user_id, fcm_token
     *
     * Returns boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Disable User Account
     *..................................................................................................................
     *
     * Required parameters: user_id
     *
     * Returns boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Get User Recovery Password
     *..................................................................................................................
     *
     * Required parameters: user_id
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function getUserRecoveryPassword($user_id) {
        return true;
    }




    /*..................................................................................................................
     *
     * Get User ID By Email
     *..................................................................................................................
     *
     * Required parameters: email
     *
     * Returns integer
     *   user_id
     *
     * Note: Returns 0 if email not found
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Get User Access Token
     *..................................................................................................................
     *
     * Required parameters: user_id
     *
     * Returns string
     *   access_token
     *
     * Note: Returns null if user not found
     * .................................................................................................................
     */

    public function getAccessToken($user_id)
    {
        $query = "
          SELECT MD5(CONCAT(id, password, fcm_token)) AS access_token
          FROM user
          WHERE id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ? $user['access_token'] : null;
    }




    /*..................................................................................................................
     *
     * Get User Recovery Password
     *..................................................................................................................
     *
     * Required parameters: user_id
     *
     * Returns string
     *   recovery_password
     *
     * Note: Returns null if user not found
     * .................................................................................................................
     */

    private function getRecoveryPassword($email)
    {
        $query = "
          SELECT RIGHT(MD5(password), 15) AS recovery_password
          FROM user
          WHERE email = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ? $user['recovery_password'] : null;
    }




    /*..................................................................................................................
     *
     * Verify User Password
     *..................................................................................................................
     *
     * Required parameters: user_id, password
     *
     * Returns boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Get All Pets With Filter
     *..................................................................................................................
     *
     * Required parameters: type, country_code, latitude, longitude, page
     *
     * Returns array of array
     *   [ id, type, thumbnail, country_code, contact_area_level_1, contact_area_level_2, view_count, created_at,
     *     day_left ]
     * .................................................................................................................
     */

    public function getPets($type, $country_code, $latitude, $longitude, $page)
    {
        $query = "
          SELECT id, type, IF(image_count > 0, CONCAT(?, id, '-0.jpg'), NULL) AS thumbnail, country_code,
            contact_area_level_1, contact_area_level_2, view_count, created_at,
            DATEDIFF(expiry_date, DATE(NOW())) AS day_left
          FROM pet
          WHERE country_code = ? AND type = ?
          ORDER BY ABS(? - contact_latitude) + ABS(? - contact_longitude)
          LIMIT ?, ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('sssddii', $upload_path = self::PET_IMAGES_UPLOAD_PATH, $country_code, $type,
            $latitude, $longitude, ($page - 1) * self::PET_RESULTS_PER_PAGE,
            $results_per_page = self::PET_RESULTS_PER_PAGE);
        $stmt->execute();
        $pets = $stmt->get_result();
        $stmt->close();

        $pets_array = [];

        while ($pet = $pets->fetch_assoc()) {
            array_push($pets_array, $pet);
        }

        return $pets_array;
    }




    /*..................................................................................................................
     *
     * Get Pet By ID
     *..................................................................................................................
     *
     * Required parameters: pet_id
     *
     * Returns array
     *   { id, type, user_id, user_name, breed, gender, images[], age_month, description, country_code, contact_name,
     *     contact_phone, contact_latitude, contact_longitude, contact_area_level_1, contact_area_level_2, view_count,
     *     created_at, day_left }
     *
     * Note: Returns null if pet not found
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Add A New Pet
     *..................................................................................................................
     *
     * Required parameters: user_id, country_code, type, breed, gender, birth_year, birth_month, description,
     *                      contact_name, contact_phone, contact_latitude, contact_longitude, contact_area_level_1
     *                      contact_area_level_2
     *
     * Returns integer
     *   pet_id
     *
     * Note: Returns 0 if failed
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Upload Pet Images
     *..................................................................................................................
     *
     * Required parameters: pet_id, images[]
     *
     * Returns integer
     *   boolean
     *
     * Note: All old images will be removed for the pet
     * .................................................................................................................
     */

    public function uploadPetImages($pet_id, $images)
    {
        array_map('unlink', glob(__DIR__ . '/../' . self::PET_IMAGES_UPLOAD_PATH . $pet_id . '-*.jpg'));

        require __DIR__ . '/../include/ImageResizer.php';
        $imageResizer = new ImageResizer();

        $name = $pet_id . '-0.jpg';
        $target = self::PET_IMAGES_UPLOAD_PATH . $name;

        if (!$imageResizer->resize($images[0]['tmp_name'], __DIR__ . '/../' . $target, 150, 200)) {
            return false;
        }

        for ($i = 0; $i < sizeof($images); $i++) {

            $name = $pet_id . '-' . ($i + 1) . '.jpg';
            $target = self::PET_IMAGES_UPLOAD_PATH . $name;

            if (!$imageResizer->resize($images[$i]['tmp_name'], __DIR__ . '/../' . $target, 450, 600)) {
                return false;
            }
        }

        $query = "
          UPDATE pet
          SET image_count = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ii', sizeof($images), $pet_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }




    /*..................................................................................................................
     *
     * Update Pet Details
     *..................................................................................................................
     *
     * Required parameters: pet_id, type, breed, gender, birth_year, birth_month, description, contact_name,
     *                      contact_phone
     *
     * Returns integer
     *   boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Update Pet Contact Place
     *..................................................................................................................
     *
     * Required parameters: pet_id, contact_latitude, contact_longitude, contact_area_level_1, contact_area_level_2
     *
     * Returns integer
     *   boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Delete Pet
     *..................................................................................................................
     *
     * Required parameters: pet_id
     *
     * Returns integer
     *   boolean
     * .................................................................................................................
     */

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




    /*..................................................................................................................
     *
     * Report Pet
     *..................................................................................................................
     *
     * Required parameters: pet_id, user_id
     *
     * Returns integer
     *   boolean
     * .................................................................................................................
     */

    public function reportPet($pet_id, $user_id)
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




    /*..................................................................................................................
     *
     * Increment Pet View Count
     *..................................................................................................................
     *
     * Required parameters: pet_id
     *
     * Returns (void)
     * .................................................................................................................
     */

    public function updatePetIncrementViewCount($pet_id)
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





    /*..................................................................................................................
     *
     * Process Place ID From Google Places API
     *..................................................................................................................
     *
     * Required parameters: place_id
     *
     * Returns array
     *   { country_code, area_level_1, area_level_2, latitude, longitude }
     *
     * Note: returns null if invalid place id or google places service unavailable / over quota
     * .................................................................................................................
     */

    public function processPlaceID($place_id)
    {
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $place_id .
            '&key=' . self::GOOGLE_PLACES_API_KEY;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ($response['status'] == 'OK') {

            $address_components = $response['result']['address_components'];

            for ($i = sizeof($address_components) - 1; $i >= 0 ; $i--) {

                if (in_array('country', $address_components[$i]['types'])) {

                    return [
                        'country_code' => $address_components[$i]['short_name'],
                        'area_level_1' => $address_components[$i - 1]['short_name'] ?? null,
                        'area_level_2' => $address_components[$i - 2]['short_name'] ?? null,
                        'latitude' => $response['result']['geometry']['location']['lat'],
                        'longitude' => $response['result']['geometry']['location']['lng']
                    ];
                }
            }
        }

        return null;
    }




    /*..................................................................................................................
     *
     * Get Basic Token From Authorization Header
     *..................................................................................................................
     *
     * Required parameters: -
     *
     * Returns string
     *   basic_token
     *
     * Note: returns null if token not found
     * .................................................................................................................
     */

    public function getBasicToken()
    {
        $authorization = $_SERVER['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        return (preg_match('/Basic\s(\S+)/', $authorization, $matches)) ? $matches[1] : null;
    }




    /*..................................................................................................................
     *
     * Re-Array and ability to get file extension to PHP $_FILES
     *..................................................................................................................
     *
     * Required parameters: images[]
     *
     * Returns array
     *   [ name, extension, type, tmp_name, error, size ]
     * .................................................................................................................
     */

    public function reArrayImages($images)
    {
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




    /*..................................................................................................................
     *
     * Validation: Name
     *..................................................................................................................
     *
     * Required parameters: name
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidName($name)
    {
        return (strlen($name) <= self::USER_NAME_MAX_LENGTH);
    }




    /*..................................................................................................................
     *
     * Validation: Email
     *..................................................................................................................
     *
     * Required parameters: email
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }




    /*..................................................................................................................
     *
     * Validation: Password
     *..................................................................................................................
     *
     * Required parameters: password
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidPassword($password)
    {
        return (strlen($password) >= self::USER_PASSWORD_MIN_LENGTH && strlen($password) <= self::USER_PASSWORD_MAX_LENGTH);
    }




    /*..................................................................................................................
     *
     * Validation: Country Code
     *..................................................................................................................
     *
     * Required parameters: country_code
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidCountryCode($country_code)
    {
        return in_array($country_code, self::COUNTRIES);
    }




    /*..................................................................................................................
     *
     * Validation: Gender
     *..................................................................................................................
     *
     * Required parameters: gender
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidGender($gender)
    {
        return ($gender == 'M' || $gender == 'F');
    }




    /*..................................................................................................................
     *
     * Validation: Phone Number
     *..................................................................................................................
     *
     * Required parameters: phone
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidPhoneNumber($phone)
    {
        return (is_numeric($phone) && strlen($phone) <= self::USER_PHONE_NUMBER_MAX_LENGTH);
    }




    /*..................................................................................................................
     *
     * Validation: Pet Type
     *..................................................................................................................
     *
     * Required parameters: type
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidPetType($type)
    {
        return in_array($type, self::PET_TYPES);
    }




  /*..................................................................................................................
   *
   * Validation: Pet Breed
   *..................................................................................................................
   *
   * Required parameters: breed
   *
   * Returns boolean
   * .................................................................................................................
   */

    public function isValidPetBreed($breed)
    {
        return (strlen($breed) <= self::PET_BREED_MAX_LENGTH);
    }




    /*..................................................................................................................
     *
     * Validation: Pet Date of Birth
     *..................................................................................................................
     *
     * Required parameters: year, month
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidPetDob($year, $month)
    {
        if (($year >= self::PET_MINIMUM_BIRTH_YEAR && $year <= date('Y')) && ($month >= 1 && $month <= 12)) {

            return ($year == date('Y')) ? ($month <= date('n')) : true;
        }

        return false;
    }




    /*..................................................................................................................
     *
     * Validation: Pet Description
     *..................................................................................................................
     *
     * Required parameters: description
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidPetDescription($description)
    {
        return (strlen($description) <= self::PET_DESCRIPTION_MAX_LENGTH);
    }




    /*..................................................................................................................
     *
     * Validation: Latitude
     *..................................................................................................................
     *
     * Required parameters: latitude
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidLatitude($latitude)
    {
        return preg_match('/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/', $latitude);
    }




    /*..................................................................................................................
     *
     * Validation: Longitude
     *..................................................................................................................
     *
     * Required parameters: longitude
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidLongitude($longitude)
    {
        return preg_match('/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',
            $longitude);
    }




    /*..................................................................................................................
     *
     * Validation: Image Upload Count
     *..................................................................................................................
     *
     * Required parameters: images[]
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidImageUploadCount($images)
    {
        return ((sizeof($images) > 0) && (sizeof($images) <= self::PET_IMAGES_UPLOAD_MAX_COUNT));
    }




    /*..................................................................................................................
     *
     * Validation: Image format
     *..................................................................................................................
     *
     * Required parameters: images[]
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidImages($images)
    {
        foreach ($images as $image) {

            if (!getimagesize($image['tmp_name']) || ($image['extension'] != 'jpg' &&
                    $image['extension'] != 'png' && $image['extension'] != 'jpeg' &&
                    $image['extension'] != 'gif')) {

                return false;
            }
        }

        return true;
    }




    /*..................................................................................................................
     *
     * Validation: Page Number
     *..................................................................................................................
     *
     * Required parameters: page
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isValidPageNumber($page)
    {
        return (is_numeric($page) && $page > 0);
    }




    /*..................................................................................................................
     *
     * Encode Response As JSON with Http Status Code
     *..................................................................................................................
     *
     * Required parameters: code
     * Optional parameters: array
     *
     * Returns string
     *   JSON
     *
     * Note: Returns null if array parameter not found
     * .................................................................................................................
     */

    public function response($code, $array = null)
    {
        http_response_code($code);

        if (!is_null($array)) {
            header('Content-type: application/json;');
            return json_encode($array);
        }

        return null;
    }




    /*..................................................................................................................
     *
     * Validating existence of array keys
     *..................................................................................................................
     *
     * Required parameters: array, ...vars
     *
     * Returns boolean
     * .................................................................................................................
     */

    public function isset($array, ...$vars)
    {
        foreach ($vars as $var) {
            if (!isset($array[$var])) {
                return false;
            }
        }
        return true;
    }




    /*..................................................................................................................
     *
     * Function to improve the PHP's empty() function
     *..................................................................................................................
     *
     * Required parameters: ...vars
     *
     * Returns boolean
     *
     * Note:
     *   Advantages over PHP empty() function
     *     - Accept unlimited parameters
     *     - '0' will not be treated as an empty value
     * .................................................................................................................
     */

    public function empty(...$vars)
    {
        foreach ($vars as $var) {
            if (empty($var) && $var !== '0') {
                return true;
            }
        }
        return false;
    }

}