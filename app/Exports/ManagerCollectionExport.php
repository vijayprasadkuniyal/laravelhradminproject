<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ManagerCollectionExport implements FromCollection, WithHeadings, WithMapping
{
    protected $managers;

    public function __construct($managers)
    {
        $this->managers = $managers;
    }

    public function collection()
    {
        $collection = collect();

        foreach ($this->managers as $manager) {
            // Add summary data for each manager
            $collection->push([
                'Manager Name' => $manager['manager_name'],
                'Total Paid' => $manager['total']->total_paid,
                'Total New' => $manager['total']->total_new,
                'Total Renew' => $manager['total']->total_renew,
                'Total Due' => $manager['total']->total_due,
                'Total Retention' => $manager['total']->total_retention,
                'Details' => '', // Placeholder for details
            ]);

            // Add detailed data for each manager
            foreach ($manager['details'] as $detail) {
                $collection->push([
                    'Manager Name' => '', // Leave blank for detail rows
                    'Total Paid' => $detail->total_paid,
                    'Total New' => $detail->total_new,
                    'Total Renew' => $detail->total_renew,
                    'Total Due' => $detail->total_due,
                    'Total Retention' => $detail->total_retention,
                    'Details' => $detail->product_name . ' (' . $detail->group_name . ')', // Combine details as needed
                ]);
            }
        }

        return $collection;
    }

    public function headings(): array
    {
        return [
            'Manager Name',
            'Total Paid',
            'Total New',
            'Total Renew',
            'Total Due',
            'Total Retention',
            'Details',
        ];
    }

    public function map($row): array
    {
        return [
            $row['manager_name'],
            $row['total']->total_paid,
            $row['total']->total_new,
            $row['total']->total_renew,
            $row['total']->total_due,
            $row['total']->total_retention,
            $row['details'] ?? '',
        ];
    }
}
