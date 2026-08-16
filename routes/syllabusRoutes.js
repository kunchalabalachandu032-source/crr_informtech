const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireAdminOrCR } = require('../middleware/auth');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

// Ensure uploads/syllabus directory exists
const uploadDir = path.join(__dirname, '..', 'uploads', 'syllabus');
if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
}

const storage = multer.diskStorage({
    destination: (req, file, cb) => cb(null, uploadDir),
    filename: (req, file, cb) => cb(null, Date.now() + '_' + file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_'))
});

const upload = multer({ storage: storage });

// Auto-create syllabus table on startup
(async () => {
    try {
        await db.query(`
            CREATE TABLE IF NOT EXISTS syllabus (
                id INT AUTO_INCREMENT PRIMARY KEY,
                year VARCHAR(10) NOT NULL,
                subject_name VARCHAR(100) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                drive_link TEXT,
                file_path VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        `);
    } catch(e) { console.error("Syllabus Table Init Error:", e); }
})();

// 1. GET Syllabus for Subject
router.get('/', async (req, res) => {
    const { year, subject } = req.query;
    try {
        const [rows] = await db.query(
            "SELECT * FROM syllabus WHERE (year = ? OR year LIKE ?) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 2. POST / Upload Syllabus (PDF, Excel, Drive Link)
router.post('/', requireAdminOrCR, upload.single('syllabus_file'), async (req, res) => {
    const { year, subject_name, title, description, drive_link } = req.body;
    const file_path = req.file ? `uploads/syllabus/${req.file.filename}` : null;

    try {
        await db.query(
            "INSERT INTO syllabus (year, subject_name, title, description, drive_link, file_path) VALUES (?, ?, ?, ?, ?, ?)",
            [year, subject_name, title, description || '', drive_link || '', file_path]
        );
        res.json({ success: true, message: 'Syllabus uploaded successfully!' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 3. DELETE Syllabus
router.delete('/:id', requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT file_path FROM syllabus WHERE id = ?", [req.params.id]);
        if (rows.length > 0 && rows[0].file_path) {
            const fullPath = path.join(__dirname, '..', rows[0].file_path);
            if (fs.existsSync(fullPath)) fs.unlinkSync(fullPath);
        }
        await db.query("DELETE FROM syllabus WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Syllabus deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;