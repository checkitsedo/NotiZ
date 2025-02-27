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

namespace CuyZ\Notiz\ViewHelpers\Slot;

use CuyZ\Notiz\View\Slot\Application\Slot;
use CuyZ\Notiz\View\Slot\SlotContainer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

abstract class SlotViewHelper extends AbstractViewHelper
{
    const SLOT_CONTAINER = 'SlotContainer';

    /**
     * @inheritdoc
     */
    public function initializeArguments()
    {
        $this->registerArgument('name', 'string', 'Name of the slot, must be unique.', true);
        $this->registerArgument('label', 'string', 'Label of the slot, can use LLL references.');
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $this->getSlotContainer()->add($this->getSlot());
    }

    /**
     * @return Slot
     */
    abstract protected function getSlot(): Slot;

    /**
     * @return string
     */
    protected function getSlotName(): string
    {
        return $this->arguments['name'];
    }

    /**
     * @return string
     */
    protected function getSlotLabel(): string
    {
        $label = $this->arguments['label'];

        if (!$label) {
            $label = $this->renderChildren();
        }

        return (string)$label;
    }

    /**
     * @return SlotContainer
     */
    protected function getSlotContainer(): SlotContainer
    {
        return $this->viewHelperVariableContainer->get(__CLASS__, self::SLOT_CONTAINER);
    }
}
