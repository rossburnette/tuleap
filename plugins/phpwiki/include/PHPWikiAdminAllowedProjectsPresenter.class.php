<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class PHPWikiAdminAllowedProjectsPresenter {

    const TEMPLATE = 'grant-only-allowed-projects';

    /**
     * @var Project[]
     */
    public $allowed_projects;

    public function __construct($allowed_projects) {
        $this->allowed_projects = $allowed_projects;
    }

    public function there_is_no_project() {
        return count($this->allowed_projects) === 0;
    }

    public function update_allowed_projects_action() {
        return '/plugins/phpwiki/admin.php?action=update_project';
    }

    public function update_allowed_projects_action_csrf() {
        $csrf = new CSRFSynchronizerToken($this->update_allowed_projects_action());
        return $csrf->fetchHTMLInput();
    }

    public function resource_allowed_project_back_link() {
        return '/admin/';
    }

    public function resource_allowed_project_back_link_title() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_back_link_title');
    }

    public function resource_allowed_project_title() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_title');
    }

    public function information() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_information');
    }

    public function resource_allowed_project_list() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list');
    }

    public function resource_allowed_project_list_allow_placeholder() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list_allow_placeholder');
    }

    public function resource_allowed_project_list_filter_placeholder() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list_filter_placeholder');
    }

    public function resource_allowed_project_list_allow_project() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list_allow_project');
    }


    public function resource_allowed_project_list_id() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list_id');
    }

    public function resource_allowed_project_list_name() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list_name');
    }

    public function resource_allowed_project_list_empty() {
        return $GLOBALS['Language']->getText('plugin_phpwiki', 'allowed_project_list_empty');
    }
}
