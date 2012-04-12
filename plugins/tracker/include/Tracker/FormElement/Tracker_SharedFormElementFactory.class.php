<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class Tracker_SharedFormElementFactory {
    /**
     * @var Tracker_FormElementFactory
     */
    protected $factory;
    /**
     * @var Tracker_FormElement_Field_List_BindFactory $boundValuesFactory 
     */
    private $boundValuesFactory;

    private $dao;
    
    function __construct(Tracker_FormElementFactory $factory, Tracker_FormElement_Field_List_BindFactory $boundValuesFactory) {
        $this->boundValuesFactory = $boundValuesFactory;
        $this->factory = $factory;
    }
    
    public function setDao(Tracker_FormElement_FieldDao $dao) {
        $this->dao = $dao;
    }
            
    /**
     * @return Tracker_FormElement_FieldDao
     */
    public function getDao() {
        if (! $this->dao) {
            $this->dao = new Tracker_FormElement_FieldDao();
        }
        return $this->dao;
    }
    
    public function fixOriginalFieldIdsAfterDuplication($field_mapping, $project_id) {
        $project_target_shared_fields = $this->getDao()->searchProjectSharedFieldsTargets($project_id);
        foreach ($project_target_shared_fields as $shared_field_row) {
            $new_source_field_id = $field_mapping[$shared_field_row['original_field_id']];
            $this->getDao()->updateOriginalFieldId($shared_field_row['id'], $new_source_field_id);
        }
    }
    
    public function createFormElement(Tracker $tracker, array $formElement_data, User $user) {
        $field = $this->getRootOriginalField($this->factory->getFormElementById($formElement_data['field_id']));
        $this->assertFieldCanBeCopied($field, $user);
        
        $data = $this->populateFormElementDataForASharedField($field);
        $type = $data['type'];
        $id = $this->factory->createFormElement($tracker, $type, $data);
        $this->boundValuesFactory->duplicateByReference($field->getId(), $id);
        return $id;
    }
    
    private function getRootOriginalField(Tracker_FormElement $field) {
        $originalField = $field->getOriginalField();
        if ($originalField === null) {
            return $field;
        }
        return $this->getRootOriginalField($originalField);
    }
    
    private function assertFieldCanBeCopied(Tracker_FormElement $field, User $user) {
        $this->assertFieldIsReadable($field, $user);
        $this->assertFieldIsStaticSelectbox($field);
    }
    
    private function assertFieldIsReadable(Tracker_FormElement $field, User $user) {
        if ( ! ($field->userCanRead($user) 
              && $field->getTracker()->userCanView($user))) {
            throw new Exception('Permission denied');
        }
    }
    
    private function assertFieldIsStaticSelectbox(Tracker_FormElement $field) {
        if ( ! ($field instanceof Tracker_FormElement_Field_Selectbox
                && $field->getBind() instanceof Tracker_FormElement_Field_List_Bind_Static)) {
            throw new Exception('Can only share static selectbox fields');
        }
    }
    
    private function populateFormElementDataForASharedField($originField) {
        return array(
            'type'              => $this->factory->getType($originField),
            'label'             => $originField->getLabel(),
            'description'       => $originField->getDescription(),
            'label'             => $originField->getLabel(),
            'use_it'            => $originField->isUsed(),
            'scope'             => $originField->getScope(),
            'required'          => $originField->isRequired(),
            'notifications'     => $originField->hasNotifications(),
            'original_field_id' => $originField->getId(),
        );
    }
    
}

?>
