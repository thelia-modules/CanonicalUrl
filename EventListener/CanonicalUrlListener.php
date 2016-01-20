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

use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;
use Thelia\Model\RewritingUrl;
use Thelia\Model\RewritingUrlQuery;

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
        $addUrlParameters = true;

        $parseUrlByCurrentLocale = $this->getParsedUrlByCurrentLocale();

        // Be sure to use the proper domain name
        $canonicalUrl = $parseUrlByCurrentLocale['scheme'] . '://' . $parseUrlByCurrentLocale['host'];

        $uri = $this->request->getUri();

        if (!empty($uri) && false !== $parse = parse_url($uri)) {
            // Remove script name from path, preserving a potential subdirectory, e.g. http://somehost.com/mydir/index.php/...
            $filePart = preg_replace("!/index(_dev)?\.php!", '', $parse['path']);

            $canonicalUrl .= $filePart;

            // If URL rewriting is enabled, check if our URL is rewritten.
            // If it's the case, we will not add parameters to prevent duplicate content.
            if (ConfigQuery::isRewritingEnable()) {
                $pathList = [];

                $filePart = trim($filePart, '/');

                while (! empty($filePart)) {
                    $pathList[] = $filePart;

                    $filePart = preg_replace("!^[^/]+/?!", '', $filePart);
                }

                // Check if we have a rewriten URL
                $addUrlParameters =  0 === RewritingUrlQuery::create()->filterByUrl($pathList, Criteria::IN)->count();
            }
        }

        if ($addUrlParameters) {
            $queryString = $this->request->getQueryString();

            if (! empty($queryString)) {
                $canonicalUrl .= '?' . $queryString;
            }

        }

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
    protected function getParsedUrlByCurrentLocale()
    {
        // for one domain by lang
        if ((int) ConfigQuery::read('one_domain_foreach_lang', 0) === 1) {
            // We always query the DB here, as the Lang configuration (then the related URL) may change during the
            // user session lifetime, and improper URLs could be generated. This is quite odd, okay, but may happen.
            $langUrl = LangQuery::create()->findPk($this->request->getSession()->getLang()->getId())->getUrl();

            if (!empty($langUrl) && false !== $parse = parse_url($langUrl)) {
                return $parse;
            }
        }

        // Configured site URL
        $urlSite =  ConfigQuery::read('url_site');
        if (!empty($urlSite) && false !== $parse = parse_url($urlSite)) {
            return $parse;
        }

        // return current URL
        return parse_url($this->request->getUri());
    }
}
