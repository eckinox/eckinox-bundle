# Installation

**Composer must be installed to the latest version**

1. Run `composer create-project symfony/website-skeleton my-project`
2. Edit the DATABASE_URL in .env file `DATABASE_URL=mysql://database_user:password@localhost:3306/database_name`
3. Run `composer config repositories.eckinox vcs https://git.eckidev.com/Eckidev/eckinox-bundle.git`
4. Run `composer require eckinox/eckinox-bundle`
5. Run `bin/console eckinox:install`

Don't forget the .htaccess
```
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Removing trailing slashes
RewriteRule ^(.+)/$ https://%{HTTP_HOST}/$1 [R=301,L]

# Redirect everything that is not a file to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^(.*)$ index.php?/$0 [PT,QSA,L]
```

That's it !
