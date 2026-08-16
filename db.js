const mysql = require('mysql2/promise');
require('dotenv').config();

// Create the connection pool (Supports Localhost & TiDB Cloud SSL)
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT) || 3307,
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || 'root@123',
  database: process.env.DB_NAME || 'crr_informtech',
  ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: true } : false,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Test the database connection
(async () => {
  try {
    const connection = await pool.getConnection();
    console.log('✅ Successfully connected to MySQL database: ' + (process.env.DB_NAME || 'crr_informtech'));
    connection.release();
  } catch (error) {
    console.error('❌ Database connection error:', error.message);
    // Try alternate database name
    try {
      const altPool = mysql.createPool({
        host: process.env.DB_HOST || 'localhost',
        port: parseInt(process.env.DB_PORT) || 3307,
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || '',
        database: 'crrinformtech',
        ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: true } : false,
        waitForConnections: true,
        connectionLimit: 10,
        queueLimit: 0
      });
      const conn2 = await altPool.getConnection();
      console.log('✅ Connected to alternate database: crrinformtech');
      conn2.release();
    } catch (err2) {
      console.error('❌ Could not connect to any database variant:', err2.message);
    }
  }
})();

module.exports = pool;