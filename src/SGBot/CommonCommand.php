<?php

namespace SGBot;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Guzzle\Http\Client;
use Symfony\Component\DomCrawler\Crawler;

class CommonCommand extends Command
{
    protected function configure()
    {
        $this->setName('common:test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = new Client('http://localhost:8000');
        $request = $client->get('/list.html');
//        $request->addCookie('PHPSESSID', '');
//        $request->addCookie('expires', 'Sun, 16-Mar-2014 09:22:24 GMT');
//        $request->addCookie('path', '/');
//        $request->addCookie('domain', '.example.com');
        $response = $request->send();

        $output->writeln('-----------------------');

        $crawler = new Crawler($response->getBody(true));
        $posts = $crawler
            ->filter('div.post:not(.fade) > div.left > div.title > a')
            ->each(function (Crawler $node, $i) {
                return $node->text() . ' - ' . $node->attr('href');
            }
        );
        $output->writeln(join(PHP_EOL, $posts));

        $output->writeln('-----------------------');
    }
}