<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class BranchDetails implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
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
            'ID',
            'Branch',
        ];
    }
}
