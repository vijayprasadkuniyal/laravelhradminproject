<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use App\Models\Sallary;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use PDF;
use File;
use DB;

class GenerateLattersController extends Controller
{
    public function generate_latters(Request $request){
        $emp_id = $request->emp_id;
        $emp_details = BasicInfo::leftjoin('department','department.id','emp_basic_info.dept_id')
                       ->leftjoin('designation','designation.id','emp_basic_info.desi_id')
                       ->leftjoin('branch_details','branch_details.id','emp_basic_info.branch_id')
                       ->select('branch_details.branch_name','designation.designation_name','department.department_name',
                       'emp_basic_info.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_lame',
                       'emp_basic_info.emp_father_name','emp_basic_info.emp_doj','emp_basic_info.emp_doc',
                       'emp_basic_info.emp_eod')
                       ->where('emp_basic_info.emp_id',$emp_id)->first();

        $emp_ctc = Sallary::where('emp_id', $emp_id)->first();
        if($emp_ctc){
            $ctc =  $emp_ctc->emp_package;
        }
        else{
            $ctc  = 0;

        }

        if($request->latter_type == 'relieving_latter'){
             $fileName = $emp_details->emp_fname . '_' . $emp_details->emp_lame . 'relieving_latter.pdf';
             $pdf = PDF::loadView('latters.relaeving_latter', compact('emp_details'));
             //return $pdf->download($fileName);
        }
        elseif($request->latter_type =='exprience_latter'){
             $fileName = $emp_details->emp_fname . '_' . $emp_details->emp_lame . '_exprience_letter.pdf';
             $pdf = PDF::loadView('latters.Exprience_latter', compact('emp_details','ctc'));
             //return $pdf->download($fileName);

        }
        elseif($request->latter_type =='confirmation_latter'){
            $fileName = $emp_details->emp_fname . '_' . $emp_details->emp_lame . '_confirmation_letter.pdf';
             $pdf = PDF::loadView('latters.confirmation_latter', compact('emp_details'));
             //return $pdf->download($fileName);

        }
        $folderPath = public_path('emp_letters');
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0777, true);
        }

        $filePath = $folderPath . '/' . $fileName;

        File::put($filePath, $pdf->output());
    
        $data_array = [
            'emp_id' => $request->emp_id,
            'created_by' => $request->created_by,
            'file_name' => url('emp_letters/' . $fileName),
        ];
    
        DB::table('download_emp_letters')->insert($data_array);
        return response($pdf->output(), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function get_emp_letters(){

        $data = DB::table('download_emp_letters')->leftjoin('emp_basic_info','emp_basic_info.emp_id',
                  'download_emp_letters.emp_id')
                  ->leftjoin('department','department.id','emp_basic_info.dept_id')
                  ->select('emp_basic_info.emp_fname','department.department_name','emp_basic_info.emp_lame','download_emp_letters.*')
                  ->orderBy('download_emp_letters.id','DESC')
                ->paginate(10);

        return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }
}
