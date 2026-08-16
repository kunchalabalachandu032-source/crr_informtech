const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireCR } = require('../middleware/auth');

router.get('/dashboard', requireCR, async (req, res) => {
    try {
        const { cr_name, cr_roll, cr_year, cr_section } = req.session;

        // 1. Count subjects ONLY for this CR's assigned year & section
        const [subRows] = await db.query(
            "SELECT COUNT(*) as total FROM subjects WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', ''))",
            [cr_year, `%${cr_year}%`, cr_section, cr_section]
        );

        // 2. Count active assignments ONLY for this section
        const [assignRows] = await db.query(
            "SELECT COUNT(*) as total FROM assignments WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', ''))",
            [cr_year, `%${cr_year}%`, cr_section, cr_section]
        );

        // 3. Count class works ONLY for this section
        const [workRows] = await db.query(
            "SELECT COUNT(*) as total FROM class_works WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', ''))",
            [cr_year, `%${cr_year}%`, cr_section, cr_section]
        );

        // 4. Fetch subjects ONLY for this CR's assigned section
        const [subjects] = await db.query(
            "SELECT * FROM subjects WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) ORDER BY subject_type ASC, id ASC",
            [cr_year, `%${cr_year}%`, cr_section, cr_section]
        );

        res.json({
            success: true,
            data: {
                name: cr_name,
                roll_number: cr_roll,
                year: cr_year,
                section: cr_section,
                subjectCount: subRows[0].total,
                assignmentCount: assignRows[0].total,
                classWorkCount: workRows[0].total,
                subjects: subjects
            }
        });
    } catch (err) {
        console.error("CR Dashboard Error:", err);
        res.status(500).json({ success: false, message: err.message });
    }
});

module.exports = router;