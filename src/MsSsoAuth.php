<?php

namespace GumarovDev\MicrosoftSsoAuth;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Exception;

class MsSsoAuth
{
    public const REQUEST_MS_LOGIN_DOMAIN = 'https://login.microsoftonline.com/';
    public const REQUEST_GRAPH_API_DOMAIN = 'https://graph.microsoft.com/';
    public const OAUTH_PATH = '/oauth2/v2.0/';

    private $tenantId;
    private $clientId;
    private $secretKey;
    private $redirectUri;

    public function __construct(
        $tenantId = null,
        $clientId = null,
        $secretKey = null,
        $redirectUri = null
    ) {
        $this->tenantId = $tenantId ?? config('ms-auth.tenant_id');
        $this->clientId = $clientId ?? config('ms-auth.client_id');
        $this->secretKey = $secretKey ?? config('ms-auth.secret_key');
        $this->redirectUri = $redirectUri ?? config('ms-auth.redirect_uri');
    }

    /**
     * Запрос на авторизацию через редирект на microsoft, после этого microsoft редиректит на страницу, которая указана
     * в настройках используемого приложения
     *
     * @return void
     */
    public function checkAuthByRedirect(): void
    {
        $params = $this->getParamsForAutnMsQuery();
        $urlWithoutParams = $this->getUrl('authorize');
        $stateParam = Crypt::encryptString($urlWithoutParams . '?'
            . http_build_query($params));
        $redirectUrl = $urlWithoutParams . '?'
            . http_build_query(array_merge($params, ['state' => $stateParam]));
        header('Location:' . $redirectUrl);

        exit();
    }

    /**
     * Цепочка запросов для получения email авторизованного usera
     *
     * @throws Exception
     * @return string
     */
    public function getAuthUserEmail(string $code, string $encryptedKey): string
    {
        if ($code && $this->checkEncryptedKey($encryptedKey)) {
            $accessToken = $this->getMsAuthToken($code);
        } else {
            throw new Exception('пустой $msCode или не прошла проверка зашифрованного ключа');
        }

        if ($accessToken) {
            $emailAuthenticatedMsUser = $this->getAuthMsUserEmail($accessToken);
        } else {
            throw new Exception('Ошибка получения access_token');
        }

        if (!$emailAuthenticatedMsUser) {
            throw new Exception('Не получили email от Microsoft');
        }

        return $emailAuthenticatedMsUser;
    }

    /**
     * Получение ссылки для авторизации
     *
     * @param string $action
     * @return string
     */
    private function getUrl(string $action): string
    {
        return self::REQUEST_MS_LOGIN_DOMAIN
            . $this->tenantId
            . self::OAUTH_PATH
            . $action;
    }

    /**
     * Получение MS токена
     *
     * @param string $code Код от MS
     * @return string|null
     */
    private function getMsAuthToken(string $code): ?string
    {
        return Http::asForm()
            ->retry(3, 1000)
            ->post(
                $this->getUrl('token'),
                $this->getParamsForMsToken($code)
            )
            ->json('access_token');
    }

    /**
     * Получение email авторизованного usera
     *
     * @param string $accessToken Токен MS
     * @return string|null
     */
    private function getAuthMsUserEmail(string $accessToken): ?string
    {
        return Http::retry(3, 1000)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->get(self::REQUEST_GRAPH_API_DOMAIN . 'v1.0/me/')
            ->json('mail');
    }

    /**
     * Проверка поля state
     *
     * @param string $encryptedKey Строка для проверки
     * @return bool
     */
    private function checkEncryptedKey(string $encryptedKey): bool
    {
        $urlForCheck = $this->getUrl('authorize') . '?'
            . http_build_query($this->getParamsForAutnMsQuery());

        return Crypt::decryptString($encryptedKey) === $urlForCheck;
    }

    /**
     * Получение параметров для авторизации
     *
     * @return array
     */
    private function getParamsForAutnMsQuery(): array
    {
        return [
            'client_id' => $this->clientId,
            'scope' => 'user.read',
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
        ];
    }

    /**
     * Получение параметров для авторизации
     *
     * @param string $code Код от MS
     * @return array
     */
    private function getParamsForMsToken(string $code)
    {
        return [
            'client_id' => $this->clientId,
            'scope' => 'user.read',
            'grant_type' => 'authorization_code',
            'redirect_uri' => '',
            'client_secret' => $this->redirectUri,
            'code' => $code,
        ];
    }
}
