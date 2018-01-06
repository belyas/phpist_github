## Description
Prestashop module which allows to display a list of commits from a chosen public Github repository.

## Pre-requisite
  * You need to have [GIT](https://git-scm.com/) installed
  * You need to have [Composer](https://getcomposer.org/) installed

## Installation
It's easy and simple to install this module. Once you download it or clone it, put it in */modules* folder then head to the module folder and run this line of code to install the dependencies:
  * If you have composer installed globally: ``` composer install ```
  * Otherwise put the composer phar file in the module folder, then run this command: ``` composer.phar install ```

Once composer finishes downloading the dependencies, you can go to the Prestashop back end and install the module. Once it's installed, you will find three text fields:
  * Github account: this is the github username
  * Github repo: You put here the repository's name in this Github account
  * Number of commits: this is the number of commits you want the module to display in the front end

Once you're done, click on save button and here you go :)