# CodeIgniter Rest Server

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

Please see `controllers/api/Example.php` for a few examples how to use this.

