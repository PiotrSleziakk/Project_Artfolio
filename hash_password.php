<?php
// hash_password.php
require 'vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

$factory = new PasswordHasherFactory([
    'common' => ['algorithm' => 'bcrypt'],
]);

$passwordHasher = $factory->getPasswordHasher('common');
$hashedPassword = $passwordHasher->hash('moderator');

echo $hashedPassword;
