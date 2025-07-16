-- Sjekk eksisterende brukere i databasen
-- Kjør denne SQL-kommandoen i phpMyAdmin for å se hvilke user_id som eksisterer

SELECT id, email, role FROM users ORDER BY id;
