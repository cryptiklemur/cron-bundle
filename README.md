# Cron Bundle

This bundle provides a simple interface for registering repeated scheduled tasks within your application.

This bundle is tested only against Symfony 2.3, but should work on anything after 2.3, until at least 3.0.

## Installation

1. Add the bundle to your project as a composer dependency:

```shell
$ composer require aequasi/cron-bundle "~1.0.0"
```

3. Add the bundle to AppKernel:

```php
// AppKernel.php
public function registerBundles()
{
	// ...
	$bundles = array(
		// ...
        new Aequasi\Bundle\CronBundle\AequasiCronBundle(),
	);
    // ...

    return $bundles;
}
```

3. Start using the bundle:
```shell
$ app/console cron:scan
$ app/console cron:run
```

## Running your cron jobs automatically

This bundle is designed around the idea that your tasks will be run with a minimum interval - the tasks will be run no more frequently than you schedule them, but they can only run when you trigger then (by running `app/console cron:run`, or the forthcoming web endpoint, for use with webcron services).

To facilitate this, you can create a cron job on your system like this:
```
*/5 * * * * /path/to/symfony/install/app/console cron:run
```
This will schedule your tasks to run at most every 5 minutes - for instance, tasks which are scheduled to run every 3 minutes will only run every 5 minutes.

## Creating your own tasks

Creating your own tasks with CronBundle couldn't be easier - all you have to do is create a normal Symfony2 Command (or ContainerAwareCommand) and tag it with the @CronJob annotation, as demonstrated below:

```php
/**
 * @CronJob("PT1H")
 */
class DemoCommand extends Command
{
    // ...
}
```

The interval spec ("PT1H" in the above example) is documented on the [DateInterval](http://au.php.net/manual/en/dateinterval.construct.php) documentation page, and can be modified whenever you choose.
For your CronJob to be scanned and included in future runs, you must first run `app/console cron:scan` - it will be scheduled to run the next time you run `app/console cron:run`


#### If you add a new command, you have to run the scan function for it to get picked up, or clear the symfony cache.