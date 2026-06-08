-- ============================================================
--  TaskFlow - Sistema Web de Gestión de Tareas
--  Esquema de base de datos (MySQL 5.7+ / MariaDB 10.2+)
-- ============================================================

CREATE DATABASE IF NOT EXISTS taskflow
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE taskflow;

-- ----------------------------------------------------------------
--  Tabla de usuarios
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL,
    password    VARCHAR(255)  NOT NULL,           -- hash con password_hash()
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  Tabla de tareas
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150)  NOT NULL,
    description TEXT          NULL,
    due_date    DATE          NULL,
    status      ENUM('pendiente','en_progreso','completada')
                              NOT NULL DEFAULT 'pendiente',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tasks_user   (user_id),
    KEY idx_tasks_status (status),
    CONSTRAINT fk_tasks_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
--  NOTA: No se crea un usuario inicial aquí porque la contraseña
--  debe cifrarse con password_hash() de PHP. Crea tu primera cuenta
--  desde la pantalla de registro (auth/register.php), o ejecuta una
--  vez el script de ayuda  setup.php  desde el navegador.
-- ----------------------------------------------------------------
