CREATE DATABASE IF NOT EXISTS comune_palermo;
USE comune_palermo;

CREATE TABLE IF NOT EXISTS strade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_strada VARCHAR(50),
    toponimo VARCHAR(255),
    cap VARCHAR(10),
    quartiere VARCHAR(100),
    circoscrizione VARCHAR(50),
    indirizzo_completo VARCHAR(255),
    INDEX (indirizzo_completo)
);

CREATE TABLE IF NOT EXISTS cittadini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    codice_fiscale VARCHAR(16) NOT NULL UNIQUE,
    data_nascita DATE NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    token_verifica VARCHAR(64) NULL,
    verificato TINYINT(1) DEFAULT 0,
    data_registrazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS segnalazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codice_pratica VARCHAR(64) NOT NULL UNIQUE,
    cittadino_id INT NOT NULL,
    strada_id INT DEFAULT NULL,
    categoria ENUM('Buca stradale', 'Illuminazione', 'Rifiuti', 'Altro') NOT NULL,
    descrizione TEXT NOT NULL,
    stato ENUM('In attesa', 'Presa in carico', 'Risolto') DEFAULT 'In attesa',
    data_inserimento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cittadino_id) REFERENCES cittadini(id) ON DELETE CASCADE,
    FOREIGN KEY (strada_id) REFERENCES strade(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS dipendenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricola VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS upvotes_segnalazioni (
    segnalazione_id INT NOT NULL,
    cittadino_id INT NOT NULL,
    PRIMARY KEY (segnalazione_id, cittadino_id),
    FOREIGN KEY (segnalazione_id) REFERENCES segnalazioni(id) ON DELETE CASCADE,
    FOREIGN KEY (cittadino_id) REFERENCES cittadini(id) ON DELETE CASCADE
);

-- Dipendente admin di prova (matricola: admin, password: admin)
-- L'hash Bcrypt generato da password_hash() per la stringa 'admin'
INSERT INTO dipendenti (matricola, password_hash) 
VALUES ('admin', '$2y$12$a8tanN/6RyXDjgIdllBqSeLpyj0b3EsfglxTx1Q6asYKXz9bZAV.6');