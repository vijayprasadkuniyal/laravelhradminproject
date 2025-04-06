<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Validator;

class CategoryController extends Controller
{
    public function create_category(Request $request){
        $input = $request->all();
            $validator = Validator::make($input, [
                'category_name' => 'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $category = new Category();
            $category->category_name = $request->category_name;
            $category->created_by = $request->emp_id;
            $category->save();
            if($category){
                return response()->json(['status'=>200,'message'=>'Category Created Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in category creation']);
             }
          }
       public function category_list(){
        $category_list = Category::get(['id','category_name','status']);
        return response()->json(['status'=>200,'message'=>'Category List','data'=>$category_list]);
       }
       public function category_edit($id){
        $category = Category::findOrFail($id);
        return response()->json(['status'=>200,'message'=>'Category List','category'=>$category]);
      }
    public function category_update(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'category_name' => 'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
        $category = Category::findOrFail($id);
        $category->category_name = $request->category_name;
        $category->created_by = $request->emp_id;
        $category->save();
        if($category){
            return response()->json(['status'=>200,'message'=>'Category Updated Successfully']);
        }
        else{
            return response()->json(['message'=>'Issue in category creation']);
         }

    }
    public function category_status($id){
        $category = Category::findOrFail($id);
       if($category){
        return response()->json(['status' => 200, 'message' => 'Category status','data'=>$category->status]);
      }
     else{
        return response()->json(['status' =>500, 'message' => 'data not found']);
      }

    }
    public function update_category_status(Request $request){
        $request->validate([
            'category_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        $category = Category::findOrFail($request->category_id);
        $category->status = $request->status;
        $category->save();
        if($category){
            return response()->json(['status' => 200, 'message' => 'category status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }

    }
}
