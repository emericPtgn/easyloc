<?php

// src/EventSubscriber/MyEventSubscriber.php
namespace App\EventSubscriber;

use App\Entity\Order;
use App\Document\Product;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\LifecycleEventArgs;


class MyEventSubscriber
{

    private $dm;
    
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function postLoad(LifecycleEventArgs $eventArgs): void
    {
        $order = $eventArgs->getEntity();

        if (!$order instanceof Order) {
            return;
        }

        $em = $eventArgs->getEntityManager();
        $productReflProp = $em->getClassMetadata(Order::class)
            ->reflClass->getProperty('product');
        $productReflProp->setAccessible(true);
        $productReflProp->setValue(
            $order, $this->dm->getReference(Product::class, $order->getProductId())
        );
    }
}