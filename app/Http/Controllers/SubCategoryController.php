<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubCategoryController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = SubCategory::with('category')->get();
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                // 'name' => 'required|json',
            
            ]);

            // Initialize a new doctor instance
            $data = new SubCategory();

            // Define the translatable fields
            $translatableFields = [
                'name','slug'

            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $categoryField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($categoryField as $locale => $value) {
                    $data->setTranslation($field, $locale, $value);
                }
            }


            $data->category_id = $request->category_id;
      
            $data->save();
      

            return response()->json($data);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SubCategory $subCategory)
    {
        // Prepare response data
        $responseData = $subCategory->toArray();
        return response()->json($responseData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SubCategory $subCategory)
    {
        try {
            $validatedData = $request->validate([
                // 'name' => 'required|json',
                
            ]);
    
            $translatableFields = [
                'name','slug'
            ];
    
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $categoryField = json_decode($request->$field, true);
    
                    // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }
    
                    foreach ($categoryField as $locale => $value) {
                        $subCategory->setTranslation($field, $locale, $value);
                    }
                }
            }
    
        

            $subCategory->category_id = $request->category_id;
            $subCategory->save();
    
            // Prepare response data
            $responseData = $subCategory->toArray();
    
    
            return response()->json($responseData);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubCategory $subSategory)
    {
        $subSategory->delete();

        // Return a success message in JSON format
        return response()->json(['message' => 'Sub Category successfully deleted'], 200);
    }
}
