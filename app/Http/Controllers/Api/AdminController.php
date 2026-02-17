<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRoleEnum;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Api\Certificate;
use App\Models\Api\CertificationRequest;
use App\Models\Api\Membership;
use App\Models\Api\MembershipPayment;
use App\Models\Api\Podcast;
use App\Models\Api\Project;
use App\Models\Assets;
use App\Models\Certification;
use App\Models\Gallery;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['users'] = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'moderators' => User::where('role', 'moderator')->count(),
            'users' => User::where('role', 'user')->count(),
            'verified' => User::where('email_verified_at')->count(),
            'pending' => User::whereNot('email_verified_at')->count(),
            'male' => User::where('gender', 'male')->count(),
            'female' => User::where('gender', 'female')->count(),
        ];

        $data['projects'] = [
            'total' => Project::count(),
            'comments' => Project::with(['comments'])->count(),
            'shares' => Project::sum('shares'),
            'likes' => Project::sum('likes'),
            'views' => Project::sum('views'),
            // 'map', 'discussion', 'link'
            'maps' => Project::where('category', 'map')->count(),
            'discussions' => Project::where('category', 'discussion')->count(),
            'drafts' => Project::where('category', 'link')->count(),
            // public, private, draft
            'public' => Project::where('status', 'public')->count(),
            'private' => Project::where('status', 'private')->count(),
            'draft' => Project::where('status', 'draft')->count(),
            'trash' => Project::onlyTrashed()->count(),
        ];

        $data['podcasts'] = [
            'total' => Podcast::count(),
            'comments' => Podcast::with(['comments'])->count(),
            'shares' => Podcast::sum('shares'),
            'likes' => Podcast::sum('likes'),
            'views' => Podcast::sum('views'),
            'videos' => Podcast::where('category', 'video')->count(),
            'audios' => Podcast::where('category', 'audio')->count(),
            'trash' => Podcast::onlyTrashed()->count(),
        ];

        $data['assets'] = [
            'total' => Assets::count(),
            'sizes' => Assets::sum('size'),
            'capacity' => 'KB',
        ];


        $data['resources'] = [
            'users' => [
                'total' => User::count(),
                'male' => User::where('gender', 'male')->count(),
                'female' => User::where('gender', 'female')->count(),
            ],
            'certificates' => [
                'total' => Certificate::all(),
                'courses' => Certificate::select('course', DB::raw('count(*) as total'))
                    ->groupBy('course')
                    ->get()
            ],
            'profession' => User::select('profession', DB::raw('count(*) as total'))
                ->groupBy('profession')
                ->get(),
            'membership_status' => User::select('membership_status', DB::raw('count(*) as total'))
                ->groupBy('membership_status')
                ->get(),
            'cities' => User::select('city', DB::raw('count(*) as total'))
                ->groupBy('city')
                ->get(),
            'states' => User::select('state', DB::raw('count(*) as total'))
                ->groupBy('state')
                ->get(),
            'countries' => User::select('country', DB::raw('count(*) as total'))
                ->groupBy('country')
                ->get(),
            'organizations' => User::select('organization', DB::raw('count(*) as total'))
                ->groupBy('organization')
                ->get(),
            'organization_categories' => User::select('organization_category', DB::raw('count(*) as total'))
                ->groupBy('organization_category')
                ->get(),
            'organization_roles' => User::select('organization_role', DB::raw('count(*) as total'))
                ->groupBy('organization_role')
                ->get(),
        ];

        $data['newsletters'] = [
            'total' => Newsletter::count(),
            'active' => Newsletter::where('active', 'yes')->count(),
        ];

        $data['galleries'] = [
            'total' => Gallery::count(),
        ];

        if (!$data) {
            return $this->sendError([], 'unable to load data', 500);
        }

        return $this->sendSuccess($data, 'resource loaded successfully', 200);
    }


    /**
     * Get all statistical resource
     */
    public function resources()
    {
        $data = [
            'users' => [
                'total' => User::count(),
                'male' => User::where('gender', 'male')->count(),
                'female' => User::where('gender', 'female')->count(),
            ],
            'certificates' => [
                'total' => Certificate::all(),
                'courses' => Certificate::select('course', DB::raw('count(*) as total'))
                    ->groupBy('course')
                    ->get()
            ],
            'profession' => User::select('profession', DB::raw('count(*) as total'))
                ->groupBy('profession')
                ->get(),
            'membership_status' => User::select('membership_status', DB::raw('count(*) as total'))
                ->groupBy('membership_status')
                ->get(),
            'cities' => User::select('city', DB::raw('count(*) as total'))
                ->groupBy('city')
                ->get(),
            'states' => User::select('state', DB::raw('count(*) as total'))
                ->groupBy('state')
                ->get(),
            'countries' => User::select('country', DB::raw('count(*) as total'))
                ->groupBy('country')
                ->get(),
            'organizations' => User::select('organization', DB::raw('count(*) as total'))
                ->groupBy('organization')
                ->get(),
            'organization_categories' => User::select('organization_category', DB::raw('count(*) as total'))
                ->groupBy('organization_category')
                ->get(),
            'organization_roles' => User::select('organization_role', DB::raw('count(*) as total'))
                ->groupBy('organization_role')
                ->get(),
        ];

        if (!$data) {
            return $this->sendError([], 'unable to load data', 500);
        }

        return $this->sendSuccess($data, 'resource loaded successfully', 200);
    }

    /**
     * Get all admin.
     */
    public function admin()
    {
        $admin = User::where('role', 'admin')->latest()->get();

        if (!$admin) {
            return $this->sendError([], 'unable to load admins', 404);
        }

        return $this->sendSuccess($admin, 'successful', 200);
    }

    /**
     * Display all users.
     */
    public function allUsers()
    {
        $users = User::latest()->get();
        $metadata = $this->getMetadata($users);

        if (!$users) {
            return $this->sendError([], 'unable to load users', 500);
        }

        return $this->sendSuccess($users, 'successful', 200, $metadata);
    }



    /**
     * Display and paginate users.
     */
    public function users(Request $request)
    {

        $users = User::query();
        if ($request->filled('search') && $request->has('search')) {
            $users->whereAny([
                'name',
                'email',
                'password',
                'first_name',
                'last_name',
                'other_name',
                'security_question',
                'answer',
                'phone_number',
                'gender',
                'dob',
                'address',
                'city',
                'state',
                'country',
                'membership_status',
                'role',             // ['user', 'moderator', 'admin', 'super-admin']
                'assigned_by',

                'email_verified',
                'email_verified_at',

                'profession',
                'organization',
                'organization_category',
                'organization_role',
                'organization_name',
                'asset_id',
                'qualification',
                'course'
            ], 'like', "%{$request?->search}%");
        }
        $users = $users->paginate();
        $metadata = $this->getMetadata($users);

        if (!$users) {
            return $this->sendError([], 'unable to load users', 404);
        }

        return $this->sendSuccess($users, 'successful', 200, $metadata);
    }

    /**
     * Display all location of registered users.
     */
    public function locations()
    {
        $locations = User::select('state', DB::raw('count(*) as total'))
            ->groupBy('state')
            ->get();

        // $locations = User::select('state')->groupBy('state')->get();

        $metadata = $this->getMetadata($locations);

        if (!$locations) {
            return $this->sendError([], 'unable to load locations', 500);
        }

        return $this->sendSuccess($locations, 'successful', 200, $metadata);
    }


    /**
     * Display statistical data for memberships.
     */
    public function memberships()
    {

        $data = [];

        $data['total_members'] = Membership::count();
        $data['active_members'] = Membership::where('expires_on', '>=', now())->count();
        $data['expired_members'] = Membership::where('expires_on', '<', now())->count();
        $data['pending_members'] = Membership::where('status', 'pending')->count();
        $data['certified_members'] = Membership::where('certificate_status', 'generated')->count();
        $data['certificates_processing'] = Membership::where('certificate_status', 'processing')->count();

        // $data['members'] = Membership::with(['certificationRequest.certification'])->get();
        // $data['certifications'] = Certification::withCount('memberships')->get();
        // $data['certifications'] = Certification::with('certificationRequestsMemberships')->get();
        $data['certifications'] = Certification::count();

        // certifications
        $data['membership_requests'] = CertificationRequest::count();

        $data['membership_types'] = Certification::select('type', DB::raw('count(*) as total'))
            ->selectRaw('(SELECT COUNT(*) FROM memberships INNER JOIN certification_requests ON certification_requests.id = memberships.certification_request_id WHERE certification_requests.certification_id IN (SELECT id FROM certifications c WHERE c.type = certifications.type AND c.deleted_at IS NULL) AND memberships.deleted_at IS NULL AND certification_requests.deleted_at IS NULL) as certification_requests_memberships_count')
            ->selectRaw('(SELECT COUNT(*) FROM certification_requests WHERE certification_requests.certification_id IN (SELECT id FROM certifications c WHERE c.type = certifications.type AND c.deleted_at IS NULL) AND certification_requests.deleted_at IS NULL) as certification_requests_count')
            ->whereNull('deleted_at')
            ->groupBy('type')
            ->get();

        $data['membership_status'] = User::select('membership_status', DB::raw('count(*) as total'))
            ->groupBy('membership_status')
            ->get();


        if (!$data) {
            return $this->sendError([], 'unable to load data', 500);
        }
        return $this->sendSuccess($data, 'successful', 200);
    }


    /**
     * Assign role to a user
     * Update user role [user, admin]
     * @param ['user', 'admin']
     */
    public function assignRole(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:user,admin'
        ]);


        $user = User::where('email', $request->email)->first();
        // I remove the package from the middleware
        // {
        //   "success": false,
        //   "message": "An error occurred. Please try again later.",
        //   "error": "Authorizable class `App\\Models\\User` must use Spatie\\Permission\\Traits\\HasRoles trait."
        // }

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found'], 201);
        }

        if (!$request->role) {
            return response()->json(['status' => false, 'message' => 'enter a role'], 201);
        }

        if (!in_array($request->role, ['user', 'moderator', 'admin', 'super-admin'])) {
            return response()->json(['status' => false, 'message' => 'Invalid role'], 201);
        }


        // Represent the user role
        if ($request->role == 'admin') {
            // $user->assignRole(UserRoleEnum::ADMIN->value);
            // Remove previous roles
            $user->syncRoles(UserRoleEnum::ADMIN->value);
            $user->role = UserRoleEnum::ADMIN->value;
        } else if ($request->role == 'moderator') {
            $user->syncRoles(UserRoleEnum::MODERATOR->value);
            $user->role = UserRoleEnum::MODERATOR->value;
        } else {
            $user->syncRoles(UserRoleEnum::USER->value);
            $user->role = UserRoleEnum::USER->value;
        }
        // $user->role = $request->role;
        $user->save();


        $message = $request->role . ' role assign to ' . $request->email;
        return response()->json([
            'status' => true,
            'message' => $message
        ], 201);
    }

    /**
     * Set up existing premium users based on successful membership payments.
     */
    public function setupPremiumUsers()
    {

        $premiumUser = [];
        $membershipPayment = MembershipPayment::where('status', 'successful')->get();
        foreach ($membershipPayment as $payment) {
            // Update the user membership status the paid membership name
            $payment->user->membership_status = 'premium';
            $payment->user->save();
            $premiumUser[] = $payment->user;
        }
        return ApiResponse::success($premiumUser, 'Premium users updated successfully');
    }

    /**
     * Get all users with free membership status.
     */
    public function freeMembershipUsers()
    {
        $user = User::where('membership_status', 'free')->paginate();
        $response = UserResource::collection($user);
        return ApiResponse::success($response, 'Free membership users retrieved successfully', 200, $user);
    }

    /**
     * Get all users with premium membership status.
     */
    public function premiumMembershipUsers()
    {
        $user = User::where('membership_status', 'premium')->paginate();
        $response = UserResource::collection($user);
        return ApiResponse::success($response, 'Premium membership users retrieved successfully', 200, $user);
    }

    /** 
     * Set up test premium users based on email address.
     * This method is for testing purposes and should be removed in production.
     * It updates the membership status of users with specific email address to 'premium'.
     * @param email
     */
    public function setupTestPremiumUsers(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'exists:users,email']
        ]);
        $user = User::where('email', $data['email'])->first();
        // Update the user membership status the paid membership name
        $user->membership_status = 'premium';
        $user->save();
        return ApiResponse::success($user, 'Premium membership setup successfully');
    }
}
