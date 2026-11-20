<?php

namespace App\Http\Controllers;

use App\Models\Category;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportTemplateController extends Controller
{
    public function download(): StreamedResponse
    {
        $spreadsheet = IOFactory::load(resource_path('templates/products-import-template.xlsm'));
        $categorySheet = $spreadsheet->getSheetByName('Categories');

        if ($categorySheet === null) {
            throw new \RuntimeException('The product import template is missing its category list sheet.');
        }

        $categoryNames = Category::query()->orderBy('name')->pluck('name')->all();
        $categoryList = $spreadsheet->getDefinedName('CategoryList');

        if ($categoryList === null) {
            throw new \RuntimeException('The product import template is missing its category dropdown range.');
        }

        foreach (range(2, max(1001, count($categoryNames) + 1)) as $row) {
            $categorySheet->setCellValue("A{$row}", null);
        }

        foreach ($categoryNames as $index => $categoryName) {
            $categorySheet->setCellValueExplicit('A' . ($index + 2), $categoryName, DataType::TYPE_STRING);
        }

        $lastCategoryRow = max(2, count($categoryNames) + 1);
        $categoryList->setValue('Categories!$A$2:$A$' . $lastCategoryRow);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(
            static fn () => $writer->save('php://output'),
            'products-import-template.xlsm',
            ['Content-Type' => 'application/vnd.ms-excel.sheet.macroEnabled.12']
        );
    }
}
