# Installation Guide

## Quick Start (5 minutes)

### For Windows Users with XAMPP:

1. **Download the project:**
```bash
   git clone https://github.com/CarterCattadoris/CSE389TermProject.git
```
   Or download ZIP from GitHub and extract to `C:\xampp\htdocs\`

2. **Start XAMPP:**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

3. **Create Database:**
   - Open http://localhost/phpmyadmin
   - Click "New" to create database
   - Name it: `academichub`
   - Click "Import" tab
   - Choose file: `database/schema.sql`
   - Click "Go"

4. **Configure:**
   - Copy `config.example.php` to `config.php`
   - Edit `config.php` if needed (default XAMPP settings work as-is)

5. **Run:**
   - Open http://localhost/academichub
   - Login with username: `thomas` (or carter, aaron, angelo, glenn)

### For Linux Users:

1. **Clone repository:**
```bash
   cd /opt/lampp/htdocs
   git clone https://github.com/CarterCattadoris/CSE389TermProject.git academichub
```

2. **Set permissions:**
```bash
   sudo chown -R $USER:$USER /opt/lampp/htdocs/academichub
```

3. **Start LAMPP:**
```bash
   sudo /opt/lampp/lampp start
```

4. **Import database:**
```bash
   mysql -u root -p -e "CREATE DATABASE academichub;"
   mysql -u root -p academichub < database/schema.sql
```
NOTE: -p flag not necessary if you have not configured a password for XAMPP, there is no password by default

5. **Configure:**
```bash
   cp config.example.php config.php
   nano config.php  # Edit if needed
```

6. **Access:**
   - http://localhost/academichub

### For Mac Users with MAMP:

1. **Clone to MAMP directory:**
```bash
   cd /Applications/MAMP/htdocs
   git clone https://github.com/CarterCattadoris/CSE389TermProject.git academichub
```

2. **Start MAMP**

3. **Import database via phpMyAdmin:**
   - http://localhost:8888/phpMyAdmin
   - Create `academichub` database
   - Import `database/schema.sql`

4. **Configure:**
```bash
   cp config.example.php config.php
```

5. **Access:**
   - http://localhost:8888/academichub

## Troubleshooting

### "Connection failed"
- Check MySQL is running
- Verify credentials in `config.php`

### "Table doesn't exist"
- Make sure you imported `database/schema.sql`

### "Permission denied"
- Linux: `sudo chown -R $USER:$USER /opt/lampp/htdocs/academichub`

### "Port already in use"
- Another service is using port 80 or 3306
- Stop other web servers or change ports in XAMPP/LAMP config
