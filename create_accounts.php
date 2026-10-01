<?php
// Include the existing database connection
require_once __DIR__ . '/config/database.php';

try {
    // ==========================================
    // 1. CREATE A NEW ADMIN ACCOUNT
    // ==========================================
    $adminUsername = 'admin1';
    $adminEmail = 'admin1@gmail.com.com';
    $adminPassword = 'admin123'; // Change this to your preferred password
    
    // Hash the password for security
    $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

    // Insert into the users table
    $stmtAdmin = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmtAdmin->execute([$adminUsername, $adminEmail, $adminHash]);
    
    echo "<h3>Admin account created successfully!</h3>";
    echo "Username: <strong>$adminUsername</strong><br><br>";


    // ==========================================
    // 2. CREATE A NEW TENANT PROFILE
    // ==========================================
    $tenantFullName = 'Jojo Siwa';
    $tenantEmail = 'jojo@gmail.com';
    $tenantContact = '555-0987';
    
    // Insert into the tenants table first to generate an ID
    $stmtTenantProfile = $pdo->prepare("INSERT INTO tenants (full_name, contact_number, email) VALUES (?, ?, ?)");
    $stmtTenantProfile->execute([$tenantFullName, $tenantContact, $tenantEmail]);
    
    // Retrieve the ID of the tenant we just created
    $newTenantId = $pdo->lastInsertId();

    // ==========================================
    // 3. CREATE THE TENANT LOGIN ACCOUNT
    // ==========================================
    $tenantUsername = 'alex_j';
    $tenantPassword = 'TenantPassword123!'; // Change this to your preferred password
    
    // Hash the tenant's password
    $tenantHash = password_hash($tenantPassword, PASSWORD_DEFAULT);

    // Insert into the users table and link the tenant_id
    $stmtTenantLogin = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, tenant_id) VALUES (?, ?, ?, 'tenant', ?)");
    $stmtTenantLogin->execute([$tenantUsername, $tenantEmail, $tenantHash, $newTenantId]);

    echo "<h3>Tenant account created successfully!</h3>";
    echo "Username: <strong>$tenantUsername</strong><br>";

} catch (PDOException $e) {
    // If a username or email already exists, it will throw an error here
    echo "Database Error: " . $e->getMessage();
}
?>