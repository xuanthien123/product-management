<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductsExport extends DefaultValueBinder implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStrictNullComparison,
    WithColumnWidths,
    WithColumnFormatting,
    WithCustomValueBinder
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(private array $filters)
    {
    }

    public function query(): Builder
    {
        $query = Product::query()->with('categories');

        if (! empty($this->filters['search'])) {
            $query->where('name', 'LIKE', '%' . $this->filters['search'] . '%');
        }
        if (isset($this->filters['min_price'])) {
            $query->where('price', '>=', $this->filters['min_price']);
        }
        if (isset($this->filters['max_price'])) {
            $query->where('price', '<=', $this->filters['max_price']);
        }
        if (isset($this->filters['category_id'])) {
            $query->whereHas(
                'categories',
                fn ($categoryQuery) => $categoryQuery->whereKey($this->filters['category_id'])
            );
        }

        if (
            ($this->filters['sort_by'] ?? null) === 'price'
            || (! isset($this->filters['sort_by']) && isset($this->filters['sort']))
        ) {
            $query
                ->orderBy('price', $this->filters['sort'] ?? 'asc')
                ->orderByDesc('id');
        } else {
            $sortDirection = ($this->filters['sort_by'] ?? null) === 'created_at'
                ? ($this->filters['sort'] ?? 'desc')
                : 'desc';

            $query
                ->orderBy('created_at', $sortDirection)
                ->orderByDesc('id');
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Price',
            'Quantity',
            'Categories',
            'Created at',
            'Updated at',
        ];
    }

    /**
     * @param Product $product
     * @return array<int, mixed>
     */
    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->description,
            (float) $product->price,
            (int) $product->quantity,
            $product->categories->pluck('name')->implode(' | '),
            $product->created_at?->format('Y-m-d H:i:s'),
            $product->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 30,
            'C' => 45,
            'D' => 14,
            'E' => 12,
            'F' => 35,
            'G' => 20,
            'H' => 20,
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit(StringHelper::sanitizeUTF8($value), DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
