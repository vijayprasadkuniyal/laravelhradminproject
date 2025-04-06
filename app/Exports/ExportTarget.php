<?php

namespace App\Exports;

use App\Models\CompanyTarget;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportTarget implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return CompanyTarget::select('attribute_id','group_id','category_id','department_id','value','created_by')->get();

    }
    public function headings(): array
    {
        return ["attribute_id", "group_id", "category_id",'department_id','value','created_by'];
    }
}
