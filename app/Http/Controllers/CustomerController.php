<?php

// app/Http/Controllers/CustomerController.php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('files')->get()->map(function ($customer) {
            // Decode the project_ids from JSON string to array
            $customer->project_ids = json_decode($customer->project_ids);
            return $customer;
        });
        return response()->json($customers);
    }
    

    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                // 'name' => 'required|json',
                // 'slug' => 'required|json',
                // 'description' => 'required|json',
                // 'overview' => 'required|json',
                // 'seo_title' => 'nullable|json',
                // 'seo_keywords' => 'nullable|json',
                // 'seo_description' => 'nullable|json'
            ]);
    
            // Initialize a new customer instance
            $customer = new Customer();
    
            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description',
                'seo_title', 'seo_keywords', 'seo_description',
            ];
    
            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $customerField = json_decode($request->$field, true);
    
                // Validate English translation
                // if (!isset($blogField['en']) || !is_string($blogField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }
    
                foreach ($customerField as $locale => $value) {
                    $customer->setTranslation($field, $locale, $value);
                }
            }
    
            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $customer->addMediaFromRequest('logo')->toMediaCollection('logo');
            }
    
            $customer->status = $request->status;
            $customer->robots = $request->robots;
            $customer->project_ids = $request->project_ids;
    
            // Ensure project_ids is an array of integers
            // if ($request->has('project_ids')) {
            //     $projectIds = array_map('intval', $request->project_ids);
            //     $customer->project_ids = $projectIds;
            // }
    
            // Persist the customer instance into the database
            $customer->save();
            $customer->files()->attach($request->logo, ['type' => 'logo']);
    
            return response()->json($customer);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }
    

    public function show(Customer $customer)
    {
        $logoId = null;
        $logoUrl = '';
    
        // Loop through the files to find logo
        foreach ($customer->files as $file) {
            if ($file->pivot->type == 'logo') {
                $logoId = $file->id;  // Store the logo ID
                $logoUrl = $file->file_url;
            }
        }
    
        // Decode the project_ids from JSON string to array
        $customer->project_ids = json_decode($customer->project_ids, true);
    
        // Prepare response data
        $responseData = $customer->toArray();
        $responseData['logo_id'] = $logoId;
        $responseData['logo_url'] = $logoUrl;
    
        unset($responseData['files']);
    
        return response()->json($responseData);
    }
    

    public function update(Request $request, $id)
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

            // Find the existing customer instance
            $customer = Customer::findOrFail($id);

            // Define the translatable fields
            $translatableFields = [
                'name', 'slug', 'description',
                'seo_title', 'seo_keywords', 'seo_description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $customerField = json_decode($request->$field, true);

                    foreach ($customerField as $locale => $value) {
                        $customer->setTranslation($field, $locale, $value);
                    }
                }
            }

            if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                $customer->addMediaFromRequest('logo')->toMediaCollection('logo');
            }

            // Update non-translatable fields
            $customer->status = $request->status;
            $customer->robots = $request->robots;
            $customer->project_ids = $request->project_ids;
            // Persist the updated customer instance into the database
            $customer->save();

            // Handle logo file attachment
            if ($request->has('logo')) {
                $customer->files()->attach($request->logo, ['type' => 'logo']);
            }

            return response()->json($customer);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(null, 204);
    }

    public function getSingleCustomer($id)
    {
        $lang = app()->getLocale();

        // Try to find by ID first
        $customer = Customer::where('status', 1)
            ->where('id', $id)
            ->with('files')
            ->first();

        // If not found, try to find by slug
        if (!$customer) {
            $customer = Customer::where('status', 1)
                ->where("slug->$lang", $id)
                ->with('files')
                ->firstOrFail();
        }

        $logo = '';
        foreach ($customer->files as $file) {
            if ($file->pivot->type == 'logo') {
                $logo = $file->file_url;
            }
        }

        // Retrieve and format the projects related to the customer
        // $projects = $customer->projects->map(function ($project) use ($lang) {
        //     return [
        //         'id' => $project->id,
        //         'name' => $project->name,
        //         'slug' => $project->slug,
        //     ];
        // });

        $data = [
            'id' => $customer->id,
            'name' => $customer->name,
            'slug' => $customer->slug,
            'description' => $customer->description,
            'logo' => $logo,
            'alt' => $customer->name,
            'projects' => $customer->projects, // Ensure all projects are included
            'created_at' => Carbon::createFromFormat('Y-m-d H:i:s', $customer->created_at)->isoFormat('MMM Do YY'),
            'seo' => [
                'title' => $customer->seo_title,
                'keywords' => $customer->seo_keywords,
                'description' => $customer->seo_description,
                'robots' => $customer->robots,
                'facebook_title' => $customer->seo_title,
                'facebook_description' => $customer->seo_description,
                'twitter_title' => $customer->seo_title,
                'twitter_description' => $customer->seo_description,
                'twitter_image' => $logo,
                'facebook_image' => $logo,
            ],
        ];

        return response()->json([
            'data' => $data,
        ], 200);
    }

    public function getAllCustomers()
    {
        // Retrieve all customers with their associated files
        $customers = Customer::where('status', 1)
            ->with('files')
            ->get();

        // Format the customers data
        $data = $customers->map(function ($customer) {
            $logo = '';
            foreach ($customer->files as $file) {
                if ($file->pivot->type == 'logo') {
                    $logo = $file->file_url;
                }
            }

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'slug' => $customer->slug,
                'logo' => $logo,
            ];
        });

        return response()->json([
            'data' => $data,
        ], 200);
    }
}
