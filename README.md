# Pulchritudinous Queue - Queue Labour Manager

    ``pulchritudinous/module-queue``

 - [License](#license)
 - [Requirements](#requirements)
 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)

## License

Pulchritudinous Queue is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Requirements

* PHP 7.2 or above.
* A processes controller (like [Supervisord](http://supervisord.org/))

## Main Functionalities

For asynchronously job scheduling and management.

### Features
* No dependency on ordinary Magento cron job.
* Prevents parallel queue execution.
* Support simultaneous worker execution (default 2).
* Supports multiple methods of execution.
    - Run any worker of the same type simultaneously.
    - Waits for any running worker of the same type to finish.
    - Batches workers of the same type to be run simultaneously.
* Worker error management and rescheduling.
* Recurring worker execution.

## Installation
\* = in production please use the `--keep-generated` option

- Unzip the zip file in `app/code/Pulchritudinous`
- Enable the module by running `php bin/magento module:enable Pulchritudinous_Queue`
- Apply database updates by running `php bin/magento setup:upgrade`\*
- Flush the cache by running `php bin/magento cache:flush`

## Configuration

### Example Supervisord Configuration

This is a example of how to configure Supervisord to work with this queue.

```shell
[program:queue]
command = ./bin/magento pulchqueue:server
directory=/var/www/
autostart=true
autorestart=true
user = www-data
priority=20
```
