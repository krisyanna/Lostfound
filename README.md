# Secure Student Lost and Found System (PHP + MySQL)
1. Install XAMPP/WAMP (PHP 8+, MySQL). Copy this folder to `htdocs/lostfound`.
2. Import `schema.sql` in phpMyAdmin. Edit DB credentials at the top of `inc.php`.
3. Open `/lostfound/setup.php`, set the admin password (username `admin`), then DELETE `setup.php`.
4. Log in, create student accounts under Admin > Accounts. Use HTTPS in production.
