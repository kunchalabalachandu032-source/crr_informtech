const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireAdmin } = require('../middleware/auth');

// 1. GET Dashboard Stats
router.get('/dashboard-stats', requireAdmin, async (req, res) => {
    try {
        const [[{ crCount }]] = await db.query("SELECT COUNT(*) AS crCount FROM cr_accounts");
        const [[{ subjectCount }]] = await db.query("SELECT COUNT(*) AS subjectCount FROM subjects");
        const [[{ facultyCount }]] = await db.query("SELECT COUNT(*) AS facultyCount FROM faculty");
        const [[{ announcementCount }]] = await db.query("SELECT COUNT(*) AS announcementCount FROM announcements");

        res.json({
            success: true,
            data: {
                username: req.session.username || 'admin',
                crCount,
                subjectCount,
                facultyCount,
                announcementCount
            }
        });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 2. POST UPDATE ADMIN CREDENTIALS (Change Username / Email & Password)
router.post('/update-credentials', requireAdmin, async (req, res) => {
    const { new_username, new_password, confirm_password } = req.body;
    try {
        if (!new_username || new_username.trim() === '') {
            return res.status(400).json({ success: false, message: 'Username / Email cannot be empty.' });
        }

        const currentAdmin = req.session.username || 'admin';

        if (new_password && new_password.trim() !== '') {
            if (new_password !== confirm_password) {
                return res.status(400).json({ success: false, message: 'Passwords do not match.' });
            }
            // Update both Username/Email and Password in DB
            await db.query("UPDATE admins SET username = ?, password = ? WHERE username = ? OR id = 1", [new_username.trim(), new_password, currentAdmin]);
            try { await db.query("UPDATE managers SET username = ?, password = ? WHERE role = 'admin'", [new_username.trim(), new_password]); } catch(e){}
        } else {
            // Update Username/Email only
            await db.query("UPDATE admins SET username = ? WHERE username = ? OR id = 1", [new_username.trim(), currentAdmin]);
            try { await db.query("UPDATE managers SET username = ? WHERE role = 'admin'", [new_username.trim()]); } catch(e){}
        }

        // Update Session Username
        req.session.username = new_username.trim();
        res.json({ success: true, message: 'Admin username and credentials updated successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 3. GET CR Accounts List
router.get('/crs', requireAdmin, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT * FROM cr_accounts ORDER BY id DESC");
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 4. POST Create New CR Account
router.post('/crs', requireAdmin, async (req, res) => {
    const { name, roll_number, year, section, email, phone, password } = req.body;
    try {
        const [existing] = await db.query("SELECT id FROM cr_accounts WHERE email = ? OR roll_number = ?", [email, roll_number]);
        if (existing.length > 0) {
            return res.status(400).json({ success: false, message: 'CR with this Email or Roll Number already exists.' });
        }

        await db.query(
            "INSERT INTO cr_accounts (name, roll_number, year, section, email, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [name, roll_number, year, section, email, phone, password]
        );

        // Sync legacy tables if present
        try {
            await db.query("INSERT INTO crs (name, roll_number, year, section, email, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?)", [name, roll_number, year, section, email, phone, password]);
        } catch(e){}

        res.json({ success: true, message: 'CR Account created successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 5. DELETE CR Account
router.delete('/crs/:id', requireAdmin, async (req, res) => {
    try {
        await db.query("DELETE FROM cr_accounts WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'CR Account deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 6. GET Faculty Directory
router.get('/faculty', requireAdmin, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT * FROM faculty ORDER BY id DESC");
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 7. POST Add Faculty
router.post('/faculty', requireAdmin, async (req, res) => {
    const { name, designation } = req.body;
    try {
        await db.query("INSERT INTO faculty (name, designation) VALUES (?, ?)", [name, designation || 'IT Faculty']);
        res.json({ success: true, message: 'Faculty added successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 8. DELETE Faculty
router.delete('/faculty/:id', requireAdmin, async (req, res) => {
    try {
        await db.query("DELETE FROM faculty WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Faculty deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;