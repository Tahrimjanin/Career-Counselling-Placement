@echo off
setlocal
cd /d "%~dp0.."
if not exist ccp-api\vendor\autoload.php (
 echo Run scripts\setup.bat or scripts\update.bat first.
 exit /b 1
)
if not exist ccp-api\.env (
 echo Backend .env missing. Run the setup/update helper first.
 exit /b 1
)
if not exist ccp-web\node_modules (
 echo Frontend dependencies missing. Run npm ci in ccp-web first.
 exit /b 1
)
start "CCP API" /D "%CD%\ccp-api" cmd /k "php.exe artisan serve --host=localhost --port=8000"
start "CCP Frontend" /D "%CD%\ccp-web" cmd /k "npm run dev -- --host localhost --port 5173 --strictPort"
start "CCP Mail Queue" /D "%CD%\ccp-api" cmd /k "php.exe artisan queue:work --tries=3"
start "CCP Scheduler" /D "%CD%\ccp-api" cmd /k "php.exe artisan schedule:work"
echo Open http://localhost:5173 . Keep XAMPP MySQL running. Ctrl+C stops each process.
