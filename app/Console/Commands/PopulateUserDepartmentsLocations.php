<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Console\Command;

class PopulateUserDepartmentsLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-user-departments-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate missing department_id and location_id for existing users with random values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $departments = Department::all();
        $locations = Location::all();

        if ($departments->isEmpty()) {
            $this->error('No departments found. Please create departments first.');
            return;
        }

        if ($locations->isEmpty()) {
            $this->error('No locations found. Please create locations first.');
            return;
        }

        $usersWithoutDepartment = User::whereNull('department_id')->count();
        $usersWithoutLocation = User::whereNull('location_id')->count();
        $usersWithoutBoth = User::whereNull('department_id')->whereNull('location_id')->count();

        $this->info("Found {$usersWithoutDepartment} users without department_id");
        $this->info("Found {$usersWithoutLocation} users without location_id");
        $this->info("Found {$usersWithoutBoth} users without both department_id and location_id");

        if (!$this->confirm('Do you want to continue with populating random department and location IDs?')) {
            return;
        }

        $bar = $this->output->createProgressBar(User::count());
        $bar->start();

        User::chunk(100, function ($users) use ($departments, $locations, $bar) {
            foreach ($users as $user) {
                $updated = false;

                if (is_null($user->department_id)) {
                    $user->department_id = $departments->random()->id;
                    $updated = true;
                }

                if (is_null($user->location_id)) {
                    $user->location_id = $locations->random()->id;
                    $updated = true;
                }

                if ($updated) {
                    $user->save();
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $remainingWithoutDepartment = User::whereNull('department_id')->count();
        $remainingWithoutLocation = User::whereNull('location_id')->count();

        $this->info("Population completed!");
        $this->info("Remaining users without department_id: {$remainingWithoutDepartment}");
        $this->info("Remaining users without location_id: {$remainingWithoutLocation}");
    }
}
