<?php

namespace App\Exports;

use App\Models\Memoire;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MemoiresParStatutExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Memoire::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get();
    }

    public function headings(): array
    {
        return ['Statut', 'Nombre de mémoires'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
