<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'Date' => Illuminate\Support\Facades\Date::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'RateLimiter' => Illuminate\Support\Facades\RateLimiter::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        // 'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

    ],



    'all_columns' => [
        "bsid" => "number",
        "school_number" => "number",
        "bsid_school_number" => "number",
        "school_name" => "name",
        "school_type" => "type",
        "school_level" => "level",
        "grade_range" => "grade_range",
        "semester_type" => "semester_type",
        "language_of_instruction" => "language_of_instruction",
        "total_teachers" => "teachers_num",
        "total_oct_teachers" => "oct_teachers",
        "teachers_with_oct" => "oct_teachers",

        "enrolment_2020_2021" => "enrollment",
        "enrollment_2020_2021" => "enrollment",

        "enrolment_2021_2022" => "enrollment_21_22",
        "enrollment_2021_2022" => "enrollment_21_22",

        "enrolment" => "enrollment",
        "enrollment" => "enrollment",

        "student_enrolment" => "enrollment",
        "student_enrollment" => "enrollment",

        "student_enrollment_2020_2021" => "enrollment",
        "student_enrolment_2020_2021" => "enrollment",

        "student_enrollment_2021_2022" => "enrollment_21_22",
        "student_enrolment_2021_2022" => "enrollment_21_22",
        
        "student_enrollment_2022_2023" => "enrollment_22_23",
        "student_enrolment_2022_2023" => "enrollment_22_23",
        
        "student_enrolment_2023_2024" => "enrollment_23_24",
        "student_enrollment_2023_2024" => "enrollment_23_24",

        "student_enrolment_2024_2025" => "enrollment_24_25",
        "student_enrollment_2024_2025" => "enrollment_24_25",

        "student_enrolment_2025_2026" => "enrollment_25_26",
        "student_enrollment_2025_2026" => "enrollment_25_26",



        "ossd_continuous_intake" => "ossd_continuous_intake",
        "ossd_credits_offered" => "ossd_credits_offered",
        "date_of_last_noi_submission" => "noi_last_date_submission",
        "noi_status_description" => "noi_status_description",

        "date_open" => "open_date",
        "open_date" => "open_date",
        "opened_date" => "open_date",

        "date_closed" => "closed_date",
        "closed_date" => "closed_date",
        "close_date" => "closed_date",

        "date_revoked" => "revoked_date",
        "revoked_date" => "revoked_date",
        "revoke_date" => "revoked_date",

        "school_status" => "status",
        "status" => "status",
        "status_open" => "status_open",
        "status_closed" => "status_closed",
        "status_revoked" => "status_revoked",



        "corporation_name" => "corporation_name",
        "corporation_establish_date" => "corporation_establish_date",
        "corporation_contact_name" => "corporation_contact_name",
        "corporatiion_number" => "corporation_number",
        "corporation_number" => "corporation_number",

        "corporation_email" => "corporation_email",
        "corporation_email_address" => "corporation_email",


        "ownership_type" => "ownership_type",
        "cra_bn" => "cra_bn",
        "owner_cra_bn" => "cra_bn",
        "owner_email" => "owner_email",

        "diploma_2014_2015" => "diploma_2014_2015",
        "diploma_2015_2016" => "diploma_2015_2016",
        "diploma_2016_2017" => "diploma_2016_2017",
        "diploma_2017_2018" => "diploma_2017_2018",
        "diploma_2018_2019" => "diploma_2018_2019",
        "diploma_2019_2020" => "diploma_2019_2020",
        "diploma_2020_2021" => "diploma_2020_2021",
        "diploma_2021_2022" => "diploma_2021_2022",
        "diploma_2022_2023" => "diploma_2022_2023",
        "diploma_2023_2024" => "diploma_2023_2024",
        "diploma_2024_2025" => "diploma_2024_2025",
        "diploma_2025_2026" => "diploma_2025_2026",


        "po_box" => "po_box",
        "suite" => "suite",
        "street" => "street",
        "street_address" => "street",
        "city" => "city",
        "postal_code" => "postal_code",
        "province" => "province",
        "region" => "region",
        "telephone_number" => "telephone",
        "fax" => "fax",
        "school_website" => "website",
        "website" => "website",


        //principals

        "principal_name" => "principal_name",
        "principal_first_name" => "principal_name",
        "principal_last_name" => "principal_last_name",
        "principa_last_name" => "principal_last_name",

        "principal_qualification" => "principal_qualification",
        "principal_qulification" => "principal_qualification",

        "principal_qualification_other" => "principal_qualification_other",
        "principal_qulification_other" => "principal_qualification_other",

        "principal_start_date" => "principal_start_date",
        "assignment_start_date" => "principal_start_date",


        "principal_email_address" => "principal_email",



        //affiliations and associations

        "affiliation" => "affiliation",
        "affiliations" => "affiliation",
        "association" => "association_membership",
        "association_other" => "association_other",
        "associations" => "association_membership",
        "association_membership" => "association_membership",
        "condition_type" => "special_conditions_code",
        "email_contact" => "contact_email",
        "school_email_address" => "contact_email",
        "school_special_conditions_code" => "special_conditions_code",
        "program_type" => "program_type",
    ],

];
