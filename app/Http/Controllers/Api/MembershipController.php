<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipRequest;
use App\Http\Requests\UpdateMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Api\Membership;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $memberships = Membership::with(['user']);
        if ($request->filled('search') && $request->input('search')) {
            $memberships = $memberships->whereAny([
                'user_id',
                'full_name',
                'certification_request_id',
                'issued_on',
                'expires_on',
                'serial_no',
                'qr_code',
            ],  'like', '%' . $request->input('search') . '%');
        }
        // Pending or Paid
        if ($request->filled('status') && $request->input('status')) {
            $memberships = $memberships->where('status', $request->input('status'));
        }
        // Pending or Generated
        if ($request->filled('certificate_status') && $request->input('certificate_status')) {
            $memberships = $memberships->where('certificate_status', $request->input('certificate_status'));
        }
        // Issued on - start date
        if ($request->filled('issued_on') && $request->input('issued_on')) {
            $memberships = $memberships->where('issued_on', $request->input('issued_on'));
        }
        // Expires on - end date
        if ($request->filled('expires_on') && $request->input('expires_on')) {
            $memberships = $memberships->where('expires_on', $request->input('expires_on'));
        }
        $memberships = $memberships->latest()->paginate();

        // Check if there are any memberships
        if ($memberships->isEmpty()) {
            return ApiResponse::error([], 'No memberships found', 404);
        }
        $data = MembershipResource::collection($memberships);
        // Return the memberships resource
        return ApiResponse::success($data, 'memberships retrieved successfully.', 200, $memberships);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMembershipRequest $request)
    {
        // 'user_id',
        // 'full_name',
        // 'certification_request_id',
        // 'issued_on',
        // 'expires_on',
        // 'serial_no',
        // 'qr_code',
        // Create a new membership
        // $membership = Membership::create($request->validated());

        // Return the created membership resource
        // return ApiResponse::success(new MembershipResource($membership), 'Membership created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Membership $membership)
    {
        // Check if the membership exists
        if (!$membership) {
            return ApiResponse::error([], 'Membership not found', 404);
        }
        // load the certification and management signature
        // $membership->load(['certificationRequest.certification.managementSignature.signature', 'certificationRequest.userSignature', 'certificationRequest.credential', 'user']);
        $membership->load([
            'certificationRequest.certification.managementSignature.signature',
            'certificationRequest.certification.secretarySignature.signature',
            'certificationRequest.credential',
            'user'
        ]);
        // Transform the membership into a resource
        $response = new MembershipResource($membership);
        // Return the membership resource
        return ApiResponse::success($response, 'Membership retrieved successfully.');
    }

    /**
     * Update and change the name on certificate.
     */
    public function update(UpdateMembershipRequest $request, Membership $membership)
    {
        $data = $request->validated();
        // Check if the membership exists
        if (!$membership) {
            return ApiResponse::error([], 'Membership not found', 404);
        }

        // Update the membership with validated data
        $membership->update($data);

        // Return the updated membership resource
        return ApiResponse::success(new MembershipResource($membership), 'Membership updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Membership $membership)
    {
        // Check if the membership exists
        if (!$membership) {
            return ApiResponse::error([], 'Membership not found', 404);
        }

        // Delete the membership
        $membership->delete();

        // Return success response
        return ApiResponse::success([], 'Membership deleted successfully.', 204);
    }


    /**
     * [admin] Search all membership certificate.
     */
    public function searchMemberships(Request $request)
    {

        $data = $request->validate([
            'search' => ['required', 'string']
        ]);

        if (!$request->input('search')) {
            return ApiResponse::error([], 'Membership not found', 404);
        }

        $memberships = Membership::whereAny([
            'user_id',
            'full_name',
            'certification_request_id',
            'issued_on',
            'expires_on',
            'serial_no',
            'qr_code',
        ],  'like', '%' . $data['search'] . '%')
            // ->with(['certificationRequest.certification.managementSignature.signature', 'certificationRequest.userSignature', 'certificationRequest.credential', 'user'])
            ->with([
                'certificationRequest.certification.managementSignature.signature',
                'certificationRequest.certification.secretarySignature.signature',
                'certificationRequest.credential',
                'user'
            ])
            ->latest()
            ->paginate();

        // Check if there are any memberships
        if ($memberships->isEmpty()) {
            return ApiResponse::error([], 'No memberships found', 404);
        }

        $response = MembershipResource::collection($memberships);
        // Return the created membership resource
        return ApiResponse::success($response, 'successfully.', 200, $memberships);
    }


    /**
     * [User] Display all membership.
     */
    public function myMemberships()
    {
        $user = request()->user();
        $memberships = Membership::where('user_id', $user->id)
            ->latest()->paginate();

        // Check if there are any memberships
        if ($memberships->isEmpty()) {
            return ApiResponse::error([], 'No memberships found', 404);
        }
        $data = MembershipResource::collection($memberships);
        // Return the memberships resource
        return ApiResponse::success($data, 'memberships retrieved successfully.');
    }


    /**
     * [User] Display the specified resource.
     */
    public function showMembership(Membership $membership)
    {
        // Check if the membership exists
        if (!$membership || $membership->user_id !== request()->user()->id) {
            return ApiResponse::error([], 'Membership not found', 404);
        }

        // If not paid for, return error
        if ($membership->status !== 'paid') {
            return ApiResponse::error([], 'Membership is invalid or pending.', 400);
        }
        // load the certification and management signature
        // 'user', 'userSignature', 'credential', 'certification', 'membership'
        // $membership->load(['certificationRequest.certification.managementSignature.signature', 'certificationRequest.userSignature', 'certificationRequest.credential', 'user']);
        $membership->load([
            'certificationRequest.certification.managementSignature.signature',
            'certificationRequest.certification.secretarySignature.signature',
            'certificationRequest.credential',
            'user'
        ]);

        // Transform the membership into a resource
        $response = new MembershipResource($membership);
        // Return the membership resource
        return ApiResponse::success($response, 'Membership retrieved successfully.');
    }


    /**
     * [Public] verify membership certificate 'SDSSN68448AB8CA236'.
     * @param "serial_no": "SDSSN68448AB8CA236",
     */
    public function verifyMembership(Membership $membership)
    {
        //   "serial_no": "SDSSN68448AB8CA236",
        $membership;
        // Check if the membership exists
        if (!$membership) {
            return ApiResponse::error([], 'Membership not found', 404);
        }
        // verify the membership status
        if ($membership->status !== 'paid') {
            return ApiResponse::error([], 'Membership is invalid or pending.', 400);
        }
        // load the certification and management signature
        // 'user', 'userSignature', 'credential', 'certification', 'membership'
        // $membership->load(['certificationRequest.certification.managementSignature.signature', 'certificationRequest.userSignature', 'certificationRequest.credential', 'user']);
        $membership->load([
            'certificationRequest.certification.managementSignature.signature',
            'certificationRequest.certification.secretarySignature.signature',
            'certificationRequest.credential',
            'user'
        ]);

        // Transform the membership into a resource
        $response = new MembershipResource($membership);
        // Return the membership resource
        return ApiResponse::success($response, 'Membership retrieved successfully.');
    }
}
