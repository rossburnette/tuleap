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

use Tuleap\DB\DataAccessObject;

class HiddenFieldsetsDao extends DataAccessObject
{

    public function isFieldsetUsedInPostAction(int $fieldset_id): bool
    {
        $sql = 'SELECT NULL
            FROM plugin_tracker_workflow_postactions_hidden_fieldsets_value
            WHERE plugin_tracker_workflow_postactions_hidden_fieldsets_value.fieldset_id = ?';

        $result = $this->getDB()->cell($sql, $fieldset_id);

        return $result !== false;
    }

    public function searchByTransitionId(int $transition_id): array
    {
        $sql = 'SELECT plugin_tracker_workflow_postactions_hidden_fieldsets_value.*
            FROM plugin_tracker_workflow_postactions_hidden_fieldsets_value
                INNER JOIN plugin_tracker_workflow_postactions_hidden_fieldsets
                ON (plugin_tracker_workflow_postactions_hidden_fieldsets_value.postaction_id =
                    plugin_tracker_workflow_postactions_hidden_fieldsets.id)
            WHERE plugin_tracker_workflow_postactions_hidden_fieldsets.transition_id = ?';

        return $this->getDB()->q($sql, $transition_id);
    }
}
