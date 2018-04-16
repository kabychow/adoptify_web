<?php

$con = new mysqli('localhost', 'root', '\'', 'adoptify');

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
 *
 * Unit Test => Success
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

    if ($user_id = $app->getUserId($email)) {

        if ($app->auth($user_id, $password)) {

            if ($app->updateUserFcmToken($user_id, $fcm_token)) {

                if ($access_token = $app->getAccessToken($user_id)) {

                    return $restapi->response(200, [
                        'user_id' => $user_id,
                        'access_token' => $access_token
                    ]);
                }

            }

            return $restapi->response(500);
        }

    }

    return $restapi->response(401);
});



/*................................................................................................................................
 *
 * Get user details
 *
 * URL => /users/{id}
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
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('GET', '/users/[i:user_id]', function ($user_id) use ($app, $restapi) {

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if ($user = $app->getUserDetails($user_id)) {

            return $restapi->response(200, [
                'user_id' => $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'country_code' => $user['country_code'],
                'created_at' => $user['created_at']
            ]);
        }

        return $restapi->response(500);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * User register
 *
 * URL => /users
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
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('POST', '/users', function () use ($app, $restapi) {

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

            if ($access_token = $app->getAccessToken($user_id)) {

                return $restapi->response(201, [
                    'user_id' => $user_id,
                    'access_token' => $access_token
                ]);
            }

        }

        return $restapi->response(500);
    }

    return $restapi->response(409);
});



/*................................................................................................................................
 *
 * Update user profile
 *
 * URL => /users/{id}
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
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]', function ($user_id) use ($app, $restapi) {

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
 * URL => /users/{id}/password
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
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]/password', function ($user_id) use ($app, $restapi) {

    parse_str(file_get_contents('php://input'), $request);

    if (!$restapi->found($request, 'current_password', 'new_password')) {
        return $restapi->response(400);
    }

    $current_password = $request['current_password'];
    $new_password = $request['new_password'];

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if ($app->auth($user_id, $current_password)) {

            if ($app->updateUserPassword($user_id, $new_password)) {

                if($access_token = $app->getAccessToken($user_id)) {

                    return $restapi->response(200, [
                        'access_token' => $access_token
                    ]);
                }

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
 * URL => /users/{id}/fcm_token
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
 *
 * Unit Test => Successs
 * ...............................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]/fcm_token', function ($user_id) use ($app, $restapi) {

    parse_str(file_get_contents('php://input'), $request);

    if (!$restapi->found($request, 'fcm_token')) {
        return $restapi->response(400);
    }

    $fcm_token = $request['fcm_token'];

    $basic_token = $restapi->getBasicToken();

    if ($app->verifyAccessToken($user_id, $basic_token)) {

        if ($app->updateUserFcmToken($user_id, $fcm_token)) {

            if ($access_token = $app->getAccessToken($user_id)) {

                return $restapi->response(200, [
                    'access_token' => $access_token
                ]);
            }

            return $restapi->response(500);
        }

        return $restapi->response(500);
    }

    return $restapi->response(403);
});



/*................................................................................................................................
 *
 * Disable user account
 *
 * URL => /users/{id}
 * Method => DELETE
 * Authorization => Basic
 *
 * Required parameters => -
 *
 * Return
 * => 204 when update success
 * => 403 when unauthorized
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('DELETE', '/users/[i:user_id]', function ($user_id) use ($app, $restapi) {

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
 * URL => /pets/dogs/{id}
 * Method => GET
 * Authorization => -
 *
 * Required parameters => -
 *
 * Return
 * => 200: {
 *   dog_id => integer
 *   breed => string
 *   gender => char(M/F)
 *   age_month => integer
 *   images => [
 *     name => string
 *   ]
 *   description => string
 *   country_code => string
 *   user => {
 *     user_id => integer
 *     name => string
 *   }
 *   contact => {
 *     name => string
 *     phone => string
 *     latitude => double
 *     longitude => double
 *   }
 *   comments => [
 *     dog_comment_id => integer
 *     user_id
 *   ]
 *   views => integer
 *   day_left => integer
 *   updated_at => string
 *   created_at => string
 * }
 * => 404 when dog not found
 * => 500 when server error
 *
 * Unit Test => Pending
 * ...............................................................................................................................
 */

$router->route('GET', '/pets/dogs/[i:dog_id]', function ($dog_id) use ($app, $restapi) {

    $dog = $app->getDog($dog_id);

    if ($dog) {

        if ($app->updateDogIncrementViews($dog_id)) {

            return $restapi->response(200, [
                'dog_id' => $dog['dog_id'],
                'breed' => $dog['breed'],
                'gender' => $dog['gender'],
                'age_month' => $dog['age_month'],
                'images' => $dog['images'],
                'description' => $dog['description'],
                'country_code' => $dog['country_code'],
                'user' => [
                    'user_id' => $dog['user_id'],
                    'name' => $dog['user_name']
                ],
                'contact' => [
                    'name' => $dog['contact_name'],
                    'phone' => $dog['contact_phone'],
                    'latitude' => $dog['contact_latitude'],
                    'longitude' => $dog['contact_longitude']
                ],
                'comments' => $dog['comments'],
                'views' => $dog['views'],
                'day_left' => $dog['day_left'],
                'updated_at' => $dog['updated_at'],
                'created_at' => $dog['created_at']
            ]);
        }

        return $restapi->response(500);
    }

    return $restapi->response(404);
});



$router->run();