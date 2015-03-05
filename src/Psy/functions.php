<?php

namespace Psy;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

if (!function_exists('Psy\sh')) {
    /**
     * Command to return the eval-able code to startup PsySH.
     *
     *     eval(\Psy\sh());
     *
     * @return string
     */
    function sh()
    {
        return 'extract(\Psy\Shell::debug(get_defined_vars(), $this ?: null));';
    }
}

if (!function_exists('Psy\bin')) {
    /**
     * `psysh` command line executable.
     *
     * @return Closure
     */
    function bin()
    {
        return function () {
            $usageException = null;

            $input = new ArgvInput();
            try {
                $input->bind(new InputDefinition(array(
                    new InputOption('help',    'h', InputOption::VALUE_NONE),
                    new InputOption('config',  'c', InputOption::VALUE_REQUIRED),
                    new InputOption('version', 'v', InputOption::VALUE_NONE),

                    new InputArgument('include', InputArgument::IS_ARRAY),
                )));
            } catch (\RuntimeException $e) {
                $usageException = $e;
            }

            $config = array();

            // Handle --config
            if ($configFile = $input->getOption('config')) {
                $config['configFile'] = $configFile;
            }

            $shell = new Shell(new Configuration($config));

            // Handle --help
            if ($usageException !== null || $input->getOption('help')) {
                if ($usageException !== null) {
                    echo $usageException->getMessage() . PHP_EOL . PHP_EOL;
                }

                $version = $shell->getVersion();
                $name    = basename(reset($_SERVER['argv']));
                echo <<<EOL
$version

Usage:
  $name [--version] [--help] [files...]

Options:
  --help     -h Display this help message.
  --config   -c Use an alternate PsySH config file location.
  --version  -v Display the PsySH version.

EOL;
                exit($usageException === null ? 0 : 1);
            }

            // Handle --version
            if ($input->getOption('version')) {
                echo $shell->getVersion() . PHP_EOL;
                exit(0);
            }

            // Pass additional arguments to Shell as 'includes'
            $shell->setIncludes($input->getArgument('include'));

            try {
                // And go!
                $shell->run();
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;

                // TODO: this triggers the "exited unexpectedly" logic in the
                // ForkingLoop, so we can't exit(1) after starting the shell...
                // fix this :)

                // exit(1);
            }
        };
    }
}
