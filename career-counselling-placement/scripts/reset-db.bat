@echo off
setlocal
echo WARNING: This deletes ALL records in the configured database and recreates demo data.
set /p "confirmation=Type RESET to continue: "
if not "%confirmation%"=="RESET" exit /b 1
cd /d "%~dp0..\ccp-api"
php.exe artisan migrate:fresh --seed
