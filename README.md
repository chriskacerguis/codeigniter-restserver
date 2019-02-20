# CodeIgniter Rest Server

[![StyleCI](https://github.styleci.io/repos/230589/shield?branch=development)](https://github.styleci.io/repos/230589) ![Travis (.com) branch](https://img.shields.io/travis/com/chriskacerguis/codeigniter-restserver/development.svg?style=flat-square)

A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.

## Requirements

Please refer to the [CodeIgniter Server Requiremnts](https://www.codeigniter.com/user_guide/general/requirements.html), as this project will follow those.

## Installation

This repo is setup with the latest stable version of CodeIgniter.  Just clone and drop this into place and start working.  To add to or update an existing installation, you will need to move the following files to your install (in the same folder)

- application/rest.php
- application/languages/english/rest_controller_lang.php
- application/libraries/Restserver.php

Delete the `application/controllers/api/Example.php` file as those are just examples.

`application/controllers/api/Key.php` is included as a way to manage API keys.  If you don't want to use that, feel free just to delete it as well.

Simply add this to your API controller.

```php
$this->load->library('restserver');
```

## Usage

API Keys table

```sql
CREATE TABLE `keys` (
  `key` VARCHAR(40) NOT NULL,
  `expire_on` DATETIME,
  `comments` VARCHAR(255),
  `max_hourly` INT(11),
  `created_on` DATETIME NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

Logging Table:

```sql
CREATE TABLE `logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uri` VARCHAR(255) NOT NULL,
  `method` VARCHAR(6) NOT NULL,
  `params` TEXT DEFAULT NULL,
  `api_key` VARCHAR(40) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `authorized` VARCHAR(1) NOT NULL,
  `response_code` smallint(3) DEFAULT '0',
  `created_on` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

## User Auth

Please see `controllers/api/Example.php` for a few examples how to use this.

