<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UserController extends Controller
{

    private static $hidden_fields = [
        'national_insurance_no',
        'dob',
        'full_address',
        'updated_at'
    ];

    public function store(Request $request)
    {
        
        $user_data = [
            'email' => $request->email,
            'first_name' => $request->first_name,
            'surname' => $request->surname,
            'dob' => $request->dob,
            'national_insurance_no' => $request->national_insurance_no,
            'profile_image' => $request->profile_image,
            'full_address' => $request->full_address,
            'bio' => $request->bio,
        ];

        // Used to validate the dob entered makes the user over 18. Add a day to account for validator 'before' option
        $min_dob = Carbon::now()->subYears(18)->addDay()->toDateString();

        // Validator rules
        $rules = [
            'email' => 'required',
            'first_name' => 'required',
            'dob' => 'required|date|before:'.$min_dob
        ];

        // Amend the Validator error messages 
        $messages = [
            'email.required' => 'You must include your :attribute in your requests body',
            'first_name.required' => 'You must include you :attribute in your requests body',
            'dob.required' => 'You must include your :attribute in your requests body',
            'dob.before' => 'You must be 18 or over to create a user on DevBook',
            'dob.date' => 'You must ensure that dates are entered using the Y-m-d format'
        ];

        // Run validation
        $validator = Validator::make($user_data, $rules, $messages);

        // Check if validation failed, return errors
        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }

        // Check if email already exists
        $email_exists = User::where('email', $request->email)->first();
        if($email_exists){
            return response()->json("This email has already been used", 409);
        }

        // Generate a random token for the user and add it to the data array
        $new_token = Str::random(10);
        $user_data['token'] = $new_token;

        // Create user, abort if creation fails
        $user = User::create($user_data);
        if(!$user){
            abort(500);
        }

        $response = [
            'message' => 'Profile created successfully for '.$request->first_name.'.',
            'access_token' => $new_token,
        ];

        return response()->json($response);
        
    }

    public function show(Request $request, $user)
    {
        // Grab token from request headers
        $token = $request->header('token');
        $profile = null;
        
        // Check if user has used 'me' in the URL
        if($user == 'me'){
            $profile = User::firstWhere('token', $token);
        }else if(is_numeric($user)){ 
            // Check for use of number to search by id
            $profile = User::findOrFail($user);

            // Check if id provided is for the user to determine if fields need hiding
            if($profile->token != $token){
                // Hiding confidential data.
            $profile->makeHidden(self::$hidden_fields);
            }
            
        }else if(is_string($user)){
            // Fall back to searching by email
            $profile = User::firstWhere('email', $user);

            if(!$profile){
                return response()->json("No profile was found with that email");
            }

            // Hiding confidential data.
            $profile->makeHidden(self::$hidden_fields);
        }else{

        }     

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        // Grab token from request headers
        $token = $request->header('token');
        $profile = User::firstWhere('token', $token);
        $error_message = false;

        //TODO - Rewrite so fails on editing these fields

        if($request->email || $request->dob || $request->national_insurance_no){
            $response = 'You cannot update your profiles email, dob or national insurance number. Please remove these from your request';
            return response()->json($response, 403);
        }

        if($request->first_name) $profile->first_name = $request->first_name;
        if($request->surname) $profile->surname = $request->surname;
        if($request->profile_image) $profile->profile_image = $request->profile_image;
        if($request->full_address) $profile->full_address = $request->full_address;
        if($request->bio) $profile->bio = $request->bio;

        if($profile->isDirty()){
            if(!$profile->save()){
                abort(500);
            }
            $response_message = 'Your profile has been updated successfully';
        }else{
            $response_message = 'The details you provided were no different from what we had recorded. No changes have been made.';            
        }

        $response = [
            'message' => $response_message,
            'profile' => $profile
        ];

        return response()->json($response, 200);

    }

    public function destroy(Request $request)
    {
        // Grab token from request headers
        $token = $request->header('token');
        $profile = User::firstWhere('token', $token);

        if(!$profile->forceDelete()){
            abort(500);
        };

        return response()->json('Your profile has now been deleted', 200);
    }    

    public function index(Request $request)
    {
        $search_terms = explode(',', $request->search);
        
        $query = new User;

        foreach($search_terms as $term){
            if(!empty($term)){
                $query = $query->orWhere('bio', 'LIKE', '%'.$term.'%');
            }
        }

        $profiles = $query->get();
        foreach($profiles as $profile){
            // Hiding confidential data.
            $profile->makeHidden(self::$hidden_fields);
        }

        return response()->json($profiles, 200);
        

    }
}
