<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use ForgeConfig;
use Git_GitRepositoryUrlManager;
use GitRepository;
use HTTPRequest;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Git\GitPHP\Ref;

class FilesHeaderPresenterBuilder
{
    /**
     * @var CommitForCurrentTreeRetriever
     */
    private $commit_retriever;
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    public function __construct(
        CommitForCurrentTreeRetriever $commit_retriever,
        Git_GitRepositoryUrlManager $url_manager
    ) {
        $this->commit_retriever = $commit_retriever;
        $this->url_manager      = $url_manager;
    }

    /**
     * @param HTTPRequest $request
     * @param GitRepository $repository
     *
     * @return FilesHeaderPresenter
     */
    public function build(HTTPRequest $request, GitRepository $repository)
    {
        $repository_url = $this->url_manager->getRepositoryBaseUrl($repository);

        if (!ForgeConfig::get('git_repository_bp')) {
            return new FilesHeaderPresenter(
                $repository,
                $repository_url,
                false,
                '',
                false,
                ''
            );
        }

        $action = $request->get('a');
        if ($action !== 'tree' && $action !== false) {
            return new FilesHeaderPresenter(
                $repository,
                $repository_url,
                false,
                '',
                false,
                ''
            );
        }

        $commit = $this->commit_retriever->getCommitOfCurrentTree($request, $repository);
        list($head_name, $is_tag) = $commit ? $this->getHeadNameForCurrentCommit($request, $commit) : ['', false];
        $committer_epoch = $commit ? $commit->GetCommitterEpoch() : '';

        return new FilesHeaderPresenter(
            $repository,
            $repository_url,
            true,
            $head_name,
            $is_tag,
            $committer_epoch
        );
    }

    /**
     * @param HTTPRequest $request
     * @param Commit $commit
     *
     * @return array [string, bool]
     */
    private function getHeadNameForCurrentCommit(HTTPRequest $request, Commit $commit)
    {
        /** @var Ref[] $refs */
        if (empty($commit->GetHeads()) && empty($commit->GetTags())) {
            return [$commit->GetHash(), false];
        }

        $matching_ref = $this->searchRequestedRef($request, $commit);
        if ($matching_ref) {
            return $matching_ref;
        }

        if (! empty($commit->GetHeads())) {
            return $this->firstBranch($commit);
        }

        return $this->firstTag($commit);
    }

    /**
     * @param Ref[] $refs
     *
     * @return string[]
     */
    private function flattenRefsWithName(array $refs)
    {
        $refs_names = array_map(
            function (Ref $ref) {
                return $ref->GetName();
            },
            $refs
        );
        return $refs_names;
    }

    /**
     * @param Commit $commit
     * @return array
     */
    private function firstBranch(Commit $commit)
    {
        return [$commit->GetHeads()[0]->GetName(), false];
    }

    /**
     * @param Commit $commit
     * @return array
     */
    private function firstTag(Commit $commit)
    {
        return [$commit->GetTags()[0]->GetName(), true];
    }

    /**
     * @param HTTPRequest $request
     * @param Commit $commit
     * @return array|null
     */
    private function searchRequestedRef(HTTPRequest $request, Commit $commit)
    {
        $requested_hashbase = preg_replace('%^refs/(?:tags|heads)/%', '', $request->get('hb'));

        $matching_ref = null;
        if (array_search($requested_hashbase, $this->flattenRefsWithName($commit->GetHeads())) !== false) {
            $matching_ref = [$requested_hashbase, false];
        }
        if (array_search($requested_hashbase, $this->flattenRefsWithName($commit->GetTags())) !== false) {
            $matching_ref = [$requested_hashbase, true];
        }

        return $matching_ref;
    }
}
