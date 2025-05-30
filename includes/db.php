<?php
class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die('Veritabanı bağlantı hatası: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset('utf8mb4');
    }
    
    // Genel sorgu çalıştırma
    public function query($sql) {
        $result = $this->connection->query($sql);
        
        if (!$result) {
            die('Sorgu hatası: ' . $this->connection->error);
        }
        
        return $result;
    }
    
    // SELECT için güvenli sorgu
    public function select($sql, $params = []) {
        $stmt = $this->prepare($sql);
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                
                $bindParams[] = $param;
            }
            
            array_unshift($bindParams, $types);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }
    
    // Tek satır veri alma
    public function getRow($sql, $params = []) {
        $result = $this->select($sql, $params);
        $row = $result->fetch_assoc();
        $result->free();
        
        return $row;
    }
    
    // Tüm sonuçları alma
    public function getAll($sql, $params = []) {
        $result = $this->select($sql, $params);
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $result->free();
        
        return $rows;
    }
    
    // Hazırlanmış sorgu oluşturma
    public function prepare($sql) {
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            die('Hazırlık hatası: ' . $this->connection->error);
        }
        
        return $stmt;
    }
    
    // INSERT için
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->prepare($sql);
        
        $types = '';
        $values = [];
        
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            
            $values[] = $value;
        }
        
        array_unshift($values, $types);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($values));
        
        $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    // UPDATE için
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        
        foreach (array_keys($data) as $column) {
            $set[] = "$column = ?";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
        $stmt = $this->prepare($sql);
        
        $types = '';
        $values = array_values($data);
        
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        foreach ($whereParams as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            
            $values[] = $param;
        }
        
        array_unshift($values, $types);
        call_user_func_array([$stmt, 'bind_param'], $this->refValues($values));
        
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    // DELETE için
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->prepare($sql);
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                
                $bindParams[] = $param;
            }
            
            array_unshift($bindParams, $types);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        }
        
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    // Son eklenen ID'yi alma
    public function getLastId() {
        return $this->connection->insert_id;
    }
    
    // Karakter dizisini güvenli hale getirme
    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    // Bağlantıyı kapatma
    public function close() {
        $this->connection->close();
    }
    
    // Referans değeri için yardımcı fonksiyon
    private function refValues($arr) {
        $refs = [];
        
        foreach($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        
        return $refs;
    }
}

// Veritabanı bağlantısını başlat
$db = new Database();
?>