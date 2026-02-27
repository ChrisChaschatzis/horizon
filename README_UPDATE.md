# Database Update Instructions - IMPORTANT

To enable the new Converter and Comparison Pack features, you must update your database schema.
**Do not run** `fix_mysql_schema.php` or `migrate_db.php` for this update, as they are for older versions.

## Correct Step

Run the specific summary schema fix script:

```bash
php app/fix_schema_summary.php
```

This script will:
1.  Add the `dataset_type` column to your `datasets` table (if missing).
2.  Add the `entities_count` column.
3.  Create all new tables starting with `summary_` (e.g., `summary_groups`, `summary_pillar_participation`).

## Verification
After running the command, check your database (e.g., via phpMyAdmin). You should see tables like `summary_groups`.

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
