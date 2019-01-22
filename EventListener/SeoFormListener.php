<?php

namespace CanonicalUrl\EventListener;

use CanonicalUrl\CanonicalUrl;
use Thelia\Core\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Event\UpdateSeoEvent;
use Thelia\Model\MetaDataQuery;

class SeoFormListener extends BaseAction implements EventSubscriberInterface
{
    /** @var \Thelia\Core\HttpFoundation\Request */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::FORM_AFTER_BUILD.'.thelia_seo' => array('addCanonicalField', 128),
            TheliaEvents::CATEGORY_UPDATE_SEO => array('saveCategorySeoFields', 128),
            TheliaEvents::BRAND_UPDATE_SEO => array('saveBrandSeoFields', 128),
            TheliaEvents::CONTENT_UPDATE_SEO => array('saveContentSeoFields', 128),
            TheliaEvents::FOLDER_UPDATE_SEO => array('saveFolderSeoFields', 128),
            TheliaEvents::PRODUCT_UPDATE_SEO => array('saveProductSeoFields', 128)
        );
    }

    public function saveCategorySeoFields(UpdateSeoEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'category');
    }

    public function saveBrandSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'brand');
    }

    public function saveContentSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'content');
    }

    public function saveFolderSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'folder');
    }

    public function saveProductSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        $this->saveSeoFields($event, $eventName, $dispatcher, 'product');
    }

    protected function saveSeoFields(UpdateSeoEvent $event, $eventName, EventDispatcher $dispatcher, $elementKey)
    {
        $form = $this->request->request->get('thelia_seo');


        if (null === $form || !array_key_exists('id', $form) || !array_key_exists('canonical', $form)) {
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

    public function addCanonicalField(TheliaFormEvent $event)
    {
        $event->getForm()->getFormBuilder()
            ->add(
                'canonical',
                'text'
            );
    }
}