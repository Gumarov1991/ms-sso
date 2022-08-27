<?php

namespace GumarovDev\MicrosoftSsoAuth;

use Illuminate\Support\Facades\Http;

class MsSsoAuth
{
    public const REQUEST_MS_DOMAIN = 'https://login.microsoftonline.com/';
    public const OAUTH_PATH = '/oauth2/v2.0/';

    private $tenantId;
    private $clientId;
    private $secretKey;

    public function __construct($tenantId = null, $clientId = null, $secretKey = null)
    {
        $this->tenantId = $tenantId ?? config('ms-auth.tenant_id');
        $this->clientId = $clientId ?? config('ms-auth.client_id');
        $this->secretKey = $secretKey ?? config('ms-auth.secret_key');
    }

    public function checkAuthByRedirect()
    {
        $params = [
            'client_id' => $this->clientId,
            'scope' => 'User.Read+Directory.read.all',
            'redirect_uri' => 'redirect_uri',
            'response_type' => 'code',
        ];

        $url = $this->getUrl('authorize') . '?' . http_build_query($params);

        header('Location:' . $url);

        exit();
    }

    private function getUrl($action)
    {
        return self::REQUEST_MS_DOMAIN
            . $this->tenantId
            . self::OAUTH_PATH
            . $action;
    }
}
