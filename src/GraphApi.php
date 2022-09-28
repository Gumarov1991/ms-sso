<?php

namespace GumarovDev\MicrosoftSsoAuth;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;
use Illuminate\Support\Facades\Http;

class GraphApi
{
    public const REQUEST_MS_LOGIN_DOMAIN = 'https://login.microsoftonline.com/';
    public const OAUTH_PATH = '/oauth2/v2.0/';
    public const API_VERSION = 'v1.0';

    public static function sendMailFrom($userId, $mailData) : bool
    {
         $status = self::getGraph()
            ->setApiVersion(self::API_VERSION)
            ->createRequest('POST', '/users/' . $userId . '/sendMail')
            ->attachBody($mailData)
            ->execute()->getStatus();

         if($status == 200 || $status == 202) {
             return true;
         }
         return false;
    }

    public static function prepareMailData($from, $to, $subject, $text) : array
    {
        return [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'html',
                    'content' => $text
                ],
                'from' => [
                    'emailAddress' => [
                        'address' => $from
                    ]
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $to
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function searchUser($query) : array
    {
        return self::getGraph()
            ->setApiVersion(self::API_VERSION)
            ->createRequest('GET', '/users/?$search="'.$query.'"')
            ->setReturnType(User::class)
            ->addHeaders(['ConsistencyLevel' => 'eventual'])
            ->execute();
    }

    public static function getUsers() : array
    {
        return self::getGraph()
            ->setApiVersion(self::API_VERSION)
            ->createRequest('GET', '/users')
            ->setReturnType(User::class)
            ->execute();
    }

    public static function downloadUserPhoto($userId, $directory = '', $extension = '.jpg') : array|null
    {
        if(empty($directory)) $directory = storage_path('app/public/uploads/');

        $userPhoto = $userId . $extension;
        $userPhotoPath = $directory . $userPhoto;

        $uploaded = self::getGraph()
            ->setApiVersion(self::API_VERSION)
            ->createRequest('GET', '/users/' . $userId . '/photo/$value')
            ->download( $userPhotoPath );

        if($uploaded == null) {
            return ['file_path' => $userPhotoPath, 'file_name' => $userPhoto];
        }
        return null;
    }

    public static function getGraph() : Graph
    {
        return (new Graph())->setAccessToken(self::getToken());
    }

    private static function getToken() : string
    {
        return Http::asForm()
            ->retry(3, 1000)
            ->post(
                self::getURL('token'),
                self::getParamsForMsToken()
            )
            ->json('access_token');
    }

    private static function getURL($action) : string
    {
        return self::REQUEST_MS_LOGIN_DOMAIN . config('ms-auth.tenant_id') . self::OAUTH_PATH . $action;
    }

    private static function getParamsForMsToken() : array
    {
        return [
            'client_id' => config('ms-auth.client_id'),
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
            'client_secret' => config('ms-auth.secret_key')
        ];
    }
}
