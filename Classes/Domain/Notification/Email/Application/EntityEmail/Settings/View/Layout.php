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

namespace CuyZ\Notiz\Domain\Notification\Email\Application\EntityEmail\Settings\View;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use CuyZ\Notiz\Core\Definition\Tree\AbstractDefinitionComponent;
use CuyZ\Notiz\Service\LocalizationService;

class Layout extends AbstractDefinitionComponent
{
    /**
     * @var string
     *
     * @Extbase\Validate("NotEmpty")
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     *
     * @Extbase\Validate("NotEmpty")
     */
    protected $path;

    /**
     * @param string $identifier
     * @param string $path
     */
    public function __construct(string $identifier, string $path)
    {
        $this->identifier = $identifier;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return bool
     */
    public function hasLabel(): bool
    {
        return strlen(trim($this->label)) > 0;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return LocalizationService::localize($this->label);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
