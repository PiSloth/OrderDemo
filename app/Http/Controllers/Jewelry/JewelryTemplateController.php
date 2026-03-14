<?php

namespace App\Http\Controllers\Jewelry;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class JewelryTemplateController
{
    public function download(Request $request)
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'jewelry_template_') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($tempFilePath);

        $itemsSheet = $writer->getCurrentSheet();
        $itemsSheet->setName('Items');

        $writer->addRow(Row::fromValues([
            'Branch ID',
            'Product Name',
            'Quality',
            'Gold Weight',
            'Barcode',
            'Total Weight',
            'ကျောက်ချိန်',
            'ပန်းထိမ်အလျော့တွက်',
            'ပန်းထိမ် လက်ခ',
            'ကျောက်ဖိုး',
            'အမြတ်အလျော့',
            'အမြတ်လက်ခ',
        ]));

        // Sample row (Batch Number intentionally blank)
        $writer->addRow(Row::fromValues([
            1,
            'Gold Ring',
            '၁၅ ပဲရည်',
            5.25,
            'GAR26011',
            5.75,
            0.5,
            1.2,
            150000,
            200000,
            0.12,
            10000,
        ]));

        $rulesSheet = $writer->addNewSheetAndMakeItCurrent();
        $rulesSheet->setName('Rules');

        $writer->addRow(Row::fromValues(['Jewelry Import Template - Rules']));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Headers (Row 1) must be exactly:']));
        $writer->addRow(Row::fromValues([
            'Branch ID',
            'Product Name',
            'Quality',
            'Gold Weight',
            'Barcode',
            'Total Weight',
            'ကျောက်ချိန်',
            'ပန်းထိမ်အလျော့တွက်',
            'ပန်းထိမ် လက်ခ',
            'ကျောက်ဖိုး',
            'အမြတ်အလျော့',
            'အမြတ်လက်ခ',
        ]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Branch ID column:']));
        $writer->addRow(Row::fromValues([
            'Required. Must be a valid branches.id value.',
        ]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Unique Logic (Auto-batching fingerprint):']));
        $writer->addRow(Row::fromValues([
            'Rows with identical Branch ID, Product Name, Quality, Total Weight, ပန်းထိမ်အလျော့တွက်, ပန်းထိမ် လက်ခ, and ကျောက်ချိန် will be grouped into the same batch upon upload.',
        ]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Barcode:']));
        $writer->addRow(Row::fromValues(['If provided, barcode should be unique per item.']));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Voucher limits:']));
        $writer->addRow(Row::fromValues(['Max 120 items per voucher (group).']));
        $writer->addRow(Row::fromValues(['Max 12 unique batches per voucher (group).']));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Implementation tip (fingerprint example):']));
        $writer->addRow(Row::fromValues([
            '$fingerprint = branch_id . product_name . quality . total_weight . goldsmith_deduction . goldsmith_labor_fee . kyauk_weight;',
        ]));

        $writer->close();

        return Response::download($tempFilePath, 'jewelry-import-template.xlsx')->deleteFileAfterSend(true);
    }

    public function downloadExternalMappingTemplate(Request $request)
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'jewelry_external_template_') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($tempFilePath);

        $itemsSheet = $writer->getCurrentSheet();
        $itemsSheet->setName('Mapping');

        $writer->addRow(Row::fromValues([
            'External ID',
            'Lot serial',
            'Purchase Order',
            'Quality',
            'Total Weight',
            'Kyauk Weight',
            'Gold smith detuction',
            'Labor-fee',
            'ကျောက်ဖိုး',
        ]));

        // Sample row
        $writer->addRow(Row::fromValues([
            'EXT-000001',
            'LOT-001',
            'PO-2026-0001',
            '၁၅ ပဲရည်',
            5.750,
            0.500,
            1.200,
            150000,
            75000,
        ]));

        $rulesSheet = $writer->addNewSheetAndMakeItCurrent();
        $rulesSheet->setName('Rules');

        $writer->addRow(Row::fromValues(['Jewelry External Mapping Template - Rules']));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Headers (Row 1) must be exactly:']));
        $writer->addRow(Row::fromValues([
            'External ID',
            'Lot serial',
            'Purchase Order',
            'Quality',
            'Total Weight',
            'Kyauk Weight',
            'Gold smith detuction',
            'Labor-fee',
            'ကျောက်ဖိုး',
        ]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Match logic:']));
        $writer->addRow(Row::fromValues([
            'A row matches an existing item when Purchase Order + Quality + Total Weight + Kyauk Weight + Gold smith detuction + Labor-fee all match exactly, and stone price matches using the rule below. (Quality may be mapped/normalized before matching.)',
        ]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Stone price rule:']));
        $writer->addRow(Row::fromValues([
            'The template uses the UI value for stone price (half). The system matches by (file stone price × 2) == stored stone_price.',
        ]));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Update action:']));
        $writer->addRow(Row::fromValues([
            'When matched, the system updates external_id and lot/serial on the matched item(s).',
        ]));

        $writer->close();

        return Response::download($tempFilePath, 'jewelry-external-mapping-template.xlsx')->deleteFileAfterSend(true);
    }
}
