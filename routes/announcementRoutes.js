const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireAdminOrCR } = require('../middleware/auth');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const uploadDir = path.join(__dirname, '..', 'uploads', 'announcements');
if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
}

const storage = multer.diskStorage({
    destination: (req, file, cb) => cb(null, uploadDir),
    filename: (req, file, cb) => cb(null, Date.now() + '_' + file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_'))
});
const upload = multer({ storage });

// Auto-create announcements table
(async () => {
    try {
        await db.query(`
            CREATE TABLE IF NOT EXISTS announcements (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                target_audience VARCHAR(50) DEFAULT 'All',
                image_path VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
    } catch(e) {}
})();

// 1. GET all announcements
router.get('/', async (req, res) => {
    try {
        const [rows] = await db.query("SELECT * FROM announcements ORDER BY id DESC");
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 2. GET latest announcement for Instant Popup Modal
router.get('/latest', async (req, res) => {
    const { year } = req.query;
    try {
        let query = "SELECT * FROM announcements";
        let params = [];
        if (year) {
            const yrStr = year.toString().includes('2') ? '2nd Year' : '3rd Year';
            query += " WHERE target_audience = 'All' OR target_audience = ? OR target_audience LIKE ?";
            params = [yrStr, `%${year}%`];
        }
        query += " ORDER BY id DESC LIMIT 1";
        const [rows] = await db.query(query, params);
        res.json({ success: true, data: rows.length > 0 ? rows[0] : null });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 3. POST announcement (Admin or CR)
router.post('/', requireAdminOrCR, upload.single('poster'), async (req, res) => {
    const { title, content, target_audience } = req.body;
    const image_path = req.file ? `uploads/announcements/${req.file.filename}` : null;
    try {
        await db.query(
            "INSERT INTO announcements (title, content, target_audience, image_path) VALUES (?, ?, ?, ?)",
            [title, content, target_audience || 'All', image_path]
        );
        res.json({ success: true, message: 'Announcement posted successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 4. DELETE announcement
router.delete('/:id', requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT image_path FROM announcements WHERE id = ?", [req.params.id]);
        if (rows.length > 0 && rows[0].image_path) {
            const fullPath = path.join(__dirname, '..', rows[0].image_path);
            if (fs.existsSync(fullPath)) fs.unlinkSync(fullPath);
        }
        await db.query("DELETE FROM announcements WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Announcement deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;