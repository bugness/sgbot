<?php

namespace SGBot\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use SGBot\Service\Provider;

class MainCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app:exec')
            ->setDescription('Run application')
            ->addArgument('config', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = realpath($input->getArgument('config'));
        if (!file_exists($file)) {
            $output->writeln("File '{$file}' not found");
            return;
        }

        $yaml = new Parser;
        try {
            $config = $yaml->parse(file_get_contents($file));
        } catch (ParseException $e) {
            $output->writeln("Unable to parse the YAML string: {$e->getMessage()}");
            return;
        }

        $provider = new Provider($config);
        $result = $provider->enterToGiveaways();

        if (!count($result)) {
            return;
        }

        array_unshift($result, '---', date('d M Y H:i:s'));

        file_put_contents(
            $config['username'] . '.log',
            join(PHP_EOL, $result) . PHP_EOL,
            FILE_APPEND
        );
    }
}
