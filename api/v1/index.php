<?php

$con = new mysqli('localhost', 'root', '\'', 'adoptify');
if ($con->errno) {  http_response_code(500); die(); }

require __DIR__ . '/../../include/RestAPI.php';
$app = new RestAPI();

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

$router->route('POST', '/auth', function () use ($app, $con) {

    $request = $_POST;

    if (!$app->found($request, 'email', 'password', 'fcm_token')) {
        return $app->response(400);
    }

    $email = $request['email'];
    $password = $request['password'];
    $fcm_token = $request['fcm_token'];

    $query = "
      SELECT user_id, password, MD5(CONCAT(user_id, password)) AS access_token
      FROM users
      WHERE email = ? AND is_disabled = 0
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {

        $query = "
          UPDATE users
          SET fcm_token = ?
          WHERE user_id = ? AND is_disabled = 0
        ";
        $stmt = $con->prepare($query);
        $stmt->bind_param('si', $fcm_token, $user['user_id']);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            return $app->response(200, [
                'user_id' => $user['user_id'],
                'access_token' => $user['access_token']
            ]);
        }

        return $app->response(500);
    }

    return $app->response(401);
});



/*................................................................................................................................
 *
 * Get user details
 *
 * URL => /users/{id}
 * Method => GET
 * Authorization => Bearer
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
 * => 404 when user not found
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('GET', '/users/[i:user_id]', function ($user_id) use ($app, $con) {

    $query = "
      SELECT user_id, name, email, country_code, created_at, MD5(CONCAT(user_id, password)) AS access_token
      FROM users
      WHERE user_id = ? AND is_disabled = 0
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {

        $bearer_token = $app->getBearerToken();

        if ($bearer_token === $user['access_token']) {
            return $app->response(200, [
                'user_id' => $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'country_code' => $user['country_code'],
                'created_at' => $user['created_at']
            ]);
        }

        return $app->response(403);
    }

    return $app->response(404);
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
 * => 422 when input validation failed
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('POST', '/users', function () use ($app, $con) {

    $request = $_POST;

    if (!$app->found($request, 'name', 'email', 'password', 'country_code', 'fcm_token')) {
        return $app->response(400);
    }

    $name = trim($request['name']);
    $email = $request['email'];
    $password = password_hash($request['password'], PASSWORD_DEFAULT);
    $country_code = strtoupper($request['country_code']);
    $fcm_token = $request['fcm_token'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $app->response(422);
    }

    $query = "
      SELECT COUNT(*) AS count
      FROM users
      WHERE email = ?
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    if ($count < 1) {

        $query = "
          INSERT INTO users (name, email, password, country_code, fcm_token)
          VALUES (?, ?, ?, ?, ?)
        ";
        $stmt = $con->prepare($query);
        $stmt->bind_param('sssss', $name, $email, $password, $country_code, $fcm_token);
        $stmt->execute();
        $stmt->store_result();
        $affected_rows = $stmt->affected_rows;
        $insert_id = $stmt->insert_id;
        $stmt->close();

        if ($affected_rows > 0) {

            $access_token = md5($insert_id . $password);

            return $app->response(201, [
                'user_id' => $insert_id,
                'access_token' => $access_token
            ]);
        }

        return $app->response(500);
    }

    return $app->response(409);
});



/*................................................................................................................................
 *
 * Update user profile
 *
 * URL => /users/{id}
 * Method => PUT
 * Authorization => Bearer
 *
 * Required parameters => name, email, country_code
 *
 * Return
 * => 204 when update success
 * => 403 when unauthorized
 * => 404 when when user not found
 * => 409 when email exists
 * => 422 when input validation failed
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]', function ($user_id) use ($app, $con) {

    $query = "
      SELECT email, MD5(CONCAT(user_id, password)) AS access_token
      FROM users
      WHERE user_id = ? AND is_disabled = 0
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {

        $bearer_token = $app->getBearerToken();

        if ($bearer_token === $user['access_token']) {

            parse_str(file_get_contents('php://input'), $request);

            if (!$app->found($request, 'name', 'email', 'country_code')) {
                return $app->response(400);
            }

            $name = trim($request['name']);
            $email = $request['email'];
            $country_code = strtoupper($request['country_code']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $app->response(422);
            }

            $query = "
              SELECT COUNT(*) AS count
              FROM users
              WHERE email = ? AND email != ?
            ";
            $stmt = $con->prepare($query);
            $stmt->bind_param('ss', $email, $user['email']);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];
            $stmt->close();

            if ($count < 1) {

                $query = "
                  UPDATE users
                  SET name = ?, email = ?, country_code = ?
                  WHERE user_id = ? AND is_disabled = 0
                ";
                $stmt = $con->prepare($query);
                $stmt->bind_param('sssi', $name, $email, $country_code, $user_id);
                $result = $stmt->execute();
                $stmt->close();

                if ($result) {
                    return $app->response(204);
                }

                return $app->response(500);
            }

            return $app->response(409);
        }

        return $app->response(403);
    }

    return $app->response(404);
});



/*................................................................................................................................
 *
 * Update user password
 *
 * URL => /users/{id}/password
 * Method => PUT
 * Authorization => Bearer
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
 * => 404 when user not found
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]/password', function ($user_id) use ($app, $con) {

    $query = "
      SELECT password, MD5(CONCAT(user_id, password)) AS access_token
      FROM users
      WHERE user_id = ? AND is_disabled = 0
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {

        $bearer_token = $app->getBearerToken();

        if ($bearer_token === $user['access_token']) {

            parse_str(file_get_contents('php://input'), $request);

            if (!$app->found($request, 'current_password', 'new_password')) {
                return $app->response(400);
            }

            $current_password = $request['current_password'];
            $new_password = password_hash($request['new_password'], PASSWORD_DEFAULT);

            if (password_verify($current_password, $user['password'])) {

                $query = "
                  UPDATE users
                  SET password = ?
                  WHERE user_id = ? AND is_disabled = 0
                ";
                $stmt = $con->prepare($query);
                $stmt->bind_param('si', $new_password, $user_id);
                $result = $stmt->execute();
                $stmt->close();

                if ($result) {

                    $access_token = md5($user_id . $new_password);

                    return $app->response(200, [
                        'access_token' => $access_token
                    ]);
                }

                return $app->response(500);
            }

            return $app->response(401);
        }

        return $app->response(403);
    }

    return $app->response(404);
});



/*................................................................................................................................
 *
 * Update user fcm token
 *
 * URL => /users/{id}/fcm_token
 * Method => PUT
 * Authorization => Bearer
 *
 * Required parameters => fcm_token
 *
 * Return
 * => 204 when update success
 * => 400 when required parameters is blank
 * => 403 when unauthorized
 * => 404 when user not found
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('PUT', '/users/[i:user_id]/fcm_token', function ($user_id) use ($app, $con) {

    $query = "
      SELECT MD5(CONCAT(user_id, password)) AS access_token
      FROM users
      WHERE user_id = ? AND is_disabled = 0
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {

        $bearer_token = $app->getBearerToken();

        if ($bearer_token === $user['access_token']) {

            parse_str(file_get_contents('php://input'), $request);

            if (!$app->found($request, 'fcm_token')) {
                return $app->response(400);
            }

            $fcm_token = $request['fcm_token'];

            $query = "
              UPDATE users
              SET fcm_token = ?
              WHERE user_id = ?
            ";
            $stmt = $con->prepare($query);
            $stmt->bind_param('si', $fcm_token, $user_id);
            $result = $stmt->execute();
            $stmt->close();

            if ($result) {
                return $app->response(204);
            }

            return $app->response(500);
        }

        return $app->response(403);
    }

    return $app->response(404);
});



/*................................................................................................................................
 *
 * Disable user account
 *
 * URL => /users/{id}
 * Method => DELETE
 * Authorization => Bearer
 *
 * Required parameters => -
 *
 * Return
 * => 204 when update success
 * => 403 when unauthorized
 * => 404 when user not found
 * => 500 when server error
 *
 * Unit Test => Success
 * ...............................................................................................................................
 */

$router->route('DELETE', '/users/[i:user_id]', function ($user_id) use ($app, $con) {

    $bearer_token = $app->getBearerToken();

    $query = "
      SELECT MD5(CONCAT(user_id, password)) AS access_token
      FROM users
      WHERE user_id = ? AND is_disabled = 0
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {

        if ($bearer_token === $user['access_token']) {

            $query = "
              DELETE FROM users
              WHERE user_id = ? AND is_disabled = 0
            ";
            $stmt = $con->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->store_result();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if ($affected_rows > 0) {
                return $app->response(204);
            }

            return $app->response(500);
        }

        return $app->response(403);
    }

    return $app->response(404);
});



$router->run();