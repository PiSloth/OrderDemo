<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Township;
use Illuminate\Database\Seeder;

class MyanmarRegionTownshipSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Mandalay',
                'name_mm' => 'မန္တလေး',
                'townships' => [
                    ['name' => 'Aungmyaythazan', 'name_mm' => 'အောင်မြေသာစံ'],
                    ['name' => 'Chanayethazan', 'name_mm' => 'ချမ်းအေးသာစံ'],
                    ['name' => 'Chanmyathazi', 'name_mm' => 'ချမ်းမြသာစည်'],
                    ['name' => 'Maha Aungmyay', 'name_mm' => 'မဟာအောင်မြေ'],
                    ['name' => 'Pyigyidagun', 'name_mm' => 'ပြည်ကြီးတံခွန်'],
                    ['name' => 'Amarapura', 'name_mm' => 'အမရပူရ'],
                    ['name' => 'Patheingyi', 'name_mm' => 'ပုသိမ်ကြီး'],
                    ['name' => 'Kyaukse', 'name_mm' => 'ကျောက်ဆည်'],
                    ['name' => 'Tada-U', 'name_mm' => 'တံတားဦး'],
                    ['name' => 'Pyin Oo Lwin', 'name_mm' => 'ပြင်ဦးလွင်'],
                    ['name' => 'Meiktila', 'name_mm' => 'မိတ္ထီလာ'],
                    ['name' => 'Myingyan', 'name_mm' => 'မြင်းခြံ'],
                ],
            ],
            [
                'name' => 'Sagaing',
                'name_mm' => 'စစ်ကိုင်း',
                'townships' => [
                    ['name' => 'Sagaing', 'name_mm' => 'စစ်ကိုင်း'],
                    ['name' => 'Monywa', 'name_mm' => 'မုံရွာ'],
                    ['name' => 'Shwebo', 'name_mm' => 'ရွှေဘို'],
                ],
            ],
            [
                'name' => 'Yangon',
                'name_mm' => 'ရန်ကုန်',
                'townships' => [
                    ['name' => 'Downtown', 'name_mm' => 'ကျောက်တံတား'],
                    ['name' => 'Sanchaung', 'name_mm' => 'စမ်းချောင်း'],
                    ['name' => 'Kamayut', 'name_mm' => 'ကမာရွတ်'],
                    ['name' => 'Hlaing', 'name_mm' => 'လှိုင်'],
                    ['name' => 'Bahan', 'name_mm' => 'ဗဟန်း'],
                    ['name' => 'Yankin', 'name_mm' => 'ရန်ကင်း'],
                    ['name' => 'Thingangyun', 'name_mm' => 'သင်္ဃန်းကျွန်း'],
                    ['name' => 'Insein', 'name_mm' => 'အင်းစိန်'],
                ],
            ],
            [
                'name' => 'Shan',
                'name_mm' => 'ရှမ်း',
                'townships' => [
                    ['name' => 'Taunggyi', 'name_mm' => 'တောင်ကြီး'],
                    ['name' => 'Lashio', 'name_mm' => 'လားရှိုး'],
                    ['name' => 'Kalaw', 'name_mm' => 'ကလော'],
                ],
            ],
            [
                'name' => 'Naypyidaw',
                'name_mm' => 'နေပြည်တော်',
                'townships' => [
                    ['name' => 'Zabuthiri', 'name_mm' => 'ဇမ္ဗူသီရိ'],
                    ['name' => 'Pyinmana', 'name_mm' => 'ပျဉ်းမနား'],
                ],
            ],
            [
                'name' => 'Magway',
                'name_mm' => 'မကွေး',
                'townships' => [
                    ['name' => 'Magway', 'name_mm' => 'မကွေး'],
                    ['name' => 'Pakokku', 'name_mm' => 'ပခုက္ကူ'],
                ],
            ],
            [
                'name' => 'Bago',
                'name_mm' => 'ပဲခူး',
                'townships' => [
                    ['name' => 'Bago', 'name_mm' => 'ပဲခူး'],
                    ['name' => 'Pyay', 'name_mm' => 'ပြည်'],
                ],
            ],
            [
                'name' => 'Ayeyarwady',
                'name_mm' => 'ဧရာဝတီ',
                'townships' => [
                    ['name' => 'Pathein', 'name_mm' => 'ပုသိမ်'],
                ],
            ],
        ];

        foreach ($data as $regionData) {
            $region = Region::updateOrCreate(
                ['name' => $regionData['name']],
                ['name_mm' => $regionData['name_mm']]
            );

            foreach ($regionData['townships'] as $twpData) {
                Township::updateOrCreate(
                    ['region_id' => $region->id, 'name' => $twpData['name']],
                    ['name_mm' => $twpData['name_mm']]
                );
            }
        }
    }
}
