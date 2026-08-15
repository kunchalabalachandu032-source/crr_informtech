@echo off
cls
title CRR-INFORMTECH Development Server (Port 8080)

echo ========================================================================
echo             SIR C.R. REDDY COLLEGE OF ENGINEERING
echo           DEPARTMENT OF INFORMATION TECHNOLOGY (CRR-INFORMTECH)
echo ========================================================================
echo.
echo [1] MAIN PORTAL URLS (PORT 8080 FRESH SERVER):
echo ------------------------------------------------------------------------
echo 1. ADMIN LOGIN PORTAL:       http://localhost:8080/admin/login.php
echo 2. CR LOGIN PORTAL:          http://localhost:8080/cr/login.php
echo 3. STUDENT ACADEMIC PORTAL:  http://localhost:8080/student/index.php
echo ------------------------------------------------------------------------
echo.
echo Opening Fresh CR Login Portal in Browser...
start "" "http://localhost:8080/cr/login.php"
echo.
echo Starting Fresh PHP Development Server on Port 8080...
echo Server Running at: http://localhost:8080/
echo Press Ctrl + C to stop server.
echo ========================================================================
echo.

cd /d "%~dp0.."
php -S localhost:8080

pause