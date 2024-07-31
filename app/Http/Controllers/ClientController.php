<?php

// app/Http/Controllers/ClientController.php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\UserApiCall;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
// use Kreait\Firebase\Auth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientController extends Controller
{   
    protected $auth;
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register','getAuthenticatedClient']]);
        // $serviceAccount = config('firebase.credentials.file');
        // if (!$serviceAccount) {
        //     throw new \Exception('Firebase credentials file not found. Please set FIREBASE_CREDENTIALS in your .env file.');
        // }
        // $factory = (new Factory)
        //     ->withServiceAccount($serviceAccount);
        // $this->auth = $factory->createAuth();
    }

    public function index()
    {
        $users = Client::select('id', 'name', 'email', 'created_at')->get();
        return response()->json(["data" => $users]);
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Successfully Deleted'], 200);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|unique:clients',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $client = Client::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => bcrypt($validated['password']),
        ]);

        $token = JWTAuth::fromUser($client);

        return response()->json([
            'success' => true,
            'user' => $client,
            'token' => $token,
        ], 201);
    }

    // protected function sendEmailVerification($email)
    // {
    //     try {
    //         $user = $this->auth->getUserByEmail($email);
    //         $this->auth->sendEmailVerification($user->uid);
    //     } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
    //         // Handle error if user is not found in Firebase
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User not found in Firebase',
    //             'errors' => ['email' => $e->getMessage()],
    //         ], 404);
    //     } catch (\Exception $e) {
    //         // Handle other errors
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to send email verification',
    //             'errors' => ['email' => $e->getMessage()],
    //         ], 500);
    //     }
    // }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api_clients')->attempt($credentials)) {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        $user = Auth::guard('api_clients')->user();

        return response()->json([
            'access_token' => $token,
            'client' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function getAuthenticatedClient(Request $request)
    {
        // dd(Carbon::now()->subDay());
        $user = Auth::guard('api_clients')->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Increment the call count
        $apiCall = UserApiCall::firstOrCreate(['user_id' => $user->id]);

        // Check if 24 hours have passed since the last call
        
        if ($apiCall->last_call_at && $apiCall->last_call_at->lt(Carbon::now()->subDay())) {
            $apiCall->call_count = 0; // Reset the call count
        }

        $apiCall->increment('call_count');
        $apiCall->last_call_at = Carbon::now();
        $apiCall->save();

        return response()->json(['client' => $user, 'call_count' => $apiCall->call_count], 200);
    }
}

