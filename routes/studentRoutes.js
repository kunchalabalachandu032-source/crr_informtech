const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireCR } = require('../middleware/auth');

// 1. GET Subjects for Student Portal
router.get('/subjects', async (req, res) => {
    let { year, section } = req.query;
    if (!year || !section) {
        return res.status(400).json({ success: false, message: 'Year and section required' });
    }
    const cleanYear = year.toString().replace(/[^0-9]/g, '');

    try {
        const [rows] = await db.query(
            `SELECT * FROM subjects 
             WHERE (year = ? OR year LIKE ?) 
             AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) 
             ORDER BY subject_type ASC, id ASC`,
            [cleanYear, `%${cleanYear}%`, section, section]
        );

        if (rows.length === 0) {
            const [fallback] = await db.query(
                `SELECT * FROM subjects WHERE year = ? OR year LIKE ? ORDER BY subject_type ASC, id ASC`,
                [cleanYear, `%${cleanYear}%`]
            );
            return res.json({ success: true, data: fallback });
        }

        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 2. GET Resource Counts per Subject (For Real-Time Yellow Dot Indicator System)
router.get('/resource-counts', async (req, res) => {
    const { year, section } = req.query;
    if (!year || !section) return res.json({ success: true, counts: {} });
    const cleanYear = year.toString().replace(/[^0-9]/g, '');

    try {
        const [works] = await db.query("SELECT subject_name, COUNT(*) as cnt FROM class_works WHERE (year = ? OR year LIKE ?) AND LOWER(section) = LOWER(?) GROUP BY subject_name", [cleanYear, `%${cleanYear}%`, section]);
        const [assigns] = await db.query("SELECT subject_name, COUNT(*) as cnt FROM assignments WHERE (year = ? OR year LIKE ?) AND LOWER(section) = LOWER(?) GROUP BY subject_name", [cleanYear, `%${cleanYear}%`, section]);
        const [mids] = await db.query("SELECT subject_name, COUNT(*) as cnt FROM mid_marks WHERE (year = ? OR year LIKE ?) AND LOWER(section) = LOWER(?) GROUP BY subject_name", [cleanYear, `%${cleanYear}%`, section]);
        const [imps] = await db.query("SELECT subject_name, COUNT(*) as cnt FROM important_questions WHERE (year = ? OR year LIKE ?) AND LOWER(section) = LOWER(?) GROUP BY subject_name", [cleanYear, `%${cleanYear}%`, section]);
        const [obs] = await db.query("SELECT subject_name, COUNT(*) as cnt FROM lab_observations WHERE (year = ? OR year LIKE ?) AND LOWER(section) = LOWER(?) GROUP BY subject_name", [cleanYear, `%${cleanYear}%`, section]);
        const [recs] = await db.query("SELECT subject_name, COUNT(*) as cnt FROM lab_records WHERE (year = ? OR year LIKE ?) AND LOWER(section) = LOWER(?) GROUP BY subject_name", [cleanYear, `%${cleanYear}%`, section]);

        const countsMap = {};
        const addCounts = (list) => {
            list.forEach(item => {
                const sName = item.subject_name.trim();
                countsMap[sName] = (countsMap[sName] || 0) + item.cnt;
            });
        };
        addCounts(works); addCounts(assigns); addCounts(mids); addCounts(imps); addCounts(obs); addCounts(recs);

        res.json({ success: true, counts: countsMap });
    } catch(err) {
        res.json({ success: false, counts: {} });
    }
});

// 3. GET CRs for Student Portal
router.get('/crs', async (req, res) => {
    let { year, section } = req.query;
    const cleanYear = year ? year.toString().replace(/[^0-9]/g, '') : '';

    try {
        let query = "SELECT id, name, roll_number, year, section, email, phone FROM cr_accounts";
        let params = [];

        if (cleanYear && section) {
            query += " WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', ''))";
            params = [cleanYear, `%${cleanYear}%`, section, section];
        }

        const [rows] = await db.query(query, params);
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

// 4. GET Announcements for Student Portal
router.get('/announcements', async (req, res) => {
    const { year } = req.query;
    try {
        let query = "SELECT * FROM announcements";
        let params = [];
        if (year) {
            const yrStr = year.toString().includes('2') ? '2nd Year' : '3rd Year';
            query += " WHERE target_audience = 'All' OR target_audience = ? OR target_audience LIKE ?";
            params = [yrStr, `%${year}%`];
        }
        query += " ORDER BY id DESC LIMIT 5";
        const [rows] = await db.query(query, params);
        res.json({ success: true, data: rows });
    } catch (err) {
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;