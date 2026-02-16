<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\Paystack;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDonationPaymentRequest;
use App\Http\Requests\UpdateDonationPaymentRequest;
use App\Http\Resources\DonationPaymentResource;
use App\Models\Api\Donation;
use App\Models\Api\DonationPayment;
use Illuminate\Http\Request;

class DonationPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $donationPayments = DonationPayment::with(['donation']);
        // Search
        if ($request->filled('search') && $request->input('search')) {
            // Search through donation payments
            $donationPayments = $donationPayments->whereAny([
                'user_id',
                'donation_id',
                'payment_type', // [donation]
                'payment_method',
                'amount',
                'reference',
                'status',
                'data',
            ],  'like', '%' . $request->input('search') . '%');
            // Search through the donation
            $search = $request->input('search');
            $donationPayments = $donationPayments->whereHas('donation', function ($query) use ($search) {
                $query->whereAny([
                    'user_id',
                    'full_name',
                    'email',
                    'amount',
                    'reason_for_donation',
                    'note',
                    'status',
                    'created_by',
                    'updated_by',
                    'deleted_by',
                ],  'like', '%' . $search . '%');
            });
        }
        // Pending or Paid
        if ($request->filled('status') && $request->input('status')) {
            $donationPayments = $donationPayments->where('status', $request->input('status'));
        }
        // Created at
        if ($request->filled('created_at') && $request->input('created_at')) {
            $donationPayments = $donationPayments->where('created_at', $request->input('created_at'));
        }
        $donationPayments->latest()->paginate();

        // Check if there are any donation payments
        if ($donationPayments->isEmpty()) {
            return ApiResponse::error([], 'No donation payments found', 404);
        }
        $data = DonationPaymentResource::collection($donationPayments);
        // Return the donation payments resource
        return ApiResponse::success($data, 'Donation payments retrieved successfully.', 200, $donationPayments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDonationPaymentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DonationPayment $donationPayment)
    {
        $donationPayment->load(['user', 'donation']);
        $response = new DonationPaymentResource($donationPayment);
        return ApiResponse::success($response, 'Donation payment retrieved successfully.');
        //
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DonationPayment $donationPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDonationPaymentRequest $request, DonationPayment $donationPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DonationPayment $donationPayment)
    {
        //
    }

    // verify transaction
    /**
     * Verify payment donated domain.com?reference=oo5ihug1qm
     * @param reference=oo5ihug1qm
     */
    public function verifyDonation(Request $request)
    {
        // http://localhost:3000/?trxref=oo5ihug1qm&reference=oo5ihug1qm
        // http://127.0.0.1:8000/events/8?trxref=soq9s7fxmf&reference=soq9s7fxmf

        // validate request
        $request->validate([
            'trxref' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        $redirectUrl = config('app.frontend_url') . '/payment/error';

        try {
            // Verify payment transaction
            if ($request?->filled('trxref') || $request?->filled('reference')) {
                $reference = $request?->reference ?? $request?->trxref;
                $PSP = Paystack::verify($reference);
                info('paystack validation response: ', $PSP);
                // return $PSP;
                $message = $PSP['message'];
                info('verify payment message: ', [$message]);
                if ($PSP['success']) {
                    $donationPayment = DonationPayment::where('reference', $reference)->first();
                    if ($donationPayment) {
                        $donationPayment->status = 'successful';
                        $donationPayment->save();

                        // redirect to success page
                        $redirectUrl = config('app.frontend_url') . '/payment/success?trxref=' . $donationPayment->reference;

                        // If payment type for donation is new
                        if ($donationPayment->payment_type == 'donation' && $donationPayment->donation_id) {
                            // update donation
                            $donation = Donation::where('id', $donationPayment->donation_id)->first();
                            // update donation
                            $donation->status = 'paid';
                            $donation->save();
                        }

                        // return $donationPayment;
                        return redirect($redirectUrl);
                    } else {
                        info('Donation Payment Transaction not found: ', [$message]);
                        // Redirect to error page
                        $redirectUrl = config('app.frontend_url') . '/payment/error?trxref=' . $reference;
                    }
                } else {
                    // return $message;
                    // log error and return error response
                    info('Donation Payment Transaction verification failed: ', [$message]);
                    // Redirect to error page
                    $redirectUrl = config('app.frontend_url') . '/payment/error?trxref=' . $reference;
                }
            }

            // return $redirectUrl;
            // return response
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            // log error and return error response
            // return response()->json(['message' => 'Transaction verification failed', 'error' => $e->getMessage()], 500);
            info('Donation transaction verification failed: ', [$e->getMessage()]);
            return redirect($redirectUrl);
        }
    }
}
