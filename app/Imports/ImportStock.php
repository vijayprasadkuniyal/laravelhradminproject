<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Stock;
use Maatwebsite\Excel\Concerns\WithStartRow;
use DB;

class ImportStock implements ToCollection,WithStartRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	foreach ($collection as $row) {
            if($row[4] == ''){
                $status = 0;
            }
            else{
                $status = $row[4];
            }
            if($row[9] == ''){
                $half_day = 0;
            }
            else{
                 $half_day = $row[9];
            }
           
            DB::table('attendance')->insert([
                'emp_id' => $row[0],     
                'attendance_date' => $row[1],
                'login_date_time' => $row[2],
                'logout_date_time'=>$row[3],
                'status'=>$status,
                'leave_status'=>$row[5],
                'leave_type'=>$row[6],
                'weekday'=>$row[7],
                'total_hours'=>$row[8],
                'is_half_day'=> $half_day,
                'created_date'=>$row[10],

            ]);
        }
        
    }
    public function startRow(): int
    {
        return 2;
    }
}
