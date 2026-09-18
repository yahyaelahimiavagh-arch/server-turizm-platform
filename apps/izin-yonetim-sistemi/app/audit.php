<?php

declare(strict_types=1);

function audit_log_event(
    PDO $pdo,
    ?int $actorUserId,
    string $eventType,
    string $entityType,
    string|int $entityId,
    array $metadata = []
): void {
    $eventType = trim($eventType);
    $entityType = trim($entityType);
    $entityId = trim((string) $entityId);

    if ($eventType === '' || $entityType === '' || $entityId === '') {
        throw new InvalidArgumentException('Audit event identity cannot be empty.');
    }

    $metadataJson = $metadata === []
        ? null
        : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $stmt = $pdo->prepare(
        'INSERT INTO audit_log
         (actor_user_id, event_type, entity_type, entity_id, metadata_json)
         VALUES
         (:actor_user_id, :event_type, :entity_type, :entity_id, :metadata_json)'
    );
    $stmt->execute([
        'actor_user_id' => $actorUserId,
        'event_type' => mb_substr($eventType, 0, 80),
        'entity_type' => mb_substr($entityType, 0, 80),
        'entity_id' => mb_substr($entityId, 0, 191),
        'metadata_json' => $metadataJson,
    ]);
}

function audit_events_for_entity(
    PDO $pdo,
    string $entityType,
    string|int $entityId,
    int $limit = 100
): array {
    $limit = max(1, min(500, $limit));

    $stmt = $pdo->prepare(
        "SELECT al.id, al.actor_user_id, al.event_type, al.entity_type, al.entity_id,
                al.metadata_json, al.created_at, u.full_name AS actor_name
         FROM audit_log al
         LEFT JOIN users u ON u.id = al.actor_user_id
         WHERE al.entity_type = :entity_type
           AND al.entity_id = :entity_id
         ORDER BY al.id DESC
         LIMIT {$limit}"
    );
    $stmt->execute([
        'entity_type' => $entityType,
        'entity_id' => (string) $entityId,
    ]);

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $decoded = [];
        if (!empty($row['metadata_json'])) {
            $candidate = json_decode((string) $row['metadata_json'], true);
            if (is_array($candidate)) {
                $decoded = $candidate;
            }
        }
        $row['metadata'] = $decoded;
        unset($row['metadata_json']);
        $rows[] = $row;
    }

    return $rows;
}
