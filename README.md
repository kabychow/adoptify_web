# Adoptify API Documentation


## User Authentication
    POST /auth
    Required Parameters: email, password, fcm_token
    Authorization: -
    Unit Test: Passed
    
    Success Response: 200 (OK)
        {
            user_id => int
            access_token => int
        }
    
    Error Response:
        400 (Bad Request) => Invalid request parameters
        401 (Unauthorized) => Incorrect username or password
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## User Register
    POST /users
    Required Parameters: name, gender, email, password, country_code, fcm_token
    Authorization: -
    Unit Test: Passed

    Success Response: 201 (Created)
        {
            user_id => int
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
    Authorization: Basic
    Unit Test: Passed
    
    Success Response: 200 (OK)
        {
            user_id => int
            name => string
            gender => string (M/F)
            email => string (email)
            country_code => string (country short codes)
            created_at => string (timestamp)
        }
    
    Error Response:
        403 (Forbidden) => Invalid access token
        500 (Internal Server Error) => Unexpected error occurred


## User Update Details
    PUT /users/{id}
    Required Parameters: name, gender, email, country_code
    Authorization: Basic
    Unit Test: Passed

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        409 (Conflict) => Email already used by another account
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## User Update Password
    PUT /users/{id}/password
    Required Parameters: current_password, new_password
    Authorization: Basic
    Unit Test: Passed
    
    Success Response: 200 (OK)
        {
            access_token => string
        }
    
    Error Response:
        400 (Bad Request) => Invalid request parameters
        401 (Unauthorized) => Incorrect password
        403 (Forbidden) => Invalid access token
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        
        
## User Update FCM Token
    PUT /users/{id}/fcm_token
    Required Parameters: fcm_token
    Authorization: Basic
    Unit Test: Passed
        
    Success Response: 200 (OK)
        {
            access_token => string
        }
        
    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
    
    
## User Disable Account
    DELETE /users/{id}
    Required Parameters: -
    Authorization: Basic
    Unit Test: Passed
    
    Success Response: 204 (No Content)
    
    Error Response:
        403 (Forbidden) => Invalid access token
        500 (Internal Server Error) => Unexpected error occurred


## Dog Get All Nearby By Country
    GET /pets/dogs
    Required Parameters: country_code, latitude, longitude
    Authorization: -
    Unit Test: -

    Success Response: 200 (OK)
        [
            dog_id => integer
            thumbnail => string
            area_level_1 => string
            area_level_2 => string
            view_count => integer
            day_left => integer
        ]

    Error Response:
        400 (Bad Request) => Invalid request parameters
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Dog Publish
    POST /pets/dogs
    Required Parameters: user_id, breed, gender, birth_year, birth_month, description, contact_name, contact_phone, contact_place_id, images[]
    Authorization: Basic
    Unit Test: Passed

    Success Response: 201 (Created)
        {
            dog_id => integer
        }

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        412 (Precondition Failed) => Google Place API returns foreign country
        415 (Unsupported Media Type) => Invalid image format or corrupted images found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        503 (Service Unavailable) => Google Places API does not return a proper response


## Dog Get Details
    GET /pets/dogs/{id}
    Required Parameters: -
    Authorization: -
    Unit Test: Passed

    Success Response: 200 (OK)
        {
            dog_id => integer
            user => {
                user_id => integer
                name => string
            }
            breed => string
            gender => string (M/F)
            age_month => integer
            images => string[]
            description => string
            country_code => string (country short codes)
            contact => {
                name => string
                phone => string
                latitude => double
                longitude => double
                area_level_1 => string
                area_level_2 => string
            }
            view_count => integer
            day_left => integer
            thumbnail => string
            created_at => string (timestamp)
        }

    Error Response:
        404 (Not Found) => Dog does not found
        500 (Internal Server Error) => Unexpected error occurred


## Dog Update Details
    PUT /pets/dogs/{id}
    Required Parameters: breed, gender, birth_year, birth_month, description, contact_name, contact_phone
    Authorization: Basic
    Unit Test: Passed

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Dog Upload Images
    POST /pets/dogs/{id}/images
    Required Parameters: images[]
    Authorization: Basic
    Unit Test: Passed

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        415 (Unsupported Media Type) => Invalid image format or corrupted images found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Dog Update Contact Place
    PUT /pets/dogs/{id}/contact_place
    Required Parameters: contact_place_id
    Authorization: Basic
    Unit Test: Passed

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
        500 (Internal Server Error) => Unexpected error occurred
