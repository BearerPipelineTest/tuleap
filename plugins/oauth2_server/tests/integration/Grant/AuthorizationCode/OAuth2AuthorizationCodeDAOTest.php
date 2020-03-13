<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBFactory;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenAuthorizationGrantAssociationDAO;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\User\OAuth2\AccessToken\Scope\OAuth2AccessTokenScopeDAO;

final class OAuth2AuthorizationCodeDAOTest extends TestCase
{
    /**
     * @var OAuth2AuthorizationCodeDAO
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = new OAuth2AuthorizationCodeDAO();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->delete('plugin_oauth2_authorization_code', []);
        $db->delete('plugin_oauth2_authorization_code_access_token', []);
        $db->delete('oauth2_access_token', []);
        $db->delete('oauth2_access_token_scope', []);
    }

    public function testAnAuthorizationCodeCanBeCreatedAndRemoved(): void
    {
        $user_id              = 102;
        $verification_string  = 'hashed_verification_string';
        $expiration_timestamp = 20;

        $auth_code_id = $this->dao->create(
            $user_id,
            $verification_string,
            $expiration_timestamp
        );

        $authorization_code_row = $this->dao->searchAuthorizationCode($auth_code_id);
        $this->assertEquals(
            ['user_id' => $user_id, 'verifier' => $verification_string, 'expiration_date' => $expiration_timestamp, 'has_already_been_used' => 0],
            $authorization_code_row
        );

        $this->dao->deleteAuthorizationCodeByID($auth_code_id);

        $this->assertNull($this->dao->searchAuthorizationCode($auth_code_id));
    }

    public function testDeletingAnAuthorizationCodeDeletesTheAssociatedTokens(): void
    {
        $auth_code_id = $this->dao->create(102, 'hashed_verification_string_auth', 20);

        $access_token_dao  = new OAuth2AccessTokenDAO();
        $access_token_id_1 = $access_token_dao->create(102, 'hashed_verification_string_access', 30);
        $access_token_id_2 = $access_token_dao->create(102, 'hashed_verification_string_access', 30);
        $grant_auth_access_token_association_dao = new OAuth2AccessTokenAuthorizationGrantAssociationDAO();
        $grant_auth_access_token_association_dao->createAssociationBetweenAuthorizationGrantAndAccessToken(
            $auth_code_id,
            $access_token_id_1
        );
        $grant_auth_access_token_association_dao->createAssociationBetweenAuthorizationGrantAndAccessToken(
            $auth_code_id,
            $access_token_id_2
        );
        $access_token_scope_dao = new OAuth2AccessTokenScopeDAO();
        $access_token_scope_dao->saveScopeKeysByOAuth2AccessTokenID($access_token_id_1, 'scope:A', 'scope:B');

        $this->assertNotNull($access_token_dao->searchAccessToken($access_token_id_1));
        $this->assertNotNull($access_token_dao->searchAccessToken($access_token_id_2));
        $this->assertNotEmpty($access_token_scope_dao->searchScopeIdentifiersByAccessTokenID($access_token_id_1));

        $this->dao->deleteAuthorizationCodeByID($auth_code_id);

        $this->assertNull($this->dao->searchAuthorizationCode($auth_code_id));
        $this->assertNull($access_token_dao->searchAccessToken($access_token_id_1));
        $this->assertNull($access_token_dao->searchAccessToken($access_token_id_2));
        $this->assertEmpty($access_token_scope_dao->searchScopeIdentifiersByAccessTokenID($access_token_id_1));
    }
}
