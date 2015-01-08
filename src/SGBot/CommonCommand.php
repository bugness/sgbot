<?php

namespace SGBot;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Guzzle\Http\Client;
use Symfony\Component\DomCrawler\Crawler;

class CommonCommand extends Command
{
    protected $config;

    protected function configure()
    {
        $this
            ->setName('app:exec')
            ->addArgument('config', InputArgument::REQUIRED, 'Config File')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yaml = new Parser;
        $this->config = $yaml->parse(file_get_contents($input->getArgument('config')));
        $wishlist = $this->config['wishList'];

        $client = new Client('http://www.steamgifts.com');

        $crawler = $this->getCrawlerByLink($client, '/');
        $links = array_filter($crawler
            ->filter('div.post:not(.fade) > div.left > div.title > a')
            ->each(function (Crawler $node, $i) use ($wishlist) {
                return in_array($node->text(), $wishlist)
                    ? $node->attr('href') : null;
            }
        ));

        foreach ($links as $link) {
            $crawler = $this->getCrawlerByLink($client, $link);
            if (count($crawler->filter('a.submit_entry'))) {
                $output->writeln($link . ' - ' . $this->submitForm($client, $link));
            }
        }
    }

    protected function getCrawlerByLink($client, $link)
    {
        $request = $client->get($link);
        $this->fillHeaders($request);
        $response = $request->send();
        return new Crawler($response->getBody(true));
    }

    protected function submitForm($client, $link)
    {
        $request = $client->post($link, null, [
            'form_key'       => $this->config['formKey'],
            'enter_giveaway' => '1'
        ]);
        $this->fillHeaders($request);
        $response = $request->send();
        return $response->getStatusCode();
    }

    protected function fillHeaders($request)
    {
        $request->addCookie('PHPSESSID', $this->config['sessionId']);
        $request->addHeaders(['User-Agent' => $this->config['userAgent']]);
    }
}
