<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

use Codendi_Request;
use SimpleXMLElement;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElement_Field;
use Transition_PostAction;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

class HiddenFieldsets extends Transition_PostAction
{
    public const SHORT_NAME = 'hidden_fieldsets';

    /**
     * @var Tracker_FormElement_Container_Fieldset[]
     */
    private $fieldsets = [];

    public function __construct(\Transition $transition, int $id, array $fieldsets)
    {
        parent::__construct($transition, $id);

        $this->fieldsets = $fieldsets;
    }

    /**
     * @return Tracker_FormElement_Container_Fieldset[]
     */
    public function getFieldsets(): array
    {
        return $this->fieldsets;
    }

    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    public function getShortName() : string
    {
        return self::SHORT_NAME;
    }

    /**
     * Get the label of the post action
     *
     * @return string
     */
    public static function getLabel()
    {
        // Not implemented. We do not support the legacy UI for this new post action
        return '';
    }

    /**
     * Get the html code needed to display the post action in workflow admin
     *
     * @return string html
     */
    public function fetch()
    {
        /// Not implemented. We do not support the legacy UI for this new post action
        return '';
    }

    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    public function isDefined()
    {
        // Since we do not support the legacy UI, it is always well defined
        return true;
    }

    /**
     * Update/Delete action
     *
     * @param Codendi_Request $request The request
     *
     * @return void
     */
    public function process(Codendi_Request $request)
    {
        // Not implemented. We do not support the legacy UI for this new post action
    }

    /**
     * Export postactions to XML
     *
     * @param SimpleXMLElement &$root the node to which the postaction is attached (passed by reference)
     * @param array $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        // Not implemented.
    }

    /**
     * Get the value of bypass_permissions
     *
     * @param Tracker_FormElement_Field $field
     *
     * @return bool
     */
    public function bypassPermissions(Tracker_FormElement_Field $field)
    {
        return false;
    }

    public function accept(Visitor $visitor)
    {
        // TODO: Implement accept() method.
    }
}
