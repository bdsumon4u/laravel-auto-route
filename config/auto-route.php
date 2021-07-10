<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Method(s) Mapping
    |--------------------------------------------------------------------------
    | Default HTTP methods for the methods of a controller.
    | You can map multiple HTTP methods by using an array.
    |
    | type: array
    */
    'methods' => [
        '__invoke' => ['GET', 'POST'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parameter Patterns
    |--------------------------------------------------------------------------
    | You can define more new patterns for the all parameters that
    | you'll use at methods of the Controllers. Parameters that do not match
    | any pattern will accept all values.
    |
    | Format: $variable => pattern
    | Example: 'id' => '(\d+)'
    */
    'patterns' => [
        'slug' => '([\w\-_]+)',
        'uuid' => '([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})',
        'date' => '([0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]))',
    ],

];
