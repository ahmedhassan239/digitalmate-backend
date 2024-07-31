<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{

    public function index()
    {
        $projects = Project::with('files')->get();
        return response()->json($projects);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                // 'name' => 'required|string|max:255',
                // 'slug' => 'required|string|max:255|unique:projects',
                // 'overview' => 'nullable|string',
                // 'description' => 'nullable|string',
                // 'client' => 'nullable|string|max:255',
                // 'date' => 'nullable|date',
                // 'category' => 'nullable|string|max:255',
                // 'country' => 'nullable|string|max:255',
                // 'service_id' => 'required',
                // 'other_services' => 'nullable|array',
                // 'featured' => 'nullable',
                // 'status' => 'nullable',
                // 'seo_title' => 'nullable',
                // 'seo_keywords' => 'nullable',
                // 'seo_description' => 'nullable',
                // 'robots' => 'nullable',
                // 'thumb' => 'nullable|integer|exists:files,id',
                // 'gallery' => 'nullable|array',
                // 'gallery.*' => 'nullable|integer|exists:files,id',
            ]);

            // Initialize a new Project instance
            $project = new Project();

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description', 'overview', 'client', 'category', 'country',
                'seo_title', 'seo_keywords', 'seo_description', 'other_services'
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $projectField = json_decode($request->$field, true);

                    if (is_array($projectField)) {
                        foreach ($projectField as $locale => $value) {
                            $project->setTranslation($field, $locale, $value);
                        }
                    }
                }
            }

            // Non-translatable fields
            $project->service_id = $request->service_id;
            $project->date = $request->date;
            $project->featured = $request->featured;
            $project->status = $request->status;
            $project->robots = $request->robots;
            $project->save();

            // Handle thumb file attachment
            if ($request->has('thumb')) {
                $project->files()->attach($request->thumb, ['type' => 'thumb']);
            }

            // Handle gallery file attachments
            if ($request->has('gallery') && is_array($request->gallery)) {
                $galleryFiles = $request->gallery;
                foreach ($galleryFiles as $fileId) {
                    $project->files()->attach($fileId, ['type' => 'gallery']);
                }
            }

            return response()->json($project, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            // General error handling (optional)
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        // Initialize IDs and URLs for thumb and gallery
        $thumbId = null;
        $thumbUrl = '';
        $gallery = [];

        // Loop through the files to find thumb and gallery
        foreach ($project->files as $file) {
            if ($file->pivot->type == 'thumb') {
                $thumbId = $file->id;  // Store the thumb ID
                $thumbUrl = $file->file_url;
            } elseif ($file->pivot->type == 'gallery') {
                $gallery[] = [
                    'id' => $file->id,
                    'url' => $file->file_url,
                ];
            }
        }

        // Prepare response data
        $responseData = $project->toArray();
        $responseData['thumb_id'] = $thumbId;
        $responseData['thumb_url'] = $thumbUrl;
        $responseData['gallery'] = $gallery;
        unset($responseData['files']);

        return response()->json($responseData);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                // 'name' => 'required|string|max:255',
                // 'slug' => 'required|string|max:255|unique:projects,slug,' . $project->id,
                // 'overview' => 'nullable|string',
                // 'description' => 'nullable|string',
                // 'client' => 'nullable|string|max:255',
                // 'date' => 'nullable|date',
                // 'category' => 'nullable|string|max:255',
                // 'country' => 'nullable|string|max:255',
                // 'service_id' => 'required',
                // 'other_services' => 'nullable|array',
                // 'featured' => 'nullable',
                // 'status' => 'nullable',
                // 'seo_title' => 'nullable',
                // 'seo_keywords' => 'nullable',
                // 'seo_description' => 'nullable',
                // 'robots' => 'nullable',
                // 'thumb' => 'nullable|integer|exists:files,id',
                // 'gallery' => 'nullable|array',
                // 'gallery.*' => 'nullable|integer|exists:files,id',
            ]);

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description', 'overview', 'client', 'category', 'country',
                'seo_title', 'seo_keywords', 'seo_description', 'other_services'
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $projectField = json_decode($request->$field, true);

                    if (is_array($projectField)) {
                        foreach ($projectField as $locale => $value) {
                            $project->setTranslation($field, $locale, $value);
                        }
                    }
                }
            }

            // Non-translatable fields
            $project->service_id = $request->service_id;
            $project->date = $request->date;
            $project->featured = $request->featured;
            $project->status = $request->status;
            $project->robots = $request->robots;
            $project->save();

            // Handle thumb file attachment
            if ($request->has('thumb')) {
                // Detach old thumb if exists
                $project->files()->wherePivot('type', 'thumb')->detach();
                // Attach new thumb
                $project->files()->attach($request->thumb, ['type' => 'thumb']);
            }

            // Handle gallery file attachments
            if ($request->has('gallery') && is_array($request->gallery)) {
                // Detach old gallery files if needed
                $project->files()->wherePivot('type', 'gallery')->detach();
                $galleryFiles = $request->gallery;
                foreach ($galleryFiles as $fileId) {
                    $project->files()->attach($fileId, ['type' => 'gallery']);
                }
            }

            return response()->json($project, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            // General error handling (optional)
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(null, 204);
    }

    public function getAllProjects(Request $request)
    {
        $serviceId = $request->query('service_id');
        $serviceSlug = $request->query('service_slug');

        // Build the initial query.
        $query = Project::with(['service'])->where('status', 1);

        // Conditionally add filters to the query.
        if ($serviceId && $serviceId !== 'all') {
            $query->where('service_id', $serviceId);
        } elseif ($serviceSlug && $serviceSlug !== 'all') {
            $query->whereHas('service', function ($q) use ($serviceSlug) {
                $q->where('slug', 'like', '%' . $serviceSlug . '%');
            });
        }

        // Execute the query to get a collection of projects.
        $projects = $query->get();

        // Extract unique services from the projects
        $services = $projects->pluck('service')->values();

        // Format services
        $services = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
            ];
        });

        // Format projects
        $formattedProjects = $projects->map(function ($project) {
            $thumbUrl = $project->files->where('pivot.type', 'thumb')->first()->file_url ?? '';
            $galleryUrls = $project->files->where('pivot.type', 'gallery')->map(function ($file) {
                return $file->file_url;
            })->values()->toArray();
            return [
                'service' => [
                    'name' => $project->service->name,
                    'slug' => $project->service->slug,
                ],
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'alt' => $project->name,
                'thumb' => $thumbUrl,
                'gallery' => $galleryUrls,
                'date' => $project->date,
            ];
        });

        return response()->json([
            'data' => [
                // 'services' => $services,
                'projects' => $formattedProjects
            ]
        ]);
    }

    public function getFeaturedProjects(Request $request)
    {
        $serviceId = $request->query('service_id');
        $serviceSlug = $request->query('service_slug');

        // Build the initial query.
        $query = Project::with(['service'])->where('status', 1)->where('featured', 1);

        // Conditionally add filters to the query.
        if ($serviceId && $serviceId !== 'all') {
            $query->where('service_id', $serviceId);
        } elseif ($serviceSlug && $serviceSlug !== 'all') {
            $query->whereHas('service', function ($q) use ($serviceSlug) {
                $q->where('slug', 'like', '%' . $serviceSlug . '%');
            });
        }

        // Execute the query to get a collection of projects.
        $projects = $query->get();

        // Extract unique services from the projects
        $services = $projects->pluck('service')->values();

        // Format services
        $services = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'slug' => $service->slug,
            ];
        });

        // Format projects
        $formattedProjects = $projects->map(function ($project) {
            $thumbUrl = $project->files->where('pivot.type', 'thumb')->first()->file_url ?? '';
            $galleryUrls = $project->files->where('pivot.type', 'gallery')->map(function ($file) {
                return $file->file_url;
            })->values()->toArray();
            return [
                'service' => [
                    'name' => $project->service->name,
                    'slug' => $project->service->slug,
                ],
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'alt' => $project->name,
                'thumb' => $thumbUrl,
                'gallery' => $galleryUrls,
                'date' => $project->date,
            ];
        });

        return response()->json([
            'data' => [
                // 'services' => $services,
                'projects' => $formattedProjects
            ]
        ]);
    }

  
    public function getSingleProject($id)
    {
        $lang = app()->getLocale();

        // Try to find by ID first
        $value = Project::where('status', 1)->where('id', $id)->with(['files', 'service'])->first();

        // If not found, try to find by slug
        if (!$value) {
            $value = Project::where('status', 1)
                ->where("slug->$lang", $id)
                ->with(['files', 'service'])
                ->firstOrFail();
        }

        // Initialize URLs for thumb and gallery
        $thumb = '';
        $gallery = [];

        // Loop through the files to find thumb and gallery
        foreach ($value->files as $file) {
            if ($file->pivot->type == 'thumb') {
                $thumb = $file->file_url;
            } elseif ($file->pivot->type == 'gallery') {
                $gallery[] = $file->file_url;
            }
        }

        $data = [
            [
                'service' => [
                    'id' => $value->service->id,
                    'name' => $value->service->name,
                    'slug' => $value->service->slug,
                ],
                'id' => $value->id,
                'name' => $value->name,
                'slug' => $value->slug,
                'overview' => $value->overview,
                'description' => $value->description,
                'client' => $value->client,
                'category' => $value->category,
                'country' => $value->country,
                'thumb' => $thumb,
                'gallery' => $gallery,
                'alt' => $value->name,
                'date' => $value->date,
                'seo' => [
                    'title' => $value->seo_title,
                    'keywords' => $value->seo_keywords,
                    'description' => $value->seo_description,
                    'robots' => $value->robots,
                    'facebook_title' => $value->seo_title,
                    'facebook_description' => $value->seo_description,
                    'twitter_title' => $value->seo_title,
                    'twitter_description' => $value->seo_description,
                    'twitter_image' => $thumb,
                    'facebook_image' => $thumb,
                ],
            ]
        ];

        return response()->json([
            'data' => $data,
        ], 200);
    }
}
