import React from 'react';
import { usePage } from '@inertiajs/react';

function formatNumber(value, maxDecimals = 2) {
    const n = Number(value);
    if (!Number.isFinite(n) || n === 0) return '';
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: maxDecimals }).format(n);
}

function formatDisplayDate(value) {
    if (!value) return '';
    const str = String(value);
    const match = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) return str;
    return `${match[2]}/${match[3]}/${match[1]}`;
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

export default function Voucher18KTemplate({ sale, voucherRef }) {
    const { auth } = usePage().props;
    if (!sale) return null;

    const items = Array.isArray(sale.items) ? sale.items : [];
    const customerName = sale.customer_name_snapshot || sale.customer?.name || '';
    const saleDate = formatDisplayDate(sale.sale_at);
    const address = sale.customer_address_snapshot || '';
    const phone = sale.customer_phone_snapshot || '';
    const goldPrice = Number(sale.gold_price || 0);
    const rawBranchName = auth?.user?.branch?.name || sale.branch_name_raw || sale.branch?.name || '';
    const branchName = rawBranchName ? rawBranchName.charAt(0).toUpperCase() + rawBranchName.slice(1) : '';
    const reprintDate = formatReprintDate();
    const reprintBy = auth?.user?.name || '';

    const itemsTotalAmount = items.reduce((sum, item) => sum + Number(item.item_amount || 0), 0);
    const itemsTotalWeightG = items.reduce(
        (sum, item) => sum + Number(item.item_total_weight_g || item.item_weight_g || 0),
        0
    );

    const totalGram = Number(sale.invoice_total_weight_g || 0) || itemsTotalWeightG;
    const totalAmount = itemsTotalAmount || Number(sale.invoice_total || 0);
    const grandTotal = Number(sale.invoice_total || 0) || totalAmount;

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
            {/* Header */}
            <div className="mb-3">
                <div className="flex justify-between items-center text-xs text-neutral-700">
                    <div>{reprintBy ? `Reprint by ${reprintBy}` : ''}</div>
                    <div>
                        Reprint Voucher - {reprintDate}
                        {branchName ? ` at ${branchName}` : ''}
                    </div>
                </div>

                <div className="mt-24 mb-2 text-center text-[15px] font-bold py-1.5 bg-neutral-500 text-neutral-900">
                    အာမခံအရောင်းဘောက်ချာ
                </div>

                <div className="text-right text-xs font-bold mb-1">18K</div>

                <div className="grid grid-cols-2 gap-x-8 gap-y-1.5 text-xs">
                    <div className="space-y-1.5">
                        <div className="grid grid-cols-[85px_1fr] gap-2 items-baseline">
                            <div className="font-semibold">အမည်</div>
                            <div>{customerName}</div>
                        </div>
                        <div className="grid grid-cols-[85px_1fr] gap-2 items-baseline">
                            <div className="font-semibold">နေရပ်လိပ်စာ</div>
                            <div>{address}</div>
                        </div>
                        <div className="grid grid-cols-[85px_1fr] gap-2 items-baseline">
                            <div className="font-semibold">ဖုန်းနံပါတ်</div>
                            <div>{phone}</div>
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <div className="grid grid-cols-[95px_1fr] gap-2 items-baseline">
                            <div className="font-semibold">နေ့စွဲ</div>
                            <div>{saleDate}</div>
                        </div>
                        <div className="grid grid-cols-[95px_1fr] gap-2 items-baseline">
                            <div className="font-semibold">အရောင်းအမှတ်</div>
                            <div>{sale.sale_number || ''}</div>
                        </div>
                        {goldPrice > 0 && (
                            <div className="grid grid-cols-[95px_1fr] gap-2 items-baseline">
                                <div className="font-semibold">ရွှေစျေး</div>
                                <div>{formatNumber(goldPrice, 2)}</div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Items Table */}
            <table className="w-full border-collapse text-xs mt-2">
                <thead>
                    <tr>
                        <th className="border border-neutral-800 px-2 py-1.5 text-center font-semibold w-[40px]">
                            စဉ်
                        </th>
                        <th className="border border-neutral-800 px-2 py-1.5 text-center font-semibold w-[125px]">
                            Code No.
                        </th>
                        <th className="border border-neutral-800 px-2 py-1.5 text-center font-semibold">
                            ပစ္စည်းအမည်
                        </th>
                        <th className="border border-neutral-800 px-2 py-1.5 text-center font-semibold w-[65px]">
                            Gram
                        </th>
                        <th className="border border-neutral-800 px-2 py-1.5 text-center font-semibold w-[95px]">
                            ဈေးနှုန်း
                        </th>
                        <th className="border border-neutral-800 px-2 py-1.5 text-center font-semibold w-[100px]">
                            သင့်ငွေ
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {items.map((item, idx) => {
                        const gram = Number(item.item_total_weight_g || item.item_weight_g || 0);
                        const amount = Number(item.item_amount || 0);
                        const unitRate = gram > 0 ? Math.round(amount / gram) : 0;
                        const productName = item.product?.product_name || item.product?.item_name || '';

                        return (
                            <tr key={item.id ?? idx}>
                                <td className="border border-neutral-800 px-2 py-1.5 text-center align-top">
                                    {idx + 1}
                                </td>
                                <td className="border border-neutral-800 px-2 py-1.5 text-left align-top break-all">
                                    {item.barcode || ''}
                                </td>
                                <td className="border border-neutral-800 px-2 py-1.5 text-left align-top">
                                    {productName}
                                </td>
                                <td className="border border-neutral-800 px-2 py-1.5 text-right align-top tabular-nums">
                                    {gram > 0 ? gram.toFixed(2) : ''}
                                </td>
                                <td className="border border-neutral-800 px-2 py-1.5 text-right align-top tabular-nums">
                                    {formatNumber(unitRate, 0)}
                                </td>
                                <td className="border border-neutral-800 px-2 py-1.5 text-right align-top tabular-nums">
                                    {formatNumber(amount, 0)}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>

            {/* Bottom Policy + Totals Section */}
            <div className="mt-3 grid grid-cols-[1fr_270px] gap-4 items-start text-xs">
                {/* Left Column: Remark & Staff */}
                <div className="space-y-2 leading-relaxed">
                    {sale.remark && (
                        <div className="whitespace-pre-line">{sale.remark}</div>
                    )}

                    <div>ဆိုင်ထည်နဲ့ ပြင်ပထည်များကို အလဲအထပ်လုပ်ပေးပါသည်။</div>

                    <div className="flex items-center gap-2 pt-1">
                        <span>ယခင် အခွန်ဆောင်ပြီးဘောက်ချာနံပါတ်</span>
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

                {/* Right Column: Totals + Status Boxes */}
                <div>
                    <div className="border border-neutral-800">
                        <div className="grid grid-cols-[105px_65px_1fr]">
                            <div className="border-r border-neutral-800 px-2 py-1.5 font-semibold">
                                စုစုပေါင်း
                                <br />
                                စိုက်ရွှေ ./ပေးရွှေ .
                            </div>
                            <div className="px-1.5 py-1.5 text-right tabular-nums">
                                <div>{totalGram.toFixed(2)} g</div>
                                <div>0.00 g</div>
                            </div>
                            <div className="px-2 py-1.5 text-right tabular-nums">
                                {formatNumber(totalAmount, 0)}
                            </div>
                        </div>
                        <div className="grid grid-cols-[105px_1fr] border-t border-neutral-800">
                            <div className="border-r border-neutral-800 px-2 py-1 font-semibold">Discount</div>
                            <div className="px-2 py-1 text-right tabular-nums"></div>
                        </div>
                        <div className="grid grid-cols-[105px_1fr] border-t border-neutral-800">
                            <div className="border-r border-neutral-800 px-2 py-1 font-semibold">
                                စုစုပေါင်းသင့်ငွေ .
                            </div>
                            <div className="px-2 py-1 text-right tabular-nums font-semibold">
                                {formatNumber(grandTotal, 0)}
                            </div>
                        </div>
                        <div className="grid grid-cols-[105px_1fr] border-t border-neutral-800">
                            <div className="border-r border-neutral-800 px-2 py-1 font-semibold">
                                လက်ခံရရှိငွေ .
                            </div>
                            <div className="px-2 py-1 text-right tabular-nums">
                                {formatNumber(grandTotal, 0)}
                            </div>
                        </div>
                        <div className="grid grid-cols-[105px_1fr] border-t border-neutral-800">
                            <div className="border-r border-neutral-800 px-2 py-1 font-semibold">
                                ပိုငွေ ./လိုငွေ .
                            </div>
                            <div className="px-2 py-1 text-right tabular-nums"></div>
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
                စီးပွားရေးလာဘ်လာဘ ဒီရေအလား တိုးပွားအောင်မြင်ပါစေ။
            </div>
        </section>
    );
}
