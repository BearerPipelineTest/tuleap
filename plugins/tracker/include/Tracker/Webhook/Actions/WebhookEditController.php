<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook\Actions;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tracker;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;

class WebhookEditController implements DispatchableWithRequest
{

    /**
     * @var WebhookFactory
     */
    private $webhook_factory;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var WebhookDao
     */
    private $dao;

    /**
     * @var WebhookURLValidator
     */
    private $validator;

    public function __construct(
        WebhookFactory $webhook_factory,
        TrackerFactory $tracker_factory,
        WebhookDao $dao,
        WebhookURLValidator $validator,
    ) {
        $this->webhook_factory = $webhook_factory;
        $this->tracker_factory = $tracker_factory;
        $this->dao             = $dao;
        $this->validator       = $validator;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $webhook_id = $request->get('webhook_id');

        if (! $webhook_id) {
            $layout->redirect('/');
        }

        $webhook = $this->webhook_factory->getWebhookById($webhook_id);
        if (! $webhook) {
            throw new NotFoundException();
        }

        $tracker = $this->tracker_factory->getTrackerById($webhook->getTrackerId());
        if (! $tracker) {
            throw new NotFoundException();
        }

        $redirect_url = $this->getAdminWebhooksURL($tracker);
        $webhook_url  = $this->validator->getValidURL($request, $layout, $redirect_url);

        $user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($user)) {
            throw new ForbiddenException();
        }

        $csrf = $this->getCSRFSynchronizerToken($tracker);
        $csrf->check();

        $this->dao->edit($webhook_id, $webhook_url);

        $layout->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-tracker', 'Webhook sucessfully updated')
        );

        $layout->redirect($redirect_url);
    }

    /**
     * @return CSRFSynchronizerToken
     */
    private function getCSRFSynchronizerToken(Tracker $tracker)
    {
        return new CSRFSynchronizerToken($this->getAdminWebhooksURL($tracker));
    }

    private function getAdminWebhooksURL(Tracker $tracker)
    {
        return '/plugins/tracker/?' . http_build_query(
            [
                "func"    => "admin-webhooks",
                "tracker" => $tracker->getId(),
            ]
        );
    }
}
