<?php
namespace App\Http\Controllers\CustomerSupport;
use DB;
use File;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminSales\package;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Validator;

class tataDialer extends Controller
{
    public function addLmartTollFreeResponse(Request $request)
    {
        $uuid = $request->input('uuid');
        $call_to_number = $request->input('call_to_number');
        $caller_id_number = $request->input('caller_id_number');
        $start_stamp = $request->input('start_stamp');      
        $call_id = $request->input('call_id');
        $billing_circle = $request->input('billing_circle');
        $customer_no_with_prefix = $request->input('customer_no_with_prefix_');

        $data = [
            'uuid' => $uuid,
            'call_to_number' => $call_to_number,
            'caller_id_number' => $caller_id_number,
            'start_stamp' => $start_stamp,
            'call_id' => $call_id,
            'billing_circle' => json_encode($billing_circle),
            'customer_no_with_prefix' => $customer_no_with_prefix
        ];

        $addTollfreeCall = DB::connection('sales_db')->table('tata_lm_tollfree_incoming')->insertGetId($data);
    }

    public function updateLmartTollFreeResponse(Request $request)
    {
        $call_id = $request->input('call_id');
        $uuid = $request->input('uuid');
        $call_to_number = $request->input('call_to_number');
        $caller_id_number = $request->input('caller_id_number');
        $start_stamp = $request->input('start_stamp');
        $answer_stamp = $request->input('answer_stamp');
        $end_stamp = $request->input('end_stamp');
        $hangup_cause = $request->input('hangup_cause');
        $billsec = $request->input('billsec');
        $digits_dialed = $request->input('digits_dialed');
        $direction = $request->input('direction');
        $duration = $request->input('duration');
        $answered_agent = $request->input('answered_agent');
        $answered_agent_name = $request->input('answered_agent_name');
        $answered_agent_number = $request->input('answered_agent_number');
        $missed_agent = $request->input('missed_agent');
        $call_flow = $request->input('call_flow');
        $recording_url = $request->input('recording_url');
        $call_status = $request->input('call_status');
        $outbound_sec = $request->input('outbound_sec');
        $agent_ring_time = $request->input('agent_ring_time');
        $billing_circle = $request->input('billing_circle');

        $updateData = [
            'uuid' => $uuid,
            'call_to_number' => $call_to_number,
            'caller_id_number' => $caller_id_number,
            'start_stamp' => $start_stamp,
            'answer_stamp' => $answer_stamp,
            'end_stamp' => $end_stamp,
            'hangup_cause' => $hangup_cause,
            'billsec' => $billsec,
            'digits_dialed' => $digits_dialed,
            'direction' => $direction,
            'duration' => $duration,
            'answered_agent' => json_encode($answered_agent),
            'answered_agent_name' => $answered_agent_name,
            'answered_agent_number' => $answered_agent_number,
            'missed_agent' => json_encode($missed_agent),
            'call_flow' => json_encode($call_flow),
            'recording_url' => $recording_url,
            'call_status' => $call_status,
            'outbound_sec' => $outbound_sec,
            'agent_ring_time' => $agent_ring_time,
            'billing_circle' => json_encode($billing_circle)
        ];

        $updateLmartTollFree = DB::connection('sales_db')->table('tata_lm_tollfree_incoming')
        ->where('call_id',$call_id)
        ->update($updateData);
        
    }

    public function autoC2CResponse(Request $request)
    {
       
        $data = [
            'call_end' => $validatedData['end_stamp'],
            'end_status' => 1,
            'end_message' => $validatedData['hangup_cause'],
            'uuid' => $validatedData['uuid'],
            'call_to_number' => $validatedData['call_to_number'],
            'caller_id_number' => $validatedData['caller_id_number'],
            'start_stamp' => $validatedData['start_stamp'],
            'answer_stamp' => $validatedData['answer_stamp'],
            'direction' => $validatedData['direction'],
            'duration' => $validatedData['duration'],
            'answered_agent' => json_encode($validatedData['answered_agent']),
            'call_flow' => json_encode($validatedData['call_flow']),
            'recording_url' => $validatedData['recording_url'],
            'call_status' => $validatedData['call_status'],
            'outbound_sec' => $validatedData['outbound_sec'],
            'agent_ring_time' => $validatedData['agent_ring_time'],
            'billing_circle' => json_encode($validatedData['billing_circle']),
            'logs' => json_encode($request->all()), // Log all input data
        ];

        $condition = ['call_id' => $validatedData['call_id']];
        $updated = DB::connection('sales_db')->table('tata_ctoc')->where($condition)->update($data);
    }

    // public function addZoopgoTollFreeResponse(Request $request)
    // {
    //     $uuid = $request->input('uuid');
    //     $call_to_number = $request->input('call_to_number');
    //     $caller_id_number = $request->input('caller_id_number');
    //     $start_stamp = $request->input('start_stamp');      
    //     $call_id = $request->input('call_id');
    //     $billing_circle = $request->input('billing_circle');
    //     $customer_no_with_prefix = $request->input('customer_no_with_prefix_');

    //     $data = [
    //         'uuid' => $uuid,
    //         'call_to_number' => $call_to_number,
    //         'caller_id_number' => $caller_id_number,
    //         'start_stamp' => $start_stamp,
    //         'call_id' => $call_id,
    //         'billing_circle' => json_encode($billing_circle),
    //         'customer_no_with_prefix' => $customer_no_with_prefix
    //     ];

    //     $addTollfreeCall = DB::connection('sales_db')->table('tata_zoopgo_tollfree_incoming')->insertGetId($data);
    //    // return response()->json(['success' => true, 'data' => $insertdata]);
    // }


   

    // public function updateZoopgoTollFreeResponse(Request $request)
    // {
    //     $call_id = $request->input('call_id');
    //     $uuid = $request->input('uuid');
    //     $call_to_number = $request->input('call_to_number');
    //     $caller_id_number = $request->input('caller_id_number');
    //     $start_stamp = $request->input('start_stamp');
    //     $answer_stamp = $request->input('answer_stamp');
    //     $end_stamp = $request->input('end_stamp');
    //     $hangup_cause = $request->input('hangup_cause');
    //     $billsec = $request->input('billsec');
    //     $digits_dialed = $request->input('digits_dialed');
    //     $direction = $request->input('direction');
    //     $duration = $request->input('duration');
    //     $answered_agent = $request->input('answered_agent');
    //     $answered_agent_name = $request->input('answered_agent_name');
    //     $answered_agent_number = $request->input('answered_agent_number');
    //     $missed_agent = $request->input('missed_agent');
    //     $call_flow = $request->input('call_flow');
    //     $recording_url = $request->input('recording_url');
    //     $call_status = $request->input('call_status');
    //     $outbound_sec = $request->input('outbound_sec');
    //     $agent_ring_time = $request->input('agent_ring_time');
    //     $billing_circle = $request->input('billing_circle');

    //     $updateData = [
    //         'uuid' => $uuid,
    //         'call_to_number' => $call_to_number,
    //         'caller_id_number' => $caller_id_number,
    //         'start_stamp' => $start_stamp,
    //         'answer_stamp' => $answer_stamp,
    //         'end_stamp' => $end_stamp,
    //         'hangup_cause' => $hangup_cause,
    //         'billsec' => $billsec,
    //         'digits_dialed' => $digits_dialed,
    //         'direction' => $direction,
    //         'duration' => $duration,
    //         'answered_agent' => json_encode($answered_agent),
    //         'answered_agent_name' => $answered_agent_name,
    //         'answered_agent_number' => $answered_agent_number,
    //         'missed_agent' => json_encode($missed_agent),
    //         'call_flow' => json_encode($call_flow),
    //         'recording_url' => $recording_url,
    //         'call_status' => $call_status,
    //         'outbound_sec' => $outbound_sec,
    //         'agent_ring_time' => $agent_ring_time,
    //         'billing_circle' => json_encode($billing_circle),
    //     ];
        
    //     $updateZoopgoTollFree = DB::connection('sales_db')->table('tata_zoopgo_tollfree_incoming')
    //     ->where('call_id',$call_id)
    //     ->update($updateData);
    // }

    // public function click2callTata(Request $request)
    // {
    //     $CustomerPhone = $request->input('custPhoneNum');
    //     $p_type = $request->input('type', 0);
    //     $agentNo = $request->input('agentNo');
    //     $leadID = $request->input('leadIdDa');

    //     $client = new Client();
    //     $response = $client->post('https://api-smartflo.tatateleservices.com/v1/click_to_call', [
    //         'body' => json_encode([
    //             'agent_number' => $agentNo,
    //             'destination_number' => $CustomerPhone,
    //             'custom_identifier' => 'test',
    //             'get_call_id' => 1
    //         ]),
    //         'headers' => [
    //             'Accept' => 'application/json',
    //             'Authorization' => env('TATA_API_AUTH_VAL'), // Ensure this is defined in your .env file
    //             'Content-Type' => 'application/json',
    //         ],
    //     ]);

    //     $json_resp = $response->getBody();
    //     $result = json_decode($json_resp, true);

    //     if($result['success'])
    //     {
    //         $dataresp = [
    //             'lead_id' => $leadID,
    //             'call_id' => $result['call_id'],
    //             'message' => $result['message'],
    //             'status' => $result['success'],
    //             'agent_id' => session('ADMIN_EMPID'),
    //             'p_type' => $p_type
    //         ];

    //         if ($p_type == 22) {
    //             $insertdata = DB::connection('sales_db')->table('tata_ctoc')->insertGetId($dataresp);
               
    //         } else {
    //            $insertdata = DB::connection('sales_db')->table('tata_ctoc')->insertGetId($dataresp);
    //         }

    //         if ($insertdata) {
    //             $response = [
    //                 'success' => $result['success'],
    //                 'message' => $result['message'],
    //                 'call_id' => $result['call_id'],
    //                 'last_inserted' => $insertdata
    //             ];
    //         } else {
    //             $response = [
    //                 'success' => 3,
    //                 'message' => 'Insert ID is missing',
    //                 'call_id' => $result['call_id'],
    //                 'last_inserted' => $insertdata
    //             ];
    //         }
    //     } else {
    //         $response = [
    //             'success' => 2,
    //             'message' => 'Error while connecting the call',
    //             'call_id' => ''
    //         ];
    //     }
    //     return response()->json($response);
    // }
}
