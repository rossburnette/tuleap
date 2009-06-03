<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
require_once('Docman_FileStorage.class.php');
require_once('Docman_VersionFactory.class.php');
class Docman_ActionsDeleteVisitor /* implements Visitor */ {
    
    function Docman_ActionsDeleteVisitor(&$file_storage, &$docman) {
        //More coherent to have only one delete date for a whole hierarchy.
        $this->deleteDate   = time();
        $this->file_storage =& $file_storage;
        $this->docman       =& $docman;
    }
    
    function visitFolder(&$item, $params = array()) {
        //delete all sub items before
        $items = $item->getAllItems();
        $parent =& $params['parent'];
        $one_item_has_not_been_deleted = false;
        if ($items->size()) {
            $it =& $items->iterator();
            while($it->valid()) {
                $o =& $it->current();
                $params['parent'] =& $item;
                if (!$o->accept($this, $params)) {
                    $one_item_has_not_been_deleted = true;
                }
                $it->next();
            }
        }
        
        if ($one_item_has_not_been_deleted) {
            $this->docman->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_delete_notempty', $item->getTitle()));
            return false;
        } else {
            //Mark the folder as deleted;
            $params['parent'] =& $parent;
            return $this->_deleteItem($item, $params);
        }
    }
    function visitDocument(&$item, $params = array()) {
        //Mark the document as deleted
        return $this->_deleteItem($item, $params);
    }
    function visitWiki(&$item, $params = array()) {
        // delete the document.
        $deleted = $this->visitDocument($item, $params);
        if($deleted) {
            // grant a wiki permission only to wiki admins on the corresponding wiki page.
            $this->restrictAccess($item, $params);
        }
        return $deleted;
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        if ($this->docman->userCanWrite($item->getId())) {
            //Delete all versions before
            $version_factory =& $this->_getVersionFactory();
            if ($versions = $version_factory->getAllVersionForItem($item)) {
                if (count($versions)) {
                    foreach ($versions as $key => $nop) {
                        $this->file_storage->delete($versions[$key]->getPath());
                    }
                }
            }
            return $this->visitDocument($item, $params);
        } else {
            $this->docman->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete_item', $item->getTitle()));
            return false;
        }
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitFile($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function restrictAccess($item, $params = array()) {
        // Check whether there is other references to this wiki page.
        $dao =& $this->_getItemDao();
        $referenced = $dao->isWikiPageReferenced($item->getPageName(), $item->getGroupId());
        if(!$referenced) {
            $dIF =& $this->_getItemFactory();
            $id_in_wiki = $dIF->getIdInWikiOfWikiPageItem($item->getPageName(), $item->getGroupId());
            // Restrict access to wiki admins if the page already exists in wiki.
            if($id_in_wiki !== null) {
                permission_clear_all($item->getGroupId(), 'WIKIPAGE_READ', $id_in_wiki, false);
                permission_add_ugroup($item->getGroupId(), 'WIKIPAGE_READ', $id_in_wiki, $GLOBALS['UGROUP_WIKI_ADMIN']);
            }
        }
    }

    function _deleteItem($item, $params) {
       if ($this->docman->userCanWrite($item->getId())) {

            // The event must be processed before the item is deleted
            $em =& $this->_getEventManager();
            $em->processEvent('plugin_docman_event_del', array(
                'group_id' => $item->getGroupId(),
                'item'     => &$item,
                'parent'   => &$params['parent'],
                'user'     => &$params['user'])
            );
            
            $item->setDeleteDate($this->deleteDate);
            $dao = $this->_getItemDao();
            $dao->updateFromRow($item->toRow());
            return true;
        } else {
            $this->docman->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_perms_delete_item', $item->getTitle()));
            return false;
        }
    }
    function &_getEventManager() {
        return EventManager::instance();
    }
    function &_getVersionFactory() {
        $f = new Docman_VersionFactory();
        return $f;
    }
    var $item_factory;
    function &_getItemFactory() {
        if (!$this->item_factory) {
            $this->item_factory =& new Docman_ItemFactory();
        }
        return $this->item_factory;
    }
    function &_getFileStorage() {
        $fs = new Docman_FileStorage();
        return $fs;
    }
    function &_getItemDao() {
        $dao = new Docman_ItemDao(CodendiDataAccess::instance());
        return $dao;
    }
}
?>
