#!/bin/bash
# Admin Access Setup Script for Windows (Git Bash)

echo "================================"
echo "PaperVista Admin Setup"
echo "================================"
echo ""

cd "$(dirname "$0")"

echo "This script will help you set up admin access."
echo ""
echo "Current status:"
php -r "
require_once 'includes/config.php';
\$result = \$conn->query('SELECT email, role FROM users WHERE role = \"admin\"');
if (\$result->num_rows > 0) {
    \$admin = \$result->fetch_assoc();
    echo '✓ Admin user exists: ' . \$admin['email'] . PHP_EOL;
} else {
    echo '✗ No admin user found' . PHP_EOL;
}
"

echo ""
echo "Options:"
echo "1. Set new admin password"
echo "2. Create new admin user"
echo "3. Check database status"
echo ""
read -p "Select option (1-3): " choice

case $choice in
    1)
        echo ""
        echo "Enter new password for admin@university.edu:"
        read -s password
        echo ""
        
        php -r "
        require_once 'includes/config.php';
        \$email = 'admin@university.edu';
        \$hashed = password_hash('$password', PASSWORD_DEFAULT);
        \$stmt = \$conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        \$stmt->bind_param('ss', \$hashed, \$email);
        if (\$stmt->execute()) {
            echo '✓ Password updated successfully!' . PHP_EOL;
            echo 'You can now login with:' . PHP_EOL;
            echo 'Email: admin@university.edu' . PHP_EOL;
            echo 'Password: ' . '$password' . PHP_EOL;
        } else {
            echo '✗ Error: ' . \$stmt->error . PHP_EOL;
        }
        "
        ;;
    2)
        echo ""
        read -p "Enter email for new admin: " new_email
        read -p "Enter first name: " first_name
        read -p "Enter last name: " last_name
        read -s -p "Enter password: " new_password
        echo ""
        
        php -r "
        require_once 'includes/config.php';
        \$email = '$new_email';
        \$fname = '$first_name';
        \$lname = '$last_name';
        \$hashed = password_hash('$new_password', PASSWORD_DEFAULT);
        
        \$stmt = \$conn->prepare('INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, \"admin\")');
        \$stmt->bind_param('ssss', \$email, \$hashed, \$fname, \$lname);
        if (\$stmt->execute()) {
            echo '✓ Admin user created!' . PHP_EOL;
            echo 'Email: ' . \$email . PHP_EOL;
        } else {
            echo '✗ Error: ' . \$stmt->error . PHP_EOL;
        }
        "
        ;;
    3)
        echo ""
        php -r "
        require_once 'includes/config.php';
        echo 'Admins in database:' . PHP_EOL;
        \$result = \$conn->query('SELECT id, email, first_name, last_name FROM users WHERE role = \"admin\"');
        if (\$result->num_rows > 0) {
            while (\$row = \$result->fetch_assoc()) {
                echo '- ' . \$row['email'] . ' (' . \$row['first_name'] . ' ' . \$row['last_name'] . ')' . PHP_EOL;
            }
        } else {
            echo 'No admin users found' . PHP_EOL;
        }
        "
        ;;
    *)
        echo "Invalid option"
        ;;
esac

echo ""
echo "Admin access URL: http://localhost/php_summarizer/admin/"
echo ""
