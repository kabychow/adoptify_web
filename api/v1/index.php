<?php

$con = new mysqli('127.0.0.1', 'root', '\'', 'adoptify');

require __DIR__ . '/../../include/Adoptify.php';
$app = new Adoptify($con);

require __DIR__ . '/../../include/RestAPI.php';
$restapi = new RestAPI();

require __DIR__ . '/../../include/Router.php';
$router = new Router();




$router->route('POST', '/auth', function () use ($app, $restapi)
{
    if ($restapi->isset($_POST, 'email', 'password', 'fcm_token')) {

        $email = $_POST['email'];
        $password = $_POST['password'];
        $fcm_token = $_POST['fcm_token'];

        if (!$restapi->empty($email, $password, $fcm_token)) {

            if ($app->isValidEmail($email)) {

                if ($user_id = $app->login($email, $password)) {

                    if ($app->updateUserFcmToken($user_id, $fcm_token)) {

                        return $restapi->response(200, [
                            'user_id' => $user_id,
                            'access_token' => $app->getAccessToken($user_id)
                        ]);
                    }

                    return $restapi->response(500);
                }

                return $restapi->response(401);
            }

        }

        return $restapi->response(422);
    }

    return $restapi->response(400);
});




$router->route('POST', '/users', function () use ($app, $restapi)
{
    if ($restapi->isset($_POST, 'name', 'gender', 'email', 'password', 'country_code', 'fcm_token')) {

        $name = trim($_POST['name']);
        $gender = strtoupper($_POST['gender']);
        $email = $_POST['email'];
        $password = $_POST['password'];
        $country_code = strtoupper($_POST['country_code']);
        $fcm_token = $_POST['fcm_token'];

        if (!$restapi->empty($name, $gender, $email, $password, $country_code, $fcm_token)) {

            if ($app->isvalidName($name) && $app->isValidGender($gender) && $app->isValidEmail($email) &&
                $app->isValidPassword($password) && $app->isValidCountryCode($country_code)) {

                if (!$app->isEmailExists($email)) {

                    if ($user_id = $app->addUser($name, $gender, $email, $password, $country_code, $fcm_token)) {

                        return $restapi->response(201, [
                            'user_id' => $user_id,
                            'access_token' => $app->getAccessToken($user_id)
                        ]);
                    }

                    return $restapi->response(500);
                }

                return $restapi->response(409);
            }

        }

        return $restapi->response(422);
    }

    return $restapi->response(400);
});




$router->route('GET', '/users/[i:user_id]', function ($user_id) use ($app, $restapi)
{
    if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

        return $restapi->response(200, [
            'user_id' => $user['id'],
            'name' => $user['name'],
            'gender' => $user['gender'],
            'email' => $user['email'],
            'country_code' => $user['country_code'],
            'created_at' => $user['created_at']
        ]);
    }

    return $restapi->response(403);
});




$router->route('PUT', '/users/[i:user_id]', function ($user_id) use ($app, $restapi)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($restapi->isset($_PUT, 'name', 'gender', 'email', 'country_code')) {

        $name = trim($_PUT['name']);
        $gender = strtoupper($_PUT['gender']);
        $email = $_PUT['email'];
        $country_code = strtoupper($_PUT['country_code']);

        if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

            if (!$restapi->empty($name, $gender, $email, $country_code)) {

                if ($app->isvalidName($name) && $app->isValidGender($gender) && $app->isValidEmail($email) &&
                    $app->isValidCountryCode($country_code)) {

                    if ($email == $user['email'] || !$app->isEmailExists($email)) {

                        if ($app->updateUserDetails($user_id, $name, $gender, $email, $country_code)) {

                            return $restapi->response(204);
                        }

                        return $restapi->response(500);
                    }

                    return $restapi->response(409);
                }

            }

            return $restapi->response(422);
        }

        return $restapi->response(403);
    }

    return $restapi->response(400);
});




$router->route('PUT', '/users/[i:user_id]/password', function ($user_id) use ($app, $restapi)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($restapi->isset($_PUT, 'current_password', 'new_password')) {

        $current_password = $_PUT['current_password'];
        $new_password = $_PUT['new_password'];

        if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

            if (!$restapi->empty($current_password, $new_password)) {

                if ($app->isValidPassword($new_password)) {

                    if (password_verify($current_password, $user['password'])) {

                        if ($app->updateUserPassword($user_id, $new_password)) {

                            return $restapi->response(200, [
                                'access_token' => $app->getAccessToken($user_id)
                            ]);
                        }

                        return $restapi->response(500);
                    }

                    return $restapi->response(401);
                }

            }

            return $restapi->response(422);
        }

        return $restapi->response(403);
    }

    return $restapi->response(400);
});




$router->route('PUT', '/users/[i:user_id]/fcm_token', function ($user_id) use ($app, $restapi)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($restapi->isset($_PUT, 'fcm_token')) {

        $fcm_token = $_PUT['fcm_token'];

        if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

            if (!$restapi->empty($fcm_token)) {

                if ($app->updateUserFcmToken($user_id, $fcm_token)) {

                    return $restapi->response(200, [
                        'access_token' => $app->getAccessToken($user_id)
                    ]);
                }

                return $restapi->response(500);
            }

            return $restapi->response(422);
        }

        return $restapi->response(403);
    }

    return $restapi->response(400);
});




$router->route('DELETE', '/users/[i:user_id]', function ($user_id) use ($app, $restapi)
{
    if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

        if ($app->disableUser($user_id)) {

            return $restapi->response(204);
        }

        return $restapi->response(500);
    }

    return $restapi->response(403);
});




$router->route('GET', '/pets/dogs', function () use ($app, $restapi)
{
    if ($restapi->isset($_GET, 'country_code', 'latitude', 'longitude')) {

        $country_code = $_GET['country_code'];
        $latitude = $_GET['latitude'];
        $longitude = $_GET['longitude'];

        if (!$restapi->empty($country_code, $latitude, $longitude)) {

            // TODO: get dog here
        }

        return $restapi->response(422);
    }

    return $restapi->response(400);
});




$router->route('POST', '/pets/dogs', function () use ($app, $restapi)
{
    if ($restapi->isset($_POST, 'user_id', 'breed', 'gender', 'birth_year', 'birth_month', 'description',
        'contact_name', 'contact_phone', 'contact_place_id' && isset($_FILES['images']))) {

        $user_id = $_POST['user_id'];
        $breed = $_POST['breed'];
        $gender = strtoupper($_POST['gender']);
        $birth_year = $_POST['birth_year'];
        $birth_month = $_POST['birth_month'];
        $description = $_POST['description'];
        $contact_name = $_POST['contact_name'];
        $contact_phone = $_POST['contact_phone'];
        $contact_place_id = $_POST['contact_place_id'];
        $images = $app->reArrayImages($_FILES['images']);

        if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

            if (!$restapi->empty($user_id, $breed, $gender, $birth_year, $birth_month, $description, $contact_name,
                $contact_phone, $contact_place_id)) {

                if ($app->isValidBreed($breed) && $app->isValidGender($gender) && $app->isValidBirthYear($birth_year) &&
                    $app->isValidBirthMonth($birth_month) && $app->isValidDescription($description) &&
                    $app->isValidName($contact_name) && $app->isValidPhone($contact_phone) && (sizeof($images) <= 8)) {

                    foreach ($images as $image) {

                        if (!getimagesize($image['tmp_name']) || ($image['extension'] != 'jpg' &&
                                $image['extension'] != 'png' && $image['extension'] != 'jpeg' &&
                                $image['extension'] != 'gif')) {

                            return $restapi->response(415);
                        }
                    }

                    if ($place = $app->processPlaceID($contact_place_id)) {

                        if ($place['country_code'] == $user['country_code']) {

                            if ($dog_id = $app->addDog($user_id, $user['country_code'], $breed, $gender, $birth_year,
                                $birth_month, $description, $contact_name, $contact_phone, $place['latitude'],
                                $place['longitude'], $place['area_level_1'], $place['area_level_2'])) {

                                if ($app->updateDogImages($dog_id, $images)) {

                                    return $restapi->response(201, [
                                        'dog_id' => $dog_id
                                    ]);
                                }

                                $app->deleteDog($dog_id);
                            }

                            return $restapi->response(500);
                        }

                        return $restapi->response(412);
                    }

                    return $restapi->response(503);
                }

            }

            return $restapi->response(422);
        }

        return $restapi->response(403);
    }

    return $restapi->response(400);
});




$router->route('GET', '/pets/dogs/[i:dog_id]', function ($dog_id) use ($app, $restapi)
{
    if ($dog = $app->getDog($dog_id)) {

        $app->updateDogIncrementViews($dog_id);

        return $restapi->response(200, [
            'dog_id' => $dog['dog_id'],
            'user' => [
                'user_id' => $dog['user']['user_id'],
                'name' => $dog['user']['name']
            ],
            'breed' => $dog['breed'],
            'gender' => $dog['gender'],
            'age_month' => $dog['age_month'],
            'images' => $dog['images'],
            'description' => $dog['description'],
            'country_code' => $dog['country_code'],
            'contact' => [
                'name' => $dog['contact']['name'],
                'phone' => $dog['contact']['phone'],
                'latitude' => $dog['contact']['latitude'],
                'longitude' => $dog['contact']['longitude'],
                'area_level_1' => $dog['contact']['area_level_1'],
                'area_level_2' => $dog['contact']['area_level_2']
            ],
            'view_count' => $dog['view_count'] + 1,
            'day_left' => $dog['day_left'],
            'created_at' => $dog['created_at']
        ]);
    }

    return $restapi->response(404);
});




$router->route('PUT', '/pets/dogs/[i:dog_id]', function ($dog_id) use ($app, $restapi)
{
    if ($restapi->isset($_POST, 'breed', 'gender', 'birth_year', 'birth_month', 'description',
        'contact_name', 'contact_phone')) {

        $breed = $_POST['breed'];
        $gender = $_POST['gender'];
        $birth_year = $_POST['birth_year'];
        $birth_month = $_POST['birth_month'];
        $description = $_POST['description'];
        $contact_name = $_POST['contact_name'];
        $contact_phone = $_POST['contact_phone'];

        if ($dog = $app->getDog($dog_id)) {

            if ($user = $app->verifyAccessToken($dog['user_id'], $restapi->getBasicToken())) {

                if (!$restapi->empty($breed, $gender, $birth_year, $birth_month, $description, $contact_name,
                    $contact_phone)) {

                    if ($app->isValidBreed($breed) && $app->isValidGender($gender) &&
                        $app->isValidBirthYear($birth_year) && $app->isValidBirthMonth($birth_month) &&
                        $app->isValidDescription($description) && $app->isValidName($contact_name) &&
                        $app->isValidPhone($contact_phone)) {

                        if ($app->updateDogDetails($dog_id, $breed, $gender, $birth_year, $birth_month, $description,
                            $contact_name, $contact_phone)) {

                            return $restapi->response(204);
                        }

                        return $restapi->response(500);
                    }

                }

                return $restapi->response(422);
            }

            return $restapi->response(403);
        }

        return $restapi->response(404);
    }

    return $restapi->response(400);
});




$router->route('PUT', '/pets/dogs/[i:dog_id]/images', function ($dog_id) use ($app, $restapi)
{
    if (isset($_FILES['images'])) {

        $images = $app->reArrayImages($_FILES['images']);

        if ($dog = $app->getDog($dog_id)) {

            if ($user = $app->verifyAccessToken($dog['user_id'], $restapi->getBasicToken())) {

                if ((sizeof($images) <= 8)) {

                    foreach ($images as $image) {

                        if (!getimagesize($image['tmp_name']) || ($image['extension'] != 'jpg' &&
                                $image['extension'] != 'png' && $image['extension'] != 'jpeg' &&
                                $image['extension'] != 'gif')) {

                            return $restapi->response(415);
                        }
                    }

                    if ($app->updateDogImages($dog_id, $images)) {

                        return $restapi->response(204);
                    }

                    return $restapi->response(500);
                }

                return $restapi->response(422);
            }

            return $restapi->response(403);
        }

        return $restapi->response(404);
    }

    return $restapi->response(400);
});




$router->route('PUT', '/pets/dogs/[i:dog_id]/contact_place', function ($dog_id) use ($app, $restapi)
{
    if ($restapi->isset($_POST, 'contact_place_id')) {

        $contact_place_id = $_POST['contact_place_id'];

        if ($dog = $app->getDog($dog_id)) {

            if ($user = $app->verifyAccessToken($dog['user_id'], $restapi->getBasicToken())) {

                if (!$restapi->empty($contact_place_id)) {

                    if ($place = $app->processPlaceID($contact_place_id)) {

                        if ($app->updateDogContactPlace($dog_id, $place['latitude'], $place['longitude'],
                            $place['area_level_1'], $place['area_level_2'])) {

                            return $restapi->response(204);
                        }

                        return $restapi->response(500);
                    }

                    return $restapi->response(503);
                }

                return $restapi->response(422);
            }

            return $restapi->response(403);
        }

        return $restapi->response(404);
    }

    return $restapi->response(400);
});




$router->route('DELETE', '/pets/dogs/[i:dog_id]', function ($dog_id) use ($app, $restapi)
{
    if ($dog = $app->getDog($dog_id)) {

        if ($user = $app->verifyAccessToken($dog['user_id'], $restapi->getBasicToken())) {

            if ($app->deleteDog($dog['dog_id'])) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(403);
    }

    return $restapi->response(404);
});




$router->route('POST', '/pets/dogs/[i:dog_id]/report', function ($dog_id) use ($app, $restapi)
{
    if ($restapi->isset($_POST, 'user_id')) {

        $user_id = $_POST['user_id'];

        if ($dog = $app->getDog($dog_id)) {

            if ($user = $app->verifyAccessToken($user_id, $restapi->getBasicToken())) {

                if ($app->reportDog($user_id, $dog_id)) {

                    return $restapi->response(204);
                }

                return $restapi->response(500);
            }

            return $restapi->response(403);
        }

        return $restapi->response(404);
    }

    return $restapi->response(400);
});



$router->run();