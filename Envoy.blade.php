@servers(['web' => ['ressonance_api@10.0.1.225']])
 
@story('deploy')
    update-code
    install-dependencies
	run-migrations
    restart-reverb
    restart-pulse
@endstory
 
@task('update-code')
    cd /var/www/html/ressonance-api/
    git pull origin main
@endtask
 
@task('install-dependencies')
    cd /var/www/html/ressonance-api/
    composer install --no-dev
@endtask

@task('run-migrations')
    cd /var/www/html/ressonance-api/
    php artisan migrate --force
@endtask

@task('restart-reverb')
    cd /var/www/html/ressonance-api/
    php artisan reverb:restart
@endtask

@task('restart-pulse')
    cd /var/www/html/ressonance-api/
    php artisan pulse:restart
@endtask

@task('show-logs')
    cd /var/www/html/ressonance-api/
    cat storage/logs/laravel.log
@endtask

@task('tail-logs')
    cd /var/www/html/ressonance-api/
    tail -f storage/logs/laravel.log
@endtask