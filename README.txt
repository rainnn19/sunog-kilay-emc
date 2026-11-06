SUNOG KILAY EMC LMS - SETUP
1. Extract sunog-kilay-emc into XAMPP/htdocs.
2. Start Apache and MySQL.
3. Open http://localhost/phpmyadmin and import database.sql.
4. Check includes/config.php. Default XAMPP DB settings: localhost, root, blank password.
5. Open http://localhost/sunog-kilay-emc
Initial admin: admin@sunogkilay.local / password
Change the default admin password immediately. For a real deployment use HTTPS and review server security.
Administrators can add and edit courses from Dashboard > Manage Courses; each course must be assigned to an active teacher.
Administrators can add professor details from Dashboard > Add Professor using last name, first name, middle name, optional extension, and email. Professors do not need login accounts; they can be selected when adding or editing a course.
Administrators can create user accounts using the same last name, first name, middle name, and optional name extension format. Existing accounts continue to use their existing full name until updated.
The database.sql file can be imported for a fresh setup or run against an existing database; it includes the course-field upgrade and preserves existing course data.
If the existing database does not have the professors table, run this once in phpMyAdmin:
CREATE TABLE IF NOT EXISTS professors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  last_name VARCHAR(80),
  first_name VARCHAR(80),
  middle_name VARCHAR(80),
  name_extension VARCHAR(20),
  email VARCHAR(160),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE professors
  ADD COLUMN IF NOT EXISTS last_name VARCHAR(80),
  ADD COLUMN IF NOT EXISTS first_name VARCHAR(80),
  ADD COLUMN IF NOT EXISTS middle_name VARCHAR(80),
  ADD COLUMN IF NOT EXISTS name_extension VARCHAR(20);

Uploads: teacher course materials and student assignment files, max 10 MB. Allowed PDF, Office documents, images, TXT, ZIP.
