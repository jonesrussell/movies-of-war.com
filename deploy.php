<?php

namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:jonesrussell/movies-of-war.com.git');
set('git_ssh_command', 'ssh -i /home/deployer/.ssh/id_ed25519_github -o IdentitiesOnly=yes');

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

host('movies-of-war.com')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/movies-of-war.com');

// Tasks

task('deploy:npm', function () {
    cd('{{release_path}}');
    run('npm ci --production=false');
});

task('deploy:wayfinder', function () {
    cd('{{release_path}}');
    run('{{bin/php}} artisan wayfinder:generate --with-form');
});

task('deploy:build', function () {
    cd('{{release_path}}');
    run('npm run build:ssr');
});

/**
 * Restart queue workers gracefully.
 * This tells all queue workers to finish their current job and then exit.
 * Supervisor will automatically restart them with the new code.
 */
task('artisan:queue:restart', function () {
    run('{{bin/php}} {{release_path}}/artisan queue:restart');
});

/**
 * Restart SSR server gracefully.
 * This stops the existing SSR server, forcing Supervisor to restart it with the new code.
 */
task('artisan:ssr:restart', function () {
    run('{{bin/php}} {{release_path}}/artisan inertia:stop-ssr');
});

/**
 * Reorganize poster images into subdirectories (posters/XX/filename.ext).
 * This is idempotent - it will skip files already in subdirectories.
 */
task('artisan:reorganize-posters', function () {
    run('{{bin/php}} {{release_path}}/artisan posters:reorganize');
});

/**
 * Optimize poster images for movies that haven't been optimized yet.
 * This is idempotent - it will skip posters that already have optimized versions.
 */
task('artisan:optimize-posters', function () {
    run('{{bin/php}} {{release_path}}/artisan posters:optimize');
});

// Hooks

after('deploy:update_code', 'deploy:npm');
before('deploy:build', 'deploy:wayfinder');
after('deploy:vendors', 'deploy:build');
before('deploy:symlink', 'artisan:migrate');
after('deploy:symlink', 'artisan:queue:restart');
after('deploy:symlink', 'artisan:ssr:restart');
after('deploy:symlink', 'artisan:reorganize-posters');
after('deploy:symlink', 'artisan:optimize-posters');
after('deploy:failed', 'deploy:unlock');
