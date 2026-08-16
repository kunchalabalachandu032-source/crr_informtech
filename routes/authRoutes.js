const express = require('express');
const router = express.Router();
const db = require('../db');

// 1. POST ADMIN LOGIN (Matches Saved Email or Username & Password)
router.post('/admin/login', async (req, res) => {
    const { username, password } = req.body;
    try {
        if (!username || !password) {
            return res.status(400).json({ success: false, message: 'Username/Email and Password are required.' });
        }

        const inputUser = username.trim();

        // 1st Check: admins table
        const [adminRows] = await db.query(
            "SELECT * FROM admins WHERE LOWER(username) = LOWER(?) AND password = ?",
            [inputUser, password]
        );

        if (adminRows.length > 0) {
            req.session.admin_logged_in = true;
            req.session.role = 'admin';
            req.session.username = adminRows[0].username;
            return res.json({ success: true, message: 'Admin login successful!' });
        }

        // 2nd Check: managers table
        const [managerRows] = await db.query(
            "SELECT * FROM managers WHERE LOWER(username) = LOWER(?) AND password = ?",
            [inputUser, password]
        );

        if (managerRows.length > 0) {
            req.session.admin_logged_in = true;
            req.session.role = 'admin';
            req.session.username = managerRows[0].username;
            return res.json({ success: true, message: 'Admin login successful!' });
        }

        // 3rd Fallback Check
        if ((inputUser.toLowerCase() === 'admin' || inputUser === 'kunchalabalachandu032') && password === 'chandu@2213') {
            req.session.admin_logged_in = true;
            req.session.role = 'admin';
            req.session.username = inputUser;
            return res.json({ success: true, message: 'Admin login successful!' });
        }

        return res.status(401).json({ success: false, message: 'Invalid Admin Username/Email or Password.' });

    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 2. POST CR LOGIN (Flexible login with Roll Number, Email, or Name)
router.post('/cr/login', async (req, res) => {
    const { roll_number, password } = req.body;
    try {
        if (!roll_number || !password) {
            return res.status(400).json({ success: false, message: 'Identifier and password required.' });
        }

        const identifier = roll_number.trim();

        // Search cr_accounts table
        const [rows] = await db.query(
            `SELECT * FROM cr_accounts 
             WHERE (LOWER(roll_number) = LOWER(?) OR LOWER(email) = LOWER(?) OR LOWER(name) = LOWER(?)) 
             AND password = ?`,
            [identifier, identifier, identifier, password]
        );

        if (rows.length > 0) {
            const cr = rows[0];
            req.session.cr_logged_in = true;
            req.session.role = 'cr';
            req.session.cr_name = cr.name;
            req.session.cr_year = cr.year;
            req.session.cr_section = cr.section;
            req.session.cr_roll = cr.roll_number;

            return res.json({
                success: true,
                message: 'CR Login successful!',
                data: { name: cr.name, year: cr.year, section: cr.section }
            });
        }

        return res.status(401).json({ success: false, message: 'Invalid CR Credentials.' });

    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 3. POST LOGOUT
router.post('/logout', (req, res) => {
    req.session.destroy(err => {
        if (err) return res.status(500).json({ success: false, message: 'Logout failed.' });
        res.json({ success: true, message: 'Logged out successfully.' });
    });
});

// 4. GET ME SESSION INFO
router.get('/me', (req, res) => {
    if (req.session.admin_logged_in) {
        res.json({ success: true, role: 'admin', username: req.session.username });
    } else if (req.session.cr_logged_in) {
        res.json({
            success: true,
            role: 'cr',
            name: req.session.cr_name,
            year: req.session.cr_year,
            section: req.session.cr_section
        });
    } else {
        res.json({ success: false, loggedIn: false });
    }
});

module.exports = router;