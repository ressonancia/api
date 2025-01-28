@servers(['web' => ['ressonance_api@10.0.1.225']])
 
@story('deploy')
    update-code
    install-dependencies
	run-migrations
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