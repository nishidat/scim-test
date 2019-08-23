<?php

namespace App\Api;
use App\Model\User;
use Log;

class ApiClient
{
    private const HOST_URL = 'http://luck-gw.mail-luck.jp/v2/Users';
    private const SUCCESS_HTTP_STATUS = 200;
    private const ALREADY_REGIST_MAIL_STATUS = 4001001;
    private const NOT_EXIST_MAIL_STATUS = 4000004;
    private const ALREADY_REGIST_NAME_STATUS = 4003001;
    private const SUCCESS = 'OK';
    private const OK_STATUS = 100;
    private const NG_STATUS = 200;
    private const OTHER_STATUS = 300;
    private $headers = 
    [
        'Authorization' => 'Basic YXBpdGVzdEBtYWlsLWx1Y2suanA6UGFzc1dvcmQ=',
        'Content-Type' => 'application/json',
    ];
    
    /**
     * [createUser API]
     * @param  array  $data      [登録内容]
     * 
     * @return string $res_code  [結果]
     */
    public function createUser( array $data ): string
    {
        try 
        {
            $client = new \GuzzleHttp\Client();
            $request_body = [
                        'headers' => $this->headers,
                        'json' => 
                        [
                            'request-data'=> 
                            [
                                'userName' => $data['userName'],
                                'name' => $data['displayName'],
                                'tenantId' => $data['tenant_id'],
                            ]
                        ]
                    ];
            Log::debug( 'APIリクエスト' );
            Log::debug( print_r( $request_body, true ) );
            $response = $client->request( 'POST', self::HOST_URL, $request_body);
            $response_body = json_decode( $response->getBody(), true );
            Log::debug( 'APIレスポンス' );
            Log::debug( print_r( $response_body, true ) );
            if ( $response_body['response-data']['status'] != self::SUCCESS ) 
            {
                
            }
            
            return self::OK_STATUS;
        }
        catch ( \GuzzleHttp\Exception\ClientException $e ) 
        {
            $response =$e->getResponse();
            if ( isset( $response )) 
            {
                $response_body = json_decode( $response->getBody(), true );
                switch ( $response_body['response-data']['responseCode'] ) 
                {
                    case self::ALREADY_REGIST_MAIL_STATUS:
                    case self::ALREADY_REGIST_NAME_STATUS:
                    
                        Log::debug( '既に登録済みです。：' . $data['userName'] );
                        return self::OK_STATUS;
                        break;
                    
                    case self::NOT_EXIST_MAIL_STATUS:
                    
                        Log::debug( '存在しないアカウントです。：' . $data['userName'] );
                        return self::OTHER_STATUS;
                        break;
                        
                    default:
                    
                        Log::debug( 'API HTTPステータスコード不正：' . $response_body['response-data']['responseCode'] );
                        return self::NG_STATUS;
                }
            }
            else 
            {
                Log::debug( 'API処理で問題が発生しました。' );
                Log::debug( $e->getMessage() );
                return self::NG_STATUS;
            }
        }
        catch (\Exception $e) 
        {
            Log::debug( 'API処理で問題が発生しました。' );
            Log::debug( $e->getMessage() );
            return self::NG_STATUS;
        }
    }
    
    /**
     * [updateUser API]
     * @param  array $data [登録内容]
     * 
     * @return bool        [結果]
     */
    public function updateUser( array $data ): bool
    {
        try 
        {
            $request_data = [
                'userName' => $data['olduserName'],
                'tenantId' => $data['tenant_id'],
            ];
            if ( isset( $data['displayName'] ) ) {
                $request_data['name'] = $data['displayName'];
            }
            if ( isset( $data['userName'] ) ) {
                $request_data['newUserName'] = $data['userName'];
            }
            
            $request_body = [
                                'headers' => $this->headers,
                                'json' => 
                                [
                                    'request-data'=> $request_data
                                ]
                            ];
            $client = new \GuzzleHttp\Client();
            Log::debug( 'APIリクエスト' );
            Log::debug( print_r( $request_body, true ) );
            $response = $client->request( 'PUT', self::HOST_URL, $request_body);
            if ( $response->getStatusCode() != self::SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            Log::debug( 'APIレスポンス' );
            Log::debug( print_r( $response_body, true ) );
            if ( $response_body['response-data']['status'] != self::SUCCESS ) 
            {
                Log::debug( 'API レスポンス不正：' . $response_body );
                return false;
            }
            
            return true;
        } 
        catch (\Exception $e) 
        {
            Log::debug( 'API処理で問題が発生しました。' );
            Log::debug( $e->getMessage() );
            return false;
        }
    }
    
    /**
     * [deleteUser API]
     * @param  User|null $users [usersテーブルオブジェクト]
     * 
     * @return bool        [結果]
     */
    public function deleteUser( ?User $users = null ): bool
    {
        try 
        {
            if ( $users === null ) return false;
            $client = new \GuzzleHttp\Client();
            $request_body = [
                                'headers' => $this->headers,
                                'json' => 
                                [
                                    'request-data'=> 
                                    [
                                        'userName' => $users->email,
                                        'tenantId' => $users->tenant_id,
                                    ]
                                ]
                            ];
            Log::debug( 'APIリクエスト' );
            Log::debug( print_r( $request_body, true ) );
            $response = $client->request( 'DELETE', self::HOST_URL, $request_body);
            if ( $response->getStatusCode() != self::SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            Log::debug( 'APIレスポンス' );
            Log::debug( print_r( $response_body, true ) );
            if ( $response_body['response-data']['status'] != self::SUCCESS ) 
            {
                Log::debug( 'API レスポンス不正：' . $response_body );
                return false;
            }
            
            return true;
        } 
        catch (\Exception $e) 
        {
            Log::debug( 'API処理で問題が発生しました。' );
            Log::debug( $e->getMessage() );
            return false;
        }
    }
}