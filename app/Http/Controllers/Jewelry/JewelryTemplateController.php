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
}
