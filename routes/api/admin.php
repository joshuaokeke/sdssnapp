<?php

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Api\Admin\ConversationController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CertificationRequestController;
use App\Http\Controllers\Api\DonationPaymentController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\MembershipPaymentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\PodcastCommentController;
use App\Http\Controllers\Api\PodcastController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\CertificationController;

use App\Http\Controllers\CredentialController;
use App\Http\Controllers\ManagementSignatureController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OurPartnerController;
use App\Http\Controllers\UpcomingEventController;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;







// ADMIN ROUTES
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth:sanctum', 'verified', 'role:admin|super-admin'])
    ->group(
        function () {

            // Admin dashboard route
            Route::apiResource('/', AdminController::class)
                ->only(['index']);
            // Users routes
            Route::get('/users', [AdminController::class, 'users']);
            // Get all users routes
            Route::get('/users/all', [AdminController::class, 'allUsers']);

            // Get all projects
            Route::get('/projects/all/', [ProjectController::class, 'allProjects']);
            // Approve project
            Route::put('/projects/{project}/approve', [ProjectController::class, 'approve']);
            // Reject project
            Route::put('/projects/{project}/reject', [ProjectController::class, 'reject']);
            // Approved projects
            Route::get('/projects/approved', [ProjectController::class, 'approved']);
            // Force delete project
            Route::delete('/projects/{project}/delete-from-trash', [ProjectController::class, 'forceDelete']);
            // Get deleted trash projects
            Route::get('/projects/trash', [ProjectController::class, 'trash']);

            // Certificate routes [admin]
            Route::apiResource('/certificates', CertificateController::class);
            // Membership status routes [admin]
            Route::get('/memberships', [AdminController::class, 'memberships']);


            // Newsletters
            Route::apiResource('/newsletters', NewsletterController::class)
                ->only(['index', 'show']);


            // Gallery resources
            Route::apiResource('galleries', GalleryController::class);
            Route::post('galleries/{gallery}/update', [GalleryController::class, 'update']);

            // Assign role to user
            // Route::put('/update-role', [AdminController::class, 'assignRole']);

            Route::post('/assign-role', [AdminController::class, 'assignRole']);



            // Partners routes
            // Route::apiResource('partners', PartnerController::class);
            Route::get('partners', [PartnerController::class, 'index']);
            Route::post('partners', [PartnerController::class, 'store']);
            Route::post('/partners-store', [PartnerController::class, 'store']);
            Route::get('partners/{partner}', [PartnerController::class, 'show']);
            Route::put('partners/{partner}', [PartnerController::class, 'update']);
            Route::post('partners/{partner}/update', [PartnerController::class, 'update']);
            Route::delete('partners/{partner}', [PartnerController::class, 'destroy']);
            Route::post('partners/{partner}/delete', [PartnerController::class, 'destroy']);


            // Our Partners Route
            Route::apiResource('our-partners', OurPartnerController::class);
            Route::get('our-partners', [OurPartnerController::class, 'index']);
            Route::post('our-partners', [OurPartnerController::class, 'store']);
            Route::post('/our-partners-store', [OurPartnerController::class, 'store']);
            Route::get('our-partners/{ourPartner}', [OurPartnerController::class, 'show']);
            Route::put('our-partners/{ourPartner}', [OurPartnerController::class, 'update']);
            Route::post('our-partners/{ourPartner}/update', [OurPartnerController::class, 'update']);
            Route::delete('our-partners/{ourPartner}', [OurPartnerController::class, 'destroy']);
            Route::post('our-partners/{ourPartner}/delete', [OurPartnerController::class, 'destroy']);

            // Our Team routes


            // Quest Messages routes
            Route::apiResource('messages', MessageController::class);


            // PODCAST
            // Project routes
            // Podcast routes [admin]
            Route::apiResource('/podcasts', PodcastController::class);
            // Route::apiResource('podcasts', PodcastController::class);
            // Update podcast
            Route::post('podcasts/{podcast}/update', [PodcastController::class, 'update']);
            Route::put('podcasts/{podcast}', [PodcastController::class, 'update']);
            // Podcast comments
            Route::apiResource('podcasts.comments', PodcastCommentController::class)
                ->only(['store', 'update', 'destroy']);

            // Like podcast
            Route::put('podcasts/{podcast}/likes', [PodcastController::class, 'like']);
            // Share podcast
            Route::put('podcasts/{podcast}/shares', [PodcastController::class, 'share']);


            // Podcast like and share using POST
            Route::post('podcasts/{podcast}/likes', [PodcastController::class, 'like']);
            Route::post('podcasts/{podcast}/shares', [PodcastController::class, 'share']);


            // management signature api  resource routes
            // put method is not supported in apiResource, so we use post method for update
            Route::apiResource('management-signature', ManagementSignatureController::class)
                ->except(['update']);
            Route::post('management-signature/{managementSignature}/update', [ManagementSignatureController::class, 'update']);

            // Certification resource routes
            Route::apiResource('certifications', CertificationController::class);


            // Certification request routes
            Route::apiResource('certification-requests', CertificationRequestController::class)
                ->only(['index', 'show', 'update', 'destroy']);

            // credential routes
            // List all credentials
            Route::get('credentials', [CredentialController::class, 'index']);
            // Get credential by id
            Route::get('credentials/{credential}', [CredentialController::class, 'show']);

            // Membership routes
            Route::apiResource('memberships', MembershipController::class)
                ->only(['index', 'show', 'update']);
            // Search for membership
            Route::get('search-memberships', [MembershipController::class, 'searchMemberships']);

            Route::apiResource('membership-payments', MembershipPaymentController::class)
                ->only(['index', 'show']);

            Route::apiResource('conversations', ConversationController::class)->except(['update']);

            // Donation payments
            Route::apiResource('donation-payments', DonationPaymentController::class)
                ->only(['index', 'show']);

            // get free membership users
            Route::get('free-membership-users', [AdminController::class, 'freeMembershipUsers']);
            // get premium membership users
            Route::get('premium-membership-users', [AdminController::class, 'premiumMembershipUsers']);
            // Set up existing premium users
            Route::get('/setup-premium-users', [AdminController::class, 'setupPremiumUsers']);
            // Set up premium user by email
            Route::post('/setup-test-premium-user', [AdminController::class, 'setupTestPremiumUsers']);


            // Upcoming events
            Route::apiResource('/upcoming-events', UpcomingEventController::class);
            Route::post('/upcoming-events/{upcomingEvent}/banner', [UpcomingEventController::class, 'updateBanner']);
        }
    );







// Assign role to first admin
Route::get('assign-admin', function () {
    $user = User::where('email', 'abdulsalamamtech@gmail.com')->first();
    // $user->assignRole(UserRoleEnum::ADMIN->value);

    // Remove previous roles
    $user->syncRoles(UserRoleEnum::ADMIN->value);

    $user->role = UserRoleEnum::ADMIN->value;
    $user->save();

    return $user;
});

// Route::get('assign-admin-all', function(){
//     $assign = [];
//     $users = User::where('role', 'admin')->get();
//     foreach ($users as $user) {
//         # code...
//         $assign[] = $user;

//         $user->assignRole(UserRoleEnum::ADMIN->value);
//         $user->role = UserRoleEnum::ADMIN->value;
//         $user->save();
//     }

//     return $assign;
// });

Route::get('assign-admin-test', function () {
    return [
        'test' => 'testing of admin permission successful!'
    ];
})->middleware(['role:admin']);

// Route::get('assign-admin-get', function(){
//     return [
//         'test' => 'testing of admin permission successful!',
//         'roles' => Role::all()
//     ];
// });


// management signature routes
// Route::get('management-signature', [\App\Http\Controllers\ManagementSignatureController::class, 'show'])
//     // ->middleware(['auth:sanctum', 'verified', 'role:admin|super-admin'])
//     ->name('management-signature.show');
// Route::post('management-signature', [\App\Http\Controllers\ManagementSignatureController::class, 'store'])
// // ->middleware(['auth:sanctum', 'verified', 'role:admin|super-admin'])
//     ->name('management-signature.store');
// Route::put('management-signature/{managementSignature}', [\App\Http\Controllers\ManagementSignatureController::class, 'update'])
//     // ->middleware(['auth:sanctum', 'verified', 'role:admin|super-admin'])
//     ->name('management-signature.update');
// Route::delete('management-signature/{managementSignature}', [\App\Http\Controllers\ManagementSignatureController::class, 'destroy'])
//     // ->middleware(['auth:sanctum', 'verified', 'role:admin|super-admin'])
//     ->name('management-signature.destroy');
