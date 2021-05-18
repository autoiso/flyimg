<?php


namespace Core\DB;


use \PDO;

class TextDetectStorage extends ConnectionBase
{
    private $pgTableName;

    public function __construct($pgTableName)
    {
        parent::__construct();
        $this->pgTableName = $pgTableName;
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
    }

    public function getDetectedData($imgHash): ?array
    {
        $query = sprintf('select * from %s where img_hash=:hash', $this->pgTableName);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['hash' => $imgHash]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($record['detected_data']) ? json_decode($record['detected_data'],true) : null;
    }

    public function setDetectedData($imgHash, array $data, $imgUrl): void
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $query = sprintf('INSERT INTO %s (img_hash, detected_data, img_url) VALUES(:hash, :data, :url) 
            ON CONFLICT (img_hash) 
            DO UPDATE SET detected_data = :data, img_url = :url',
            $this->pgTableName);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['hash' => $imgHash, 'data' => $data, 'url' => $imgUrl]);
    }
}