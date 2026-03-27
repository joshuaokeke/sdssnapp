<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreUpcomingEventRequest;
use App\Http\Requests\UpdateUpcomingEventRequest;
use App\Http\Resources\UpcomingEventResource;
use App\Models\Assets;
use App\Models\UpcomingEvent;
use Illuminate\Http\Request;

class UpcomingEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $upcomingEvents = UpcomingEvent::with('banner')->latest()->paginate();
        if ($upcomingEvents->isEmpty()) {
            return ApiResponse::error([], 'No upcoming events found', 404);
        }
        $data = UpcomingEventResource::collection($upcomingEvents);
        return ApiResponse::success($data, 'successful');
    }

    /**
     * Public - Display a listing of all upcoming events (upcoming active events).
     */
    public function upcoming()
    {
        $upcomingEvents = UpcomingEvent::with('banner')
            ->where('status', 'published')
            ->latest()
            ->paginate();
        if ($upcomingEvents->isEmpty()) {
            return ApiResponse::error([], 'No upcoming events found', 404);
        }
        $data = UpcomingEventResource::collection($upcomingEvents);
        return ApiResponse::success($data, 'successful');
    }

    /**
     * Public - Display a listing of all recent events (past inactive events).
     */
    public function recent()
    {
        $upcomingEvents = UpcomingEvent::with('banner')
            ->where('status', 'recent')
            ->orWhere('start_date', '<=', now())
            ->latest()
            ->paginate();
        if ($upcomingEvents->isEmpty()) {
            return ApiResponse::error([], 'No recent events found', 404);
        }
        $data = UpcomingEventResource::collection($upcomingEvents);
        return ApiResponse::success($data, 'successful');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUpcomingEventRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()?->id ?? null;

        // upload banner to cloudinary server
        // Upload Asset if exists
        if ($request->hasFile('banner')) {

            $cloudinaryImage = $request->file('banner')->storeOnCloudinary('sdssn-app');
            $url = $cloudinaryImage->getSecurePath();
            $public_id = $cloudinaryImage->getPublicId();

            // dd($cloudinaryImage);

            $asset = Assets::create([
                'original_name' => 'partner image',
                'path' => 'image',
                'hosted_at' => 'cloudinary',
                'name' =>  $cloudinaryImage->getOriginalFileName(),
                'description' => 'assignment file upload',
                'url' => $url,
                'file_id' => $public_id,
                'type' => $cloudinaryImage->getFileType(),
                'size' => $cloudinaryImage->getSize(),
            ]);

            $data['banner_id'] = $asset->id;
        }

        $upcomingEvent = UpcomingEvent::create($data);
        $upcomingEvent->load(['banner']);

        return ApiResponse::success($upcomingEvent, 'Upcoming event created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UpcomingEvent $upcomingEvent)
    {
        $upcomingEvent->load('banner');
        return ApiResponse::success($upcomingEvent, 'Upcoming event retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUpcomingEventRequest $request, UpcomingEvent $upcomingEvent)
    {
        $data = $request->validated();

        if ($request->hasFile('banner')) {

            $cloudinaryImage = $request->file('banner')->storeOnCloudinary('sdssn-app');
            $url = $cloudinaryImage->getSecurePath();
            $public_id = $cloudinaryImage->getPublicId();


            $asset = Assets::create([
                'original_name' => 'partner image',
                'path' => 'image',
                'hosted_at' => 'cloudinary',
                'name' =>  $cloudinaryImage->getOriginalFileName(),
                'description' => 'assignment file upload',
                'url' => $url,
                'file_id' => $public_id,
                'type' => $cloudinaryImage->getFileType(),
                'size' => $cloudinaryImage->getSize(),
            ]);

            $data['banner_id'] = $asset->id;
        }

        $upcomingEvent->update($data);
        $upcomingEvent->load(['banner']);
        return ApiResponse::success($upcomingEvent, 'Upcoming event updated successfully');
    }


    /**
     * Update the banner image of the event.
     */
    public function updateBanner(Request $request, UpcomingEvent $upcomingEvent)
    {
        $request->validate([
            'banner' => ['required', 'image', 'max:5480'], // 2MB size
        ]);

        if ($request->hasFile('banner')) {

            $cloudinaryImage = $request->file('banner')->storeOnCloudinary('sdssn-app');
            $url = $cloudinaryImage->getSecurePath();
            $public_id = $cloudinaryImage->getPublicId();


            $asset = Assets::create([
                'original_name' => 'partner image',
                'path' => 'image',
                'hosted_at' => 'cloudinary',
                'name' =>  $cloudinaryImage->getOriginalFileName(),
                'description' => 'assignment file upload',
                'url' => $url,
                'file_id' => $public_id,
                'type' => $cloudinaryImage->getFileType(),
                'size' => $cloudinaryImage->getSize(),
            ]);

            $data['banner_id'] = $asset->id;
        }

        $upcomingEvent->update($data);
        $upcomingEvent->load(['banner']);
        return ApiResponse::success($upcomingEvent, 'Event banner updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UpcomingEvent $upcomingEvent)
    {
        $upcomingEvent->delete();
        return ApiResponse::success([], 'Upcoming event deleted successfully', 200);
    }
}



// {
//   "success": true,
//   "message": "Upcoming event created successfully",
//   "data": {
//     "title": "Deskto Event",
//     "description": "For all Desktop users",
//     "category": "General",
//     "start_time": "07:00",
//     "start_date": "02-12-2026",
//     "end_time": null,
//     "end_date": null,
//     "status": "true",
//     "contact_name": null,
//     "contact_phone_number": null,
//     "speakers": null,
//     "facilitators": null,
//     "created_by": 2,
//     "banner_id": 4,
//     "updated_at": "2026-03-08T16:10:35.000000Z",
//     "created_at": "2026-03-08T16:10:35.000000Z",
//     "id": 1,
//     "banner": {
//       "id": 4,
//       "name": "phpaeMWje",
//       "original_name": "partner image",
//       "type": "image",
//       "path": "image",
//       "file_id": "sdssn-app/tvqeljr4gkjghhwaqoa1",
//       "url": "https://res.cloudinary.com/dpjdupkot/image/upload/v1772986233/sdssn-app/tvqeljr4gkjghhwaqoa1.png",
//       "size": 71806,
//       "hosted_at": "cloudinary",
//       "active": 1,
//       "deleted_at": null,
//       "created_at": "2026-03-08T16:10:35.000000Z",
//       "updated_at": "2026-03-08T16:10:35.000000Z"
//     }
//   },
//   "metadata": null
// }