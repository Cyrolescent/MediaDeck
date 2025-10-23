<?php
/**
 * Get default tags for a specific media type
 * @param PDO $pdo Database connection
 * @param string $media_type The media type (image, video, audio, text)
 * @return array Array of default tags
 */
function getDefaultTags($pdo, $media_type) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM tags 
            WHERE is_default = 1 
            AND (media_type = :media_type OR media_type = 'universal')
            ORDER BY 
                CASE WHEN parent_tag_id IS NULL THEN 0 ELSE 1 END,
                name ASC
        ");
        $stmt->execute(['media_type' => $media_type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get custom tags for a specific user and media type
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $media_type The media type
 * @return array Array of custom tags
 */
function getCustomTags($pdo, $user_id, $media_type) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM tags 
            WHERE user_id = :user_id 
            AND (media_type = :media_type OR media_type = 'universal')
            ORDER BY name ASC
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'media_type' => $media_type
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get all tags (default + custom) for a media item
 * @param PDO $pdo Database connection
 * @param int $media_id Media ID
 * @return array Array with 'default' and 'custom' keys
 */
function getMediaTags($pdo, $media_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   CASE WHEN t.is_default = 1 THEN 'default' ELSE 'custom' END as tag_type
            FROM media_tags mt
            JOIN tags t ON mt.tag_id = t.id
            WHERE mt.media_id = :media_id
            ORDER BY t.is_default DESC, t.name ASC
        ");
        $stmt->execute(['media_id' => $media_id]);
        $all_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = ['default' => [], 'custom' => []];
        foreach ($all_tags as $tag) {
            if ($tag['is_default'] == 1) {
                $result['default'][] = $tag;
            } else {
                $result['custom'][] = $tag;
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        return ['default' => [], 'custom' => []];
    }
}

/**
 * Save tags for a media item
 * @param mysqli $conn Database connection (MySQLi)
 * @param int $media_id Media ID
 * @param array $tag_ids Array of tag IDs
 */
function saveMediaTags($conn, $media_id, $tag_ids) {
    // First, delete existing tags
    $delete_stmt = $conn->prepare("DELETE FROM media_tags WHERE media_id = ?");
    $delete_stmt->bind_param("i", $media_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Then insert new tags
    if (!empty($tag_ids) && is_array($tag_ids)) {
        $insert_stmt = $conn->prepare("INSERT INTO media_tags (media_id, tag_id) VALUES (?, ?)");
        foreach ($tag_ids as $tag_id) {
            $tag_id = (int)$tag_id;
            if ($tag_id > 0) {
                $insert_stmt->bind_param("ii", $media_id, $tag_id);
                $insert_stmt->execute();
            }
        }
        $insert_stmt->close();
    }
}

/**
 * Organize tags by parent-child relationship
 * @param array $tags Array of tags
 * @return array Organized tags with children nested
 */
function organizeTags($tags) {
    $organized = [];
    $children = [];
    
    // Separate parents and children
    foreach ($tags as $tag) {
        if ($tag['parent_tag_id'] === null) {
            $organized[$tag['id']] = $tag;
            $organized[$tag['id']]['children'] = [];
        } else {
            if (!isset($children[$tag['parent_tag_id']])) {
                $children[$tag['parent_tag_id']] = [];
            }
            $children[$tag['parent_tag_id']][] = $tag;
        }
    }
    
    // Attach children to parents
    foreach ($children as $parent_id => $child_tags) {
        if (isset($organized[$parent_id])) {
            $organized[$parent_id]['children'] = $child_tags;
        }
    }
    
    return array_values($organized);
}
?>