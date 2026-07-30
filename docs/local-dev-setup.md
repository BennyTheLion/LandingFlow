# Local Development Setup (XAMPP / Windows)

The project root (this folder) is the web document root — `index.php` lives here
directly, and `.htaccess` denies direct access to `app/`, `config/`, `database/`,
`storage/`, `vendor/`, and other internal directories. This mirrors how the site
must be configured on Hostinger (or any host): the deployed repo root **is** the
site root, not a `public/` subfolder.

To preview locally, add virtual hosts pointing straight at the project root
(not at `htdocs/landingflow/public`, which no longer exists).

## 1. Apache vhosts

Add to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
# LandingFlow - document root is the project root itself (index.php lives
# here directly), mirroring the Hostinger production layout where the repo
# root is the site's document root.
<VirtualHost *:80>
    ServerName landingflow.local
    DocumentRoot "C:/xampp/htdocs/landingflow"
    <Directory "C:/xampp/htdocs/landingflow">
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/landingflow-error.log"
    CustomLog "logs/landingflow-access.log" common
</VirtualHost>

# LandingFlow on a dedicated port under plain "localhost" - lets you preview
# locally without any hosts file changes (landingflow.local requires one).
Listen 8080
<VirtualHost *:8080>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/landingflow"
    <Directory "C:/xampp/htdocs/landingflow">
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/landingflow-error.log"
    CustomLog "logs/landingflow-access.log" common
</VirtualHost>
```

If a default vhost for plain `localhost` (serving the rest of `htdocs`) isn't
already present, add one too so other projects under `htdocs` keep working:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs"
    ServerName localhost
</VirtualHost>
```

Validate and restart Apache after editing (via the XAMPP Control Panel -
Stop, then Start Apache):

```
"C:\xampp\apache\bin\httpd.exe" -t
```

## 2. Preview URLs

- `http://localhost:8080/` - works immediately, no extra setup.
- `http://landingflow.local/` - requires a hosts file entry (next step).

**`http://localhost/landingflow/...` (the old subfolder-style path) no
longer works for anything beyond the homepage** - it points at the wrong
document root now that `index.php` lives at the project root instead of in
`public/`.

## 3. Hosts file entry (only needed for `landingflow.local`)

Add to `C:\Windows\System32\drivers\etc\hosts` (edit as Administrator):

```
127.0.0.1       landingflow.local
```

## 4. Server-only files (not in git)

These are gitignored and must exist on any server (local or production) for
the app to boot - copy `config/config.example.php` to `config/.env.php` and
fill in real values:

```
cp config/config.example.php config/.env.php
```

`vendor/` is also gitignored - run `composer install` once per environment.
