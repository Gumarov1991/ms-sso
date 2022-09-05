## Microsoft SSO Auth

### Минимальные требования 
- если установлен illuminate/contracts, то его минимальная версия 7.0

#### Установка
- composer require gumarov-dev/microsoft-sso-dev
- php artisan vendor:publish --provider='GumarovDev\MicrosoftSsoAuth\Providers\MsSsoAuthServiceProvider'

### Использование
- заполнение .env данными по ключам
```
MS_TENANT_ID=
MS_CLIENT_ID=
MS_SECRET_ID=
MS_REDIRECT_URI=
```

- В месте где нужно проверить кто у нас залогинен

```
use GumarovDev\MicrosoftSsoAuth\Facades\MsSsoAuth;

MsSsoAuth::getAuthUserEmail()
```

- В месте куда нас перекинет Microsoft, метод вернет $email авторизованного пользователя

```
use GumarovDev\MicrosoftSsoAuth\Facades\MsSsoAuth;

$email = MsSsoAuth::getAuthUserEmail()
```
