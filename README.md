<p align="center"><a href="https://phalcon-nucleon.github.io/" target="_blank"><img width="100"src="https://phalcon-nucleon.github.io/img/nucleon.svg"></a></p>

Nucleon : Phalcon extended framework. (Kernel)
==============================================
[![Build Status](https://travis-ci.org/phalcon-nucleon/framework.svg?branch=master)](https://travis-ci.org/phalcon-nucleon/framework) [![Coverage Status](https://coveralls.io/repos/github/phalcon-nucleon/framework/badge.svg?branch=master)](https://coveralls.io/github/phalcon-nucleon/framework)

> **Note:** This repository contains the core code of the Nucleon framework. If you want to build an application using Nucleon, visit the main [Nucleon repository](https://github.com/phalcon-nucleon/nucleon).

## About
- Powerful bootstrap for Phalcon
- Optimizer (neutrino\optimizer)
  - Framework optimizer. Compile the main code & classes that are frequently used into one single file. 
  - Auto-Loader Optimizer. Transalte the composer autoloader, in Phalcon autoloader.
- Middleware
  - Logic
  - Throttle middleware
- Model
  - Lowest resource consumption for model description.
- Auth
  - Manager
  - Throttle login
- Cache Strategy 
  - Allow to manage multiple backend cache storage
- Providers
  - Lazy instantiation of all phalcon & neutrino services. Loaded once (for shared), when needed.
- Facades
  - Allows you to use the facade pattern
- Migrations
  - Migrations are like version control for your database. (inspired from [laravel migrations](https://laravel.com/docs/5.5/migrations))
- Console (neutrino\cli)
- Dotconst (neutrino\const)

## Resource
- [Phalcon](http://phalconphp.com): Phalcon framework
- [Nucleon icon](http://www.flaticon.com/free-icon/atom_170849). re-colorized by nucleon.