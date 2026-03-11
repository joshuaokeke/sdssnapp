<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CertificationRequestController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\DonationPaymentController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\MembershipPaymentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\PodcastCommentController;
use App\Http\Controllers\Api\PodcastController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\UserProfile;
use App\Http\Controllers\Api\UserSocial;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\UpcomingEventController;
use App\Models\Api\CertificationRequest;
use App\Models\Api\Membership;
use App\Models\Certification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;










// Ping the server
Route::get('/', function () {

    $data = [
        'success' => true,
        'message' => 'Welcome to the API!',
        'data' => [
            'Laravel' => app()->version(),
            'project' => 'SDSSN: Spatial and Data Science Society of Nigeria.',
            'developers' => [
                'backend' => [
                    'name' => 'Abdulsalam Abdulrahman',
                    'email' => 'abdulsalamamtech@gmail.com',
                ],
                'frontend' => [
                    'name' => 'Mayowa Sanusi',
                    'email' => 'mayowa.u.sunusi@gmail.com',
                ]
            ],
            'documentation_url' => url('/') . '/docs/api',
            'contact_email' => 'abdulsalamamtech@gmail.com',
            'contact_linkedin' => 'https://linkedin.com/abdulsalamamtech',
        ]
    ];
    return $data;
});


// Registration security questions route
Route::get('/security-questions', function (Request $request) {
    $questions = [
        "What is the name of your first pet?",
        "In what city or town was your first job?",
        "What is your mother's maiden name?",
        "What high school did you attend?",
        "What is the name of the street you grew up on?",
        "What is the name of your favorite childhood teacher?",
        "What is your oldest sibling's middle name?",
        "In what city or town was your mother born?",
        "What is the name of the first company you worked for?",
        "What is your favorite food?",
    ];

    $message = 'Security questions retrieved successfully';

    return response()->json(['success' => true, 'message' => $message, 'data' => $questions], 200);
});


// Get available roles
Route::get('/available-roles', function (Request $request) {
    $roles = ['user', 'moderator', 'admin', 'super-admin'];

    $message = 'Available roles retrieved successfully';

    return response()->json(['success' => true, 'message' => $message, 'data' => $roles], 200);
});


// Check if user is logged in
// Get the authenticated user
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {

    $message = "Authenticated";
    $statusCode = 200;
    $user =  $request->user();
    return response()->json([
        'success' => false,
        'message' => $message,
        'data' => $user
    ], $statusCode);
});





// AUTHENTICATION ROUTES
// Authentication routes
// Register route
Route::post('register', [AuthController::class, 'register'])
    ->middleware('guest');
// Login route
Route::post('login', [AuthController::class, 'login'])
    ->middleware('guest');
// Logout route
Route::post('logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');



// User Podcasts [admin only]
Route::get('/admin/podcasts', [PodcastController::class, 'personal'])
    ->middleware(['auth:sanctum', 'verified']);



// PROFILE ROUTES
// User profile, social media, projects, podcasts, certificates, information
Route::group(['prefix' => 'profile', 'middleware' => ['auth:sanctum', 'verified']], function () {
    // User Profile
    Route::get('/', [UserProfile::class, 'show']);
    Route::put('/', [UserProfile::class, 'update']);

    // User Socials
    Route::get('/socials', [UserSocial::class, 'show']);
    Route::put('/socials', [UserSocial::class, 'update']);

    // User Projects
    Route::get('/projects', [ProjectController::class, 'personal']);

    // User private projects
    Route::get('/projects/private', [ProjectController::class, 'private']);
    // User public projects
    Route::get('/projects/public', [ProjectController::class, 'public']);


    // User certificates
    Route::get('/certificates', [PodcastController::class, 'personal']);

    // User certificates approved [admin only]
    // Route::get('/certificates/approved', [PodcastController::class, 'approved']);

    // For user profile picture
    // {"picture": "image profile"}
    Route::put('/picture', [UserProfile::class, 'updatePicture']);
    Route::post('/picture/update', [UserProfile::class, 'updatePicture']);


    // User Certification request
    Route::get('certification-requests', [CertificationRequestController::class, 'myCertificationRequests']);

    // Membership routes
    Route::get('memberships', [MembershipController::class, 'myMemberships']);
    Route::get('memberships/{membership}', [MembershipController::class, 'showMembership']);

    // Membership payment routes
    Route::get('my-membership-payments', [MembershipPaymentController::class, 'myMembershipPayments']);
});



// AUTH ROUTES RESOURCES
Route::group(['middleware' => ['auth:sanctum', 'verified']], function () {

    Route::middleware(['premium_user'])->group(function () {
        // PROJECTS
        // Project routes
        Route::apiResource('projects', ProjectController::class)
            ->only(['store', 'destroy']);
        // Update Project
        Route::post('projects/{project}/update', [ProjectController::class, 'update']);
        Route::put('projects/{project}', [ProjectController::class, 'update']);
        // Project comments
        Route::apiResource('projects.comments', CommentController::class)
            ->only(['store', 'update', 'destroy']);
    });

    // Like project
    Route::put('projects/{project}/likes', [ProjectController::class, 'like']);
    // Share project
    Route::put('projects/{project}/shares', [ProjectController::class, 'share']);

    // Project like and share using POST
    Route::post('projects/{project}/likes', [ProjectController::class, 'like']);
    Route::post('projects/{project}/shares', [ProjectController::class, 'share']);

    // Certification request routes
    Route::apiResource('certification-requests', CertificationRequestController::class)
        ->only(['store']);

    // Membership payment routes
    Route::post('membership-payments', [MembershipPaymentController::class, 'store']);
});

// Verify membership certificate
Route::get('verify-memberships/{membership:serial_no}', [MembershipController::class, 'verifyMembership']);

// Certifications routes
Route::get('certifications', [CertificationController::class, 'available']);
Route::get('certifications/{certification}', [CertificationController::class, 'showAvailable']);


// Quest Messages route
Route::post('/quest-messages', [MessageController::class, 'store']);


// User profile information
Route::get('/profile/{user:name}', [UserProfile::class, 'profile']);


// Subscribe to newsletter
Route::apiResource('/newsletters', NewsletterController::class)
    ->only(['store']);


// LOCATIONS ROUTES
// Get user locations
Route::get('/locations', [AdminController::class, 'locations']);
// Get minimal resource
Route::get('/resources', [AdminController::class, 'resources']);
// Get application statistics
Route::get('/statistics', [AdminController::class, 'index']);

// Gallery resources
// Route::apiResource('galleries', GalleryController::class)
//     ->only(['index', 'show']);
Route::get('galleries', [GalleryController::class, 'index']);
Route::get('galleries/{gallery}', [GalleryController::class, 'show']);


// Partners routes
Route::apiResource('partners', PartnerController::class)
    ->only(['index', 'show']);
Route::get('all-partners', [PartnerController::class, 'index']);
// Route::get('partners/{partner}/show', [PartnerController::class, 'show']);.






// GENERAL PUBLIC ROUTES
// Projects routes
Route::apiResource('projects', ProjectController::class)
    ->only(['index', 'show']);
// Projects comments
Route::apiResource('projects.comments', CommentController::class)
    ->only(['index', 'show']);

// Search for projects
Route::get('/projects/search/query', [ProjectController::class, 'search']);

Route::get('/projects/title/{project:slug}', [ProjectController::class, 'show']);


// GENERAL PUBLIC ROUTES
// Podcasts routes
// Route::apiResource('podcasts', PodcastController::class)
//     ->only(['index', 'show']);
Route::get('/podcasts', [PodcastController::class, 'index']);
Route::get('/podcasts/{podcast}', [PodcastController::class, 'show']);

// Podcasts comments
Route::apiResource('podcasts.comments', PodcastCommentController::class)
    ->only(['index', 'show']);

// Search for podcast
Route::get('/podcasts/search/query', [PodcastController::class, 'search']);

Route::get('/podcasts/title/{podcast:slug}', [PodcastController::class, 'show']);
Route::get('/podcasts/category/video', [PodcastController::class, 'video']);
Route::get('/podcasts/category/audio', [PodcastController::class, 'audio']);



// Verify transaction
Route::get('transactions/verify', [MembershipPaymentController::class, 'verifyTransaction'])
    ->name('transactions.verify');


// Donation
Route::post('/donations', [DonationController::class, 'store']);


// Verify transaction
Route::get('donations/verify', [DonationPaymentController::class, 'verifyDonation'])
    ->name('donations.verify');

// Membership statistical routes
Route::get('/memberships', [AdminController::class, 'memberships']);


// Upcoming events
Route::get('/upcoming-events', [UpcomingEventController::class, 'upcoming']);
Route::get('/recent-events', [UpcomingEventController::class, 'recent']);



// Admin Routes
require  __DIR__ . "/api/admin.php";

// Api auth routes
require __DIR__ . '/api-auth.php';


// For terminal, artisan and special commands
// require __DIR__ . '/terminal.php';


// V1 Auth Routes
Route::prefix('v1')->group(function () {
    // Authentication
    require __DIR__ . '/v1/auth.php';
});

// Cloudinary Test Routes
// Route::apiResource('test-cloudinary', \App\Http\Controllers\Api\TestCloudinaryController::class);

// Route::post('cloudinary', [TestCloudinaryController::class,'store']);
// Route::apiResource('cloudinary', TestCloudinaryController::class);

// // Old Test Routes
// Route::get('test-cloudinary', [\App\Http\Controllers\Api\TestCloudinary::class, 'index']);
// Route::post('test-cloudinary', [TestCloudinaryController::class,'store']);
// Route::delete('test-cloudinary/{asset}', [\App\Http\Controllers\Api\TestCloudinary::class, 'destroy']);

// require __DIR__.'/api/paystack-test.php';
// Paystack Payment Routes
// Route::post('/paystack/payment', [Paystack::class, 'payment'])->name('paystack.payment');
// Route::get('/paystack/callback', [Paystack::class, 'callback'])->name('paystack.callback');
// Route::get('/paystack/webhook', [Paystack::class, 'webhook'])->name('paystack.webhook');
// Route::get('/paystack/verify/{reference}', [Paystack::class,'verifyPayment']);
