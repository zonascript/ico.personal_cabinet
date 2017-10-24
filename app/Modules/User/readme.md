# Пользователи

Модуль включает в себя:
* Пользователей
* Группы пользователей
* Права доступа: глобальные, по группам пользователей, по пользователю, до конечного объекта
* Опциональная совместимость хеша паролей с bitrix
* Сессии пользователей в базе данных
* Ключи авторизации для RESTful
* RESTful (не завершен)
* Регистрация, авторизация, восстановление пароля

# Использование в RESTful

Подключаем `urls_auth_api.php`. Обращаемся по `/login`, в случае успешной авторизации сохраняем полученный `model` с вложенным в него `key`. При последующих запросах прозрачно добавляем `X-Auth-Key` к каждому запросу. У компонента сессий `autoStart` выставляем в `false`.

Пример с jquery `$.ajax` или `$.ajaxSetup`
```js
...
	beforeSend: function (xhr, settings) {
	    var user = auth.getUser();
	    if (user.key) {
	        xhr.setRequestHeader("X-Auth-Key", user.key);
	    }
	}
...
```

И обязательно подключаем `\Modules\Core\Middleware\CorsMiddleware` и `\Modules\User\Middleware\AuthKeyCheckMiddleware`.