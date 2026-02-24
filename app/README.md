# Horizon Europe / CORDIS Mini BI Dashboard

A lightweight BI dashboard for visualizing Horizon Europe / CORDIS project data.

## Features

- **Dashboard**: Core KPIs, Coordinator Ranking, Greek Participation Breakdown, Consortium Ranking.
- **Analytics**: Top Keywords, Fields of Science, Investment Priorities.
- **Chart Builder**: Create custom charts and tables dynamically.
- **Dataset Management**: Import Excel (.xlsx) and JSONL files.
- **Glassmorphism UI**: Modern, responsive design.

## Setup

1.  **Dependencies**:
    -   PHP 8.x
    -   Composer
    -   SQLite (default) or MySQL

2.  **Installation**:
    ```bash
    cd app
    composer install
    ```

3.  **Database Initialization**:
    ```bash
    php init_db.php
    ```

4.  **Mock Data Generation** (Optional):
    ```bash
    php generate_mock_data.php
    ```
    This will create sample files in `data/`.

5.  **Running the Application**:
    You can use the built-in PHP server:
    ```bash
    php -S localhost:8000
    ```
    Access the dashboard at `http://localhost:8000`.

## Configuration

Database settings can be configured in `config.php`. By default, it uses SQLite (`database.sqlite`).

## Import Instructions

1.  Go to the **Datasets** page.
2.  Enter a name for the dataset.
3.  Upload the `.xlsx` file (Required).
4.  Upload the `.jsonl` file (Optional, for enrichment).
5.  Click **Import Dataset**.
