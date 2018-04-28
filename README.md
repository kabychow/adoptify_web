# Adoptify API Documentation


## User Authentication
    POST /auth
    Required Parameters: email, password, fcm_token
    Authorization: -
    
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
    
    Success Response: 200 (OK)
        {
            user_id => int
            gender => string (M/F)
            name => string
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
    
    Success Response: 204 (No Content)
    
    Error Response:
        403 (Forbidden) => Invalid access token
        500 (Internal Server Error) => Unexpected error occurred


## Dog Get All Nearby By Country
    GET /pets/dogs
    Required Parameters: country_code, latitude, longitude
    Authorization: -

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
    Required Parameters: user_id, breed, gender, birth_year, birth_month, description, contact_name, contact_phone, contact_place_id
    Authorization: Basic

    Success Response: 201 (Created)
        {
            dog_id => integer
        }

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        415 (Unsupported Media Type) => Invalid image format or corrupted images found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        503 (Service Unavailable) => Google Places API does not return a proper response


## Dog Get Details
    GET /pets/dogs/{id}
    Required Parameters: -
    Authorization: -

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
            created_at => string (timestamp)
        }

    Error Response:
        404 (Not Found) => Dog does not found
        500 (Internal Server Error) => Unexpected error occurred


## Dog Update Details
    PUT /pets/dogs/{id}
    Required Parameters: breed, gender, birth_year, birth_month, description, contact_name, contact_phone
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Dog Update Contact Place
    PUT /pets/dogs/{id}/contact_place
    Required Parameters: contact_place_id
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Dog Deletion
    DELETE /pets/dogs/{id}
    Required Parameters: -
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        500 (Internal Server Error) => Unexpected error occurred


## Dog Report
    POST /pets/dogs/{id}/report
    Required Parameters: user_id
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Dog does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Cat Get All Nearby By Country
    GET /pets/cats/{country_code}
    Required Parameters: latitude, longitude
    Authorization: -

    Success Response: 200 (OK)
        [
            cat_id => integer
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


## Cat Publish
    POST /pets/cats
    Required Parameters: user_id, breed, gender, birth_year, birth_month, description, contact_name, contact_phone, contact_place_id
    Authorization: Basic

    Success Response: 201 (Created)
        {
            cat_id => integer
        }

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        415 (Unsupported Media Type) => Invalid image format or corrupted images found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
        503 (Service Unavailable) => Google Places API does not return a proper response


## Cat Get Details
    GET /pets/cats/{id}
    Required Parameters: -
    Authorization: -

    Success Response: 200 (OK)
        {
            cat_id => integer
            user => {
                user_id => integer
                name => string
            }
            breed => string
            gender => string (M/F)
            age_month => integer
            images => string[]
            description => string
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
            created_at => string (timestamp)
        }

    Error Response:
        404 (Not Found) => Cat does not found
        500 (Internal Server Error) => Unexpected error occurred


## Cat Update Details
    PUT /pets/cats/{id}
    Required Parameters: breed, gender, birth_year, birth_month, description, contact_name, contact_phone
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Cat does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Cat Update Contact Place
    PUT /pets/cats/{id}/contact_place
    Required Parameters: contact_place_id
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Cat does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred


## Cat Deletion
    DELETE /pets/cats/{id}
    Required Parameters: -
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Cat does not found
        500 (Internal Server Error) => Unexpected error occurred


## Cat Report
    POST /pets/cats/{id}/report
    Required Parameters: user_id
    Authorization: Basic

    Success Response: 204 (No Content)

    Error Response:
        400 (Bad Request) => Invalid request parameters
        403 (Forbidden) => Invalid access token
        404 (Not Found) => Cat does not found
        422 (Unprocessable Entity) => Input validation failed
        500 (Internal Server Error) => Unexpected error occurred
