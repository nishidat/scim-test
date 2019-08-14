<?php

namespace App\Api;

use Log;

class ApiClient
{
    private const HOST_URL = 'http://luck-gw.mail-luck.jp/v1/Users';
    private const SUCCESS_HTTP_STATUS = 200;
    private const ALREADY_REGIST_STATUS = 4001001;
    private const SUCCESS = 'OK';
    private $headers = 
    [
        'Authorization' => 'Basic YXBpdGVzdEBtYWlsLWx1Y2suanA6UGFzc1dvcmQ=',
        'Content-Type' => 'application/json',
    ];
    
    /**
     * [createUser API]
     * @param  array $data [登録内容]
     * 
     * @return bool        [結果]
     */
    public function createUser( array $data ): bool
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
            if ( $response->getStatusCode() != self::SUCCESS_HTTP_STATUS ) 
            {
                if ( $response_body['response-data']['responseCode'] == self::SUCCESS_HTTP_STATUS ) 
                {
                    Log::debug( '既に登録済みです。：' . $data['userName'] );
                    return true;
                }
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
     * [updateUser API]
     * @param  array $data [登録内容]
     * 
     * @return bool        [結果]
     */
    public function updateUser( array $data ): bool
    {
        try 
        {
            $name = '';
            if (isset($data['displayName'])) 
            {
                $name = $data['displayName'];
            }
            $client = new \GuzzleHttp\Client();
            $request_body = [
                                'headers' => $this->headers,
                                'json' => 
                                [
                                    'request-data'=> 
                                    [
                                        'userName' => $data['olduserName'],
                                        'newUserName' => $data['userName'],
                                        'name' => $name,
                                        'tenantId' => $data['tenant_id'],
                                    ]
                                ]
                            ];
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
     * @param  array $data [登録内容]
     * 
     * @return bool        [結果]
     */
    public function deleteUser( string $userName, string $tenant_id ): bool
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
                                        'userName' => $userName,
                                        'tenantId' => $tenant_id,
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