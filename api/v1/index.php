<?php

$con = new mysqli('127.0.0.1', 'root', '\'', 'adoptify');

require __DIR__ . '/../../include/Adoptify.php';
$app = new Adoptify($con);

require __DIR__ . '/../../include/RestAPI.php';
$restapi = new RestAPI();

require __DIR__ . '/../../include/Router.php';
$router = new Router();



/*................................................................................................................................
 *
 * User login
 *
 * URL => /auth
 * Method => POST
 * Authorization => -
 *
 * Required parameters => email, password, fcm_token
 *
 * Return
 * => 200: {
 *   user_id => integer
 *   access_token => string
 * }
 * => 400 when required parameters is blank
 * => 401 when login failed
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('POST', '/auth', function () use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'email', 'password', 'fcm_token')) {
        return $restapi->response(400);
    }

    $email = $request['email'];
    $password = $request['password'];
    $fcm_token = $request['fcm_token'];

    if ($app->login($email, $password)) {

        $user_id = $app->getUserId($email);

        if ($app->updateUserFcmToken($user_id, $fcm_token)) {

            $access_token = $app->getAccessToken($user_id);

            return $restapi->response(200, [
                'user_id' => $user_id,
                'access_token' => $access_token
            ]);
        }

        return $restapi->response(500);
    }

    return $restapi->response(401);
});



/*................................................................................................................................
 *
 * Get user details
 *
 * URL => /user/{id}
 * Method => GET
 * Authorization => Basic
 *
 * Required parameters => -
 *
 * Return
 * => 200: {
 *   user_id => integer
 *   name => string
 *   email => string
 *   country_code => string
 *   created_at => string
 * }
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('GET', '/user/[i:user_id]', function ($user_id) use ($app, $restapi) {

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        $user = $app->getUserDetails($user_id);

        return $restapi->response(200, [
            'user_id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'country_code' => $user['country_code'],
            'created_at' => $user['created_at']
        ]);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * User register
 *
 * URL => /user
 * Method => POST
 * Authorization => -
 *
 * Required parameters => name, email, password, country_code, fcm_token
 *
 * Return
 * => 201: {
 *   user_id => integer
 *   access_token => string
 * }
 * => 400 when required parameters is blank
 * => 409 when email exists
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('POST', '/user', function () use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'name', 'email', 'password', 'country_code', 'fcm_token')) {
        return $restapi->response(400);
    }

    $name = $request['name'];
    $email = $request['email'];
    $password = $request['password'];
    $country_code = $request['country_code'];
    $fcm_token = $request['fcm_token'];

    if (!$app->isEmailExists($email)) {

        if ($user_id = $app->addUser($name, $email, $password, $country_code, $fcm_token)) {

            $access_token = $app->getAccessToken($user_id);

            return $restapi->response(201, [
                'user_id' => $user_id,
                'access_token' => $access_token
            ]);
        }

        return $restapi->response(500);
    }

    return $restapi->response(409);
});



/*................................................................................................................................
 *
 * Update user profile
 *
 * URL => /user/{id}
 * Method => PUT
 * Authorization => Basic
 *
 * Required parameters => name, email, country_code
 *
 * Return
 * => 204 when update success
 * => 400 when required parameters is blank
 * => 403 when unauthorized
 * => 409 when email exists
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('PUT', '/user/[i:user_id]', function ($user_id) use ($app, $restapi) {

    parse_str(file_get_contents('php://input'), $request);

    if (!$restapi->found($request, 'name', 'email', 'country_code')) {
        return $restapi->response(400);
    }

    $name = $request['name'];
    $email = $request['email'];
    $country_code = $request['country_code'];

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if (!$app->isEmailExists($email, $user_id)) {

            if ($app->updateUserDetails($user_id, $name, $email, $country_code)) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(409);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * Update user password
 *
 * URL => /user/{id}/password
 * Method => PUT
 * Authorization => Basic
 *
 * Required parameters => current_password, new_password
 *
 * Return
 * => 200: {
 *   access_token => string
 * }
 * => 400 when required parameters is blank
 * => 401 when password is incorrect
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('PUT', '/user/[i:user_id]/password', function ($user_id) use ($app, $restapi) {

    parse_str(file_get_contents('php://input'), $request);

    if (!$restapi->found($request, 'current_password', 'new_password')) {
        return $restapi->response(400);
    }

    $current_password = $request['current_password'];
    $new_password = $request['new_password'];

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if ($app->verifyPassword($user_id, $current_password)) {

            if ($app->updateUserPassword($user_id, $new_password)) {

                $access_token = $app->getAccessToken($user_id);

                return $restapi->response(200, [
                    'access_token' => $access_token
                ]);
            }

            return $restapi->response(500);
        }

        return $restapi->response(401);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * Update user fcm token
 *
 * URL => /user/{id}/fcm_token
 * Method => PUT
 * Authorization => Basic
 *
 * Required parameters => fcm_token
 *
 * Return
 * => 200 when update success
 * => 400 when required parameters is blank
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('PUT', '/user/[i:user_id]/fcm_token', function ($user_id) use ($app, $restapi) {

    parse_str(file_get_contents('php://input'), $request);

    if (!$restapi->found($request, 'fcm_token')) {
        return $restapi->response(400);
    }

    $fcm_token = $request['fcm_token'];

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if ($app->updateUserFcmToken($user_id, $fcm_token)) {

            $access_token = $app->getAccessToken($user_id);

            return $restapi->response(200, [
                'access_token' => $access_token
            ]);
        }

        return $restapi->response(500);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * Disable user account
 *
 * URL => /user/{id}
 * Method => DELETE
 * Authorization => Basic
 *
 * Required parameters => -
 *
 * Return
 * => 204 when update success
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('DELETE', '/user/[i:user_id]', function ($user_id) use ($app, $restapi) {

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if ($app->disableUser($user_id)) {

            return $restapi->response(204);
        }

        return $restapi->response(500);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * Get dog details
 *
 * URL => /pet/dog/{id}
 * Method => GET
 * Authorization => -
 *
 * Required parameters => -
 *
 * Return
 * => 200: {
 *   dog_id => integer
 *   user => {
 *     user_id => integer
 *     name => string
 *   }
 *   breed => string
 *   gender => char(M/F)
 *   age_month => integer
 *   images => string[]
 *   description => string
 *   country_code => string
 *   contact => {
 *     name => string
 *     phone => string
 *     latitude => double
 *     longitude => double
 *     area_level_1 => string
 *     area_level_2 => string
 *   }
 *   comments => [
 *     dog_comment_id => integer
 *     user => {
 *       user_id => integer
 *       name => string
 *     }
 *     content => string
 *     created_at => string
 *   ]
 *   views => integer
 *   day_left => integer
 *   created_at => string
 * }
 * => 404 when dog not found
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('GET', '/pet/dog/[i:dog_id]', function ($dog_id) use ($app, $restapi) {

    if ($dog = $app->getDog($dog_id)) {

        if ($app->updateDogIncrementViews($dog_id)) {

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
                'contact' => $dog['contact'],
                'comments' => $dog['comments'],
                'view_count' => $dog['view_count'] + 1,
                'day_left' => $dog['day_left'],
                'created_at' => $dog['created_at']
            ]);
        }

        return $restapi->response(500);
    }

    return $restapi->response(404);
});



/*................................................................................................................................
 *
 * Add dog
 *
 * URL => /pet/dog
 * Method => POST
 * Authorization => Basic
 *
 * Required parameters => user_id, breed, gender, birth_year, birth_month, description, contact_name, contact_phone,
 *   contact_place_id, images[]
 *
 * Return
 * => 201: {
 *   dog_id => integer
 * }
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('POST', '/pet/dog', function () use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'user_id', 'breed', 'gender', 'birth_year', 'birth_month', 'description',
        'contact_name', 'contact_phone', 'contact_place_id') || !isset($_FILES['images'])) {

        return $restapi->response(400);
    }

    $user_id = $request['user_id'];
    $breed = $request['breed'];
    $gender = $request['gender'];
    $birth_year = $request['birth_year'];
    $birth_month = $request['birth_month'];
    $description = $request['description'];
    $contact_name = $request['contact_name'];
    $contact_phone = $request['contact_phone'];
    $contact_place_id = $request['contact_place_id'];
    $images = $app->reArrayImages($_FILES['images']);

    if (sizeof($images) > 8) {
        return $restapi->response(422);
    }

    foreach ($images as $image) {
        if (!getimagesize($image['tmp_name']) || ($image['extension'] != 'jpg' && $image['extension'] != 'png' &&
            $image['extension'] != 'jpeg' && $image['extension'] != 'gif')) {

            return $restapi->response(415);
        }
    }

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token) || true) {

        if ($dog_id = $app->addDog($user_id, $breed, $gender, $birth_year, $birth_month, $description, $contact_name,
            $contact_phone, $contact_place_id)) {

            if ($app->updateDogImages($dog_id, $images)) {

                return $restapi->response(201, [
                    'dog_id' => $dog_id
                ]);
            }

            $app->deleteDog($dog_id);
        }

        return $restapi->response(500);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * Update dog
 *
 * URL => /pet/dog/{id}
 * Method => PUT
 * Authorization => Basic
 *
 * Required parameters => breed, gender, birth_year, birth_month, description, contact_name, contact_phone
 *
 * Return
 * => 201: {
 *   dog_id => integer
 * }
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('PUT', '/pet/dog/[i:dog_id]', function ($dog_id) use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'breed', 'gender', 'birth_year', 'birth_month', 'description',
            'contact_name', 'contact_phone')) {

        return $restapi->response(400);
    }

    if ($dog = $app->getDog($dog_id)) {

        $breed = $request['breed'];
        $gender = $request['gender'];
        $birth_year = $request['birth_year'];
        $birth_month = $request['birth_month'];
        $description = $request['description'];
        $contact_name = $request['contact_name'];
        $contact_phone = $request['contact_phone'];

        $basic_token = $restapi->getBasicToken();

        if ($app->verifyAccessToken($dog['user']['user_id'], $basic_token) || true) {

            if ($app->updateDogDetails($dog_id, $breed, $gender, $birth_year, $birth_month, $description, $contact_name, $contact_phone)) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(403);
    }

    return $restapi->response(404);
});



/*................................................................................................................................
 *
 * Update dog contact place
 *
 * URL => /pet/dog/{id}/contact_place
 * Method => PUT
 * Authorization => Basic
 *
 * Required parameters => contact_place_id
 *
 * Return
 * => 201: {
 *   dog_id => integer
 * }
 * => 403 when unauthorized
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('PUT', '/pet/dog/[i:dog_id]/contact_place', function ($dog_id) use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'contact_place_id')) {

        return $restapi->response(400);
    }

    if ($dog = $app->getDog($dog_id)) {

        $contact_place_id = $request['contact_place_id'];

        $basic_token = $restapi->getBasicToken();

        if ($app->verifyAccessToken($dog['user']['user_id'], $basic_token) || true) {

            if ($app->updateDogContactPlace($dog_id, $contact_place_id)) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(403);
    }

    return $restapi->response(404);
});



/*................................................................................................................................
 *
 * Delete dog
 *
 * URL => /pets/dogs/{id}
 * Method => DELETE
 * Authorization => Basic
 *
 * Required parameters => -
 *
 * Return
 * => 204 when update success
 * => 403 when unauthorized
 * => 404 when dog not found
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('DELETE', '/pets/dogs/[i:dog_id]', function ($dog_id) use ($app, $restapi) {

    if ($dog = $app->getDog($dog_id)) {

        $basic_token = $restapi->getBasicToken();

        if ($app->verifyAccessToken($dog['user_id'], $basic_token)) {

            if ($app->deleteDog($dog['dog_id'])) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(403);
    }

    return $restapi->response(404);
});





/*................................................................................................................................
 *
 * Report dog
 *
 * URL => /pets/dogs/{id}/report
 * Method => POST
 * Authorization => Basic
 *
 * Required parameters => user_id
 *
 * Return
 * => 204 when report success
 * => 400 when required parameters is blank
 * => 403 when unauthorized
 * => 404 when dog not found
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('POST', '/pets/dogs/[i:dog_id]/comment', function ($dog_id) use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'user_id', 'content')) {
        return $restapi->response(400);
    }

    $user_id = $request['user_id'];

    if ($app->getDog($dog_id)) {

        $basic_token = $restapi->getBasicToken();

        if ($app->verifyAccessToken($user_id, $basic_token)) {

            if ($app->commentDog($dog_id, $user_id, $dog_id)) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(403);
    }

    return $restapi->response(404);
});




/*................................................................................................................................
 *
 * Report dog
 *
 * URL => /pets/dogs/{id}/report
 * Method => POST
 * Authorization => Basic
 *
 * Required parameters => user_id
 *
 * Return
 * => 204 when report success
 * => 400 when required parameters is blank
 * => 403 when unauthorized
 * => 404 when dog not found
 * => 500 when server error
 * ...............................................................................................................................
 */

$router->route('POST', '/pets/dogs/[i:dog_id]/report', function ($dog_id) use ($app, $restapi) {

    $request = $_POST;

    if (!$restapi->found($request, 'user_id')) {
        return $restapi->response(400);
    }

    $user_id = $request['user_id'];

    if ($dog = $app->getDog($dog_id)) {

        $basic_token = $restapi->getBasicToken();

        if ($app->verifyAccessToken($user_id, $basic_token)) {

            if ($app->reportDog($user_id, $dog_id)) {

                return $restapi->response(204);
            }

            return $restapi->response(500);
        }

        return $restapi->response(403);
    }

    return $restapi->response(404);
});



$router->run();