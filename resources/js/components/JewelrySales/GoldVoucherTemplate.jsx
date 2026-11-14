import React from 'react';
import { usePage } from '@inertiajs/react';

const KYAT_TO_GRAM = 16.606;

/**
 * Convert gram weight to Myanmar traditional units (Kyat, Pae, Yway).
 * 1 Kyat = 16.606 g, 1 Kyat = 16 Pae, 1 Pae = 8 Yway.
 * Yway is rounded to 2 decimal places with carry-over at 8 Yway / 16 Pae.
 */
export function gramToMyanmarUnits(gramValue) {
    const gram = Number(gramValue);
    if (!Number.isFinite(gram) || gram <= 0) {
        return { kyat: 0, pae: 0, yway: 0 };
    }

    const totalKyat = gram / KYAT_TO_GRAM;
    let kyat = Math.floor(totalKyat);
    const remAfterKyat = (totalKyat - kyat) * 16;
    let pae = Math.floor(remAfterKyat);
    let yway = Math.round((remAfterKyat - pae) * 8 * 100) / 100;

    if (yway >= 8) {
        yway = Math.round((yway - 8) * 100) / 100;
        pae += 1;
    }
    if (pae >= 16) {
        pae -= 16;
        kyat += 1;
    }

    return { kyat, pae, yway };
}

function formatNumber(value, maxDecimals = 3) {
    const n = Number(value);
    if (!Number.isFinite(n) || n === 0) return '';
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: maxDecimals }).format(n);
}

function formatYway(value) {
    const n = Number(value);
    if (!Number.isFinite(n) || n === 0) return '';
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(n);
}

function formatDisplayDate(value) {
    if (!value) return '';
    const str = String(value);
    // Extract YYYY-MM-DD from ISO or datetime strings
    const match = str.match(/^(\d{4}-\d{2}-\d{2})/);
    return match ? match[1] : str;
}

function formatReprintDate(date = new Date()) {
    const day = String(date.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[date.getMonth()];
    const year = String(date.getFullYear()).slice(-2);
    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
}

export default function GoldVoucherTemplate({ sale, voucherRef }) {
    const { auth } = usePage().props;
    if (!sale) return null;

    const items = Array.isArray(sale.items) ? sale.items : [];
    const customerName = sale.customer_name_snapshot || sale.customer?.name || '';
    const saleDate = formatDisplayDate(sale.sale_at);
    const address = sale.customer_address_snapshot || '';
    const goldPrice = Number(sale.gold_price || 0);
    const phone = sale.customer_phone_snapshot || '';
    const rawBranchName = auth?.user?.branch?.name || sale.branch_name_raw || sale.branch?.name || '';
    const branchName = rawBranchName ? rawBranchName.charAt(0).toUpperCase() + rawBranchName.slice(1) : '';
    const reprintDate = formatReprintDate();
    const reprintBy = auth?.user?.name || '';

    const itemsTotalAmount = items.reduce((sum, item) => sum + Number(item.item_amount || 0), 0);
    const itemsTotalWeightG = items.reduce(
        (sum, item) => sum + Number(item.item_total_weight_g || 0),
        0
    );

    const totalGram = Number(sale.invoice_total_weight_g || 0) || itemsTotalWeightG;
    const totalAmount = itemsTotalAmount || Number(sale.invoice_total || 0);
    const grandTotal = Number(sale.invoice_total || 0) || totalAmount;

    const thBase = 'border border-neutral-700 px-1 py-1 text-center align-middle font-semibold';
    const thSub = 'border border-neutral-700 px-1 py-1 text-center align-middle font-semibold w-[0.4in]';

    return (
        <section
            ref={voucherRef}
            className="mx-auto bg-white text-neutral-900 border border-neutral-200 shadow-sm print:m-0 print:border-0 print:shadow-none"
            style={{
                width: '210mm',
                minHeight: '297mm',
                padding: '12mm',
                fontFamily:
                    '"Pyidaungsu", "Noto Sans Myanmar", "Myanmar Text", system-ui, -apple-system, "Segoe UI", Arial, sans-serif',
            }}
        >
            {/* Voucher Header */}
            <div className="mb-3">
                <div className="flex justify-between items-center text-xs text-neutral-700">
                    <div>{reprintBy ? `Reprint by ${reprintBy}` : ''}</div>
                    <div>
                        Reprint Voucher - {reprintDate}{branchName ? ` at ${branchName}` : ''}
                    </div>
                </div>
                <div className="mt-24 mb-2 text-center text-[14px] font-bold py-2 border-y border-neutral-300 bg-gray-200">
                    ရွှေထည်အရောင်းဘောင်ချာ
                </div>
                <div className="grid grid-cols-2 gap-x-6 gap-y-2 text-sm mt-3">
                    <div className="grid grid-cols-[90px_1fr] gap-2 items-baseline">
                        <div>အမည်</div>
                        <div className="pb-0.5 min-h-[18px]">
                            {customerName}
                        </div>
                    </div>
                    <div className="grid grid-cols-[90px_1fr] gap-2 items-baseline">
                        <div>နေ့စွဲ</div>
                        <div className="pb-0.5 min-h-[18px]">
                            {saleDate}
                        </div>
                    </div>
                    <div className="grid grid-cols-[90px_1fr] gap-2 items-baseline">
                        <div>နေရပ်လိပ်စာ</div>
                        <div className="pb-0.5 min-h-[18px]">
                            {address}
                        </div>
                    </div>
                    <div className="grid grid-cols-[90px_1fr] gap-2 items-baseline">
                        <div>ရွှေစျေး</div>
                        <div className="pb-0.5 min-h-[18px]">
                            {formatNumber(goldPrice, 2)}
                        </div>
                    </div>
                    <div className="grid grid-cols-[90px_1fr] gap-2 items-baseline">
                        <div>ဖုန်းနံပါတ်</div>
                        <div className="pb-0.5 min-h-[18px]">
                            {phone}
                        </div>
                    </div>
                    <div className="grid grid-cols-[90px_1fr] gap-2 items-baseline">
                        <div>ဘောင်ချာနံပါတ်</div>
                        <div className="pb-0.5 min-h-[18px]">
                            {sale.sale_number || ''}
                        </div>
                    </div>
                </div>
            </div>

            {/* Items Table */}
            <table className="w-full border-collapse table-fixed text-xs">
                <colgroup>
                    <col style={{ width: '0.35in' }} />
                    <col style={{ width: '1in' }} />
                    <col style={{ width: '1in' }} />
                    <col style={{ width: '0.7in' }} />
                    <col style={{ width: '0.4in' }} />
                    <col style={{ width: '0.4in' }} />
                    <col style={{ width: '0.4in' }} />
                    <col style={{ width: '0.6in' }} />
                    <col style={{ width: '0.4in' }} />
                    <col style={{ width: '0.4in' }} />
                    <col style={{ width: '0.4in' }} />
                    <col style={{ width: '0.8in' }} />
                </colgroup>
                <thead>
                    <tr>
                        <th rowSpan={2} className={`${thBase} w-[0.35in]`}>
                            စဉ်
                        </th>
                        <th rowSpan={2} className={`${thBase} w-[1in]`}>
                            Code No
                        </th>
                        <th rowSpan={2} className={`${thBase} w-[1in]`}>
                            ပစ္စည်းအမည်
                        </th>
                        <th rowSpan={2} className={`${thBase} w-[0.7in]`}>
                            အထည်ချိန်
                            <br />
                            <span className="font-semibold">Gram</span>
                        </th>
                        <th colSpan={3} className={thBase}>
                            ရွှေချိန်
                        </th>
                        <th rowSpan={1} className={`${thBase} w-[0.9in]`}>
                            လက်ခ
                        </th>
                        <th rowSpan={2} colSpan={3} className={thBase}>
                            အလျော့တွက်
                        </th>
                        <th rowSpan={2} className={`${thBase} w-[0.8in]`}>
                            သင့်ငွေ
                        </th>
                    </tr>
                    <tr>
                        <th colSpan={3} className={thSub}>
                            ကျောက်ချိန်
                        </th>
                        <th colSpan={1} className={`${thSub} w-[0.8in]`}>
                            ကျောက်ဖိုး
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item, idx) => {
                        const goldG = Number(item.gold_weight_g || 0);
                        const stoneG = Number(item.stone_weight_g || 0);
                        const decreaseG = Number(item.decrease_weight_g || 0);
                        // အထည်ချိန် = gold_weight_g + stone_weight_g (not containing decrease)
                        const itemWeightG = goldG + stoneG > 0 ? goldG + stoneG : Number(item.item_weight_g || 0);

                        const goldKpy = gramToMyanmarUnits(goldG);
                        const stoneKpy = gramToMyanmarUnits(stoneG);
                        const decreaseKpy = gramToMyanmarUnits(decreaseG);

                        const productName = item.product?.product_name || item.product?.item_name || '';

                        return (
                            <React.Fragment key={item.id ?? idx}>
                                <tr className="h-6">
                                    <td
                                        rowSpan={2}
                                        className="border border-neutral-700 px-1 py-1 align-middle text-center"
                                    >
                                        {idx + 1}
                                    </td>
                                    <td
                                        rowSpan={2}
                                        className="border border-neutral-700 px-1 py-1 align-middle text-left break-all whitespace-normal"
                                    >
                                        {item.barcode || '\u00A0'}
                                    </td>
                                    <td
                                        rowSpan={2}
                                        className="border border-neutral-700 px-1 py-1 align-middle text-left break-words whitespace-normal"
                                    >
                                        {productName || '\u00A0'}
                                    </td>
                                    <td
                                        rowSpan={2}
                                        className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums"
                                    >
                                        {formatNumber(itemWeightG, 3) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(goldKpy.kyat, 0) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(goldKpy.pae, 0) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatYway(goldKpy.yway) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(item.labor_fee, 2) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(decreaseKpy.kyat, 0) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(decreaseKpy.pae, 0) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatYway(decreaseKpy.yway) || '\u00A0'}
                                    </td>
                                    <td
                                        rowSpan={2}
                                        className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums"
                                    >
                                        {formatNumber(item.item_amount, 2) || '\u00A0'}
                                    </td>
                                </tr>
                                <tr className="h-6">
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(stoneKpy.kyat, 0) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(stoneKpy.pae, 0) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatYway(stoneKpy.yway) || '\u00A0'}
                                    </td>
                                    <td className="border border-neutral-700 px-1 py-1 align-middle text-right tabular-nums h-6">
                                        {formatNumber(item.stone_amount, 2) || '\u00A0'}
                                    </td>
                                    <td
                                        colSpan={3}
                                        className="border border-neutral-700 px-1 py-1 align-middle text-center font-semibold h-6"
                                    >
                                        {'\u00A0'}
                                    </td>
                                </tr>
                            </React.Fragment>
                        );
                    })}
                </tbody>
            </table>

            {/* Totals & Footer section */}
            <div className="mt-3 grid grid-cols-[1fr_280px] gap-4 items-start text-xs">
                <div className="space-y-2 leading-relaxed text-neutral-800">
                    {sale.remark && (
                        <div className="whitespace-pre-line">{sale.remark}</div>
                    )}

                    <div>ဆိုင်ထည်နဲ့ ပြင်ပထည်များကို အလဲအထပ်လုပ်ပေးပါသည်။</div>

                    <div className="flex items-center gap-2 pt-1">
                        <span>ယခင် အခွန်ဆောင်ပြီး ဘောင်ချာနံပါတ်</span>
                        <span className="inline-block border border-neutral-800 h-6 w-32"></span>
                    </div>

                    <div className="grid grid-cols-[100px_1fr] gap-2 pt-1">
                        <div>စာရင်းသွင်းသူ</div>
                        <div>{sale.input_staff?.name || ''}</div>
                    </div>
                    <div className="grid grid-cols-[100px_1fr] gap-2">
                        <div>အရောင်းတာဝန်ခံ</div>
                        <div>{sale.duty_staff?.name || ''}</div>
                    </div>

                    <div className="pt-2 font-medium">ပြည့်မှီကြောင်းအာမခံပါသည်။</div>
                </div>

                <div>
                    <div
                        className="border border-neutral-800"
                        style={{ width: '280px' }}
                        aria-label="Totals"
                    >
                        <div className="grid grid-cols-3">
                            <div className="border-r border-neutral-800 p-2 text-xs font-semibold">စုစုပေါင်း</div>
                            <div className="p-2 text-xs text-right tabular-nums">
                                {new Intl.NumberFormat('en-US', { maximumFractionDigits: 3 }).format(totalGram)} g
                            </div>
                            <div className="p-2 text-xs text-right tabular-nums">
                                {new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(totalAmount)}
                            </div>
                        </div>
                        <div className="grid grid-cols-3 border-t border-neutral-800">
                            <div className="border-r border-neutral-800 p-2 text-xs font-semibold">ကုန်သွယ်ခွန်</div>
                            <div className="p-2 text-xs"></div>
                            <div className="p-2 text-xs text-right tabular-nums"></div>
                        </div>
                        <div className="grid grid-cols-3 border-t border-neutral-800">
                            <div className="border-r border-neutral-800 p-2 text-xs font-semibold">Discount</div>
                            <div></div>
                            <div className="p-2 text-xs text-right tabular-nums"></div>
                        </div>
                        <div className="grid grid-cols-3 border-t border-neutral-800">
                            <div className="border-r border-neutral-800 p-2 text-xs font-semibold">စုစုပေါင်းသင့်ငွေ</div>
                            <div></div>
                            <div className="p-2 text-xs text-right tabular-nums">
                                {new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(grandTotal)}
                            </div>
                        </div>
                        <div className="grid grid-cols-3 border-t border-neutral-800">
                            <div className="border-r border-neutral-800 p-2 text-xs font-semibold">လက်ခံ ရရှိငွေ</div>
                            <div></div>
                            <div className="p-2 text-xs text-right tabular-nums">
                                {new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(grandTotal)}
                            </div>
                        </div>
                        <div className="grid grid-cols-3 border-t border-neutral-800">
                            <div className="border-r border-neutral-800 p-2 text-xs font-semibold">ပိုငွေ/ လိုငွေ</div>
                            <div></div>
                            <div className="p-2 text-xs text-right tabular-nums"></div>
                        </div>
                    </div>

                    <div className="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div className="border border-neutral-800 py-1 px-1 min-h-[42px]">ပစ္စည်းပေးပြီး</div>
                        <div className="border border-neutral-800 py-1 px-1 min-h-[42px]">ငွေရှင်းပြီး</div>
                        <div className="border border-neutral-800 py-1 px-1 min-h-[42px]">စစ်ဆေးပြီး</div>
                    </div>
                </div>
            </div>

            <div className="text-center mt-6 text-xs text-neutral-800">
                စီးပွားလာဘ်လာဘများ ဒီရေအလား တိုးပွားအောင်မြင်ပါစေ
            </div>
        </section>
    );
}
