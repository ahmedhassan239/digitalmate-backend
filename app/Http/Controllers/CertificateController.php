<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CertificateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getAllCertificates']]);
    }

    public function index()
    {
        $data = Certificate::get();
        return response()->json($data);
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
                // 'seo_description' => 'nullable|json'
            ]);

            // Initialize a new doctor instance
            $data = new Certificate();

            // Define the translatable fields
            $translatableFields = [
                'title', 'description', 

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $dataField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($dataField as $locale => $value) {
                    $data->setTranslation($field, $locale, $value);
                }
            }

            // if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            //     $blog->addMediaFromRequest('banner')->toMediaCollection('banner');
            // 

            // Persist the doctor instance into the database
            $data->save();
            $data->files()->attach($request->image, ['type' => 'image']);

            return response()->json($data);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Certificate $certificate)
    {
        // Initialize IDs and URLs
        $imageId = null;
       
        $imageUrl = '';
       

        // Loop through the files to find banner and thumb
        foreach ($certificate->files as $file) {
            if ($file->pivot->type == 'image') {
                $imageId = $file->id;  // Store the banner ID
                $imageUrl = $file->file_url;
            } 
        }

        // Prepare response data
        $responseData = $certificate->toArray();
        $responseData['image_id'] = $imageId;
    
        $responseData['image_url'] = $imageUrl;

        unset($responseData['files']);

        return response()->json($responseData);
    }


    public function update(Request $request, Certificate $certificate)
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
                'title', 'description',
            ];

            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $dataField = json_decode($request->$field, true);

                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }

                    foreach ($dataField as $locale => $value) {
                        $certificate->setTranslation($field, $locale, $value);
                    }
                }
            }



            $certificate->save();

            // Detach existing files and attach new ones
            // if()
            $certificate->files()->detach();

            $certificate->files()->attach($request->image, ['type' => 'image']);
            
            // Retrieve banner and thumb URLs
            $certificate->load('files');
            // dd($blog->load('files'));// Reload the files relationship
            $imageId = null;

            $imageUrl = '';
         
            // Loop through the files to find banner and thumb
            foreach ($certificate->files as $file) {
                if ($file->pivot->type == 'image') {
                    $imageId = $file->id;  // Store the banner ID
                    $imageUrl = $file->file_url;
                }
            }

            // Prepare response data
            $responseData = $certificate->toArray();
            $responseData['image_id'] = $imageId;

            $responseData['image_url'] = $imageUrl;
        
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

    public function destroy(Certificate $certificate)
    {
        $certificate->delete();

        // Return a success message in JSON format
        return response()->json(['message' => 'Certificate successfully deleted'], 200);
    }


    public function getAllCertificates(Request $request)
    {
        // Execute the query to get a collection of blogs.
        $data = Certificate::get();

        // Format blogs
        $formattedCertificates = $data->map(function ($data) {
            $imageUrl = $data->files->where('pivot.type', 'image')->first()->file_url ?? '';
            return [
                'id' => $data->id,
                'title' => $data->title,
                'description' => $data->description,
                'alt' => $data->title,
                'image' => $imageUrl,
            ];
        });

        return response()->json([
      
                'data' => $formattedCertificates
       
        ]);
    }

}
