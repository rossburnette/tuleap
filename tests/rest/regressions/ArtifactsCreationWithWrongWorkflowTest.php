<?php

/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../lib/autoload.php';

/**
 * @group Regressions
 */
class ArtifactsCreationWithWrongWorkflowTest extends RestBase {

    public function setUp() {
        parent::setUp();

        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            TestDataBuilder::PROJECT_PBI_ID,
            TestDataBuilder::TEST_USER_1_NAME
        );
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPostArtifactFailsIfValueInSelectBoxIsNotValidRegardingWorkflow() {
        $tracker  = $this->tracker_test_helper->getTrackerRest('releases');
        $response = $tracker->createArtifact(
            array(
               $tracker->getSubmitTextValue('Version Number', '0.1'),
               $tracker->getSubmitListValue('Progress', 'Delivered to customer')
            )
        );

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPostArtifactSuccededIfValueInSelectBoxIsValidRegardingWorkflow() {
        $tracker       = $this->tracker_test_helper->getTrackerRest('releases');
        $artifact_json = $tracker->createArtifact(
            array(
               $tracker->getSubmitTextValue('Version Number', '0.1'),
               $tracker->getSubmitListValue('Progress', 'To be defined')
            )
        );

        $this->assertTrue(isset($artifact_json['id']));
    }
}