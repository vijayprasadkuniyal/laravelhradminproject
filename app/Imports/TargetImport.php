<?php

namespace App\Imports;

use App\Models\CompanyTarget;
use App\Models\MonthlyTarget;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use DB;

class TargetImport implements ToModel, WithStartRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function model(array $row)
    {
        $companyTarget = new CompanyTarget([
            'attribute_id' => $row[0],
            'group_id' => $row[1],
            'category_id' => $row[2],
            'department_id' => $row[3],
            'value' => $row[4],
            'created_by' => $row[5],
            'financial_year'=>$row[6],
        ]);

        $companyTarget->save();

        $monthlyTargets = $this->calculateMonthlyTargets($row[4]);

        foreach ($monthlyTargets as $month => $target) {
            DB::table('company_monthly_target')->insert([
                'attribute_id' => $row[0],
                'department_id' => $row[3],
                'group_id' => $row[1],
                'category_id' => $row[2],
                'month' => $month,
                'value' =>round($target),
                'created_by' => $row[5],
                'financial_year'=>$row[6],
            ]);
        }

        return $companyTarget;
    }

    public function startRow(): int
    {
        return 2;
    }

    private function calculateMonthlyTargets($yearlyTarget)
    {
        $monthlyTarget = $yearlyTarget / 12;

        $months = [
            'April', 'May', 'June', 'July', 'August', 'September',
            'October', 'November', 'December', 'January', 'February', 'March'
        ];

        $monthlyTargets = [];
        foreach ($months as $month) {
            $monthlyTargets[$month] = $monthlyTarget;
        }

        return $monthlyTargets;
    }
}