@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Update Antrian BKPSDM

cd /d "%~dp0"

set "APP_NAME=Antrian BKPSDM"
set "REMOTE_NAME=origin"
set "MAINTENANCE_ENABLED=0"
set "WAS_ALREADY_IN_MAINTENANCE=0"
set "CURRENT_BRANCH="
set "HAS_LOCAL_CHANGES=0"
set "HAS_UNTRACKED_CONFLICTS=0"
set "REPO_ALREADY_UP_TO_DATE=0"
set "LOCAL_HEAD="
set "REMOTE_HEAD="
set "NON_INTERACTIVE=0"
set "DISCARD_LOCAL_CHANGES=0"
set "STASH_LOCAL_CHANGES=0"
set "ALLOW_LOCAL_CHANGES=0"
set "LOCAL_CHANGES_DISCARDED=0"
set "LOCAL_CHANGES_STASHED=0"
set "SHOW_HELP=0"

:parse_args
if "%~1"=="" goto :args_done
if /I "%~1"=="--non-interactive" (
    set "NON_INTERACTIVE=1"
)
if /I "%~1"=="--discard-local" (
    set "DISCARD_LOCAL_CHANGES=1"
)
if /I "%~1"=="--stash-local" (
    set "STASH_LOCAL_CHANGES=1"
)
if /I "%~1"=="--allow-local-changes" (
    set "ALLOW_LOCAL_CHANGES=1"
)
if /I "%~1"=="--help" (
    set "SHOW_HELP=1"
)
if /I "%~1"=="/?" (
    set "SHOW_HELP=1"
)
shift
goto :parse_args

:args_done

if "!SHOW_HELP!"=="1" goto :usage

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

echo [1/15] Mengecek branch aktif...
for /f "delims=" %%i in ('"%GIT_CMD%" branch --show-current') do set "CURRENT_BRANCH=%%i"
if not defined CURRENT_BRANCH set "CURRENT_BRANCH=main"
echo Branch aktif: %CURRENT_BRANCH%
echo.

echo [2/15] Mengecek perubahan lokal...
for /f "delims=" %%i in ('"%GIT_CMD%" status --porcelain') do (
    set "HAS_LOCAL_CHANGES=1"
)

if "!HAS_LOCAL_CHANGES!"=="1" (
    echo [WARNING] Ada perubahan lokal yang belum di-commit.
    echo Update otomatis bisa gagal jika file yang sama ikut berubah di GitHub.
    echo.
    "%GIT_CMD%" status --short
    echo.
    if "!DISCARD_LOCAL_CHANGES!"=="1" (
        call :discard_local_changes
        if errorlevel 1 goto :fail
        set "HAS_LOCAL_CHANGES=0"
        echo.
    ) else if "!STASH_LOCAL_CHANGES!"=="1" (
        call :stash_local_changes
        if errorlevel 1 goto :fail
        set "HAS_LOCAL_CHANGES=0"
        echo.
    ) else if "!ALLOW_LOCAL_CHANGES!"=="1" (
        echo [WARNING] Mode ini tetap melanjutkan update tanpa membersihkan perubahan lokal.
        echo Risiko konflik tetap ada jika file yang sama juga berubah di GitHub.
        echo.
    ) else if "!NON_INTERACTIVE!"=="1" (
        echo [ERROR] Mode non-interaktif menghentikan update untuk mencegah prompt yang menggantung.
        echo Gunakan salah satu opsi berikut:
        echo   update.bat --non-interactive --discard-local
        echo   update.bat --non-interactive --stash-local
        echo   update.bat --non-interactive --allow-local-changes
        echo Commit, stash, atau bersihkan perubahan lokal sebelum menjalankan update dari panel admin.
        goto :fail
    ) else (
        echo Pilih tindakan:
        echo   D = hapus perubahan lokal lalu lanjut update
        echo   S = simpan sementara ke stash lalu lanjut update
        echo   Y = lanjut update apa adanya ^(berisiko konflik^)
        echo   N = batal
        set /p "CONTINUE_UPDATE=Pilihan ^(D/S/Y/N^): "
        if /I "!CONTINUE_UPDATE!"=="D" (
            call :discard_local_changes
            if errorlevel 1 goto :fail
            set "HAS_LOCAL_CHANGES=0"
            echo.
        ) else if /I "!CONTINUE_UPDATE!"=="S" (
            call :stash_local_changes
            if errorlevel 1 goto :fail
            set "HAS_LOCAL_CHANGES=0"
            echo.
        ) else if /I "!CONTINUE_UPDATE!"=="Y" (
            echo [WARNING] Update dilanjutkan tanpa membersihkan perubahan lokal.
            echo Risiko konflik tetap ada jika file yang sama juga berubah di GitHub.
            echo.
        ) else (
            echo Update dibatalkan oleh pengguna.
            goto :end
        )
    )
)

echo [3/15] Mengecek status maintenance mode...
call :detect_maintenance_mode
if "!WAS_ALREADY_IN_MAINTENANCE!"=="1" (
    echo [WARNING] Aplikasi sudah dalam maintenance mode sebelum update dijalankan.
    echo Script akan menjaga status maintenance tetap aktif sampai Anda mematikannya manual.
) else (
    echo Aplikasi sedang online.
)
echo.

echo [4/15] Mengambil data terbaru dari GitHub...
"%GIT_CMD%" fetch %REMOTE_NAME%
if errorlevel 1 goto :fail
echo.

echo [5/15] Menganalisis status sinkronisasi repository...
for /f "delims=" %%i in ('"%GIT_CMD%" rev-parse HEAD') do set "LOCAL_HEAD=%%i"
if not defined LOCAL_HEAD goto :fail
for /f "delims=" %%i in ('"%GIT_CMD%" rev-parse %REMOTE_NAME%/%CURRENT_BRANCH%') do set "REMOTE_HEAD=%%i"
if not defined REMOTE_HEAD goto :fail

echo Commit lokal : !LOCAL_HEAD!
echo Commit remote: !REMOTE_HEAD!
if /I "!LOCAL_HEAD!"=="!REMOTE_HEAD!" (
    set "REPO_ALREADY_UP_TO_DATE=1"
    echo [INFO] Repository sudah sinkron. Tidak ada commit baru untuk di-pull.
) else (
    echo [INFO] Ditemukan update baru di %REMOTE_NAME%/%CURRENT_BRANCH%.
)
echo.

echo [6/15] Memeriksa konflik file untracked sebelum pull...
call :check_untracked_conflicts
if errorlevel 1 goto :fail
if "!HAS_UNTRACKED_CONFLICTS!"=="1" goto :end
echo Tidak ada konflik file untracked dengan update dari GitHub.
echo.

echo [7/15] Mengaktifkan maintenance mode...
if "!WAS_ALREADY_IN_MAINTENANCE!"=="1" (
    echo Maintenance mode sudah aktif dari awal. Langkah ini dilewati.
) else (
    "%PHP_CMD%" artisan down --render="errors::503" --retry=60 >nul 2>nul
    if not errorlevel 1 (
        set "MAINTENANCE_ENABLED=1"
        echo Maintenance mode aktif.
    ) else (
        echo Maintenance mode gagal diaktifkan atau dilewati.
    )
)
echo.

echo [8/15] Sinkronisasi source code...
if "!REPO_ALREADY_UP_TO_DATE!"=="1" (
    echo [INFO] Langkah git pull dilewati karena repository sudah "Already up to date."
) else (
    set "GIT_PULL_LOG=%TEMP%\antrian_pull_%RANDOM%_%RANDOM%.tmp"
    "%GIT_CMD%" pull %REMOTE_NAME% %CURRENT_BRANCH% > "!GIT_PULL_LOG!" 2>&1
    set "PULL_EXIT_CODE=!ERRORLEVEL!"
    type "!GIT_PULL_LOG!"
    call :cleanup_temp_file "!GIT_PULL_LOG!"
    if not "!PULL_EXIT_CODE!"=="0" goto :fail
)
echo.

echo [9/15] Install/update dependency PHP...
if /I "%COMPOSER_CMD%"=="composer.phar" (
    "%PHP_CMD%" "%COMPOSER_CMD%" install --no-interaction --prefer-dist --optimize-autoloader
) else (
    "%COMPOSER_CMD%" install --no-interaction --prefer-dist --optimize-autoloader
)
if errorlevel 1 goto :fail
echo.

echo [10/15] Install/update dependency Node.js...
if exist "package-lock.json" (
    "%NPM_CMD%" install
) else (
    "%NPM_CMD%" install
)
if errorlevel 1 goto :fail
echo.

echo [11/15] Menjalankan migrasi database...
"%PHP_CMD%" artisan migrate --force
if errorlevel 1 goto :fail
echo.

echo [12/15] Memastikan symbolic link storage...
"%PHP_CMD%" artisan storage:link >nul 2>nul
echo Storage link dicek.
echo.

echo [13/15] Membersihkan dan membangun ulang cache Laravel...
"%PHP_CMD%" artisan optimize:clear
if errorlevel 1 goto :fail
"%PHP_CMD%" artisan config:cache
if errorlevel 1 goto :fail
"%PHP_CMD%" artisan route:cache
if errorlevel 1 goto :fail
"%PHP_CMD%" artisan view:cache
if errorlevel 1 goto :fail
echo.

echo [14/15] Build ulang frontend...
"%NPM_CMD%" run build
if errorlevel 1 goto :fail
echo.

echo [15/15] Menonaktifkan maintenance mode...
if "%MAINTENANCE_ENABLED%"=="1" (
    "%PHP_CMD%" artisan up
    if errorlevel 1 goto :fail
    echo Maintenance mode dimatikan.
) else (
    if "!WAS_ALREADY_IN_MAINTENANCE!"=="1" (
        echo Maintenance mode tetap aktif karena sudah aktif sebelum script dijalankan.
    ) else (
        echo Maintenance mode sebelumnya tidak aktif.
    )
)
echo.

echo ==================================================
echo   UPDATE SELESAI
echo ==================================================
echo.
echo Ringkasan:
if "!REPO_ALREADY_UP_TO_DATE!"=="1" (
    echo - Source code sudah sinkron sejak awal ^(tidak ada commit baru di GitHub^)
) else (
    echo - Source code sudah ditarik dari GitHub
)
echo - Dependency PHP dan Node.js sudah diperbarui
echo - Migrasi database sudah dijalankan
echo - Asset frontend sudah dibuild ulang
echo - Cache Laravel sudah dibangun ulang
if "!LOCAL_CHANGES_DISCARDED!"=="1" (
    echo - Perubahan lokal yang tidak di-track sudah dibuang sebelum update
)
if "!LOCAL_CHANGES_STASHED!"=="1" (
    echo - Perubahan lokal disimpan sementara ke git stash sebelum update
)
if "!WAS_ALREADY_IN_MAINTENANCE!"=="1" (
    echo - Maintenance mode tetap aktif dan perlu dimatikan manual dengan: php artisan up
)
echo.
echo Silakan refresh browser di PC client.
echo.
call :maybe_pause
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

:detect_maintenance_mode
set "WAS_ALREADY_IN_MAINTENANCE=0"
if exist "storage\framework\down" set "WAS_ALREADY_IN_MAINTENANCE=1"
if exist "storage\framework\maintenance.php" set "WAS_ALREADY_IN_MAINTENANCE=1"
exit /b 0

:check_untracked_conflicts
set "HAS_UNTRACKED_CONFLICTS=0"
set "UNTRACKED_TMP=%TEMP%\antrian_untracked_%RANDOM%_%RANDOM%.tmp"
set "REMOTE_CHANGED_TMP=%TEMP%\antrian_remote_changed_%RANDOM%_%RANDOM%.tmp"
set "CONFLICT_TMP=%TEMP%\antrian_conflicts_%RANDOM%_%RANDOM%.tmp"

"%GIT_CMD%" ls-files --others --exclude-standard > "!UNTRACKED_TMP!"
if errorlevel 1 goto :check_untracked_conflicts_fail

"%GIT_CMD%" diff --name-only HEAD..%REMOTE_NAME%/%CURRENT_BRANCH% > "!REMOTE_CHANGED_TMP!"
if errorlevel 1 goto :check_untracked_conflicts_fail

break > "!CONFLICT_TMP!"
for /f "usebackq delims=" %%u in ("!UNTRACKED_TMP!") do (
    findstr /x /c:"%%u" "!REMOTE_CHANGED_TMP!" >nul
    if not errorlevel 1 (
        >> "!CONFLICT_TMP!" echo %%u
        set "HAS_UNTRACKED_CONFLICTS=1"
    )
)

if "!HAS_UNTRACKED_CONFLICTS!"=="1" (
    echo [ERROR] Ditemukan file untracked yang akan tertimpa oleh update dari GitHub.
    echo.
    echo File yang bentrok:
    type "!CONFLICT_TMP!"
    echo.
    echo Selesaikan dulu sebelum melanjutkan update. Pilih salah satu langkah berikut:
    echo 1. Backup atau pindahkan file di atas ke folder lain.
    echo 2. Jika file memang tidak diperlukan, hapus manual dari folder project.
    echo 3. Jika file ingin mulai dipantau Git, commit atau stash perubahan yang relevan dari repo yang benar.
    echo.
    echo Setelah konflik dibersihkan, jalankan update.bat lagi.
)

call :cleanup_temp_file "!UNTRACKED_TMP!"
call :cleanup_temp_file "!REMOTE_CHANGED_TMP!"
call :cleanup_temp_file "!CONFLICT_TMP!"
exit /b 0

:check_untracked_conflicts_fail
call :cleanup_temp_file "!UNTRACKED_TMP!"
call :cleanup_temp_file "!REMOTE_CHANGED_TMP!"
call :cleanup_temp_file "!CONFLICT_TMP!"
exit /b 1

:cleanup_temp_file
if exist "%~1" del /q "%~1" >nul 2>nul
exit /b 0

:discard_local_changes
echo [INFO] Membuang perubahan lokal dengan git reset --hard dan git clean -fd...
"%GIT_CMD%" reset --hard HEAD
if errorlevel 1 exit /b 1
"%GIT_CMD%" clean -fd
if errorlevel 1 exit /b 1
set "LOCAL_CHANGES_DISCARDED=1"
echo [INFO] Perubahan lokal sudah dibuang.
exit /b 0

:stash_local_changes
echo [INFO] Menyimpan perubahan lokal sementara ke git stash...
"%GIT_CMD%" stash push --include-untracked -m "Before update via update.bat"
if errorlevel 1 exit /b 1
set "LOCAL_CHANGES_STASHED=1"
echo [INFO] Perubahan lokal sudah disimpan ke stash.
exit /b 0

:banner
echo ==================================================
echo   UPDATE APLIKASI %APP_NAME%
echo ==================================================
echo.
exit /b 0

:usage
echo ==================================================
echo   UPDATE APLIKASI %APP_NAME%
echo ==================================================
echo.
echo Cara pakai:
echo   update.bat
echo   update.bat --non-interactive
echo   update.bat --non-interactive --discard-local
echo   update.bat --non-interactive --stash-local
echo   update.bat --non-interactive --allow-local-changes
echo.
echo Arti opsi:
echo   --discard-local        Hapus perubahan lokal tracked dan untracked yang tidak di-ignore.
echo   --stash-local          Simpan perubahan lokal sementara ke git stash lalu lanjut update.
echo   --allow-local-changes  Lanjutkan update apa adanya. Risiko konflik tetap ada.
echo   --non-interactive      Jalankan tanpa prompt interaktif.
echo.
echo Contoh aman di server:
echo   update.bat --non-interactive --discard-local
echo.
echo Contoh jika ingin simpan backup dulu:
echo   update.bat --non-interactive --stash-local
echo.
exit /b 0

:fail
echo.
if "%MAINTENANCE_ENABLED%"=="1" (
    echo [INFO] Mencoba menonaktifkan maintenance mode...
    "%PHP_CMD%" artisan up >nul 2>nul
    if not errorlevel 1 echo [INFO] Maintenance mode berhasil dimatikan kembali.
)
if "!WAS_ALREADY_IN_MAINTENANCE!"=="1" (
    echo [INFO] Maintenance mode sudah aktif sebelum script berjalan, jadi tidak diubah oleh script ini.
)
echo ==================================================
echo   UPDATE GAGAL
echo ==================================================
echo.
echo Periksa pesan error di atas, perbaiki masalahnya, lalu jalankan lagi.
echo Jika aplikasi masih maintenance, gunakan: php artisan up
echo.
call :maybe_pause
exit /b 1

:end
echo.
call :maybe_pause
exit /b 0

:maybe_pause
if "%NON_INTERACTIVE%"=="1" exit /b 0
pause
exit /b 0
