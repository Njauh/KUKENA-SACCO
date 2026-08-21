KUKENA SACCO DATABASE CONNECTION

Files:
- index.html = modified Kukena website
- db.php = PDO connection to MySQL database kukena_sacco
- api.php = registration, login and database status API

XAMPP setup:
1. Start Apache and MySQL.
2. Create/import a database named kukena_sacco in phpMyAdmin.
3. Put these files in C:\xampp\htdocs\kukena\
4. Open http://localhost/kukena/index.html

IMPORTANT:
The supplied SQL dump is named/database-configured as `kukena-sacco` (hyphen), while the requested database name is `kukena_sacco` (underscore). The PHP code uses `kukena_sacco`. Either create/import the database with that exact underscore name, or change $dbname in db.php to match your actual database.

The supplied SQL schema currently does not contain schedule/booking tables and contains foreign-key definitions that appear reversed (for example accounts.accounts_id -> payment.payment_id). The login/registration code above uses the accounts and customer columns exactly as supplied. Before enabling live booking/payment storage, correct the schema and add booking/schedule tables.
