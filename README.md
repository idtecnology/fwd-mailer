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

    BD_HOST =
    BD_NAME =
    BD_USERNAME =
    DB_PASSWORD =
    ```

---

## Ejecucion: 

```bash 
php -f index.php
```

> Se puede configurar en un crontab 