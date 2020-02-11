# Installation

**Composer must be installed to the latest version**

1. Run `composer create-project symfony/website-skeleton:4.3.* my-project-domain.com`
2. Run `cd my-project-domain.com`
3. Edit the DATABASE_URL in .env file `DATABASE_URL=mysql://database_user:password@localhost:3306/database_name`
4. Add the DEBUG_EMAIL variable to .env file `DEBUG_EMAIL=dev@eckinox.ca`
5. Run `composer config repositories.eckinox vcs https://git.eckidev.com/Eckidev/eckinox-bundle.git`
6. Run `composer require eckinox/eckinox-bundle`
7. Run `bin/console eckinox:install`

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
