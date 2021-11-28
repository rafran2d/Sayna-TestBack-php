# Sayna-TestBack-php

###cloner le projet git@github.com:rafran2d/Sayna-TestBack-php.git
###cd Sayna-TestBack-php
###composer install
###php bin/console doctrine:database:create
###php bin/console doctrine:migrations:migrate


# Les Différentes routes sont :  
##/register
### Le mot de passe doit contenir au moins 8 caractères, dont une chiffre, un majuscule et un minuscule
##/login
##/user/{token} method GET
##/users/{token} method GET
##/users/{token} method PUT
##/users/{token} method DELETE