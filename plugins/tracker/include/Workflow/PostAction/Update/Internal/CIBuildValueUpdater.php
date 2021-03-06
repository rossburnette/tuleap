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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use DataAccessQueryException;
use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class CIBuildValueUpdater implements PostActionUpdater
{
    /**
     * @var CIBuildValueRepository
     */
    private $ci_build_repository;
    /**
     * @var CIBuildValueValidator
     */
    private $validator;

    public function __construct(CIBuildValueRepository $ci_build_repository, CIBuildValueValidator $validator)
    {
        $this->ci_build_repository = $ci_build_repository;
        $this->validator           = $validator;
    }

    /**
     * Update (and replace) all CI Build post actions with those included in given collection.
     * @throws DataAccessQueryException
     * @throws UnknownPostActionIdsException
     * @throws InvalidPostActionException
     */
    public function updateByTransition(PostActionCollection $actions, Transition $transition): void
    {
        $actions->validateCIBuildActions($this->validator);
        $existing_ids_collection = $this->ci_build_repository->findAllIdsByTransition($transition);
        $diff                    = $actions->compareCIBuildActionsTo($existing_ids_collection);

        /** @var CIBuildValue[] $updated_actions */
        $updated_actions = $diff->getUpdatedActions();
        $this->ci_build_repository->deleteAllByTransitionIfNotIn($transition, $updated_actions);

        foreach ($diff->getAddedActions() as $added_action) {
            assert($added_action instanceof CIBuildValue);
            $this->ci_build_repository->create($transition, $added_action);
        }
        foreach ($diff->getUpdatedActions() as $updated_action) {
            assert($updated_action instanceof CIBuildValue);
            $this->ci_build_repository->update($updated_action);
        }
    }
}
