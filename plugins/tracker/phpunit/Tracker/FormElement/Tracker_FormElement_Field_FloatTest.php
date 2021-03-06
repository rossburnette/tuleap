<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\FormElement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Response;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_Value_FloatDao;
use Tracker_Report_Criteria;
use Tuleap\GlobalLanguageMock;

final class Tracker_FormElement_Field_FloatTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    protected function tearDown() : void
    {
        unset($GLOBALS['Response']);
    }

    public function testNoDefaultValue() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $float_field->shouldReceive('getProperty')->andReturn(null);
        $this->assertFalse($float_field->hasDefaultValue());
    }

    public function testDefaultValue() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $float_field->shouldReceive('getProperty')->with('default_value')->andReturn('12.34');
        $this->assertTrue($float_field->hasDefaultValue());
        $this->assertEquals(12.34, $float_field->getDefaultValue());
    }

    public function testGetChangesetValue() : void
    {
        $value_dao = \Mockery::mock(Tracker_FormElement_Field_Value_FloatDao::class);
        $value_dao->shouldReceive('searchById')->andReturn(
            \TestHelper::arrayToDar(['id' => 123, 'field_id' => 1, 'value' => '1.003'])
        );

        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $float_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $this->assertInstanceOf(
            Tracker_Artifact_ChangesetValue_Float::class,
            $float_field->getChangesetValue(\Mockery::mock(Tracker_Artifact_Changeset::class), 123, false)
        );
    }

    public function testGetChangesetValueDoesNotExist() : void
    {
        $value_dao = \Mockery::mock(Tracker_FormElement_Field_Value_FloatDao::class);
        $value_dao->shouldReceive('searchById')->andReturn(\TestHelper::emptyDar());

        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $float_field->shouldReceive('getValueDao')->andReturn($value_dao);

        $this->assertNull($float_field->getChangesetValue(null, 123, false));
    }

    public function testIsValidRequiredField() : void
    {
        $GLOBALS['Response'] = \Mockery::spy(Response::class);

        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $float_field->shouldReceive('isRequired')->andReturn(true);
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->assertTrue($float_field->isValid($artifact, 2));
        $this->assertTrue($float_field->isValid($artifact, 789));
        $this->assertTrue($float_field->isValid($artifact, 1.23));
        $this->assertTrue($float_field->isValid($artifact, -1.45));
        $this->assertTrue($float_field->isValid($artifact, 0));
        $this->assertTrue($float_field->isValid($artifact, 0.0000));
        $this->assertTrue($float_field->isValid($artifact, '56.789'));
        $this->assertFalse($float_field->isValid($artifact, 'toto'));
        $this->assertFalse($float_field->isValid($artifact, '12toto'));
        $this->assertFalse($float_field->isValid($artifact, []));
        $this->assertFalse($float_field->isValid($artifact, [1]));
        $this->assertFalse($float_field->isValidRegardingRequiredProperty($artifact, ''));
        $this->assertFalse($float_field->isValidRegardingRequiredProperty($artifact, null));
    }

    public function testIsValidNotRequiredField() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $float_field->shouldReceive('isRequired')->andReturn(true);
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->assertTrue($float_field->isValid($artifact, ''));
        $this->assertTrue($float_field->isValid($artifact, null));
    }

    public function testGetFieldData() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $this->assertEquals('3.14159', $float_field->getFieldData('3.14159'));
    }

    public function testFetchChangesetValue() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $this->assertSame('3.1416', $float_field->fetchChangesetValue(123, 456, 3.14159));
        $this->assertSame('0', $float_field->fetchChangesetValue(123, 456, 0));
        $this->assertSame('2', $float_field->fetchChangesetValue(123, 456, 2));
        $this->assertSame('', $float_field->fetchChangesetValue(123, 456, null));
    }

    public function testItSearchOnZeroValue() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $criteria    = \Mockery::mock(Tracker_Report_Criteria::class);

        $float_field->shouldReceive('isUsed')->andReturn(true);
        $float_field->shouldReceive('getCriteriaValue')->andReturn(0);

        $this->assertNotEquals($float_field->getCriteriaFrom($criteria), '');
    }

    public function testItSearchOnCustomQuery() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $criteria    = \Mockery::mock(Tracker_Report_Criteria::class);

        $float_field->shouldReceive('isUsed')->andReturn(true);
        $float_field->shouldReceive('getCriteriaValue')->andReturn('>1');

        $this->assertNotEquals($float_field->getCriteriaFrom($criteria), '');
    }

    public function testItDoesntSearchOnEmptyString() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $criteria    = \Mockery::mock(Tracker_Report_Criteria::class);

        $float_field->shouldReceive('isUsed')->andReturn(true);
        $float_field->shouldReceive('getCriteriaValue')->andReturn('');

        $this->assertEquals($float_field->getCriteriaFrom($criteria), '');
    }

    public function testItFetchCriteriaAndSetValueZero() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $criteria    = \Mockery::mock(Tracker_Report_Criteria::class);

        $float_field->setId(1);
        $float_field->shouldReceive('getCriteriaValue')->andReturn(0);

        $this->assertEquals(
            $float_field->fetchCriteriaValue($criteria),
            '<input type="text" name="criteria[1]" id="tracker_report_criteria_1" value="0" />'
        );
    }

    public function testItFetchCriteriaAndLeaveItEmptyValue() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $criteria    = \Mockery::mock(Tracker_Report_Criteria::class);

        $float_field->setId(1);
        $float_field->shouldReceive('getCriteriaValue')->andReturn('');

        $this->assertEquals(
            $float_field->fetchCriteriaValue($criteria),
            '<input type="text" name="criteria[1]" id="tracker_report_criteria_1" value="" />'
        );
    }

    public function testTheValueIndexedByFieldNameIsReturned() : void
    {
        $float_field = \Mockery::mock(Tracker_FormElement_Field_Float::class)->makePartial();
        $value = [
            'field_id' => 876,
            'value'    => 3.14,
        ];

        $this->assertEquals(3.14, $float_field->getFieldDataFromRESTValueByField($value));
    }
}
