<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github-movies-of-war:jonesrussell/movies-of-war.com.git');
set('keep_releases', 5);
set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

add('shared_files', [
    '.env',
    'database/database.sqlite',
]);
add('shared_dirs', []);
add('writable_dirs', [
    'storage',
    'bootstrap/cache',
    'database',
]);

// Hosts

host('coforge.xyz')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/movies-of-war.com');

// Tasks

task('deploy:build_assets', function (): void {
    run('bash -lc "source ~/.nvm/nvm.sh 2>/dev/null; cd {{release_path}} && npm ci && npm run build:ssr"');
});
after('deploy:vendors', 'deploy:build_assets');

task('deploy:wayfinder', function (): void {
    cd('{{release_path}}');
    run('{{bin/php}} artisan wayfinder:generate --with-form');
});
before('deploy:build_assets', 'deploy:wayfinder');

task('deploy:install_services', function (): void {
    $serviceDir = '~/.config/systemd/user';
    run("mkdir -p $serviceDir");
    run("cp {{release_path}}/deploy/systemd-user/*.service $serviceDir/");
    run('systemctl --user daemon-reload');
    run('systemctl --user enable movies-of-war-horizon.service movies-of-war-inertia-ssr.service movies-of-war-schedule-work.service');
});
before('deploy:symlink', 'deploy:install_services');

task('deploy:restart_services', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan horizon:terminate || true');
    run('cd {{release_path}} && {{bin/php}} artisan inertia:stop-ssr || true');
    run('systemctl --user restart movies-of-war-schedule-work.service movies-of-war-horizon.service movies-of-war-inertia-ssr.service || true');
});
after('deploy:symlink', 'deploy:restart_services');

task('deploy:reload_php_fpm', function (): void {
    run('sudo systemctl restart php8.4-fpm || true');
});
after('deploy:restart_services', 'deploy:reload_php_fpm');

// Hooks

after('deploy:failed', 'deploy:unlock');

// Disable view caching — Inertia renders views client-side and artisan:view:cache
// tries to connect to the SSR server which isn't running during deploy.
task('artisan:view:cache', function (): void {});
before('deploy:symlink', 'artisan:migrate');
