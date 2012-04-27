<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

if (! defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

if (! defined('TRACKER_BASE_DIR')) {
    define('TRACKER_BASE_DIR', dirname(__FILE__).'/../../../tracker/include');
}

require_once 'common/user/User.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/CrossSearch/SearchContentView.class.php';
require_once dirname(__FILE__).'/../../include/Planning/Planning.class.php';
require_once dirname(__FILE__).'/../../include/Planning/ShowPresenter.class.php';

class Planning_ShowPresenterTest extends TuleapTestCase {
    public function itProvidesThePlanningTrackerArtifactCreationUrl() {
        $planning            = stub('Planning')->getPlanningTrackerId()->returns(191);
        $content_view        = mock('Tracker_CrossSearch_SearchContentView');
        $artifacts_to_select = array();
        $artifact            = null;
        $user                = mock('User');
        
        $presenter = new Planning_ShowPresenter($planning,
                                                $content_view,
                                                $artifacts_to_select,
                                                $artifact,
                                                $user);
        
        $url = $presenter->getPlanningTrackerArtifactCreationUrl();
        $this->assertEqual($url, '/plugins/tracker/?tracker=191&func=new-artifact');
    }
}
?>
