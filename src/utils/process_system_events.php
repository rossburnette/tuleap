<?php
/**
 * Copyright (c) Enalean SAS, 2013-2015. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'pre.php';

$request_queue = (isset($argv[1])) ? $argv[1] : SystemEvent::DEFAULT_QUEUE;

$factory = new SystemEventProcessor_Factory(BackendLogger::getDefaultLogger(), SystemEventManager::instance(), EventManager::instance());

$processor = $factory->getProcessForQueue($request_queue);

$mutex = new SystemEventProcessorMutex(new SystemEventProcessManager(), $processor);
$mutex->execute();
