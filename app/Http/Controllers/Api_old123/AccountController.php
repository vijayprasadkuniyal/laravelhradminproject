<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Account;
use Validator;
use App\Events\TestEvent;
use App\Events\EnquiryCreated;
use GuzzleHttp\Client;

class AccountController extends Controller
{
    public function save_account(Request $request)

    {    

        $apiKey = 'AIzaSyAcnVv3wYJFRKTsRKHfvoW5_0y5tbri-QE';
        
        $address = 'Shree Shyam PG Badshahpur, Sector 66, Gurugram, Haryana 122102';

        // Ensure an address was found
        if (!$address) {
            return response()->json(['error' => 'Address not found'], 404);
        }

        // API Key for Google Maps Geocoding API (stored in .env for security)
        $client = new Client();

        // Make the request to Google Maps Geocoding API
        try {
            $result = $client->get("https://maps.googleapis.com/maps/api/geocode/json", [
                'query' => [
                    'address' => $address, // Assuming 'full_address' is a column in your address model
                    'key' => $apiKey,
                ]
            ]);

            // Decode the response JSON
            $json = json_decode($result->getBody());
            return $json;

            // Check if we received a valid result
            if (isset($json->results[0])) {
                $address->lat = $json->results[0]->geometry->location->lat;
                $address->lng = $json->results[0]->geometry->location->lng;

                // Save the coordinates to the address record
                $address->save();

                return response()->json([
                    'latitude' => $address->lat,
                    'longitude' => $address->lng,
                ]);
            } else {
                return response()->json(['error' => 'Geocode not found for the provided address.'], 400);
            }
        } catch (\Exception $e) {
            // Handle any exceptions or errors that may occur during the HTTP request
            return response()->json(['error' => 'Unable to get coordinates. ' . $e->getMessage()], 500);
        }
    }

public function edit_account($id){
  $data = Account::where('emp_id',$id)->get();
  if($data){
    return response()->json(['status'=>200,'message'=>'Account Details','account'=>$data]);
  }
  else{
    return response()->json(['status'=>500,'message'=>'no  data found','account'=>$data]);

  }
}
public function account_details($id){
	$account = Account::where('emp_id',$id)->get();
	if($account){
    return response()->json(['status'=>200,'message'=>'Account Details','account'=>$account]);
   }
  else{
    return response()->json(['status'=>500,'message'=>'no  data found']);
 }
}
public function account_status(Request $request){
    //dd('hi');
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $account = Account::findOrFail($request->id);
    //dd($company);

    // Update the status of the company
    $account->status = $request->status;
    $account->save();
    if($account){
        return response()->json(['status' => 200, 'message' => 'Account status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_account_status($id){
    $account = Account::findOrFail($id);
    if($account){
        return response()->json(['status' => 200, 'message' => 'Account status','data'=>$account->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
