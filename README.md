# TransactiWar — Battle for Security, Compete for Supremacy

**CS6903: Network Security, 2025-26 — IIT Hyderabad**

---

## Overview

TransactiWar is a secure web application implementing user authentication, session management, profile management, and money transfer functionality built with PHP + MySQL.

---

## Tech Stack

| Layer     | Technology                                |
| --------- | ----------------------------------------- |
| Front-end | HTML5 / CSS3 / Bootstrap 5 / JS (Vanilla) |
| Back-end  | PHP 8.2                                   |
| Database  | MySQL 8.0                                 |
| Server    | Apache 2.4                                |
| Container | Docker + Docker Compose                   |

---

## Features

- **User Registration & Login** — Secure registration restricted to `@iith.ac.in` emails, bcrypt password hashing
- **Session Management** — PHP native sessions, session destroy on logout
- **Profile Management** — Edit email/bio, upload profile image with MIME type validation, view other users' profiles
- **User Search** — Search by username or user ID from dashboard and transfer page
- **Money Transfer** — Atomic transfer with transaction rollback, negative balance prevention, optional comment visible to receiver
- **Transaction History** — Full history of sent and received transactions
- **Activity Logging** — Every page visit logged with `(webpage, username, timestamp, IP address)`

---

## Security Measures

1. **SQL Injection Prevention** — MySQLi prepared statements with parameterized queries throughout
2. **XSS Prevention** — All output escaped via `htmlspecialchars()`
3. **Password Security** — bcrypt hashing via `password_hash()` / `password_verify()`
4. **File Upload Security** — MIME type + extension validation, random filename, stored outside web root (`/var/www/uploads/`)
5. **Negative Balance Prevention** — Server-side balance check before every transfer
6. **Atomic Transactions** — `BEGIN TRANSACTION` + `ROLLBACK` on failure
7. **Access Control** — Session check on every protected page, unauthenticated users redirected to login

---

## Running with Docker

### Prerequisites

- Docker Engine 20.10+
- Docker Compose v2+

### Quick Start

```bash
# 1. Extract the project and move to the folder
cd TransactiWar

# 2. Build and start all services
docker compose up --build -d

# 3. Open in browser
open http://localhost:8000
```

The `init.sql` script automatically:

- Creates all required tables (`users`, `profiles`, `transactions`, `activity_logs`)
- Sets up foreign key relationships

### Stopping

```bash
docker compose down          # stop containers
docker compose down -v       # stop and delete volumes
```

---

## Test Accounts (Auto-Created)

Run the following command after the containers are up and running to populate the database with 100 test users.

```bash
docker exec transactiwar-web-1 php /var/www/html/scripts/auto_create.php
```

| Username      | Email                  | Password     |
| ------------- | ---------------------- | ------------ |
| `testuser1`   | testuser1@iith.ac.in   | Password@1   |
| `testuser2`   | testuser2@iith.ac.in   | Password@2   |
| ...           | ...                    | ...          |
| `testuser100` | testuser100@iith.ac.in | Password@100 |

Each account starts with a **₹100.00** balance.

---

## Viewing Activity Logs

All user actions are recorded in the `activity_logs` table in the database. You can view these logs directly by connecting to the database container.

1. **Connect to the MySQL container:**
   
   Use the following command to open a MySQL shell inside the running `db` container:
   
   ```bash
   docker exec -it transactiwar-db-1 mysql -u root -p
   ```

2. **Enter the password:**
   
   When prompted, enter the password: `asj*8@9#3$74fhj`

3. **Query the logs:**
   
   Once you are inside the MySQL shell, run the following SQL commands to see the most recent logs, last 100 (if want to see more, write the number of activities you want at the end):
   
   ```sql
   USE transactiwar_db;
   SELECT * FROM activity_logs ORDER BY timestamp DESC LIMIT 100;
   ```

---

## Environment Variables

| Variable         | Default           | Description    |
| ---------------- | ----------------- | -------------- |
| `MYSQL_HOST`     | `db`              | MySQL hostname |
| `MYSQL_PORT`     | `3306`            | MySQL port     |
| `MYSQL_DATABASE` | `transactiwar_db` | Database name  |
| `MYSQL_USER`     | `root`            | MySQL username |
| `MYSQL_PASSWORD` | `asj*8@9#3$74fhj` | MySQL password |

---

## Project Structure

```
/
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── config/
│   └── db_connect.php
├── dashboard/
│   └── index.php
├── database/
│   └── init.sql
├── includes/
│   ├── bg_scene.php
│   ├── footer.php
│   ├── header.php
│   ├── logger.php
│   └── navbar.php
├── profile/
│   ├── avatar.php
│   ├── edit.php
│   ├── search.php
│   └── view.php
├── scripts/
│   └── auto_create.php
├── transfer/
│   ├── history.php
│   └── index.php
├── .dockerignore
├── docker-compose.yml
├── Dockerfile
└── index.php
```

---

## Team 11

| Roll Number    | Name            |
| -------------- | --------------- |
| CS25MTECH11002 | Aayush Ranjan   |
| CS25MTECH11003 | Akshay Bagde    |
| CS25MTECH11022 | Ambarish Sarkar |
| CS25MTECH11023 | Ankit Kr Sinha  |
| CS25MTECH11029 | Mayank Mishra   |

---

## Resources Referenced

- PHP Documentation: https://www.php.net/docs.php
- PHP MySQLi Prepared Statements: https://www.php.net/manual/en/mysqli.prepare.php
- PHP password_hash: https://www.php.net/manual/en/function.password-hash.php
- PHP File Upload: https://www.php.net/manual/en/features.file-upload.php
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- OWASP SQL Injection Prevention: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- OWASP File Upload: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- MySQL Transactions: https://dev.mysql.com/doc/refman/8.0/en/commit.html
- Bootstrap 5: https://getbootstrap.com/docs/5.3/
- Docker Documentation: https://docs.docker.com/
- Apache HTTP Server: https://httpd.apache.org/docs/2.4/
