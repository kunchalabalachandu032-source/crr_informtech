const multer = require('multer');
const path = require('path');
const fs = require('fs');

// Base uploads directory
const UPLOADS_DIR = path.join(__dirname, '..', 'uploads');

// Ensure all upload subdirectories exist
const uploadFolders = ['works', 'assignments', 'mid_marks', 'important_questions', 'observations', 'records', 'faculty', 'announcements'];
uploadFolders.forEach(folder => {
  const dirPath = path.join(UPLOADS_DIR, folder);
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
  }
});

// Create multer storage for a specific upload subfolder
function createUploadStorage(subfolder) {
  return multer.diskStorage({
    destination: (req, file, cb) => {
      const dir = path.join(UPLOADS_DIR, subfolder);
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
      }
      cb(null, dir);
    },
    filename: (req, file, cb) => {
      // Match PHP naming: time() + '_' + sanitized original name
      const sanitized = file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_');
      const uniqueName = Date.now() + '_' + sanitized;
      cb(null, uniqueName);
    }
  });
}

// Pre-configured multer instances for each upload type
const uploadWorks = multer({ storage: createUploadStorage('works') });
const uploadAssignments = multer({ storage: createUploadStorage('assignments') });
const uploadMidMarks = multer({ storage: createUploadStorage('mid_marks') });
const uploadImportantQuestions = multer({ storage: createUploadStorage('important_questions') });
const uploadObservations = multer({ storage: createUploadStorage('observations') });
const uploadRecords = multer({ storage: createUploadStorage('records') });
const uploadFaculty = multer({ storage: createUploadStorage('faculty') });
const uploadAnnouncements = multer({ storage: createUploadStorage('announcements') });

module.exports = {
  UPLOADS_DIR,
  uploadWorks,
  uploadAssignments,
  uploadMidMarks,
  uploadImportantQuestions,
  uploadObservations,
  uploadRecords,
  uploadFaculty,
  uploadAnnouncements
};
