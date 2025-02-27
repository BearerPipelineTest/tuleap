<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Group\Token;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Gitlab\Group\GitlabGroup;

final class GroupTokenInserter implements InsertGroupToken
{
    public function __construct(private GroupApiTokenDAO $group_api_token_DAO, private KeyFactory $key_factory)
    {
    }

    public function insertToken(GitlabGroup $gitlab_group, ConcealedString $token): void
    {
        $encrypted_secret = SymmetricCrypto::encrypt(
            $token,
            $this->key_factory->getEncryptionKey()
        );

        $this->group_api_token_DAO->storeToken(
            $gitlab_group->id,
            $encrypted_secret
        );
    }
}
