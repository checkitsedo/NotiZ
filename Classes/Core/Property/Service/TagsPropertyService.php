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

namespace CuyZ\Notiz\Core\Property\Service;

use CuyZ\Notiz\Core\Exception\ClassNotFoundException;
use CuyZ\Notiz\Core\Exception\DuplicateEntryException;
use CuyZ\Notiz\Core\Exception\InvalidClassException;
use CuyZ\Notiz\Core\Exception\WrongFormatException;
use CuyZ\Notiz\Core\Property\Factory\PropertyDefinition;
use CuyZ\Notiz\Core\Property\PropertyEntry;
use CuyZ\Notiz\Service\Traits\ExtendedSelfInstantiateTrait;
use Romm\ConfigurationObject\Legacy\Reflection\ClassReflection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * This service allows filling properties entry in a property definition, from
 * processing an event class attributes and their annotations.
 *
 * For instance, an event can look like this:
 *
 * ```
 * class MyEvent implements Event
 * {
 *     /**
 *      * @var string
 *      *
 *      * @marker
 *      * @label Some custom label!
 *      * /
 *     protected $someAttribute = 'foo';
 *
 *     // ...
 * }
 * ```
 *
 * In this example, when using this service for fetching properties of the type
 * "marker", the entry `someAttribute` will automatically be added, along with
 * the label, and because it has a default value (`foo`), the value will also be
 * set.
 *
 * This service aims to facilitate the addition of new properties in any event:
 * just add a supported annotation and the system will do the job for you. All
 * you need to do then is manipulate the attribute's value however the process
 * needs to.
 *
 * Custom identifiers
 * ------------------
 *
 * Another feature of this service is the possibility to add a custom identifier
 * for a given property type. The identifier is used as annotation name selector
 * to filter which attributes to fetch.
 *
 * For instance, the default annotation for the property of type `Marker` is
 * `@marker`; you may want to use `@data` instead.
 *
 * All you need to do is call the code below in your `ext_localconf.php` file:
 *
 * ```
 * \CuyZ\Notiz\Core\Property\Service\TagsPropertyService::get()
 *     ->setPropertyTagIdentifier(
 *         \CuyZ\Notiz\Domain\Property\Marker::class,
 *         'data'
 *     );
 * ```
 *
 * Please note that to ensure the annotations will be found, a format is to be
 * respected for the identifiers:
 *
 * @see TagsPropertyService::IDENTIFIER_FORMAT_DESCRIPTION
 *
 * By default, the identifier for a property will come from its class name: the
 * last part of the class name will be transformed from UpperCamelCase to
 * lower-dashed-case.
 *
 * For instance, the class `Vendor\MyExtension\Domain\Property\MyProperty` will
 * have the default identifier `my-property`.
 */
class TagsPropertyService implements SingletonInterface
{
    use ExtendedSelfInstantiateTrait;

    const IDENTIFIER_FORMAT_DESCRIPTION = 'Must contain only dashes and alphanumeric characters, cannot begin or end with a dash.';

    /**
     * - No double dash
     * - No beginning/ending dash
     * - No beginning with a number
     */
    const IDENTIFIER_FORMAT_PATTERN = '/^([a-z]+-)*[a-z0-9]+$/';

    /**
     * Only dashes and alphanumeric characters are allowed in an identifier.
     */
    const IDENTIFIER_ALLOWED_PATTERN = '/[^a-z0-9-]/';

    /**
     * Custom tag identifiers.
     *
     * @see TagsPropertyService::setPropertyTagIdentifier
     *
     * @var array
     */
    protected $propertyTagIdentifier = [];

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param PropertyDefinition $definition
     */
    public function fillPropertyDefinition(PropertyDefinition $definition)
    {
        $identifier = $this->getPropertyTagIdentifier($definition);

        /** @var \Romm\ConfigurationObject\Core\Service\ReflectionService $reflectionService */
        $reflectionService = $this->objectManager->get(\Romm\ConfigurationObject\Core\Service\ReflectionService::class);

        $classSchema = $reflectionService->getClassReflection($definition->getEventClassName());

        foreach ($classSchema->getProperties() as $property) {
            $tags = $property->getTagsValues();
            if (!isset($tags[$identifier])) {
                continue;
            }

            $entry = $definition->addEntry($property->getName());

            $label = $tags['label'][0] ?? '';
            $entry->setLabel($label);
        }
    }

    /**
     * Adds a custom tag identifier for a given property type. See class
     * documentation for more information.
     *
     * @param string $propertyType
     * @param string $identifier
     *
     * @throws ClassNotFoundException
     * @throws DuplicateEntryException
     * @throws InvalidClassException
     * @throws WrongFormatException
     */
    public function setPropertyTagIdentifier(string $propertyType, string $identifier)
    {
        if (false === class_exists($propertyType)) {
            throw ClassNotFoundException::tagServicePropertyClassNotFound($propertyType, $identifier);
        }

        if (false === in_array(PropertyEntry::class, class_parents($propertyType))) {
            throw InvalidClassException::tagServicePropertyWrongParent($propertyType, $identifier);
        }

        $identifier = trim($identifier);

        if (1 !== preg_match(self::IDENTIFIER_FORMAT_PATTERN, $identifier)) {
            $formattedIdentifier = $this->getFormattedIdentifier($identifier);

            throw WrongFormatException::tagServiceIdentifierWrongFormat($propertyType, $identifier, $formattedIdentifier, self::IDENTIFIER_FORMAT_DESCRIPTION);
        }

        $key = array_search($identifier, $this->propertyTagIdentifier);

        if (false !== $key
            && $key !== $propertyType
        ) {
            throw DuplicateEntryException::tagServiceIdentifierDuplication($identifier, $propertyType, $key);
        }

        $this->propertyTagIdentifier[$propertyType] = $identifier;
    }

    /**
     * @param PropertyDefinition $definition
     * @return string
     */
    protected function getPropertyTagIdentifier(PropertyDefinition $definition): string
    {
        $type = $definition->getPropertyType();

        return isset($this->propertyTagIdentifier[$type])
            ? $this->propertyTagIdentifier[$type]
            : $this->getFormattedIdentifier($this->getClassShortName($type));
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function getFormattedIdentifier(string $identifier): string
    {
        // Converting case: `fooBar` will become `foo_bar`.
        $identifier = GeneralUtility::camelCaseToLowerCaseUnderscored($identifier);
        // We want dashes instead of underscores.
        $identifier = strtr($identifier, '_', '-');
        // Applying the regex pattern.
        $identifier = preg_replace(self::IDENTIFIER_ALLOWED_PATTERN, '', $identifier);

        return $identifier;
    }

    /**
     * @param string $className
     * @return string
     */
    private function getClassShortName(string $className): string
    {
        // Getting the last backslash of the class name.
        $className = strrchr($className, '\\');
        // Deleting the backslash.
        $className = substr($className, 1);

        return $className;
    }
}
