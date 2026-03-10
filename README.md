# TransactiWar

Group project for **Network Security course — CS6903**

## Project Structure

```
/transactiwar
├── config/
│   └── db_connect.php
│
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── dashboard/
│   └── index.php
│
├── profile/
│   ├── view.php
│   └── edit.php
│
├── transfer/
│   ├── index.php
│   └── history.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   └── logger.php
│      → **The "Invisible" Mandatory File**
│      → Logs user activity including:
│        - Webpage accessed
│        - Username
│        - Timestamp
│        - Client IP address
│      → This PHP script must be included at the top of every page to record activity in the database.
│
├── assets/
│   ├── css/
│   └── uploads/
│
├── scripts/
│   └── auto_create.php
│      → he Deliverables specifically require a script to create accounts automatically.
│      → This will be a standalone PHP script you can run to instantly spawn 50 dummy users for testing or grading.
│
├── index.php
├── compose.yaml
└── Dockerfile
```