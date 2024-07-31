<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class FaqController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getAllFaqs','getFeaturedFaqs']]);
    }

    public function index()
    {
        $faq = Faq::all();
        return response()->json($faq);
    }

    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                // 'title' => 'required|json',
                // 'description' => 'required|json',
                // 'category_id' => 'required',
            ]);

            $faq = new Faq();

            $translatableFields = [
                'title', 'description',
            ];

            // Loop through each translatable field and set the translation
            foreach ($translatableFields as $field) {
                $faqField = json_decode($request->$field, true);

                // Validate English translation
                // if (!isset($faqField['en']) || !is_string($faqField['en'])) {
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'Validation failed',
                //         'errors' => ['English ' . $field . ' is required and must be a string'],
                //     ], 422);
                // }

                foreach ($faqField as $locale => $value) {
                    $faq->setTranslation($field, $locale, $value);
                }
            }

            $faq->category_id = $request->category_id;
            $faq->featured = $request->featured;

            $faq->save();


            return response()->json($faq);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function update(Request $request, Faq $faq)
    {
        try {
            $validatedData = $request->validate([
                // 'title' => 'sometimes|required|json',
                // 'description' => 'sometimes|required|json',
                // 'category_id' => 'sometimes|required',
            ]);

            $translatableFields = [
                'title', 'description',
            ];

            foreach ($translatableFields as $field) {
                if ($request->has($field)) {
                    $faqField = json_decode($request->$field, true);

                    // if (!isset($faqField['en']) || !is_string($faqField['en'])) {
                    //     return response()->json([
                    //         'status' => 'error',
                    //         'message' => 'Validation failed',
                    //         'errors' => ['English ' . $field . ' is required and must be a string'],
                    //     ], 422);
                    // }

                    foreach ($faqField as $locale => $value) {
                        $faq->setTranslation($field, $locale, $value);
                    }
                }
            }

            $faq->category_id = $request->category_id;
            $faq->featured = $request->featured;

            $faq->save();

            // Prepare response data
            $responseData = $faq->toArray();


            return response()->json($responseData);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors()
            ], 422);
        }
    }

    public function show(Faq $faq)
    {

        // Prepare response data
        $responseData = $faq->toArray();
        return response()->json($responseData);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        // Return a success message in JSON format
        return response()->json(['message' => 'Faq successfully deleted'], 200);
    }

    public function getFeaturedFaqs()
    {
        $faqs = Faq::with('category')->where('featured',1)->get()
            ->map(function ($val){

                return [
                    'id'=>$val->id,
                    'title' => $val->title ?? [],
                    'description' => $val->description ?? [],
                    // 'category'=>[
                    //     'id'=>$val->category->id,
                    //     'name'=>$val->category->name,
                    //     'slug'=>$val->category->slug,
                    // ]
                ];
            });

        return response()->json([
            'data'=>$faqs
        ]);

    }

    public function getAllFaqs(Request $request)
    {
        $categoryId = $request->query('category_id');
        $categorySlug = $request->query('category_slug');

        // Start building the query with the relationship loaded
        $query = Faq::with('category');

        // Conditionally add filters to the query based on service_id or service_slug
        if ($categoryId && $categoryId !== 'all') {
            $query->whereHas('category', function ($q) use ($categoryId) {
                $q->where('id', $categoryId);
            });
        }

        if ($categorySlug && $categorySlug !== 'all') {
            $query->whereHas('service', function ($q) use ($categorySlug) {
                $q->where('slug', 'like', '%' . $categorySlug . '%');
            });
        }

        // Execute the query to get a collection of FAQs
        $faqs = $query->get();

        // Group FAQs by service_id using the collection's groupBy method
        $groupedFaqs = $faqs->groupBy('category_id')->map(function ($group) {
            if ($group->isEmpty()) {
                return null;
            }
            $category = $group->first()->category;
            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,  // Optionally include slug if useful for clients
                    // 'icon_tag' => $category->icon_tag,  // Optionally include slug if useful for clients
                    // 'svg' => $category->svg,  // Optionally include slug if useful for clients
                ],
                'faqs' => $group->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'title' => $faq->title,
                        'description' => $faq->description,
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $groupedFaqs->filter()->values()  // Ensure to remove any nulls and reindex
        ]);
    }
}
