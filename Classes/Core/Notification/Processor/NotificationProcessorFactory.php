<?php
declare(strict_types=1);

/*
 * Copyright (C)
 * Nathan Boiron <nathan.boiron@gmail.com>
 * Romain Canon <romain.hydrocanon@gmail.com>
 *
 * This file is part of the TYPO3 NotiZ project.
 * It is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License, either
 * version 3 of the License, or any later version.
 *
 * For the full copyright and license information, see:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace CuyZ\Notiz\Core\Notification\Processor;

use CuyZ\Notiz\Core\Exception\ClassNotFoundException;
use CuyZ\Notiz\Core\Exception\InvalidClassException;
use CuyZ\Notiz\Core\Notification\Notification;
use CuyZ\Notiz\Service\Traits\ExtendedSelfInstantiateTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class NotificationProcessorFactory implements SingletonInterface
{
    use ExtendedSelfInstantiateTrait;

    /**
     * @var NotificationProcessor[]
     */
    protected $processors = [];

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Container
     */
    protected $objectContainer;

    /**
     * @param ObjectManager $objectManager
     * @param Container $objectContainer
     */
    public function __construct(ObjectManager $objectManager, Container $objectContainer)
    {
        $this->objectManager = $objectManager;
        $this->objectContainer = $objectContainer;
    }

    /**
     * @param Notification $notification
     * @return NotificationProcessor
     */
    public function getFromNotification(Notification $notification): NotificationProcessor
    {
        return $this->getFromNotificationClassName(get_class($notification));
    }

    /**
     * @param string $className
     * @return NotificationProcessor
     *
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    public function getFromNotificationClassName(string $className): NotificationProcessor
    {
        $className = $this->objectContainer->getImplementationClassName($className);

        if (false === isset($this->processors[$className])) {
            if (!class_exists($className)) {
                throw ClassNotFoundException::notificationClassNotFound($className);
            }

            if (!in_array(Notification::class, class_implements($className))) {
                throw InvalidClassException::notificationMissingInterface($className);
            }

            $processorClassName = $this->getProcessorClassNameFromNotificationClassName($className);

            $this->processors[$className] = $this->objectManager->get($processorClassName, $className);
        }

        return $this->processors[$className];
    }

    /**
     * @param string $notificationClassName
     * @return string
     *
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    protected function getProcessorClassNameFromNotificationClassName(string $notificationClassName): string
    {
        /** @var Notification $notificationClassName */
        $processorClassName = $notificationClassName::getProcessorClassName();

        if (!class_exists($processorClassName)) {
            throw ClassNotFoundException::notificationProcessorClassNotFound((string)$notificationClassName, $processorClassName);
        }

        if (!in_array(NotificationProcessor::class, class_parents($processorClassName))) {
            throw InvalidClassException::notificationProcessorWrongParent((string)$notificationClassName, $processorClassName);
        }

        return $processorClassName;
    }
}
