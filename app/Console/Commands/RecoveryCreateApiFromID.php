<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Api\ApiClient;
use App\User\GetUser;

class RecoveryCreateApiFromID extends Command
{
    private const OK_STATUS = 100;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:recoveryCreateApiFromID {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'recoveryCreateApiFromID';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('id');
        $get_user = new GetUser();
        $user = $get_user->getById( $id );
        if ( $user === null ) return null;
        $data['userName'] = $user->user_name;
        $data['displayName'] = $user->display_name;
        $data['tenant_id'] = $user->tenant_id;
        $api_client = new ApiClient();
        if ( $api_client->createUser( $data ) == self::OK_STATUS ) 
        {
            $user->exist_externaldb = 'true';
            $user->save();
            echo "正常に処理が終了しました。\n";
            return true;
        }
        echo "異常終了しました。\n";
        return false;
    }
}
