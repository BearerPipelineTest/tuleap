<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

/**
 * @psalm-immutable
 */
final class GitlabCommitReferenceString implements \Tuleap\Reference\ReferenceString
{
    private function __construct(private string $reference)
    {
    }

    public static function fromRepositoryAndCommit(
        GitlabRepositoryIntegration $repository,
        PostPushCommitWebhookData $commit,
    ): self {
        return new self(
            sprintf('%s #%s/%s', GitlabCommitReference::REFERENCE_NAME, $repository->getName(), $commit->getSha1())
        );
    }

    public function getStringReference(): string
    {
        return $this->reference;
    }
}
