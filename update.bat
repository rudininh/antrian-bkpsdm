@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Update Antrian BKPSDM

cd /d "%~dp0"

set "APP_NAME=Antrian BKPSDM"
set "REMOTE_NAME=origin"
set "MAINTENANCE_ENABLED=0"
set "CURRENT_BRANCH="
set "HAS_LOCAL_CHANGES=0"

call :banner

if not exist ".git" (
    echo [ERROR] Folder ini bukan repository Git.
    echo Jalankan file ini dari folder project aplikasi.
    goto :fail
)

if not exist "artisan" (
    echo [ERROR] File artisan tidak ditemukan.
    echo Pastikan folder project Laravel ini lengkap.
    goto :fail
)

if not exist ".env" (
    echo [ERROR] File .env tidak ditemukan.
    echo Buat atau salin .env terlebih dahulu.
    goto :fail
)

call :resolve_command git.exe GIT_CMD git
if errorlevel 1 goto :fail

call :resolve_command php.exe PHP_CMD php
if errorlevel 1 goto :fail

call :resolve_command composer.bat COMPOSER_CMD composer
if errorlevel 1 call :resolve_command composer.phar COMPOSER_CMD composer.phar
if errorlevel 1 (
    echo [ERROR] Composer tidak ditemukan di PATH.
    echo Install Composer terlebih dahulu.
    goto :fail
)

call :resolve_command npm.cmd NPM_CMD npm
if errorlevel 1 (
    echo [ERROR] npm tidak ditemukan di PATH.
    echo Install Node.js terlebih dahulu.
    goto :fail
)

echo [INFO] Lokasi project : %CD%
echo [INFO] Git           : %GIT_CMD%
echo [INFO] PHP           : %PHP_CMD%
echo [INFO] Composer      : %COMPOSER_CMD%
echo [INFO] NPM           : %NPM_CMD%
echo.

echo [1/12] Mengecek branch aktif...
for /f "delims=" %%i in ('"%GIT_CMD%" branch --show-current') do set "CURRENT_BRANCH=%%i"
if not defined CURRENT_BRANCH set "CURRENT_BRANCH=main"
echo Branch aktif: %CURRENT_BRANCH%
echo.

echo [2/12] Mengecek perubahan lokal...
for /f "delims=" %%i in ('"%GIT_CMD%" status --porcelain') do (
    set "HAS_LOCAL_CHANGES=1"
)

if "%HAS_LOCAL_CHANGES%"=="1" (
    echo [WARNING] Ada perubahan lokal yang belum di-commit.
    echo Update otomatis bisa gagal jika file yang sama ikut berubah di GitHub.
    echo.
    "%GIT_CMD%" status --short
    echo.
    set /p CONTINUE_UPDATE=Lanjutkan update juga? (Y/N): 
    if /I not "!CONTINUE_UPDATE!"=="Y" (
        echo Update dibatalkan oleh pengguna.
        goto :end
    )
    echo.
)

echo [3/12] Mengambil data terbaru dari GitHub...
"%GIT_CMD%" fetch %REMOTE_NAME%
if errorlevel 1 goto :fail
echo.

echo [4/12] Mengaktifkan maintenance mode...
"%PHP_CMD%" artisan down --render="errors::503" --retry=60 >nul 2>nul
if not errorlevel 1 (
    set "MAINTENANCE_ENABLED=1"
    echo Maintenance mode aktif.
) else (
    echo Maintenance mode dilewati.
)
echo.

echo [5/12] Menarik update branch %CURRENT_BRANCH%...
"%GIT_CMD%" pull %REMOTE_NAME% %CURRENT_BRANCH%
if errorlevel 1 goto :fail
echo.

echo [6/12] Install/update dependency PHP...
if /I "%COMPOSER_CMD%"=="composer.phar" (
    "%PHP_CMD%" "%COMPOSER_CMD%" install --no-interaction --prefer-dist --optimize-autoloader
) else (
    "%COMPOSER_CMD%" install --no-interaction --prefer-dist --optimize-autoloader
)
if errorlevel 1 goto :fail
echo.

echo [7/12] Install/update dependency Node.js...
if exist "package-lock.json" (
    "%NPM_CMD%" install
) else (
    "%NPM_CMD%" install
)
if errorlevel 1 goto :fail
echo.

echo [8/12] Menjalankan migrasi database...
"%PHP_CMD%" artisan migrate --force
if errorlevel 1 goto :fail
echo.

echo [9/12] Memastikan symbolic link storage...
"%PHP_CMD%" artisan storage:link >nul 2>nul
echo Storage link dicek.
echo.

echo [10/12] Membersihkan dan membangun ulang cache Laravel...
"%PHP_CMD%" artisan optimize:clear
if errorlevel 1 goto :fail
"%PHP_CMD%" artisan config:cache
if errorlevel 1 goto :fail
"%PHP_CMD%" artisan route:cache
if errorlevel 1 goto :fail
"%PHP_CMD%" artisan view:cache
if errorlevel 1 goto :fail
echo.

echo [11/12] Build ulang frontend...
"%NPM_CMD%" run build
if errorlevel 1 goto :fail
echo.

echo [12/12] Menonaktifkan maintenance mode...
if "%MAINTENANCE_ENABLED%"=="1" (
    "%PHP_CMD%" artisan up
    if errorlevel 1 goto :fail
    echo Maintenance mode dimatikan.
) else (
    echo Maintenance mode sebelumnya tidak aktif.
)
echo.

echo ==================================================
echo   UPDATE SELESAI
echo ==================================================
echo.
echo Ringkasan:
echo - Source code sudah ditarik dari GitHub
echo - Dependency PHP dan Node.js sudah diperbarui
echo - Migrasi database sudah dijalankan
echo - Asset frontend sudah dibuild ulang
echo - Cache Laravel sudah dibangun ulang
echo.
echo Silakan refresh browser di PC client.
echo.
pause
goto :eof

:resolve_command
where %~1 >nul 2>nul
if errorlevel 1 exit /b 1
for /f "delims=" %%i in ('where %~1') do (
    set "%~2=%%i"
    goto :resolve_done
)
:resolve_done
exit /b 0

:banner
echo ==================================================
echo   UPDATE APLIKASI %APP_NAME%
echo ==================================================
echo.
exit /b 0

:fail
echo.
if "%MAINTENANCE_ENABLED%"=="1" (
    echo [INFO] Mencoba menonaktifkan maintenance mode...
    "%PHP_CMD%" artisan up >nul 2>nul
)
echo ==================================================
echo   UPDATE GAGAL
echo ==================================================
echo.
echo Periksa pesan error di atas, perbaiki masalahnya, lalu jalankan lagi.
echo.
pause
exit /b 1

:end
echo.
pause
exit /b 0
