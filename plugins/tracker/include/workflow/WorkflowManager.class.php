<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Workflow_Dao.class.php');
require_once('Workflow_TransitionDao.class.php');
require_once('Workflow.class.php');
require_once('WorkflowFactory.class.php');

class WorkflowManager {
    protected $tracker;
    public function __construct($tracker) {
        $this->tracker = $tracker;
    }
    
    public function getTracker(){
        return $tracker;
    }
    
    public function process($engine, $request, $current_user) {
        
        if ($request->get('create')) {
            
            if ($request->existAndNonEmpty('field_id')) {
                
                if (WorkflowFactory::instance()->create((int)$this->tracker->id, $request->get('field_id'))) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','created'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array(
                                                        'tracker' => (int)$this->tracker->id,
                                                        'func'    => 'admin-workflow')));
                }
            }
        }else if ($request->get('edit_transition')) {
            $workflow = WorkflowFactory::instance()->getWorkflowField($this->tracker->id);
            $t = $request->get('edit_transition');
            $res = WorkflowFactory::instance()->getTransitionId($workflow->workflow_id , $t);
            
            $row = $res->getRow();
            $field=Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id );
            $field_values = $field->getBind()->getAllValues();
            
            if(isset($field_values[$row['from_id']])) {
                $field_value_from = $field_values[$row['from_id']];
            }else {
                $field_value_from =null;
            }
            $field_value_to = $field_values[$row['to_id']];
            
            $transition = new Transition($row['transition_id'], $workflow->workflow_id, $field_value_from, $field_value_to);
            $this->displayTransitionDetails($engine, $request, $current_user, $transition);
            
        }else if ($request->get('delete')) {
            
            if (WorkflowFactory::instance()->delete($request->get('delete'))) {
                if(WorkflowFactory::instance()->deleteWorkflowTransitions($request->get('delete'))) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','deleted'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array(
                                                    'tracker' => (int)$this->tracker->id,
                                                    'func'    => 'admin-workflow')));
                }
            }            
        }else if ($request->get('create_matrix')) {
            
            $k=0;
            $workflow = WorkflowFactory::instance()->getWorkflowField($this->tracker->id);
            
            $field=Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id);
            $field_values = $field->getBind()->getAllValues();
            
            $currMatrix=array();
            $field_value_from=null;
            //Add an initial state transition
            foreach($field_values as $field_value_id_to=>$field_value_to) {
               //$field_value_from=;
               $transition = '_'.$field_value_id_to;
               
               if ($request->existAndNonEmpty($transition)) {
                   $currMatrix[]=array('', $field_value_id_to);
                   $k+=$this->addTransition($workflow, $transition, $field_value_from, $field_value_to);
               }
           }
           
           //Add a transition
            foreach($field_values as $field_value_id_from=>$field_value_from) {
               foreach($field_values as $field_value_id_to=>$field_value_to) {
                   $transition = $field_value_id_from.'_'.$field_value_id_to;                  
                   if ($request->existAndNonEmpty($transition)) {
                       $currMatrix[]=array($field_value_id_from, $field_value_id_to);
                       $k+=$this->addTransition($workflow, $transition, $field_value_from, $field_value_to);
                    }
               }
            }
            
            //Delete a transition
            $transitions_in_db = $workflow->getTransitions();
            $nb_transitions_in_db = count($transitions_in_db);
            for($i=0;$i<$nb_transitions_in_db ;$i++) {
                
                $field_value_from = $transitions_in_db[$i]->from;                
                $field_value_to = $transitions_in_db[$i]->to;
                 //Treatment of the initial state
                if($field_value_from==null) {
                    $value_to_search=array('',$field_value_to->getId());
                    //$field_value_from->getId()='';
                }else {
                    $value_to_search=array($field_value_from->getId() , $field_value_to->getId());
                }
                
                if(!in_array($value_to_search, $currMatrix)){
                    WorkflowFactory::instance()-> deleteTransition($workflow->workflow_id,  $field_value_from, $field_value_to);
                    $k++;
                }
            }

            if ($k>0) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','updated'));
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));
            } else {                
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','not_updated'));
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));
            }

            
        }else if ($request->get('enable_workflow')) {

            $workflow = WorkflowFactory::instance()->getWorkflowField($this->tracker->id);
            $is_used = $request->get('is_used');
            //TODO : use $request
            if (/*$request->existAndNonEmpty($is_used)*/$is_used=='on') {
                $is_used = 1;
                $feedback = $GLOBALS['Language']->getText('workflow_admin','workflow_enabled');
            }else {
                $is_used = 0;
                $feedback = $GLOBALS['Language']->getText('workflow_admin','workflow_disabled');
            }
                
           if (WorkflowFactory::instance()->updateActivation((int)$workflow->workflow_id, $is_used)) {
               $GLOBALS['Response']->addFeedback('info', $feedback);
               $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));                 
           }          
        } else if ($request->get('workflow_details')) {
            $ugroups = $request->get('ugroups');
            $transition = $request->get('transition');
            permission_clear_all($this->tracker->group_id, 'PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition, false); 
            if (WorkflowFactory::instance()->addPermissions($ugroups, $transition)) {
               $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('workflow_admin','permissions_updated'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_admin','permissions_not_updated'));
            }
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')));                 
            
        }else {        
            $this->displayAdminDefineWorkflow($engine, $request, $current_user);
        }
    }
    
    protected function addTransition($workflow, $transition, $field_value_from, $field_value_to) {
        $i=0;
        if ( ! $workflow->isTransitionExist($field_value_from, $field_value_to)) {                                                    
            if (WorkflowFactory::instance()-> addTransition((int)$workflow->workflow_id, $transition)) {   
                $i++;
            }
         }
         return $i;
    }
    
    protected function displayTransitionsMatrix($workflow, $engine, $request, $current_user) {

        $workflow = WorkflowFactory::instance()->getWorkflowField($this->tracker->id);
        echo '<h3>'.$GLOBALS['Language']->getText('workflow_admin','title_define_transitions').'</h3>';
        $field =Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id);
        if ($workflow->hasTransitions()) {
            $transitions = $workflow->getTransitions($workflow->workflow_id) ;
            $field->displayTransitionsMatrix($transitions);
        }else {                 
             $field->displayTransitionsMatrix();
        }
    }
    
    protected function displayTransitionDetails($engine, $request, $current_user, $transition) {
        
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');
        echo '<h3>'.$GLOBALS['Language']->getText('workflow_admin','title').'</h3>';
        $workflow = WorkflowFactory::instance()->getWorkflowField($this->tracker->id);
        if ($transition->from ==null) {
            $from_label = $GLOBALS['Language']->getText('workflow_admin','new_artifact');
        } else {
            $from_label = $transition->from->getLabel();
        }
        
        echo '<p>';
        echo $GLOBALS['Language']->getText('workflow_admin','title_define_transition_details', array($from_label, $transition->to->getLabel()));
        echo '</p>';
        
        $form_action = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker'    => (int)$this->tracker->id, 
                'func'       => 'admin-workflow', 
                'transition' => $transition->getTransitionId()
            )
        );
        echo '<form action="'. $form_action .'" method="POST">';
        echo '<table><tr><td>';
        
        $section_conditions = new Widget_Static('Under the following condition:');
        $section_conditions->setContent($this->fetchWorkflowPermissions($transition));
        $section_conditions->display();
        
        $actions = '<p><i>None for now</i></p>';
        $actions .= '<p>Add a new actions: <select><option selected>--<option>Change the value of a field</select></p>';
        $section_postactions = new Widget_Static('The following action will be automatically performed:');
        $section_postactions->setContent($actions);
        $section_postactions->display();
        
        echo '<p><input type="submit" name="workflow_details" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></p>';
        echo '</td></tr></table>';
        echo '</form>';
        
        $this->tracker->displayFooter($engine);
    }
    
    /**
     * Return permission form for the transition
     *
     * @param Transition $transition The transition
     *
     * @return string html
     */
    protected function fetchWorkflowPermissions($transition) {
        $html = '';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('workflow_admin','label_define_transition_permissions');
        $html .= '</p>';
        $html .= '<p>';
        $html .= plugin_tracker_permission_fetch_selection_field('PLUGIN_TRACKER_WORKFLOW_TRANSITION', $transition->getTransitionId(), $this->tracker->group_id); 
        $html .= '</p>';
        return $html;
    }
    
    protected function displayAdminWorkflow($engine, $request, $current_user, $workflow) {
       
        $field =Tracker_FormElementFactory::instance()->getFormElementById($workflow->field_id);
        
        echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')) .'" method="POST">';
        echo "<table>";
        echo "<tr class='boxtitle'>\n";
        echo "<td>".$GLOBALS['Language']->getText('workflow_admin','field')."</td>";
        echo "<td>".$GLOBALS['Language']->getText('workflow_admin','enabled')."</td>";
        echo "<td>".$GLOBALS['Language']->getText('workflow_admin','delete')."</td>";
        echo "</tr>";
           
        echo "<tr class=\"".util_get_alt_row_color(1)."\">\n";
        echo '<td>'.$field->label.'</td>';
        if($workflow->is_used) {
              echo '<td align="center"><input type="checkbox" name="is_used" checked="checked"></td>';
        }else {
              echo '<td align="center"><input type="checkbox" name="is_used" ></td>';
        }
           
         echo '<td align="center"><a href="'.TRACKER_BASE_URL.'/?'. http_build_query(array(
                                                        'tracker' => (int)$this->tracker->id,
                                                        'func'    => 'admin-workflow',
                                                        'delete'  => (int)$workflow->workflow_id)) .'" 
                             onClick="return confirm(\''.addslashes($GLOBALS['Language']->getText('workflow_admin','delete_workflow')).'\')">';
          echo $GLOBALS['HTML']->getImage('ic/cross.png');
          echo '</a></td></tr>';
          echo "</tr>";
          echo '</table>';
          echo '<input type="submit" name="enable_workflow" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
          echo '</FORM>';
          $this->displayTransitionsMatrix($workflow, $engine, $request, $current_user);
          $this->tracker->displayFooter($engine);
    }
    
    protected function displayAdminDefineWorkflow($engine, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');
        echo '<h3>'.$GLOBALS['Language']->getText('workflow_admin','title').'</h3>';
        $workflow = WorkflowFactory::instance()->getWorkflowField($this->tracker->id);
        
        if(count($workflow)) {
            $this->displayAdminWorkflow($engine, $request, $current_user, $workflow);
        }else {
            //Display creation form
            echo '<p>';
            echo $GLOBALS['Language']->getText('workflow_admin','choose_field');
            echo '<p>';            
            $aff =Tracker_FormElementFactory::instance(); 
            
            echo '<form action="'.TRACKER_BASE_URL.'/?'. http_build_query(array('tracker' => (int)$this->tracker->id, 'func'    => 'admin-workflow')).'" method="POST">';
            echo '<SELECT name="field_id">'; 
            //We display only the 'sb' static type field
            foreach ($aff->getUsedFormElementsByType($this->tracker, 'sb') as $field) {
                $bf = new Tracker_FormElement_Field_List_BindFactory();
                if ($bf->getType($field->getBind())=='static') {
                    echo '<OPTION value='.$field->id.'>'.$field->label.'</OPTION>';
                }
            }            
            echo '</SELECT>';
       
            echo '<input type="submit" name="create" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            echo '</from>';
            
            $this->tracker->displayFooter($engine);
        }
    }
    
       
}
?>
