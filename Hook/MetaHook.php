<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CanonicalUrl\Hook;

use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

/**
 * Class MetaHook.
 *
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class MetaHook extends BaseHook
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onMainHeadBottom(HookRenderEvent $hookRender): void
    {
        $event = new CanonicalUrlEvent();

        $this->eventDispatcher->dispatch(
            $event,
            CanonicalUrlEvents::GENERATE_CANONICAL,
        );

        if ($event->getUrl()) {
            $hookRender->add('<link rel="canonical" href="'.$event->getUrl().'">');
        }
    }
}
