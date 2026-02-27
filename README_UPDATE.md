# Database Update Instructions

To update your database schema with the new tables required for the Converter and Comparison Pack features, follow these steps:

1.  **Backup your current database.**
    -   If using SQLite, simply copy `app/database.sqlite` to a safe location.
    -   If using MySQL, export your database using `mysqldump` or phpMyAdmin.

2.  **Run the update script.**
    -   Navigate to your project root or `app/` directory in your terminal.
    -   Execute the PHP script:
        ```bash
        php app/fix_schema_summary.php
        ```
    -   This script will check your existing tables and add the necessary columns (`dataset_type`, `entities_count`) and create the new tables (`summary_groups`, `summary_pillar_participation`, etc.) if they don't exist. It is safe to run multiple times.

3.  **Verify.**
    -   Access your database (via phpMyAdmin or command line) and check for the existence of tables starting with `summary_`.

---

# Setting up phpMyAdmin

To easily manage your database via a web interface, you can install phpMyAdmin.

### Option A: Manual Installation (Simple PHP Server)
If you are running this project on a standard PHP server (XAMPP, Apache, Nginx):

1.  Run the provided setup script from the project root:
    ```bash
    bash setup_phpmyadmin.sh
    ```
    This script will download the latest phpMyAdmin, extract it to `app/pma`, and configure it.

2.  Access phpMyAdmin at: `http://your-domain/app/pma`
    -   **Server:** `localhost` (or your DB host)
    -   **Username:** Your DB username (check `app/config.php`)
    -   **Password:** Your DB password

### Option B: Docker
If you are using Docker, add the following service to your `docker-compose.yml`:

```yaml
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    environment:
      PMA_HOST: db
      MYSQL_ROOT_PASSWORD: root_password_here
    ports:
      - "8080:80"
```
Access it at `http://localhost:8080`.
