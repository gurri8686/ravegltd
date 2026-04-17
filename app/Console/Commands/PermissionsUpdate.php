<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class PermissionsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:update';

    protected $permissions = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (\Route::getRoutes()->getRoutes() as $route) {
            $action = $route->getAction();

            if (array_key_exists('as', $action)) {

                $permission = Permission::where('name', $action['as'])->where('guard_name', 'web')->first();

                if(empty($permission)){
                    $this->permissions[] = $action['as'];
                    $route_name = [
                        'name' => $action['as'],
                        'guard_name' => 'web' ,
                        'permission_module_id' => 0,
                        'permission_group_id' => 0 ,
                        'created_at' => date('y-m-d H:i:s'),
                        'updated_at' => date('y-m-d H:i:s')
                    ];
                    Permission::insert($route_name);
                }
            }
        }
        echo "Permissions Added\n";
        print_r($this->permissions);
    }
}
