<?php

namespace SGBot\Service;

use Guzzle\Http\Client;
use Symfony\Component\DomCrawler\Crawler;

class Provider
{
    protected $config;

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    public function enterToGiveaways()
    {
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

        $result = [];

        foreach ($links as $link) {
            $crawler = $this->getCrawlerByLink($client, $link);
            if (count($crawler->filter('a.submit_entry'))) {
                $result[] = ($link . ' - ' . $this->submitForm($client, $link));
            }
        }

        return $result;
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