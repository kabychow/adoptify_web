<?php

/*......................................................................................................................
 *
 * Adoptify API v1
 *......................................................................................................................
 *
 * Dependencies {
 *   apache2 with url rewrite enabled
 *   php-7.1
 *   mysql-5.7
 * }
 *......................................................................................................................
 */

require __DIR__ . '/../include/Router.php';
$router = new Router();

require __DIR__ . '/../include/Adoptify.php';
$app = new Adoptify();




/*......................................................................................................................
 *
 * User Authentication
 *......................................................................................................................
 *
 * URL: /auth
 * Method: POST
 * Authorization: -
 *
 * Required Parameters { email, password, fcm_token }
 *
 * Responses { 200, 400, 401, 404, 422, 500 }
 *
 * JSON Data {
 *   user_id => integer,
 *   access_token => string
 * }
 *......................................................................................................................
 */

$router->route('POST', '/auth', function () use ($app)
{
    if ($app->isset($_POST, 'email', 'password', 'fcm_token')) {

        $email = $_POST['email'];
        $password = $_POST['password'];
        $fcm_token = $_POST['fcm_token'];

        if (!$app->empty($email, $password, $fcm_token)) {

            if ($app->isValidEmail($email)) {

                if ($user_id = $app->getUserId($email)) {

                    if ($app->verifyPassword($user_id, $password)) {

                        if ($app->updateUserFcmToken($user_id, $fcm_token)) {

                            return $app->response(200, [
                                'user_id' => $user_id,
                                'access_token' => $app->getAccessToken($user_id)
                            ]);
                        }

                        return $app->response(500);
                    }

                    return $app->response(401);
                }

                return $app->response(404);
            }

        }

        return $app->response(422);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * User Registration
 *......................................................................................................................
 *
 * URL: /users
 * Method: POST
 * Authorization: -
 *
 * Required Parameters { name, gender, email, password, country_code, fcm_token }
 *
 * Responses { 201, 400, 409, 422, 500 }
 *
 * JSON Data { user_id, access_token }
 *......................................................................................................................
 */

$router->route('POST', '/users', function () use ($app)
{
    if ($app->isset($_POST, 'name', 'gender', 'email', 'password', 'country_code', 'fcm_token')) {

        $name = trim($_POST['name']);
        $gender = strtoupper($_POST['gender']);
        $email = $_POST['email'];
        $password = $_POST['password'];
        $country_code = strtoupper($_POST['country_code']);
        $fcm_token = $_POST['fcm_token'];

        if (!$app->empty($name, $gender, $email, $password, $country_code, $fcm_token)) {

            if ($app->isvalidName($name) && $app->isValidGender($gender) && $app->isValidEmail($email) &&
                $app->isValidPassword($password) && $app->isValidCountryCode($country_code)) {

                if (!$app->getUserId($email)) {

                    if ($user_id = $app->addUser($name, $gender, $email, $password, $country_code, $fcm_token)) {

                        return $app->response(201, [
                            'user_id' => $user_id,
                            'access_token' => $app->getAccessToken($user_id)
                        ]);
                    }

                    return $app->response(500);
                }

                return $app->response(409);
            }

        }

        return $app->response(422);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * User Get Details
 *......................................................................................................................
 *
 * URL: /users/{id}
 * Method: GET
 * Authorization: Basic
 *
 * Required Parameters {}
 *
 * Responses { 200, 403, 404, 500 }
 *
 * JSON Data { user_id, name, gender, email, country_code, created_at }
 *......................................................................................................................
 */

$router->route('GET', '/users/[i:user_id]', function ($user_id) use ($app)
{
    if ($user = $app->getUserDetails($user_id)) {

        if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

            return $app->response(200, $user);
        }

        return $app->response(403);
    }

    return $app->response(404);
});




/*......................................................................................................................
 *
 * User Get Published Pets
 *......................................................................................................................
 *
 * URL: /users/{id}/pets
 * Method: GET
 * Authorization: Basic
 *
 * Required Parameters {}
 *
 * Responses { 200, 403, 404, 500 }
 *
 * JSON Data { pet_id, type, thumbnail, country_code, contact_area_level_1, contact_area_level_2, view_count, created_at,
 *             day_left }
 *......................................................................................................................
 */

$router->route('GET', '/users/[i:user_id]/pets', function ($user_id) use ($app)
{
    if ($user = $app->getUserDetails($user_id)) {

        if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

            $pets = $app->getUserPets($user_id);

            return $app->response(200, $pets);
        }

        return $app->response(403);
    }

    return $app->response(404);
});




/*......................................................................................................................
 *
 * User Update Details
 *......................................................................................................................
 *
 * URL: /users/{id}
 * Method: PUT
 * Authorization: Basic
 *
 * Required Parameters { name, gender, email, country_code }
 *
 * Responses { 204, 400, 403, 404, 409, 422, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]', function ($user_id) use ($app)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($app->isset($_PUT, 'name', 'gender', 'email', 'country_code')) {

        $name = trim($_PUT['name']);
        $gender = strtoupper($_PUT['gender']);
        $email = $_PUT['email'];
        $country_code = strtoupper($_PUT['country_code']);

        if ($user = $app->getUserDetails($user_id)) {

            if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

                if (!$app->empty($name, $gender, $email, $country_code)) {

                    if ($app->isvalidName($name) && $app->isValidGender($gender) && $app->isValidEmail($email) &&
                        $app->isValidCountryCode($country_code)) {

                        if ($email == $user['email'] || !$app->getUserId($email)) {

                            if ($app->updateUserDetails($user_id, $name, $gender, $email, $country_code)) {

                                return $app->response(204);
                            }

                            return $app->response(500);
                        }

                        return $app->response(409);
                    }

                }

                return $app->response(422);
            }

            return $app->response(403);
        }

        return $app->response(404);

    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * User Update Password
 *......................................................................................................................
 *
 * URL: /users/{id}/password
 * Method: PUT
 * Authorization: Basic
 *
 * Required Parameters { current_password, new_password }
 *
 * Responses { 200, 400, 401, 403, 404, 422, 500 }
 *
 * JSON Data { access_token }
 *......................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]/password', function ($user_id) use ($app)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($app->isset($_PUT, 'current_password', 'new_password')) {

        $current_password = $_PUT['current_password'];
        $new_password = $_PUT['new_password'];

        if ($user = $app->getUserDetails($user_id)) {

            if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

                if (!$app->empty($current_password, $new_password)) {

                    if ($app->isValidPassword($new_password)) {

                        if ($app->verifyPassword($user_id, $current_password)) {

                            if ($app->updateUserPassword($user_id, $new_password)) {

                                return $app->response(200, [
                                    'access_token' => $app->getAccessToken($user_id)
                                ]);
                            }

                            return $app->response(500);
                        }

                        return $app->response(401);
                    }

                }

                return $app->response(422);
            }

            return $app->response(403);
        }

        return $app->response(404);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * User Update FCM Token
 *......................................................................................................................
 *
 * URL: /users/{id}/fcm_token
 * Method: PUT
 * Authorization: Basic
 *
 * Required Parameters { fcm_token }
 *
 * Responses { 200, 400, 403, 404, 422, 500 }
 *
 * JSON Data { access_token }
 *......................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]/fcm_token', function ($user_id) use ($app)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($app->isset($_PUT, 'fcm_token')) {

        $fcm_token = $_PUT['fcm_token'];

        if ($user = $app->getUserDetails($user_id)) {

            if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

                if (!$app->empty($fcm_token)) {

                    if ($app->updateUserFcmToken($user_id, $fcm_token)) {

                        return $app->response(200, [
                            'access_token' => $app->getAccessToken($user_id)
                        ]);
                    }

                    return $app->response(500);
                }

                return $app->response(422);
            }

            return $app->response(403);
        }

        return $app->response(404);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * User Disable Account
 *......................................................................................................................
 *
 * URL: /users/{id}
 * Method: DELETE
 * Authorization: Basic
 *
 * Required Parameters {}
 *
 * Responses { 204, 403, 404, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('DELETE', '/users/[i:user_id]', function ($user_id) use ($app)
{
    if ($user = $app->getUserDetails($user_id)) {

        if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

            if ($app->disableUser($user_id)) {

                return $app->response(204);
            }

            return $app->response(500);
        }

        return $app->response(403);
    }

    return $app->response(404);
});




/*......................................................................................................................
 *
 * TODO: User Recover Password
 *......................................................................................................................
 *
 * URL: /recover-password
 * Method: POST
 * Authorization: -
 *
 * Required Parameters { email }
 *
 * Responses { 204, 400, 404, 409, 422, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('POST', '/recover-password', function() use ($app)
{
    if ($app->isset($_POST, 'email')) {

        $email = $_POST['email'];

        if ($app->empty($email)) {

            if ($app->isValidEmail($email)) {

                if ($user_id = $app->getUserId($email)) {

                    $recovery_password = $app->getUserRecoveryPassword($user_id);

                    if (mail($email, 'Adoptify: Password Recovery',
                        'Your new password is ' . $recovery_password)) {

                        return $app->response(204);
                    }

                    return $app->response(500);
                }

                return $app->response(404);
            }

        }

        return $app->response(422);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * Pet Get All
 *......................................................................................................................
 *
 * URL: /pets
 * Method: GET
 * Authorization: -
 *
 * Required Parameters { type, country_code, latitude, longitude, page }
 *
 * Responses { 200, 400, 422, 500 }
 *
 * JSON Data [{ pet_id, type, user_id, user_name, thumbnail, breed, gender, images, age_month, age_year, description,
 *              country_code, contact_name, contact_phone, contact_latitude, contact_longitude, contact_area_level_1,
 *             contact_area_level_2, view_count, created_at, day_left }]
 *......................................................................................................................
 */

$router->route('GET', '/pets', function () use ($app)
{
    if ($app->isset($_GET, 'type', 'country_code', 'latitude', 'longitude', 'page')) {

        $type = strtolower($_GET['type']);
        $country_code = strtoupper($_GET['country_code']);
        $latitude = $_GET['latitude'];
        $longitude = $_GET['longitude'];
        $page = $_GET['page'];

        if (!$app->empty($type, $country_code, $latitude, $longitude, $page)) {

            if ($app->isValidPetType($type) && $app->isValidCountryCode($country_code) &&
                $app->isValidLatitude($latitude) && $app->isValidLongitude($longitude) &&
                $app->isValidPageNumber($page)) {

                $pets = $app->getPets($type, $country_code, $latitude, $longitude, $page);

                return $app->response(200, $pets);
            }

        }

        return $app->response(422);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * Pet Publish
 *......................................................................................................................
 *
 * URL: /pets
 * Method: POST
 * Authorization: Basic
 *
 * Required Parameters { user_id, type, breed, gender, birth_year, birth_month, description, contact_name,
 *                       contact_phone, contact_place_id, images[] }
 *
 * Responses { 201, 400, 403, 412, 415, 422, 500, 503 }
 *
 * JSON Data { pet_id }
 *......................................................................................................................
 */

$router->route('POST', '/pets', function () use ($app)
{
    if ($app->isset($_POST, 'user_id', 'type', 'breed', 'gender', 'birth_year', 'birth_month', 'description',
        'contact_name', 'contact_phone', 'contact_place_id') && $app->isset($_FILES, 'images')) {

        $user_id = $_POST['user_id'];
        $type = strtolower($_POST['type']);
        $breed = $_POST['breed'];
        $gender = strtoupper($_POST['gender']);
        $birth_year = $_POST['birth_year'];
        $birth_month = $_POST['birth_month'];
        $description = $_POST['description'];
        $contact_name = $_POST['contact_name'];
        $contact_phone = $_POST['contact_phone'];
        $contact_place_id = $_POST['contact_place_id'];
        $images = $app->reArrayImages($_FILES['images']);

        if (!$app->empty($user_id, $type, $breed, $gender, $birth_year, $birth_month, $description, $contact_name,
            $contact_phone, $contact_place_id)) {

            if ($app->isValidPetType($type) && $app->isValidPetBreed($breed) && $app->isValidGender($gender) &&
                $app->isValidPetDob($birth_year, $birth_month) && $app->isValidPetDescription($description) &&
                $app->isValidName($contact_name) && $app->isValidPhoneNumber($contact_phone) &&
                $app->isValidImageUploadCount($images)) {

                if ($user = $app->getUserDetails($user_id)) {

                    if ($app->isValidImages($images)) {

                        if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

                            if ($place = $app->processPlaceID($contact_place_id)) {

                                if ($place['country_code'] == $user['country_code']) {

                                    if ($pet_id = $app->addPet($user_id, $user['country_code'], $type, $breed, $gender,
                                        $birth_year, $birth_month, $description, $contact_name, $contact_phone,
                                        $place['latitude'], $place['longitude'], $place['area_level_1'],
                                        $place['area_level_2'])) {

                                        if ($app->uploadPetImages($pet_id, $images)) {

                                            return $app->response(201, [
                                                'pet_id' => $pet_id
                                            ]);
                                        }

                                        $app->deletePet($pet_id);
                                    }

                                    return $app->response(500);
                                }

                                return $app->response(412);
                            }

                            return $app->response(503);
                        }

                        return $app->response(403);
                    }

                    return $app->response(415);
                }

            }

        }

        return $app->response(422);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * Pet Update Details
 *......................................................................................................................
 *
 * URL: /pets/{id}
 * Method: PUT
 * Authorization: Basic
 *
 * Required Parameters { type, breed, gender, birth_year, birth_month, description, contact_name, contact_phone }
 *
 * Responses { 204, 400, 403, 404, 422, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('PUT', '/pets/[i:pet_id]', function ($pet_id) use ($app)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($app->isset($_PUT, 'type', 'breed', 'gender', 'birth_year', 'birth_month', 'description',
        'contact_name', 'contact_phone')) {

        $type = strtolower($_PUT['type']);
        $breed = $_PUT['breed'];
        $gender = strtoupper($_PUT['gender']);
        $birth_year = $_PUT['birth_year'];
        $birth_month = $_PUT['birth_month'];
        $description = $_PUT['description'];
        $contact_name = $_PUT['contact_name'];
        $contact_phone = $_PUT['contact_phone'];

        if ($pet = $app->getPet($pet_id)) {

            if ($app->getBasicToken() == $app->getAccessToken($pet['user_id'])) {

                if (!$app->empty($type, $breed, $gender, $birth_year, $birth_month, $description, $contact_name,
                    $contact_phone)) {

                    if ($app->isValidPetType($type) && $app->isValidPetBreed($breed) && $app->isValidGender($gender) &&
                        $app->isValidPetDob($birth_year, $birth_month) && $app->isValidPetDescription($description) &&
                        $app->isValidName($contact_name) && $app->isValidPhoneNumber($contact_phone)) {

                        if ($app->updatePetDetails($pet_id, $type, $breed, $gender, $birth_year, $birth_month,
                            $description, $contact_name, $contact_phone)) {

                            return $app->response(204);
                        }

                        return $app->response(500);
                    }

                }

                return $app->response(422);
            }

            return $app->response(403);
        }

        return $app->response(404);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * Pet Re-Upload Images
 *......................................................................................................................
 *
 * URL: /pets/{id}/images
 * Method: POST
 * Authorization: Basic
 *
 * Required Parameters { images[] }
 *
 * Responses { 204, 400, 403, 404, 415, 422, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('POST', '/pets/[i:pet_id]/images', function ($pet_id) use ($app)
{
    if ($app->isset($_FILES, 'images')) {

        $images = $app->reArrayImages($_FILES['images']);

        if ($pet = $app->getPet($pet_id)) {

            if ($app->getBasicToken() == $app->getAccessToken($pet['user_id'])) {

                if ($app->isValidImageUploadCount($images)) {

                    if ($app->isValidImages($images)) {

                        if ($app->uploadPetImages($pet_id, $images)) {

                            return $app->response(204);
                        }

                        return $app->response(500);
                    }

                    return $app->response(415);
                }

                return $app->response(422);
            }

            return $app->response(403);
        }

        return $app->response(404);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * Pet Update Contact Place
 *......................................................................................................................
 *
 * URL: /pets/{id}/contact_place
 * Method: PUT
 * Authorization: Basic
 *
 * Required Parameters { contact_place_id }
 *
 * Responses { 204, 400, 403, 404, 412, 422, 500, 503 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('PUT', '/pets/[i:pet_id]/contact_place', function ($pet_id) use ($app)
{
    parse_str(file_get_contents('php://input'), $_PUT);

    if ($app->isset($_PUT, 'contact_place_id')) {

        $contact_place_id = $_PUT['contact_place_id'];

        if ($pet = $app->getPet($pet_id)) {

            if ($app->getBasicToken() == $app->getAccessToken($pet['user_id'])) {

                if (!$app->empty($contact_place_id)) {

                    if ($place = $app->processPlaceID($contact_place_id)) {

                        if ($place['country_code'] == $pet['country_code']) {

                            if ($app->updatePetContactPlace($pet_id, $place['latitude'], $place['longitude'],
                                $place['area_level_1'], $place['area_level_2'])) {

                                return $app->response(204);
                            }

                            return $app->response(500);
                        }

                        return $app->response(412);
                    }

                    return $app->response(503);
                }

                return $app->response(422);
            }

            return $app->response(403);
        }

        return $app->response(404);
    }

    return $app->response(400);
});




/*......................................................................................................................
 *
 * Pet Deletion
 *......................................................................................................................
 *
 * URL: /pets/{id}
 * Method: DELETE
 * Authorization: Basic
 *
 * Required Parameters {}
 *
 * Responses { 204, 403, 404, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('DELETE', '/pets/[i:pet_id]', function ($pet_id) use ($app)
{
    if ($pet = $app->getPet($pet_id)) {

        if ($app->getBasicToken() == $app->getAccessToken($pet['user_id'])) {

            if ($app->deletePet($pet_id)) {

                return $app->response(204);
            }

            return $app->response(500);
        }

        return $app->response(403);
    }

    return $app->response(404);
});




/*......................................................................................................................
 *
 * Pet Report Action
 *......................................................................................................................
 *
 * URL: /pets/{id}/report
 * Method: POST
 * Authorization: Basic
 *
 * Required Parameters { user_id }
 *
 * Responses { 204, 400, 403, 404, 422, 500 }
 *
 * JSON Data {}
 *......................................................................................................................
 */

$router->route('POST', '/pets/[i:pet_id]/report', function ($pet_id) use ($app)
{
    if ($app->isset($_POST, 'user_id')) {

        $user_id = $_POST['user_id'];

        if ($pet = $app->getPet($pet_id)) {

            if (!$app->empty($user_id)) {

                if ($user = $app->getUserDetails($user_id)) {

                    if ($app->getBasicToken() == $app->getAccessToken($user_id)) {

                        if ($app->reportPet($pet_id, $user_id)) {

                            return $app->response(204);
                        }

                        return $app->response(500);
                    }

                    return $app->response(403);
                }

            }

            return $app->response(422);
        }

        return $app->response(404);
    }

    return $app->response(400);
});




$router->routeError(function() use ($app)
{
    return $app->response(500);
});


$router->run();
