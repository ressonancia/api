@setup
    if (empty($server ?? null)) {
        throw new InvalidArgumentException('Missing required parameter: --server');
    }

    if (empty($app_path ?? null)) {
        throw new InvalidArgumentException('Missing required parameter: --app_path');
    }
@endsetup

@servers(['web' => [$server]])
 
@story('deploy')
    update-code
    install-dependencies
	run-migrations
    restart-reverb
    restart-pulse
@endstory
 
@task('update-code')
    cd {{ $app_path }}
    git pull origin main
@endtask
 
@task('install-dependencies')
    cd {{ $app_path }}
    composer install --no-dev
@endtask

@task('run-migrations')
    cd {{ $app_path }}
    php artisan migrate --force
@endtask

@task('restart-reverb')
    cd {{ $app_path }}
    php artisan reverb:restart
@endtask

@task('restart-pulse')
    cd {{ $app_path }}
    php artisan pulse:restart
@endtask

@task('show-logs')
    cd {{ $app_path }}
    cat storage/logs/laravel.log
@endtask

@task('tail-logs')
    cd {{ $app_path }}
    tail -f storage/logs/laravel.log
@endtask
