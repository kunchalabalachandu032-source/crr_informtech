const express = require('express');
const router = express.Router();
const db = require('../db');
const { requireAdminOrCR } = require('../middleware/auth');
const { 
  uploadWorks, uploadAssignments, uploadMidMarks, 
  uploadImportantQuestions, uploadObservations, uploadRecords 
} = require('../middleware/upload');
const fs = require('fs');
const path = require('path');

const deleteFile = (filePath) => {
    if (filePath) {
        const fullPath = path.join(__dirname, '..', filePath);
        if (fs.existsSync(fullPath)) fs.unlinkSync(fullPath);
    }
};

// ==========================================
// 1. CLASS WORKS
// ==========================================
router.get(['/class-works', '/works', '/works.php'], async (req, res) => {
    const { year, section, subject } = req.query;
    try {
        const [rows] = await db.query(
            "SELECT * FROM class_works WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, section, section, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.post(['/class-works', '/works', '/works.php'], requireAdminOrCR, uploadWorks.single('work_file'), async (req, res) => {
    const { year, section, subject_name, title, description } = req.body;
    const work_file = req.file ? `uploads/works/${req.file.filename}` : null;
    try {
        await db.query("INSERT INTO class_works (year, section, subject_name, title, description, work_file) VALUES (?, ?, ?, ?, ?, ?)", [year, section, subject_name, title, description, work_file]);
        res.json({ success: true, message: 'Class work posted successfully!' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.delete(['/class-works/:id', '/works/:id'], requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT work_file FROM class_works WHERE id = ?", [req.params.id]);
        if (rows.length > 0) deleteFile(rows[0].work_file);
        await db.query("DELETE FROM class_works WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

// ==========================================
// 2. ASSIGNMENTS
// ==========================================
router.get(['/assignments', '/assignments.php'], async (req, res) => {
    const { year, section, subject } = req.query;
    try {
        const [rows] = await db.query(
            "SELECT * FROM assignments WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, section, section, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.post(['/assignments', '/assignments.php'], requireAdminOrCR, uploadAssignments.single('assignment_file'), async (req, res) => {
    const { year, section, subject_name, title, description, due_date } = req.body;
    const file = req.file ? `uploads/assignments/${req.file.filename}` : null;
    try {
        await db.query("INSERT INTO assignments (year, section, subject_name, title, description, due_date, assignment_file) VALUES (?, ?, ?, ?, ?, ?, ?)", [year, section, subject_name, title, description, due_date, file]);
        res.json({ success: true, message: 'Assignment posted successfully!' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.delete(['/assignments/:id'], requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT assignment_file FROM assignments WHERE id = ?", [req.params.id]);
        if (rows.length > 0) deleteFile(rows[0].assignment_file);
        await db.query("DELETE FROM assignments WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

// ==========================================
// 3. MID MARKS
// ==========================================
router.get(['/mid-marks', '/mid_marks', '/mid_marks.php'], async (req, res) => {
    const { year, section, subject } = req.query;
    try {
        const [rows] = await db.query(
             "SELECT * FROM mid_marks WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, section, section, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.post(['/mid-marks', '/mid_marks', '/mid_marks.php'], requireAdminOrCR, uploadMidMarks.single('sheet_file'), async (req, res) => {
    const { year, section, subject_name, title } = req.body;
    const file = req.file ? `uploads/mid_marks/${req.file.filename}` : null;
    try {
        await db.query("INSERT INTO mid_marks (year, section, subject_name, title, sheet_file) VALUES (?, ?, ?, ?, ?)", [year, section, subject_name, title, file]);
        res.json({ success: true, message: 'Mid Marks posted successfully!' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.delete(['/mid-marks/:id', '/mid_marks/:id'], requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT sheet_file FROM mid_marks WHERE id = ?", [req.params.id]);
        if (rows.length > 0) deleteFile(rows[0].sheet_file);
        await db.query("DELETE FROM mid_marks WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

// ==========================================
// 4. IMPORTANT QUESTIONS
// ==========================================
router.get(['/important-questions', '/important_questions', '/important_questions.php'], async (req, res) => {
    const { year, section, subject } = req.query;
    try {
        const [rows] = await db.query(
             "SELECT * FROM important_questions WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, section, section, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.post(['/important-questions', '/important_questions', '/important_questions.php'], requireAdminOrCR, uploadImportantQuestions.single('question_file'), async (req, res) => {
    const { year, section, subject_name, unit_number, title, questions_text } = req.body;
    let file = req.file ? `uploads/important_questions/${req.file.filename}` : null;
    try {
        await db.query("INSERT INTO important_questions (year, section, subject_name, unit_number, title, description, question_file) VALUES (?, ?, ?, ?, ?, ?, ?)", [year, section, subject_name, unit_number || '1', title, questions_text || '', file]);
        res.json({ success: true, message: 'Questions posted successfully!' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.delete(['/important-questions/:id', '/important_questions/:id'], requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT question_file FROM important_questions WHERE id = ?", [req.params.id]);
        if (rows.length > 0) deleteFile(rows[0].question_file);
        await db.query("DELETE FROM important_questions WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

// ==========================================
// 5. LAB OBSERVATIONS (Fixed Mismatch!)
// ==========================================
router.get(['/lab-observations', '/observations', '/observations.php'], async (req, res) => {
    const { year, section, subject } = req.query;
    try {
        const [rows] = await db.query(
             "SELECT * FROM lab_observations WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, section, section, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.post(['/lab-observations', '/observations', '/observations.php'], requireAdminOrCR, uploadObservations.single('obs_file'), async (req, res) => {
    const { year, section, subject_name, experiment_no, title, description } = req.body;
    const file = req.file ? `uploads/observations/${req.file.filename}` : null;
    try {
        await db.query("INSERT INTO lab_observations (year, section, subject_name, experiment_no, title, description, obs_file) VALUES (?, ?, ?, ?, ?, ?, ?)", [year, section, subject_name, experiment_no || '1', title, description || '', file]);
        res.json({ success: true, message: 'Observation posted successfully!' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.delete(['/lab-observations/:id', '/observations/:id'], requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT obs_file FROM lab_observations WHERE id = ?", [req.params.id]);
        if (rows.length > 0) deleteFile(rows[0].obs_file);
        await db.query("DELETE FROM lab_observations WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

// ==========================================
// 6. LAB RECORDS (Fixed Mismatch!)
// ==========================================
router.get(['/lab-records', '/records', '/record_pdf', '/record_pdf.php'], async (req, res) => {
    const { year, section, subject } = req.query;
    try {
        const [rows] = await db.query(
             "SELECT * FROM lab_records WHERE (year = ? OR year LIKE ?) AND (LOWER(section) = LOWER(?) OR REPLACE(LOWER(section), '-', '') = REPLACE(LOWER(?), '-', '')) AND LOWER(subject_name) = LOWER(?) ORDER BY id DESC",
            [year, `%${year}%`, section, section, subject]
        );
        res.json({ success: true, data: rows });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.post(['/lab-records', '/records', '/record_pdf', '/record_pdf.php'], requireAdminOrCR, uploadRecords.single('record_file'), async (req, res) => {
    const { year, section, subject_name, experiment_no, title } = req.body;
    const file = req.file ? `uploads/records/${req.file.filename}` : null;
    try {
        await db.query("INSERT INTO lab_records (year, section, subject_name, experiment_no, title, record_file) VALUES (?, ?, ?, ?, ?, ?)", [year, section, subject_name, experiment_no || '1', title, file]);
        res.json({ success: true, message: 'Record posted successfully!' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

router.delete(['/lab-records/:id', '/records/:id', '/record_pdf/:id'], requireAdminOrCR, async (req, res) => {
    try {
        const [rows] = await db.query("SELECT record_file FROM lab_records WHERE id = ?", [req.params.id]);
        if (rows.length > 0) deleteFile(rows[0].record_file);
        await db.query("DELETE FROM lab_records WHERE id = ?", [req.params.id]);
        res.json({ success: true, message: 'Deleted successfully' });
    } catch (err) { res.status(500).json({ success: false, message: err.message }); }
});

module.exports = router;