<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api', ['except' => ['getAllServices', 'getSingleService','getFeaturedServices']]);
    }
    public function index(Request $request)
    {
        $services = Service::with('files')->get();
        return response()->json($services);
    }



    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                // 'name' => 'required|json',
                // 'slug' => 'required|json',
                // 'description' => 'required|json',
                // 'overview' => 'required|json',
                // 'seo_title' => 'nullable|json',
                // 'seo_keywords' => 'nullable|json',
                // 'seo_description' => 'nullable|json',
                // 'other_data' => 'nullable|json',
                // 'status' => 'required|boolean',
                // 'featured' => 'required|boolean',
                // 'robots' => 'nullable|string',
                // 'svg' => 'nullable|string',
                // 'icon_tag' => 'nullable|string',
                // 'related_services' => 'nullable|array',
                // 'icon' => 'nullable|array',
                // 'banner' => 'nullable|array'
            ]);

            // Initialize a new service instance
            $service = new Service();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                if ($request->filled($field)) {
                    $serviceField = json_decode($request->$field, true);

                    // Check if json_decode was successful
                    if (is_array($serviceField)) {
                        foreach ($serviceField as $locale => $value) {
                            $service->setTranslation($field, $locale, $value);
                        }
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid JSON format for ' . $field,
                        ], 422);
                    }
                }
            }

            // Assign non-translatable fields
            $service->status = $request->status;
            $service->featured = $request->featured;
            $service->partner = $request->partner;
            $service->robots = $request->robots;
            $service->others = $request->others;
            $service->related_services = $request->related_services;
            $service->related_blogs = $request->related_blogs;

            // Persist the service instance into the database
            $service->save();

            if ($request->has('icon')) {
                $service->files()->attach($request->icon, ['type' => 'icon']);
            }

            if ($request->has('banner')) {
                $service->files()->attach($request->banner, ['type' => 'banner']);
            }

            return response()->json($service);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Service $service)
    {
        $iconId = null;
        $bannerId = null;

        $iconUrl = '';
        $bannerUrl = '';


        // Loop through the files to find banner and thumb
        foreach ($service->files as $file) {
            if ($file->pivot->type == 'icon') {
                $iconId = $file->id;  // Store the banner ID
                $iconUrl = $file->file_url;
            }elseif($file->pivot->type == 'banner'){
                $bannerId = $file->id;  // Store the banner ID
                $bannerUrl = $file->file_url;
            }
        }

        // Prepare response data
        $responseData = $service->toArray();
        $responseData['icon_id'] = $iconId;
        $responseData['banner_id'] = $bannerId;

        $responseData['icon_url'] = $iconUrl;
        $responseData['banner_url'] = $bannerUrl;

        unset($responseData['files']);
        
        return response()->json($responseData);
    }

    public function update(Request $request, Service $service)
    {
        try {
            $validatedData = $request->validate([
                // 'name' => 'required|json',
                // 'slug' => 'required|json',
                // 'description' => 'sometimes|required|json',
                // 'overview' => 'sometimes|required|json',
                // 'seo_title' => 'nullable|json',
                // 'seo_keywords' => 'nullable|json',
                // 'seo_description' => 'nullable|json',
                // 'status' => 'required', // Assuming status is required
                // 'featured' => 'required', // Assuming featured is required
                // 'banner' => 'required', // Assuming banner file ID is required
                // 'thumb' => 'required', // Assuming thumb file ID is required
            ]);

            $translatableFields = [
                'name', 'slug', 'description', 'overview',
                'seo_title', 'seo_keywords', 'seo_description'
            ];

            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $serviceField = json_decode($request->$field, true);

                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }

                    foreach ($serviceField as $locale => $value) {
                        $service->setTranslation($field, $locale, $value);
                    }
                }
            }


            $service->status = $request->status;
            $service->others = $request->others;
            $service->featured = $request->featured;
            $service->partner = $request->partner;
            $service->related_services = $request->related_services;
            $service->related_blogs = $request->related_blogs;
            // $service->svg = $request->svg;
            // $service->icon_tag = $request->icon_tag;

            // $service->country_id = $request->country_id;
            $service->robots = $request->robots;


            $service->save();

            $service->files()->detach();

            $service->files()->attach($request->icon, ['type' => 'icon']);
            $service->files()->attach($request->banner, ['type' => 'banner']);


            // Retrieve banner and thumb URLs
            $service->load('files');
            // dd($blog->load('files'));// Reload the files relationship
            $iconId = null;
            $bannerId = null;

            $iconUrl = '';
            $bannerUrl = '';

            // Loop through the files to find banner and thumb
            foreach ($service->files as $file) {
                if ($file->pivot->type == 'icon') {
                    $iconId = $file->id;  // Store the banner ID
                    $iconUrl = $file->file_url;
                }elseif($file->pivot->type == 'banner'){
                    $bannerId = $file->id;  // Store the banner ID
                    $bannerUrl = $file->file_url;
                }
            }

            // Prepare response data
            $responseData = $service->toArray();
            $responseData['icon_id'] = $iconId;
            $responseData['banner_id'] = $bannerId;

            $responseData['icon_url'] = $iconUrl;
            $responseData['banner_url'] = $bannerUrl;

            unset($responseData['files']);


            return response()->json($responseData);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function softDelete($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        $service->delete(); // Soft delete the country

        return response()->json(['message' => 'Service soft deleted successfully'], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $service = Service::withTrashed()->find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        if ($service->trashed()) {
            $service->forceDelete(); // Permanently delete the country
            return response()->json(['message' => 'Service  permanently deleted'], Response::HTTP_OK);
        } else {
            return response()->json(['message' => 'Service is not soft deleted'], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getAllServices()
    {
        // app()->setLocale($lang);

        $services = Service::where('status', 1)->get()
            ->map(function ($val) {
                $icon = '';
                $banner = '';
                foreach ($val->files as $file) {
                    if ($file->pivot->type == 'icon') {
                        $icon = $file->file_url;
                    }elseif($file->pivot->type == 'banner'){
                        $banner = $file->file_url;
                    }
                }
                return [
                    'id' => $val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                    'description' => $val->description ?? [],
                    'alt' => $val->name,
                    'related_services' => $val->related_services ?? [],
                    
                    'icon' => $icon,
                    'banner' => $banner,
                    'icon_tag' => $val->icon_tag
                ];
            });

        return response()->json([
            'data' => $services
        ]);
    }

    public function getFeaturedServices()
    {
        // $lang = app()->getLocale();

        $services = Service::where('status', 1)->where('featured', 1)->get()
            ->map(function ($val) {
                $icon = '';
                foreach ($val->files as $file) {
                    if ($file->pivot->type == 'icon') {
                        $icon = $file->file_url;
                    }
                }
                return [
                    'id' => $val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                    'description' => $val->description ?? [],
                    'alt' => $val->name,
                    'svg' => $val->svg,
                    'icon' => $icon,
                    'icon_tag' => $val->icon_tag
                ];
            });

        return response()->json([
            'data' => $services
        ]);
    }

    public function getPartnerServices()
    {
        // $lang = app()->getLocale();

        $services = Service::where('status', 1)->where('partner', 1)->get()
            ->map(function ($val) {
                $icon = '';
                foreach ($val->files as $file) {
                    if ($file->pivot->type == 'icon') {
                        $icon = $file->file_url;
                    }
                }
                return [
                    'id' => $val->id,
                    'name' => $val->name ?? [],
                    'slug' => $val->slug ?? [],
                    'description' => $val->description ?? [],
                    'alt' => $val->name,
                    'svg' => $val->svg,
                    'icon' => $icon,
                    'icon_tag' => $val->icon_tag
                ];
            });

        return response()->json([
            'data' => $services
        ]);
    }


    public function getSingleService($id)
    {
        $lang = app()->getLocale();  // Locale is set for slug localization
    
        // Attempt to find the service by ID or slug with related blogs and files
        $value = Service::where('status', 1)
            ->where(function ($query) use ($id, $lang) {
                $query->where('id', $id)
                      ->orWhere("slug->$lang", $id);
            })
            ->with(['files'])
            ->firstOrFail();  // Use firstOrFail to automatically handle the "not found" case
    
        $icon = '';
        $banner = '';  // Initialize the banner variable
        foreach ($value->files as $file) {
            if ($file->pivot->type == 'icon') {
                $icon = $file->file_url;
            } else if ($file->pivot->type == 'banner') {  // Correctly fetching the banner URL
                $banner = $file->file_url;
            }
        }
        $data = [
            'id' => $value->id,
            'name' => $value->name,
            'slug' => $value->slug,
            'overview' => $value->overview,
            'icon' => $icon,
            'banner' => $banner,
            'alt' => $value->name,
            // 'svg' => $value->svg,
            // 'icon_tag' => $value->icon_tag,
            'related_services' => $value->related_services_list ?? [],
            'related_blogs' => $value->related_blogs_list ?? [],
            'others' => $value->processedOthers ?? [],
            'seo' => [
                'title' => $value->seo_title,
                'keywords' => $value->seo_keywords,
                'description' => $value->seo_description,
                'robots' => $value->robots,
                'facebook_title' => $value->seo_title,
                'facebook_description' => $value->seo_description,
                'twitter_title' => $value->seo_title,
                'twitter_description' => $value->seo_description,
                'twitter_image' => $banner,
                'facebook_image' => $banner,
            ],
        ];
    
        return response()->json([
            'data' => $data,
        ], 200);
    }
    
}
