const express = require('express');
const session = require('express-session');
const path = require('path');
const fs = require('fs');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Render Reverse Proxy Trust (Mandatory for Render Deployment)
app.set('trust proxy', 1);

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Session Configuration
app.use(session({
    secret: process.env.SESSION_SECRET || 'crr_informtech_secret_key_2026',
    resave: false,
    saveUninitialized: false,
    cookie: {
        secure: false, // Set to false to allow both HTTP and HTTPS proxies
        maxAge: 24 * 60 * 60 * 1000 // 24 Hours session
    }
}));

// Ensure all Upload Folders exist on Server
const uploadDirs = [
    'uploads',
    'uploads/announcements',
    'uploads/works',
    'uploads/assignments',
    'uploads/mid_marks',
    'uploads/important_questions',
    'uploads/observations',
    'uploads/records',
    'uploads/syllabus'
];
uploadDirs.forEach(dir => {
    const fullPath = path.join(__dirname, dir);
    if (!fs.existsSync(fullPath)) {
        fs.mkdirSync(fullPath, { recursive: true });
    }
});

// Serve Static Frontend and Uploaded Files
app.use(express.static(path.join(__dirname, 'public')));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// API Routes
app.use('/api/auth', require('./routes/authRoutes'));
app.use('/api/admin', require('./routes/adminRoutes'));
app.use('/api/cr', require('./routes/crRoutes'));
app.use('/api/student', require('./routes/studentRoutes'));
app.use('/api/subjects', require('./routes/subjectRoutes'));
app.use('/api/syllabus', require('./routes/syllabusRoutes'));
app.use('/api/announcements', require('./routes/announcementRoutes'));
app.use('/api', require('./routes/resourceRoutes'));

// Fallback Route to Landing Page
app.get('*', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Start Express Server
app.listen(PORT, () => {
    console.log(`🚀 CRR-INFORMTECH Server running on port ${PORT}`);
});