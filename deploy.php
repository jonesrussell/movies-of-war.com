<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:jonesrussell/movies-of-war.com.git');
set('git_ssh_command', 'ssh -i ~/.ssh/id_ed25519_github -o IdentitiesOnly=yes');

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

task('deploy:build', function () {
    cd('{{release_path}}');
    run('npm run build');
});

// Hooks

after('deploy:update_code', 'deploy:npm');
after('deploy:npm', 'deploy:build');
after('deploy:failed', 'deploy:unlock');
