-- ============================================================
--  TaskFlow - Sistema Web de Gestión de Tareas
--  Esquema de base de datos para SQLite 3
--  (equivalente al de sql/schema.sql para MySQL)
-- ============================================================

PRAGMA foreign_keys = ON;

-- ----------------------------------------------------------------
--  Tabla de usuarios
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT      NOT NULL,
    email       TEXT      NOT NULL UNIQUE,
    password    TEXT      NOT NULL,                 -- hash con password_hash()
    created_at  TEXT      NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

-- ----------------------------------------------------------------
--  Tabla de tareas
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER   NOT NULL,
    title       TEXT      NOT NULL,
    description TEXT,
    due_date    TEXT,                               -- formato 'YYYY-MM-DD'
    status      TEXT      NOT NULL DEFAULT 'pendiente'
                          CHECK (status IN ('pendiente','en_progreso','completada')),
    created_at  TEXT      NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at  TEXT      NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_tasks_user   ON tasks (user_id);
CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks (status);

-- SQLite no tiene "ON UPDATE CURRENT_TIMESTAMP": lo emulamos con un trigger.
CREATE TRIGGER IF NOT EXISTS trg_tasks_updated_at
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    UPDATE tasks SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;
