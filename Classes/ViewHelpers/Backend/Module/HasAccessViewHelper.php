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

namespace CuyZ\Notiz\ViewHelpers\Backend\Module;

use CuyZ\Notiz\Backend\Module\ModuleHandler;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class HasAccessViewHelper extends AbstractViewHelper
{
    /**
     * @inheritdoc
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument(
            'module',
            'string',
            'Name of the module, for instance Manager or Administration.',
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return ModuleHandler::for($this->arguments['module'])->canBeAccessed();
    }
}
