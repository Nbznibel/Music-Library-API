# Music-Library-API

Overview of the Architecture
Your app follows a layered, modular structure for APIs, keeping responsibilities separated and making it scalable and maintainable.
 API Route (api.php)
Entry point for all API requests.


Maps a URL endpoint to a controller method.


Example:


GET /songs → SongController@fnc_list
POST /songs → SongController@fnc_create


Controller (e.g., SongController)
Handles the incoming HTTP request.


Inherits from ApiCore, which provides common API functionality:


Authentication & authorization checks.


Response formatting (JSON with success/error).


Timing, logging, and error handling.


Pagination helpers.


Passes request data to the Service layer for business logic.



Controller Core (ApiCore)
Base controller that all controllers extend.


Provides:


Standardized methods for CRUD (fnc_create, fnc_update, fnc_delete, fnc_list etc.).


Validation helpers (fc_ajax_validate).


Pagination & filtering helpers.


Scoped service access (fs_scope).


The controller mainly routes requests and responses, while ApiCore handles common pre/post-processing logic.

Service Layer (e.g., SongService)
Implements business logic and interacts with the Model (database).


Responsibilities:


CRUD operations (fs_create, fs_update, fs_delete, fs_list).


Validation rules for create/update (fs_data_create, fs_data_update).


Search, pagination, and filtering.


Audit logs and soft delete handling.


Returns standardized responses via helpers (fsv_success, fsv_error).


The controller calls the service, and the service does the actual data handling.

Service Core (ServiceCore)
Base class for all services.


Provides:


Standardized success/error response handling.


Transaction management (ftxn_start, ftxn_commit).


Audit & search helpers.


Language translation helpers (fsv_lang).


Ensures all services behave consistently.



Models (e.g., Song)
Represent database tables.


Interact with Eloquent ORM for:


Querying data.


Creating, updating, deleting records.


Defining relationships between tables.


Only holds data structure, no business logic.



Helpers / Common Functions
Common utility functions used by both controllers and services:


Response formatting (commonApiResponse).


Timestamps (fnow).


Auditing functions (f_audit_create, f_audit_update).


Search indexing (f_search_create).


Keeps code DRY and reusable.



Translation / Language Files
Contain dynamic success/error messages:


entity.php → Maps entity keys to human-readable names ('Song' => 'Song').


services.php → Contains templates for messages (create_ok, update_ko, etc.).


Service layer uses these files via fsv_lang to generate consistent messages.


Supports multi-language apps in the future.



 Flow Summary
API Request → hits api.php.


Controller → receives the request (e.g., SongController).


Controller Core (ApiCore) → validates, checks auth, prepares data.


Service Layer (SongService) → handles CRUD, validation, business logic.


Service Core (ServiceCore) → wraps service responses, handles transactions, audits, and message formatting.


Model (Song) → interacts with the database.


Helpers / Translations → format response and messages.


Controller / ApiCore → returns JSON response to client.



 Example Api Calls
Get all : 
http://127.0.0.1:8000/api/songs?paginated=true&rows=10&page=1&order_by=title&order_type=asc&search=shape&searchable=title,artist
Response status : 201
Body : 
{
    "success": true,
    "message": "dt_success",
    "data": [
        {
            "id": 2,
            "title": "Shape of You",
            "artist": "Ed Sheeran",
            "duration": 240,
            "release_date": "2017-01-06T00:00:00.000000Z",
            "created_at": "2026-02-05T12:46:21.000000Z",
            "updated_at": "2026-02-05T12:46:21.000000Z",
            "locker": null
        }
    ],
    "currentPage": 1,
    "lastPage": 1,
    "perPage": 10,
    "total": 10
}

Error : 
{
    "success": false,
    "message": "The title field is required.",
    "errors": {
        "title": [
            "The title field is required."
        ]
    }
}

