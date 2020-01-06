<?php

namespace App\Api;

use Log;

class ApiClient
{
    private const SEND_QR_CODE_URL = 'https://app-commander.mamoru-secure.com/SendQrCode';
    private const USER_STATUS_CHANGE_URL = 'https://app-commander.mamoru-secure.com/UserStatusChange';
    private const PRODUCT_NAME = 'cygames_sso';
    private const SUCCESS_HTTP_STATUS = 200;
    private const SUCCESS = '0000';
    private const ALREADY_REGIST_STATUS = '0301';
    
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
            $response = $client->request( 'POST', self::SEND_QR_CODE_URL, 
                [
                    'form_params' => 
                        [
                            'product_name' => self::PRODUCT_NAME,
                            'user_id' => $data['externalId'],
                            'email' => $data['userName'],
                            'comment' => ''
                        ]
                ]
            );
            if ( $response->getStatusCode() != self::SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            Log::debug( 'APIレスポンス' . var_dump($response_body) );
            if ( $response_body['status'] != self::SUCCESS ) 
            {
                if ( $response_body['status'] == self::ALREADY_REGIST_STATUS )
                {
                    return true;
                }
                Log::debug( 'API レスポンス不正：' . $response_body['status'] );
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
    public function updateUser( string $externalId, string $active ): bool
    {
        try 
        {
            if ( $active == 'true' ) 
            {
                $active = '1';
            }
            else
            {
                $active = '2';
            }
            Log::debug( 'APIリクエスト' );
            $client = new \GuzzleHttp\Client();
            $response = $client->request( 'POST', self::USER_STATUS_CHANGE_URL, 
                [
                    'form_params' => 
                        [
                            'product_name' => self::PRODUCT_NAME,
                            'user_id' => $externalId,
                            'active' => $active
                        ]
                ]
            );
            if ( $response->getStatusCode() != self::SUCCESS_HTTP_STATUS ) 
            {
                Log::debug( 'API HTTPステータスコード不正：' . $response->getStatusCode() );
                return false;
            }
            $response_body = json_decode( $response->getBody(), true );
            Log::debug( 'APIレスポンス' . var_dump($response_body) );
            if ( $response_body['status'] != self::SUCCESS ) 
            {
                Log::debug( 'API レスポンス不正：' . $response_body['status'] );
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