const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireAdminOrCR } = require('../middleware/auth');

// 1. GET ALL UNIQUE SUBJECTS BY YEAR (Smart Match across ALL Sections)
router.get('/all-by-year', async (req, res) => {
    const { year } = req.query;
    try {
        const yrDigit = (year || '2').toString().replace(/\D/g, '') || '2';
        const [rows] = await db.query(
            "SELECT DISTINCT subject_name, subject_type FROM subjects WHERE year LIKE ? OR year LIKE ? OR year = ? ORDER BY subject_name ASC",
            [`%${yrDigit}%`, `%${yrDigit === '2' ? '2nd' : '3rd'}%`, yrDigit]
        );
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 2. GET SUBJECTS FOR SPECIFIC YEAR & SECTION
router.get('/', async (req, res) => {
    const { year, section } = req.query;
    try {
        let query = "SELECT * FROM subjects";
        let params = [];

        if (year && section) {
            const yrDigit = year.toString().replace(/\D/g, '');
            query += " WHERE (year LIKE ? OR year = ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', ''))";
            params = [`%${yrDigit}%`, year, section, section];
        } else if (year) {
            const yrDigit = year.toString().replace(/\D/g, '');
            query += " WHERE year LIKE ? OR year = ?";
            params = [`%${yrDigit}%`, year];
        }

        query += " ORDER BY subject_type ASC, id ASC";
        const [rows] = await db.query(query, params);
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 3. POST ADD SUBJECT (With Automatic Session Fallbacks to prevent NULL year/section)
router.post('/', requireAdminOrCR, async (req, res) => {
    let { year, section, subject_name, subject_type } = req.body;

    // Automatic fallbacks from session if year or section is omitted or null
    if (!year || year.toString().trim() === '' || year === 'undefined' || year === 'null') {
        year = req.session.cr_year || req.session.year || '2';
    }
    if (!section || section.toString().trim() === '' || section === 'undefined' || section === 'null') {
        section = req.session.cr_section || req.session.section || 'IT2A';
    }

    try {
        await db.query(
            "INSERT INTO subjects (year, section, subject_name, subject_type) VALUES (?, ?, ?, ?)",
            [year, section, subject_name, subject_type || 'Theory']
        );
        res.json({ success: true, message: 'Subject added successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 4. PUT EDIT SUBJECT
router.put('/:id', requireAdminOrCR, async (req, res) => {
    const { subject_name, subject_type } = req.body;
    try {
        await db.query("UPDATE subjects SET subject_name = ?, subject_type = ? WHERE id = ?", [subject_name, subject_type, req.params.id]);
        res.json({ success: true, message: 'Subject updated successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 5. DELETE SUBJECT
router.delete('/:id', requireAdminOrCR, async (req, res) => {
    try {
        await db.query("DELETE FROM subjects WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Subject deleted successfully' });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;