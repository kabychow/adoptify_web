# Adoptify API Documentation

## User Login
    POST /auth
    Required Parameters: email, password, fcm_token
    Authorization: -
    
    Success Response: 200 (OK)
    {
    user_id => int
    access_token => int
    }
    
    Error Response:
    400 (Bad Request) => Required parameters not found or blank
    401 (Unauthorized) => Incorrect username or password
    500 (Internal Server Error) => When unexpected error occurred
