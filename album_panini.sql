CREATE DATABASE IF NOT EXISTS album_panini
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE album_panini;

-- =========================================
-- TABLA USUARIOS
-- =========================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- =========================================
-- TABLA PAISES
-- =========================================

CREATE TABLE paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(5) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    grupo CHAR(1),
    pagina INT
);

-- =========================================
-- TABLA FIGURITAS
-- =========================================

CREATE TABLE figuritas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pais_codigo VARCHAR(5) NOT NULL,
    numero INT NOT NULL,
    nombre VARCHAR(150),
    tipo ENUM('escudo', 'jugador', 'equipo', 'general') NOT NULL,

    UNIQUE (pais_codigo, numero)
);

-- =========================================
-- TABLA COLECCION
-- =========================================

CREATE TABLE coleccion (
    id INT AUTO_INCREMENT PRIMARY KEY,

    usuario_id INT NOT NULL,
    figurita_id INT NOT NULL,

    imagen VARCHAR(255),

    cantidad INT DEFAULT 1,

    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE,

    FOREIGN KEY (figurita_id)
        REFERENCES figuritas(id)
        ON DELETE CASCADE
);