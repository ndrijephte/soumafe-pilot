@echo off
REM === DEPLOY SOUMAFE PILOT — Session enrichissement + collapse ===
set SRC=D:\BUREAU_SOUMAFE\04_PROJETS\APP_SOUMAFE_PILOT\KIT_SPRINT0
set DST=C:\laragon\www\soumafe-pilot

REM --- Controllers ---
copy /Y "%SRC%\app\Http\Controllers\BlocageController.php" "%DST%\app\Http\Controllers\BlocageController.php"
copy /Y "%SRC%\app\Http\Controllers\CompteRenduController.php" "%DST%\app\Http\Controllers\CompteRenduController.php"
copy /Y "%SRC%\app\Http\Controllers\DashboardController.php" "%DST%\app\Http\Controllers\DashboardController.php"
copy /Y "%SRC%\app\Http\Controllers\TacheController.php" "%DST%\app\Http\Controllers\TacheController.php"

REM --- Services (new dir) ---
if not exist "%DST%\app\Services" mkdir "%DST%\app\Services"
copy /Y "%SRC%\app\Services\MailService.php" "%DST%\app\Services\MailService.php"

REM --- Views ---
copy /Y "%SRC%\resources\views\dashboard\index.blade.php" "%DST%\resources\views\dashboard\index.blade.php"
copy /Y "%SRC%\resources\views\dashboard\pilote.blade.php" "%DST%\resources\views\dashboard\pilote.blade.php"
copy /Y "%SRC%\resources\views\dashboard\dg.blade.php" "%DST%\resources\views\dashboard\dg.blade.php"
copy /Y "%SRC%\resources\views\layouts\app.blade.php" "%DST%\resources\views\layouts\app.blade.php"
copy /Y "%SRC%\resources\views\sprints\show.blade.php" "%DST%\resources\views\sprints\show.blade.php"
copy /Y "%SRC%\resources\views\comptes-rendus\create.blade.php" "%DST%\resources\views\comptes-rendus\create.blade.php"
copy /Y "%SRC%\resources\views\comptes-rendus\edit.blade.php" "%DST%\resources\views\comptes-rendus\edit.blade.php"
copy /Y "%SRC%\resources\views\comptes-rendus\show.blade.php" "%DST%\resources\views\comptes-rendus\show.blade.php"
copy /Y "%SRC%\resources\views\taches\show.blade.php" "%DST%\resources\views\taches\show.blade.php"
copy /Y "%SRC%\resources\views\taches\index.blade.php" "%DST%\resources\views\taches\index.blade.php"
copy /Y "%SRC%\resources\views\comptes-rendus\index.blade.php" "%DST%\resources\views\comptes-rendus\index.blade.php"

REM --- New views ---
if not exist "%DST%\resources\views\emails" mkdir "%DST%\resources\views\emails"
copy /Y "%SRC%\resources\views\emails\notification.blade.php" "%DST%\resources\views\emails\notification.blade.php"
copy /Y "%SRC%\resources\views\taches\pilote.blade.php" "%DST%\resources\views\taches\pilote.blade.php"

REM --- Routes ---
copy /Y "%SRC%\routes\web.php" "%DST%\routes\web.php"

REM --- Clear cache ---
del /Q "%DST%\storage\framework\views\*.php" 2>nul
cd /d "%DST%" && php artisan view:clear

echo.
echo === DEPLOY TERMINE ===
pause
