<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace CanonicalUrl\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;

/**
 * Class CanonicalUrlListener
 * @package CanonicalUrl\EventListener
 * @author Gilles Bourgeat <gbourgeat@openstudio.fr>
 */
class CanonicalUrlListener implements EventSubscriberInterface
{
    /** @var Request */
    protected $request;

    /** @var Session */
    protected $session;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->session = $this->request->getSession();
    }

    /**
     * @param CanonicalUrlEvent $event
     */
    public function generateUrlCanonical(CanonicalUrlEvent $event)
    {
        $canonicalUrl = null;

        $uri = $this->request->getUri();

        if (!empty($uri) && false !== $parse = parse_url($uri)) {
            $parseUrlByCurrentLocale = $this->getParseUrlByCurrentLocale();

            // Be sure to use the proper domain name
            $canonicalUrl = $parseUrlByCurrentLocale['scheme'] . '://' . $parseUrlByCurrentLocale['host'];

            // Remove script name from path, preserving a potential subdirectory, e.g. http://somehost.com/mydir/index.php/...
            $canonicalUrl .= preg_replace("!/index(_dev)?\.php!", '', $parse['path']);
        }

        if (empty($canonicalUrl)) {
            $canonicalUrl = '/?';
        }

        // Add query string, if any
        $canonicalUrl .= $this->request->getQueryString();

        $event->setUrl($canonicalUrl);
    }

    /**
     * @return array
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CanonicalUrlEvents::GENERATE_CANONICAL => [
                'generateUrlCanonical', 128
            ]
        ];
    }

    /**
     * @return array
     *
     * At least one element will be present within the array.
     * Potential keys within this array are:
     * scheme - e.g. http
     * host
     * port
     * user
     * pass
     * path
     * query - after the question mark ?
     * fragment - after the hashmark #
     */
    protected function getParseUrlByCurrentLocale()
    {
        // for one domain by lang
        if ((int) ConfigQuery::read('one_domain_foreach_lang', 0) === 1) {
            // $langUrl = $this->session->getLang()->getUrl();

            $langUrl = LangQuery::create()->findOneByLocale($this->session->getLang()->getLocale())->getUrl();

            if (!empty($langUrl) && false !== $parse = parse_url($langUrl)) {
                return $parse;
            }
        }

        // return config url site
        $urlSite =  ConfigQuery::read('url_site');
        if (!empty($urlSite) && false !== $parse = parse_url($urlSite)) {
            return $parse;
        }

        // return current host
        return parse_url($this->request->getUri());
    }
}
