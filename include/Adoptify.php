<?php

class Adoptify
{
    private $con;
    private const IMAGE_PATH_PET_DOG = 'uploads/pets/dogs/';
    private const IMAGE_PATH_PET_CAT = 'uploads/pets/cats/';

    public function __construct(mysqli $con)
    {
        $this->con = $con;
    }


    public function login($email, $password)
    {
        $query = "
          SELECT id, password
          FROM user
          WHERE email = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($user && password_verify($password, $user['password'])) ? $user['id'] : 0;
    }


    public function verifyAccessToken($user_id, $access_token)
    {
        $query = "
          SELECT id, name, gender, email, password, country_code, created_at,
            MD5(CONCAT(id, password, fcm_token)) AS access_token
          FROM user
          WHERE id = ? AND is_disabled = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($user && ($access_token == $user['access_token'])) ? $user : null;
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


    public function isEmailExists($email)
    {
        $query = "
          SELECT COUNT(*) AS count
          FROM user
          WHERE email = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        return ($count > 0);
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


    public function getDog($dog_id)
    {
        $query = "
          SELECT d.id, d.user_id, u.name AS user_name, d.breed, d.gender, d.image_count,
            (((YEAR(NOW()) * 12) + MONTH(NOW())) - ((YEAR(d.dob) * 12) + MONTH(d.dob))) AS age_month, d.description,
            d.country_code, d.contact_name, d.contact_phone, d.contact_latitude, d.contact_longitude,
            d.contact_area_level_1, d.contact_area_level_2, d.view_count, d.created_at,
            DATEDIFF(d.expiry_date, DATE(NOW())) AS day_left
          FROM dog AS d
          INNER JOIN user AS u ON d.user_id = u.id
          WHERE d.id = ? AND DATEDIFF(d.expiry_date, DATE(NOW())) > 0 AND d.is_deleted = 0
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $dog_id);
        $stmt->execute();
        $dog = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($dog) {

            $dog['images'] = [];

            if ($dog['image_count'] > 0) {

                $dog['thumbnail'] = self::IMAGE_PATH_PET_DOG . $dog_id . '-0.jpg';

                for ($i = 1; $i <= $dog['image_count']; $i++) {

                    array_push($dog['images'], self::IMAGE_PATH_PET_DOG . $dog_id . '-' . $i . '.jpg');
                }
            }

            return [

                'dog_id' => $dog['id'],
                'user' => [
                    'user_id' => $dog['user_id'],
                    'name' => $dog['user_name']
                ],
                'breed' => $dog['breed'],
                'gender' => $dog['gender'],
                'age_month' => $dog['age_month'],
                'images' => $dog['images'],
                'description' => $dog['description'],
                'country_code' => $dog['country_code'],
                'contact' => [
                    'name' => $dog['contact_name'],
                    'phone' => $dog['contact_phone'],
                    'latitude' => $dog['contact_latitude'],
                    'longitude' => $dog['contact_longitude'],
                    'area_level_1' => $dog['contact_area_level_1'],
                    'area_level_2' => $dog['contact_area_level_2']
                ],
                'view_count' => $dog['view_count'],
                'day_left' => $dog['day_left'],
                'thumbnail' => $dog['thumbnail'],
                'created_at' => $dog['created_at']
            ];
        }

        return null;
    }


    public function addDog($user_id, $country_code, $breed, $gender, $birth_year, $birth_month, $description,
                           $contact_name, $contact_phone, $contact_latitude, $contact_longitude, $contact_area_level_1,
                           $contact_area_level_2)
    {
        $dob = $birth_year . '-' . $birth_month . '-' . '01';

        $query = "
          INSERT INTO dog (user_id, breed, gender, dob, description, country_code, contact_name, contact_phone,
            contact_latitude, contact_longitude, contact_area_level_1, contact_area_level_2, expiry_date)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(DATE(NOW()), INTERVAL 150 DAY)) 
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('isssssssddss', $user_id, $breed, $gender, $dob, $description,
            $country_code, $contact_name, $contact_phone, $contact_latitude, $contact_longitude, $contact_area_level_1,
            $contact_area_level_2);
        $stmt->execute();
        $dog_id = $stmt->insert_id;
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return ($affected_rows > 0) ? $dog_id : 0;
    }


    public function updateDogImages($dog_id, $images)
    {
        require __DIR__ . '/../include/ImageResizer.php';

        array_map('unlink', glob(__DIR__ . '/../' . self::IMAGE_PATH_PET_DOG . $dog_id . '-*.jpg'));

        $image_count = sizeof($images);

        for ($i = 0; $i < $image_count; $i++) {

            if (!$i) {

                $name = $dog_id . '-' . $i . '.jpg';
                $target = self::IMAGE_PATH_PET_DOG . $name;

                $imageResizer = new ImageResizer($images[$i]['tmp_name'], __DIR__ . '/../' . $target);

                if (!$imageResizer->resize(150, 200)) {

                    return false;
                }
            }

            $name = $dog_id . '-' . ($i + 1) . '.jpg';
            $target = self::IMAGE_PATH_PET_DOG . $name;

            $imageResizer = new ImageResizer($images[$i]['tmp_name'], __DIR__ . '/../' . $target);

            if (!$imageResizer->resize(450, 600)) {

                return false;
            }
        }

        $query = "
          UPDATE dog
          SET image_count = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ii', $image_count, $dog_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updateDogDetails($dog_id, $breed, $gender, $birth_year, $birth_month, $description, $contact_name,
                                     $contact_phone)
    {
        $dob = $birth_year . '-' . $birth_month . '-' . '01';

        $query = "
          UPDATE dog
          SET breed = ?, gender = ?, dob = ?, description = ?, contact_name = ?, contact_phone = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ssssssi', $breed, $gender, $dob, $description, $contact_name, $contact_phone, $dog_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updateDogContactPlace($dog_id, $contact_latitude, $contact_longitude, $contact_area_level_1,
                                          $contact_area_level_2)
    {
        $query = "
          UPDATE dog
          SET contact_latitude = ?, contact_longitude = ?, contact_area_level_1 = ?, contact_area_level_2 = ?
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ddssi', $contact_latitude, $contact_longitude, $contact_area_level_1,
            $contact_area_level_2, $dog_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    public function updateDogIncrementViews($dog_id)
    {
        $query = "
          UPDATE dog
          SET view_count = view_count + 1
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $dog_id);
        $stmt->execute();
        $stmt->close();
    }


    public function deleteDog($dog_id)
    {
        $query = "
          UPDATE dog
          SET is_deleted = 1
          WHERE id = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('i', $dog_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


    public function commentDog($dog_id, $user_id, $content)
    {
        $query = "
          INSERT INTO dog_comment (user_id, dog_id, content)
          VALUES (?, ?, ?)
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('iis', $user_id, $dog_id, $content);
        $stmt->execute();
        $affected_rows = $stmt->affected_rows;
        $dog_comment_id = $stmt->insert_id;
        $stmt->close();

        return ($affected_rows > 0) ? $dog_comment_id : 0;
    }


    public function reportDog($user_id, $dog_id)
    {
        $query = "
          INSERT INTO dog_report (dog_id, user_id)
          VALUES (?, ?)
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('ii', $dog_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows > 0;
    }


    public function processPlaceID($place_id)
    {
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $place_id .
            '&key=AIzaSyC80DoVueEYQV2-c7Wo0NRtc4fuGDOo-5g';
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
        return (strlen($name) <= 50);
    }


    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }


    public function isValidPassword($password) {
        return (strlen($password) >= 6 && strlen($password) <= 32);
    }


    public function isValidCountryCode($country_code) {
        $query = "
          SELECT COUNT(*) AS count
          FROM country
          WHERE code = ?
        ";
        $stmt = $this->con->prepare($query);
        $stmt->bind_param('s', $country_code);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        return ($count > 0);
    }


    public function isValidGender($gender) {
        return ($gender == 'M' || $gender == 'F');
    }


    public function isValidBreed($breed) {
        return (strlen($breed) <= 50);
    }


    public function isValidDob($year, $month) {

        if (($year >= 1970 && $year <= date('Y')) && ($month >= 1 && $month <= 12)) {

            return ($year == date('Y')) ? ($month <= date('n')) : true;
        }

        return false;
    }


    public function isValidBirthMonth($month) {
        return ($month >= 1 && $month <= 12);
    }


    public function isValidDescription($description) {
        return (strlen($description) <= 2000);
    }


    public function isValidPhone($phone) {
        return (is_numeric($phone) && strlen($phone) <= 30);
    }


}