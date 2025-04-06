<?php

namespace App\Imports;

use App\Models\AssignStock;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\BasicInfo;
Use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStartRow;

class StockImport implements ToModel,WithStartRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
       $employee = BasicInfo::where('emp_id', $row[1])->first();
        //dd($employee);
        $date = Carbon::createFromFormat('d/m/Y', $row[4])->format('Y-m-d');
        return new AssignStock([
            'department_id' =>$employee->dept_id,
            'category_id' => $row[0],
            'assign_to' => $row[1],
            'stock_id'=>$row[2],
            'quantity'=>$row[3],
            'assign_date'=>$date,
            'created_by'=>$row[5],

        ]);
    }
    public function startRow(): int
    {
        return 2;
    }
}
