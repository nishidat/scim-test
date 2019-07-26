<?php

namespace App\Api;

use Log;

class ApiClient
{
    private const HOST_URL = 'http://luck-gw.mail-luck.jp/v1/Users';
    private const SUCCESS_HTTP_STATUS = 200;
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
            Log::debug( 'APIリクエスト' );
            $client = new \GuzzleHttp\Client();
            $response = $client->request( 'POST', self::HOST_URL, 
                [
                    'headers' => $this->headers,
                    'json' => 
                    [
                        'request-data'=> 
                        [
                            'userName' => $data['userName'],
                            'name' => $data['name']['formatted'],
                            'tenantId' => $data['tenant_id'],
                        ]
                    ]
                ]
            );
            if ( $response->getStatusCode() != SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            if ( $response_body['status'] != SUCCESS ) 
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
            Log::debug( 'APIリクエスト' );
            $client = new \GuzzleHttp\Client();
            $response = $client->request( 'PUT', self::HOST_URL, 
                [
                    'headers' => $this->headers,
                    'json' => 
                    [
                        'request-data'=> 
                        [
                            'userName' => $data['olduserName'],
                            'newUserName' => $data['userName'],
                            'name' => $data['name']['formatted'],
                            'tenantId' => $data['tenant_id'],
                        ]
                    ]
                ]
            );
            if ( $response->getStatusCode() != SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            if ( $response_body['status'] != SUCCESS ) 
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
            Log::debug( 'APIリクエスト' );
            $client = new \GuzzleHttp\Client();
            $response = $client->request( 'DELETE', self::HOST_URL, 
                [
                    'headers' => $this->headers,
                    'json' => 
                    [
                        'request-data'=> 
                        [
                            'userName' => $userName,
                            'tenantId' => $tenant_id,
                        ]
                    ]
                ]
            );
            if ( $response->getStatusCode() != SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            if ( $response_body['status'] != SUCCESS ) 
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