<?php

namespace App\Exports;

use App\Models\AssignStock;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportStock implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return AssignStock::select('category_id','assign_to','stock_id','quantity','assign_date','created_by')->get();

    }
    public function headings(): array
    {
        return ["category", "emp_id", "stock_id",'quantity','assign_date','created_by'];
    }
}


