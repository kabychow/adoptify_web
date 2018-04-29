# Adoptify API Documentation


## User Authentication
    POST /auth
    Required Parameters: email, password, fcm_token
    Optional Parameters: -
    Authorization: -
    
    Success Response: 200 (OK)
        {
            id => integer
            access_token => string
        }
    
    Error Response:
        400 (Bad Request) => Invalid request parameters
        401 (Unauthorized) => Incorrect password
        404 (Not Found) => User not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## User Register
    POST /users
    Required Parameters: name, gender, email, password, country_code, fcm_token
    Optional Parameters: -
    Authorization: -

    Success Response: 201 (Created)
        {
            id => integer
            access_token => string
        }

    Error Response:
        400 (Bad Request) => Invalid request parameters
        409 (Conflict) => Email already used by another account
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## User Get Details
    GET /users/{id}
    Required Parameters: -
    Optional Parameters: -
    Authorization: Basic
    
    Success Response: 200 (OK)
        {
            id => integer
            name => string
            gender => string (M/F)
            email => string (email)
            country_code => string (country short codes)
            created_at => string (timestamp)
        }
    
    Error Response:
        403 (Forbidden) => Invalid access token
        404 (Not Found) => User not found
        500 (Internal Server Error) => Unexpected error occurred


## User Get Published Pets
    GET /users/{id}/pets
    Required Parameters: -
    Optional Parameters: -
    Authorization: Basic

    Success Response: 200 (OK)
        [
            id => integer
            type => string (C/D)
            thumbnail => string
            country_code => string (country short codes)
            contact_area_level_1 => string
            contact_area_level_2 => string
            view_count => integer
            created_at => string (timestamp)
            day_left => integer
        ]

    Error Response:
        403 (Forbidden) => Invalid access token
        404 (Not Found) => User not found
        500 (Internal Server Error) => Unexpected error occurred


## User Update Details
    PUT /users/{id}
    Required Parameters: name, gender, email, country_code
    Optional Parameters: -
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => User not found
        409 (Conflict) => Email already used by another account
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## User Update Password
    PUT /users/{id}/password
    Required Parameters: current_password, new_password
    Optional Parameters: -
    Authorization: Basic
    
    Success Response: 200 (OK)
        {
            access_token => string
        }
    
    Error Response:
        400 (Bad Request) => Invalid request parameters
        401 (Unauthorized) => Incorrect password
        403 (Forbidden) => Invalid access token
        404 (Not Found) => User not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        
        
## User Update FCM Token
    PUT /users/{id}/fcm_token
    Required Parameters: fcm_token
    Optional Parameters: -
    Authorization: Basic
        
    Success Response: 200 (OK)
        {
            access_token => string
        }
        
    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => User not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
    
    
## User Disable Account
    DELETE /users/{id}
    Required Parameters: -
    Optional Parameters: -
    Authorization: Basic
    
    Success Response: 204 (No Content)
    
    Error Response:
        403 (Forbidden) => Invalid access token
        404 (Not Found) => User not found
        500 (Internal Server Error) => Unexpected error occurred


## User Recover Password
    POST /recover-password
    Required Parameters: email
    Optional Parameters: -
    Authorization: -

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        404 (Not Found) => User not found
        409 (Conflict) => Daily retry limit reached
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Pet Get All
    GET /pets
    Required Parameters: type, country_code, latitude, longitude
    Optional Parameters: -
    Authorization: -

    Success Response: 200 (OK)
        [
            id => integer
            type => string (C/D)
            thumbnail => string
            contact_area_level_1 => string
            contact_area_level_2 => string
            view_count => integer
            created_at => string (timestamp)
            day_left => integer
        ]

    Error Response:
        400 (Bad Request) => Invalid request parameters
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Pet Publish
    POST /pets
    Required Parameters: user_id, type, breed, gender, birth_year, birth_month, description, contact_name, contact_phone, contact_place_id, images[]
    Optional Parameters: -
    Authorization: Basic

    Success Response: 201 (Created)
        {
            id => integer
        }

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        412 (Precondition Failed) => Google Place API returns foreign country
        415 (Unsupported Media Type) => Invalid image format or corrupted images found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        503 (Service Unavailable) => Google Places API does not return a proper response


## Pet Get Details
    GET /pets/{id}
    Required Parameters: -
    Optional Parameters: -
    Authorization: -

    Success Response: 200 (OK)
        {
            id => integer
            type => string (C/D)
            user_id => integer
            user_name => string
            breed => string
            gender => string (M/F)
            images => string[]
            age_month => integer
            description => string
            country_code => string (country short codes)
            contact_name => string
            contact_phone => string
            contact_latitude => double
            contact_longitude => double
            contact_area_level_1 => string
            contact_area_level_2 => string
            view_count => integer
            created_at => string (timestamp)
            day_left => integer
        }

    Error Response:
        404 (Not Found) => Pet does not found
        500 (Internal Server Error) => Unexpected error occurred


## Pet Update Details
    PUT /pets/{id}
    Required Parameters: type, breed, gender, birth_year, birth_month, description, contact_name, contact_phone
    Optional Parameters: -
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Pet Upload Images
    POST /pets/{id}/images
    Required Parameters: images[]
    Optional Parameters: -
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        415 (Unsupported Media Type) => Invalid image format or corrupted images found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Pet Update Contact Place
    PUT /pets/{id}/contact_place
    Required Parameters: contact_place_id
    Optional Parameters: -
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        412 (Precondition Failed) => Google Place API returns foreign country
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        503 (Service Unavailable) => Google Places API does not return a proper response


## Dog Deletion
    DELETE /pets/dogs/{id}
    Required Parameters: -
    Authorization: Basic
    Unit Test: Passed

    Success Response: 204 (No Content)

    Error Response:
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        500 (Internal Server Error) => Unexpected error occurred


## Dog Report
    POST /pets/dogs/{id}/report
    Required Parameters: user_id
    Authorization: Basic
    Unit Test: Passed

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
