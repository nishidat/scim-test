<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Oauth\EditOauth;

class CreateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createToken {tenant_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'createToken';

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
        $tenant_id = $this->argument('tenant_id');
        $edit_auth = new EditOauth();
        if ( $edit_auth->createTokenBytenantID( $tenant_id ) ) 
        {
            var_dump( $edit_auth->getBytenantID( $tenant_id ) );
            return true;
        }
        echo "シークレットトークンの作成に失敗しました。\n";
        return false;
    }
}
