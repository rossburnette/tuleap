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

declare(strict_types = 1);

namespace Tuleap\Docman\Upload\Version;

use Tuleap\DB\DataAccessObject;

class DocumentOnGoingVersionToUploadDAO extends DataAccessObject
{

    public function saveDocumentVersionOngoingUpload(
        int $expiration_date,
        int $item_id,
        string $version_title,
        string $changelog,
        int $user_id,
        string $filename,
        int $filesize
    ) : int {
        $version_id = $this->getDB()->insertReturnId(
            'plugin_docman_new_version_upload',
            [
                'expiration_date' => $expiration_date,
                'item_id'         => $item_id,
                'version_title'   => $version_title,
                'changelog'       => $changelog,
                'user_id'         => $user_id,
                'filename'        => $filename,
                'filesize'        => $filesize
            ]
        );
        return (int)$version_id;
    }

    public function searchDocumentVersionOngoingUploadByItemIdAndExpirationDate(int $id, int $timestamp) : array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE item_id = ?  AND expiration_date > ?';

        return $this->getDB()->run($sql, $id, $timestamp);
    }

    public function searchDocumentVersionOngoingUploadByVersionIdAndExpirationDate(int $id, int $timestamp) : array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE id = ? AND expiration_date > ?';

        return $this->getDB()->row($sql, $id, $timestamp);
    }


    public function searchDocumentVersionOngoingUploadForAnotherUserByItemIdAndExpirationDate(int $id, int $user_id, int $timestamp) : array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE item_id = ?  AND expiration_date > ? AND user_id != ?';

        return $this->getDB()->run($sql, $id, $timestamp, $user_id);
    }

    public function deleteByVersionID(int $version_id): void
    {
        $sql = 'DELETE
                FROM plugin_docman_new_version_upload
                WHERE item_id = ?';

        $this->getDB()->run($sql, $version_id);
    }

    public function searchDocumentVersionOngoingUploadByVersionId(int $version_id) : array
    {
        $sql = 'SELECT *
                FROM plugin_docman_new_version_upload
                WHERE id = ?';

        return $this->getDB()->row($sql, $version_id);
    }
}