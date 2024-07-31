<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['store']]);
    }
    public function index()
    {
        $bookings = Booking::all();
        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        // Update validation rules
        $validated = $request->validate([
            'date' => 'required|date_format:d/m/Y',
            'client_time' => 'required|date_format:H:i',
            'company_time' => 'required|date_format:H:i',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'website_url' => 'nullable|url',
            'message' => 'nullable|string',
            'source' => 'nullable|string|max:255',
        ]);

        // Convert the date to MySQL format
        $validated['date'] = Carbon::createFromFormat('d/m/Y', $validated['date'])->format('Y-m-d');

        $booking = Booking::create($validated);

        return response()->json(['booking' => $booking], 201);
    }

    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
          
            'status' => 'sometimes',
            
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update($validated);

        return response()->json(['booking' => $booking]);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
}
