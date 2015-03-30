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
        try {
            $crawler = $this->getCrawlerByLink($client, '/');
        } catch (\Exception $e) {
            throw new \Exception('An error occured. Please check SessionID.');
        }

        $points = $crawler->filter('span.nav__points');
        if ( ! count($points)) {
            throw new \Exception(
                'You are not authorized. Please update SessionID in your config file.'
            );
        }
        if (intval(trim($points->first()->text())) < 5) {
            throw new \Exception('You have not enough points to continue.');
        }

        $links = array_filter($crawler
            ->filter(
                'div.giveaway__row-inner-wrap:not(.is-faded)'
                . ' > div.giveaway__summary'
                . ' > h2.giveaway__heading'
                . ' > a.giveaway__heading__name'
            )
            ->each(function (Crawler $node, $i) use ($wishlist) {
                return in_array($node->text(), $wishlist)
                    ? $node->attr('href') : null;
                }
            )
        );

        $result = [];

        foreach ($links as $link) {
            $crawler = $this->getCrawlerByLink($client, $link);
            $sidebar = $crawler->filter('div.sidebar.sidebar--wide');
            if (count($sidebar->filter('form > div.sidebar__entry-insert:not(.is-hidden)'))) {
                $rawParams = $sidebar
                    ->filter('form > input')
                    ->each(function (Crawler $node) {
                        return $node->attr('value');
                    })
                ;
                $response = $this->submitForm($client, [
                    'xsrf_token' => $rawParams[0],
                    'code'       => $rawParams[2],
                    'do'         => 'entry_insert',
                ]);
                $title = $crawler->filter('div.featured__heading')->first()->text();
                $result[] = trim(preg_replace('/\s+/', ' ', $title))
                    . ' / ' . $rawParams[2]
                    . ' / ' . $response['points'] . 'P left'
                ;
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

    protected function submitForm($client, $params)
    {
        $request = $client->post('/ajax.php', null, $params);
        $this->fillHeaders($request);
        $response = $request->send();
        return ($response->getStatusCode()
            ? $response->json()
            : ['points' => 'NaN']
        );
    }

    protected function fillHeaders($request)
    {
        $request->addCookie('PHPSESSID', $this->config['sessionId']);
        $request->addHeaders(['User-Agent' => $this->config['userAgent']]);
    }
}
