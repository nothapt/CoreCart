# CoreCart

A next-generation e-commerce platform built on clean PHP 8.4+ with MySQL/MariaDB.

## Requirements

- PHP 8.4 or higher
- MySQL 8.0+ or MariaDB 10.6+
- Composer

## Quick Start

### Option 1: Local development (Windows)

```bat
run.bat
```

### Option 2: Local development (Linux/macOS)

```bash
chmod +x run.sh
./run.sh
```

### Option 3: Docker

```bash
cd docker
docker-compose up -d
```

Open http://localhost:8080 in your browser.

### Option 4: CLI install

```bash
php cli.php install --db_user=root --db_pass=secret --db_name=corecart
```

Then start the dev server with `run.bat` or `run.sh`.

## Project Structure

```
/
├── admin/              # Admin panel (controllers, models, views)
├── catalog/            # Frontend store
├── system/             # Core engine
│   ├── engine/         # Router, Database, ModificationEngine
│   └── library/        # Helper classes
├── storage/            # Cache, logs, uploads (not in git)
├── install/            # Web installer
├── docker/             # Docker config
├── index.php           # Frontend entry point
├── cli.php             # CLI installer
├── run.bat / run.sh    # Dev server launcher
└── composer.json       # Dependencies and autoload config
```

## Safe OCMOD

CoreCart includes a Safe OCMOD system that modifies core files without touching the originals.
If a modification causes a PHP error, it is automatically disabled and logged.

Place your XML modification files in `system/modifications/`.
