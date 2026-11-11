@echo off
setlocal
cd /d "%~dp0..\ccp-api"
call composer install
if errorlevel 1 exit /b 1
php.exe ..\scripts\prepare-env.php
if errorlevel 1 exit /b 1
php.exe artisan config:clear
if errorlevel 1 exit /b 1
php.exe artisan route:clear
if errorlevel 1 exit /b 1
php.exe artisan migrate
if errorlevel 1 exit /b 1
cd ..\ccp-web
call npm ci
if errorlevel 1 exit /b 1
if not exist .env copy .env.example .env >nul
echo Update complete. Existing database and APP_KEY were preserved.
