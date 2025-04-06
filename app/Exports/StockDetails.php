<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class StockDetails implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return [
            'Category',
            'Stock',
            'Total Quantity',
            'Remaining Quantity',
            'vendor',
            'product_type',
            'branch',
            'product_id',
            'device_id',
            'Price',
            'Created By'
        ];
    }
}
