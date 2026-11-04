<?php
// =========================================================
//  Sesiones guardadas en MySQL
//  En Vercel cada visita puede caer en un servidor distinto y
//  los archivos de sesión se pierden. Guardándolas en la base
//  de datos, el login y el CSRF funcionan siempre.
// =========================================================
class SesionesBD implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo) {}

    private function crearTabla(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS sesiones (
            id          VARCHAR(128) NOT NULL PRIMARY KEY,
            datos       MEDIUMTEXT NOT NULL,
            actualizado INT UNSIGNED NOT NULL,
            INDEX idx_actualizado (actualizado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function open(string $ruta, string $nombre): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        try {
            $st = $this->pdo->prepare("SELECT datos FROM sesiones WHERE id = ?");
            $st->execute([$id]);
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '42S02') throw $ex;   // la tabla todavía no existe
            $this->crearTabla();
            return '';
        }
        return (string)($st->fetchColumn() ?: '');
    }

    public function write(string $id, string $datos): bool
    {
        $this->pdo->prepare("INSERT INTO sesiones (id, datos, actualizado) VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE datos = VALUES(datos), actualizado = VALUES(actualizado)")
                  ->execute([$id, $datos, time()]);
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->pdo->prepare("DELETE FROM sesiones WHERE id = ?")->execute([$id]);
        return true;
    }

    public function gc(int $vida): int|false
    {
        $st = $this->pdo->prepare("DELETE FROM sesiones WHERE actualizado < ?");
        $st->execute([time() - $vida]);
        return $st->rowCount();
    }
}
