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

namespace CanonicalUrl\EventListener;

use CanonicalUrl\CanonicalUrl;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Event\UpdateSeoEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\MetaDataQuery;

class SeoFormListener extends BaseAction implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::FORM_AFTER_BUILD.'.thelia_seo' => ['addCanonicalField', 128],
            TheliaEvents::CATEGORY_UPDATE_SEO => ['saveCategorySeoFields', 128],
            TheliaEvents::BRAND_UPDATE_SEO => ['saveBrandSeoFields', 128],
            TheliaEvents::CONTENT_UPDATE_SEO => ['saveContentSeoFields', 128],
            TheliaEvents::FOLDER_UPDATE_SEO => ['saveFolderSeoFields', 128],
            TheliaEvents::PRODUCT_UPDATE_SEO => ['saveProductSeoFields', 128],
        ];
    }

    public function saveCategorySeoFields(UpdateSeoEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'category');
    }

    public function saveBrandSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'brand');
    }

    public function saveContentSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'content');
    }

    public function saveFolderSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'folder');
    }

    public function saveProductSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'product');
    }

    protected function saveSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcherInterface $dispatcher, $elementKey): void
    {
        $form = $this->requestStack->getCurrentRequest()->get('thelia_seo');

        if (null === $form || !\array_key_exists('id', $form) || !\array_key_exists('canonical', $form)) {
            return;
        }

        $canonicalValues = [];

        $canonicalMetaData = MetaDataQuery::create()
            ->filterByMetaKey(CanonicalUrl::SEO_CANONICAL_META_KEY)
            ->filterByElementKey($elementKey)
            ->filterByElementId($form['id'])
            ->findOneOrCreate();

        if (!$canonicalMetaData->isNew()) {
            $canonicalValues = json_decode($canonicalMetaData->getValue(), true);
        }

        $locale = $form['locale'];
        $canonicalValues[$locale] = $form['canonical'];

        $canonicalMetaData
            ->setIsSerialized(0)
            ->setValue(json_encode($canonicalValues))
            ->save();
    }

    public function addCanonicalField(TheliaFormEvent $event): void
    {
        $event->getForm()->getFormBuilder()
            ->add(
                'canonical',
                 TextType::class
            );
    }
}
