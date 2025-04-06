<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;

class UtmController extends Controller
{
    public function save_utm(Request $request){
        $input = $request->all();
        //return $input;
            $validator = Validator::make($input, [
                'name' => 'required',
                
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
              $data = array('name'=>$request->name,
              'created_by'=>$request->emp_id);
                DB::connection('sales_db')->table('utm')->insert($data);

             // return $request->is_required;
                return response()->json(['status'=>200,'message'=>'Utm Created Successfully']);
        
           
             
        }
        public function utm_status($id){
            $utm = DB::connection('sales_db')->table('utm')->where('id',$id)->first();
            return response()->json(['status'=>200,'data'=>$utm->status]);

        }
        public function utm_status_change(Request $request){
            $request->validate([
                'id' => 'required',
                'status' => 'required|in:0,1', 
            ]);
            $doc = DB::connection('sales_db')->table('utm')
               ->where('id',$request->id)->update(['status'=>$request->status]);
            return response()->json(['status' => 200, 'message' => 'Utm Status updated successfully']);

        }
        public function utm_edit($id){
            $utm =  DB::connection('sales_db')->table('utm')->where('id',$id)->first();
            return response()->json(['status' => 200, 'data'=>$utm]);

        }
        public function utm_update(Request $request,$id){
            $input = $request->all();
        //return $input;
            $validator = Validator::make($input, [
               
                'name'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
              DB::connection('sales_db')->table('utm')->where('id',$id)
              ->update(['name'=>$request->name,
              'created_by'=>$request->emp_id,
               ]);
               return response()->json(['status'=>200,'message'=>'Utm Updated Successfully']);

        }
        public function utm_list(){
           $data =  DB::connection('sales_db')->table('utm')->get(['id','name','status']);
           return response()->json(['status'=>200,'data'=>$data]);


        }


        ///question 


        public function save_question(Request $request){
           //return $request->all();
            $input = $request->all();
            $option = [];
            //return $input;
                $validator = Validator::make($input, [
                    'product' => 'required',
                    'service' => 'required',
                    'question' => 'required',

                    
                ]);
            
                  if($validator->fails()){
                        $messages=$validator->messages();
                        return response()->json(["messages"=>$messages,'status'=>400]);     
                  }
                   $data = array('product_id'=>$request->product,
                  'service_id'=>$request->service,'question'=>$request->question,
                   'created_by'=>$request->emp_id,'is_option_based'=>$request->option);
                    //DB::connection('sales_db')->table('enquiry_question')->insert($data);
                    $lastInsertId =DB::connection('sales_db')->table('enquiry_question')->insertGetId($data);
                    if($request->option==1){
                        foreach(json_decode($request->optionvalue) as $row){
                            if($row->optionvalue!=""){
                               // return 'jjjj';
                              $option[] = array('product_id'=>$request->product,'service_id'=>$request->service,'question_id'=>$lastInsertId,
                              'option_name'=>$row->optionvalue,'created_by'=>$request->emp_id);
                            }
              
                          }
                          DB::connection('sales_db')->table('enq_question_option')->insert($option);
                        
                        }
                       else{
                          DB::connection('sales_db')->table('enq_question_option')->insert($option);

                      }

                    return response()->json(['status'=>200,'message'=>'Question Created Successfully']);
            
                  }

            public function question_edit($id){
                $utm =  DB::connection('sales_db')->table('enquiry_question')->where('id',$id)->first();
                return response()->json(['status' => 200, 'data'=>$utm]);
    
            }
            public function question_update(Request $request,$id){
                $input = $request->all();
            //return $input;
            $validator = Validator::make($input, [
                'product' => 'required',
                'service' => 'required',
                'question' => 'required',

                
            ]);
            
                  if($validator->fails()){
                        $messages=$validator->messages();
                        return response()->json(["messages"=>$messages,'status'=>400]);     
                  }
                  DB::connection('sales_db')->table('enquiry_question')->where('id',$id)
                  ->update(['product_id'=>$request->product,
                  'service_id'=>$request->service,
                  'question'=>$request->question,
                  'created_by'=>$request->emp_id,
                   ]);

                   DB::connection('sales_db')->table('enq_question_option')->whereIn('question_id',[$id])
                   ->update(['product_id'=>$request->product,
                   'service_id'=>$request->service,
                   'created_by'=>$request->emp_id,
                    ]);

                   return response()->json(['status'=>200,'message'=>'Question Updated Successfully']);
    
            }
            public function question_list() {
                $data = DB::connection('sales_db')
                    ->table('enquiry_question')
                    ->leftJoin('enq_question_option', 'enquiry_question.id', 'enq_question_option.question_id')
                    ->join('product', 'enquiry_question.product_id', 'product.id')
                    ->join('product_service', 'enquiry_question.service_id', 'product_service.id')
                    ->select('enquiry_question.id', 'product.product_name', 'product_service.service_name',
                        'enq_question_option.id as option_id', 'enq_question_option.option_name', 'enquiry_question.question', 'enq_question_option.status')
                    ->get();
            
                $groupedQuestions = $data->groupBy('id');
            
                $formattedQuestions = $groupedQuestions->map(function ($group) {
                    $question = $group->first();
                    $options = $group->map(function ($item) {
                        return [
                            'option_id' => $item->option_id,
                            'option_name' => $item->option_name,
                            'status' => $item->status,
                        ];
                    })->toArray();
                    if (count($options) === 1 && $options[0]['option_id'] === null && $options[0]['option_name'] === null && $options[0]['status'] === null) {
                        $options = [];
                    }
                    $question->options = $options;
                    return $question;
                });
            
                return response()->json(['status' => 200, 'data' => $formattedQuestions]);
            }
            
            public function question_option_type($id){
              $data = DB::connection('sales_db')->table('enquiry_question')->where('id',$id)->first();
              return response()->json(['status'=>200,'data'=>$data->is_option_based]);

            }

        public function option_edit($id){
            $option =  DB::connection('sales_db')->table('enq_question_option')->where('id',$id)->first();
            return response()->json(['status' => 200, 'data'=>$option]);

        }
        public function option_status($id){
            $option = DB::connection('sales_db')->table('enq_question_option')->where('id',$id)->first();
            return response()->json(['status'=>200,'data'=>$option->status]);

        }
        public function option_status_change(Request $request){
            $request->validate([
                'id' => 'required',
                'status' => 'required|in:0,1', 
            ]);
            $doc = DB::connection('sales_db')->table('enq_question_option')
               ->where('id',$request->id)->update(['status'=>$request->status]);
            return response()->json(['status' => 200, 'message' => 'Option Status updated successfully']);

        }
        public function option_update(Request $request,$id){
            $input = $request->all();
        //return $input;
        $validator = Validator::make($input, [
            'option'=>'required',

            
        ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
              DB::connection('sales_db')->table('enq_question_option')->where('id',$id)
              ->update([
              'created_by'=>$request->emp_id,
              'option_name'=>$request->option
               ]);
               return response()->json(['status'=>200,'message'=>'Option Updated Successfully']);

        }

        public function add_enq_followup(Request $request){
            $input = $request->all();
            $validator = Validator::make($input, [
                
                'name' => 'required',

                
            ]);
            
            if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }

            $data = array(
            'name'=>$request->name,
            'created_by'=>$request->emp_id);
             DB::connection('sales_db')->table('enq_followup_status')->insert($data);
             return response()->json(['status'=>200,'message'=>'Status Created Successfully']);
            }

        public function enq_followup_list(){
            $data = DB::connection('sales_db')->table('enq_followup_status')
           
            ->select('enq_followup_status.id','enq_followup_status.status',
            'enq_followup_status.name')
            ->get();
            return response()->json(['status'=>200,'data'=>$data]);
        }

        public function enq_followup_status($id){
            $enq = DB::connection('sales_db')->table('enq_followup_status')->where('id',$id)->first();
            return response()->json(['status'=>200,'data'=>$enq->status]);

        }
        public function enq_followup_status_change(Request $request){
            $request->validate([
                'id' => 'required',
                'status' => 'required|in:0,1', 
            ]);
            $doc = DB::connection('sales_db')->table('enq_followup_status')
               ->where('id',$request->id)->update(['status'=>$request->status]);
            return response()->json(['status' => 200, 'message' => 'Status updated successfully']);

        }

        public function enq_followup_status_edit($id){
            $enq =  DB::connection('sales_db')->table('enq_followup_status')->where('id',$id)->first();
            return response()->json(['status' => 200, 'data'=>$enq]);

        }
        public function enq_followup_status_update(Request $request,$id){
            $input = $request->all();
            $validator = Validator::make($input, [
                'name' => 'required',

                
            ]);
            
            if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }
              DB::connection('sales_db')->table('enq_followup_status')->where('id',$id)
              ->update([
              'name'=>$request->name,
              'created_by'=>$request->emp_id
               ]);
               return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

        }
        public function get_question_details($id){
            $data = DB::connection('sales_db')->table('enquiry_question')->where('id',$id)->first();
            return response()->json(['status'=>200,'data'=>$data]);


        }
        public function add_question_option(Request $request){
            $option = [];
            $input = $request->all();
            $validator = Validator::make($input, [
                'product' => 'required',
                'service' => 'required',
                'question' => 'required',
                'optionvalue'=>'required',

                
            ]);
            
            if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }

            foreach(json_decode($request->optionvalue) as $row){
                if($row->optionvalue!=""){
                  $option[] = array('product_id'=>$request->product,'service_id'=>$request->service,
                  'question_id'=>$request->question,
                  'option_name'=>$row->optionvalue,'created_by'=>$request->emp_id);
                }
  
              }
              DB::connection('sales_db')->table('enq_question_option')->insert($option);
              return response()->json(['status'=>200,'message'=>'Option Created Successfully']);
         }
        }