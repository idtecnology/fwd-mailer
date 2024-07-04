# FWD MAILER

### Instalacion: 

1. Instalar las dependencias de `composer`

    ```bash
    composer i
    ```

2. Configurar el `.env` con los datos solicitados
    ```env
    EMAIL_USERNAME = 'email@example.com'
    EMAIL_PASSWORD = 'app_password'
    EMAIL_HOST = '{imap.example.com:000/imap/ssl}INBOX'
    ```

3. Cargar el pool de correos para reenviar, se encuentra en el fichero lista `lista.php`
    ```php
    // POOL DE CORREOS
    return $forwardTo = [
        0 => example@mail.com,
        1 => example@mail.com,
        2 => example@mail.com,
        .
        .
        .
        1000 => example@mail.com
    ];
    ```

4. Configurar el largo del `rand` segun correos en el pool, lo encontramos en el fichero `index.php`
    ```php
    $r = rand(0, 1000);
    ```

---

## Ejecucion: 

```bash 
php -f index.php
```

> Se puede configurar en un crontab 