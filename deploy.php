<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:jonesrussell/movies-of-war.com.git');
set('git_ssh_command', 'ssh -i ~/.ssh/id_ed25519_github -o IdentitiesOnly=yes');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('movies-of-war.com')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/movies-of-war.com');

// Hooks

after('deploy:failed', 'deploy:unlock');
