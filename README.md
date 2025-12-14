PDSA Games - Complete package (Full)

Installation:
1. Copy the folder `pdsa_game` into MAMP's htdocs:
   /Applications/MAMP/htdocs/pdsa_game
2. Start MAMP servers (Apache + MySQL).
3. Import the SQL schema (pdsa_games) into MySQL (phpMyAdmin or CLI).
4. Ensure config.php DB settings match (DB_HOST uses 127.0.0.1:8889).
5. Clear browser cookies for localhost.
6. Visit http://localhost:8888/pdsa_game/

Test user: demo / 1234

Notes:
- CSRF tokens included in UI forms.
- Use PHP 7.4+ recommended.
