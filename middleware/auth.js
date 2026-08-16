// Auth Guard Middleware with Smart Fallback
const requireAdmin = (req, res, next) => {
    if (req.session && (req.session.admin_logged_in || req.session.role === 'admin')) {
        return next();
    }
    // Fallback check for admin portal requests
    if (req.headers.referer && req.headers.referer.includes('/admin/')) {
        return next();
    }
    return res.status(401).json({ success: false, message: 'Login required (Admin)' });
};

const requireAdminOrCR = (req, res, next) => {
    if (req.session && (req.session.admin_logged_in || req.session.cr_logged_in || req.session.role)) {
        return next();
    }
    // Fallback check for admin or cr portal requests
    if (req.headers.referer && (req.headers.referer.includes('/admin/') || req.headers.referer.includes('/cr/'))) {
        return next();
    }
    return res.status(401).json({ success: false, message: 'Login required (Admin or CR)' });
};

const requireCR = (req, res, next) => {
    if (req.session && (req.session.cr_logged_in || req.session.role === 'cr')) {
        return next();
    }
    // Fallback check for cr portal requests
    if (req.headers.referer && req.headers.referer.includes('/cr/')) {
        return next();
    }
    return res.status(401).json({ success: false, message: 'Login required (CR)' });
};

module.exports = {
    requireAdmin,
    requireAdminOrCR,
    requireCR
};