const mysql = require('mysql2/promise');
require('dotenv').config();

(async () => {
  try {
    console.log("MySQL ki connect avuthundi...");
    // Database name lekunda connect avuthunnam (kothaga create cheyadaniki)
    const connection = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      port: process.env.DB_PORT || 3307,
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || 'root@123',
    });

    const dbName = process.env.DB_NAME || 'crr_informtech';
    
    console.log(`'${dbName}' database ni create chestundi...`);
    await connection.query(`CREATE DATABASE IF NOT EXISTS \`${dbName}\``);
    await connection.query(`USE \`${dbName}\``);

    console.log("Tables create chestundi...");
    
    // Admins table
    await connection.query(`
      CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Default admin account create chestundi
    const [adminRows] = await connection.query("SELECT * FROM admins WHERE username = 'admin'");
    if (adminRows.length === 0) {
      await connection.query("INSERT INTO admins (username, password) VALUES ('admin', 'admin123')");
      console.log("Admin account created: username: admin / password: admin123");
    }

    // Faculty table
    await connection.query(`
      CREATE TABLE IF NOT EXISTS faculty (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        designation VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Announcements
    await connection.query(`
      CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        target_audience VARCHAR(50) DEFAULT 'All',
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Subjects
    await connection.query(`
      CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year VARCHAR(10) NOT NULL,
        section VARCHAR(10) NOT NULL,
        subject_name VARCHAR(100) NOT NULL,
        subject_type VARCHAR(20) DEFAULT 'Theory'
      )
    `);

    // CR Accounts
    await connection.query(`
      CREATE TABLE IF NOT EXISTS cr_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        roll_number VARCHAR(50) NOT NULL,
        year VARCHAR(10) NOT NULL,
        section VARCHAR(10) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL
      )
    `);

    // Legacy tables
    await connection.query(`CREATE TABLE IF NOT EXISTS crs LIKE cr_accounts`);
    await connection.query(`CREATE TABLE IF NOT EXISTS managers (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), email VARCHAR(100), password VARCHAR(255), role VARCHAR(20))`);
    await connection.query(`CREATE TABLE IF NOT EXISTS cr_users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), email VARCHAR(100), password VARCHAR(255), year VARCHAR(10), section VARCHAR(10))`);

    // Resource Tables
    const resourceSchema = `
      id INT AUTO_INCREMENT PRIMARY KEY,
      year VARCHAR(10),
      section VARCHAR(10),
      subject_name VARCHAR(100),
      title VARCHAR(255),
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    `;
    
    await connection.query(`CREATE TABLE IF NOT EXISTS class_works (${resourceSchema}, work_file VARCHAR(255))`);
    await connection.query(`CREATE TABLE IF NOT EXISTS assignments (${resourceSchema}, due_date DATE, assignment_file VARCHAR(255))`);
    await connection.query(`CREATE TABLE IF NOT EXISTS mid_marks (${resourceSchema}, sheet_file VARCHAR(255))`);
    await connection.query(`CREATE TABLE IF NOT EXISTS important_questions (${resourceSchema}, unit_number VARCHAR(50), question_file VARCHAR(255))`);
    await connection.query(`CREATE TABLE IF NOT EXISTS lab_observations (${resourceSchema}, experiment_no VARCHAR(50), obs_file VARCHAR(255))`);
    await connection.query(`CREATE TABLE IF NOT EXISTS lab_records (${resourceSchema}, experiment_no VARCHAR(50), record_file VARCHAR(255))`);

    console.log("✅ Database and all tables successfully created!");
    console.log("🎉 Ippudu meeru 'npm start' run chesi app vadukovachu.");
    process.exit(0);
  } catch (error) {
    console.error("❌ Error setting up database:", error);
    process.exit(1);
  }
})();