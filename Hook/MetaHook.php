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

namespace CanonicalUrl\Hook;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;

/**
 * Class MetaHook
 * @package UrlCanonical\Hook
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class MetaHook extends BaseHook
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param HookRenderEvent $hookRender
     */
    public function onMainHeadBottom(HookRenderEvent $hookRender)
    {
        $event = new CanonicalUrlEvent();

        $this->eventDispatcher->dispatch(
            CanonicalUrlEvents::GENERATE_CANONICAL,
            $event
        );

        if ($event->getUrl()) {
            $hookRender->add('<link rel="canonical" href="' . $event->getUrl() . '" />');
        }
    }
}
