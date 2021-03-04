<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Markdown;

use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

class CodeBlockFeaturesExtension implements ExtensionInterface
{
    /**
     * @var CodeBlockFeatures
     */
    private $code_block_features;

    public function __construct(CodeBlockFeatures $code_block_features)
    {
        $this->code_block_features = $code_block_features;
    }

    public function register(ConfigurableEnvironmentInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            function (DocumentParsedEvent $event): void {
                $document = $event->getDocument();
                $walker   = $document->walker();

                if ($this->code_block_features->isMermaidNeeded()) {
                    return;
                }

                while ($event = $walker->next()) {
                    $node = $event->getNode();

                    if (! $node instanceof FencedCode || ! $event->isEntering()) {
                        continue;
                    }

                    if ($node->getInfo() === 'mermaid') {
                        $this->code_block_features->needsMermaid();
                        break;
                    }
                }
            }
        );
    }
}
