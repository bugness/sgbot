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
        $wishlist = array(
            'Hydrophobia: Prophecy',
            'Borderlands 2',
            'Gumboy Tournament',
            'Serious Sam 2',
            '1953 - KGB Unleashed'
        );

        $client = new Client('http://www.steamgifts.com');

        $output->writeln('-----------------------');

        $crawler = $this->_getCrawlerByLink($client, '/');
        $links = array_filter($crawler
            ->filter('div.post:not(.fade) > div.left > div.title > a')
            ->each(function (Crawler $node, $i) use ($wishlist) {
                return in_array($node->text(), $wishlist)
                    ? $node->attr('href') : null;
            }
        ));
        $output->writeln(join(PHP_EOL, $links));

        $output->writeln('-----------------------');

        foreach ($links as $link) {
            $crawler = $this->_getCrawlerByLink($client, $link);
            if (count($crawler->filter('a.submit_entry'))) {
                $output->writeln($link . ' - ' . $this->_submitForm($client, $link));
            }
        }

        $output->writeln('-----------------------');
    }

    protected function _getCrawlerByLink($client, $link)
    {
        $request = $client->get($link);
        $this->_fillHeaders($request);
        $response = $request->send();
        return new Crawler($response->getBody(true));
    }

    protected function _submitForm($client, $link)
    {
        $request = $client->post($link, null, [
            'form_key'       => '',
            'enter_giveaway' => '1'
        ]);
        $this->_fillHeaders($request);
        $response = $request->send();
        return $response->getStatusCode();
    }

    protected function _fillHeaders($request)
    {
        $request->addCookie('PHPSESSID', '');
        $request->addHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0'
        ]);
    }
}