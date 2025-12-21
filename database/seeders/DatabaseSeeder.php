<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Design;
use App\Models\Grade;
use App\Models\Position;
use App\Models\Priority;
use App\Models\Quality;
use App\Models\Status;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        /* The commented out code `// \App\Models\User::factory()->create(['name' => 'Test User',
        'email' => 'test@example.com', ]);` is an example of using Laravel's model factory to create
        a new user record in the database. */


        $grades = ['ရောင်းအားအကောင်းဆုံး', 'ရောင်းအားအသင့်အတင့်', 'အမေးများဆုံး', 'ပစ္စည်းအသစ်'];
        $priorities = ['အမြန်ဆုံးမှာပေးပါ', 'သာမာန်', 'ရနိုင်လျှင်'];
        $statuses = ['မကြာသေးမီက', 'Inventory ကအသိအမှတ်ပြုသည်', 'AGM သို့တင်ပြထားသည်', 'AGM မှဝယ်ယူရန် ခွင့်ပြုပေးသည်', 'Supplier သို့မှာထားသည်', 'ပစ္စည်းရောက်ရှိပါပြီ', 'သက်ဆိုင်ရာကောင်တာသို့ ပစ္စည်းရောက်ရှိပြီး', 'ငြင်းပယ်လိုက်တယ်'];
        $roles = ['Super Admin', 'AGM', 'Inventory', 'Purchaser', 'Branch Supervisor', 'Guest'];
        $designs = ['B', 'BB', 'BN', 'BR', 'C', 'E', 'E1', 'EG', 'ES', 'FG', 'HC', 'KE', 'KE1', 'KES', 'KL', 'KP', 'KR', 'LB', 'N', 'P', 'PD', 'R', 'SP', 'X'];
        $qualities = ['999', 'S', 'A', "B", 'C', '18K', 'Dim'];
        $categories = ['Gold', '18K', 'Diamond', 'Gems'];
        $branches = ['branch 1', 'branch 2', 'branch 3', 'branch 4', 'branch 5', 'HO'];

        foreach ($branches as $branch) {
            Branch::factory()->create([
                'name' => $branch
            ]);
        }

        foreach ($grades as $grade) {
            Grade::factory()->create([
                'name' => $grade
            ]);
        }

        foreach ($priorities as $priority) {
            Priority::factory()->create([
                'name' => $priority
            ]);
        }

        foreach ($statuses as $status) {
            Status::factory()->create([
                'name' => $status
            ]);
        }

        foreach ($roles as $role) {
            // we used the position table as a role table because we mistakenly assumed using positions would be suitable.
            Position::factory()->create([
                'name' => $role
            ]);
        }

        foreach ($designs as $design) {
            Design::factory()->create([
                'name' => $design
            ]);
        }

        foreach ($qualities as $quality) {
            Quality::factory()->create([
                'name' => $quality
            ]);
        }

        foreach ($categories as $category) {
            Category::factory()->create([
                'name' => $category
            ]);
        }

        \App\Models\User::factory()->create([
            'name' => 'PiOs',
            'email' => 'pos@nexgen.com',
            'position_id' => 1,
            'branch_id' => 1,
        ]);

        // Seed departments and user-department relationships
        $this->call([
            DepartmentSeeder::class,
        ]);
    }
}
