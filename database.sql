SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS prestations_db;
SET FOREIGN_KEY_CHECKS = 1;

CREATE DATABASE prestations_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE prestations_db;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table des sections (massages, soins, maquillage, etc.)
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    meta_description VARCHAR(160),
    image VARCHAR(255),
    ordre_affichage INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des prestations
CREATE TABLE prestations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    ordre_affichage INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des tarifs
CREATE TABLE tarifs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prestation_id INT NOT NULL,
    duree VARCHAR(50),        -- "10 min", "1h", "1h30"
    nb_seances VARCHAR(50),   -- "1 séance", "5 séances" (optionnel)
    prix DECIMAL(6,2) NOT NULL,
    ordre_affichage INT DEFAULT 0,
    FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creneaux (
  id INT PRIMARY KEY AUTO_INCREMENT,
  start_at DATETIME NOT NULL,
  end_at   DATETIME NOT NULL,
  dispo TINYINT(1) NOT NULL DEFAULT 1,
  note VARCHAR(255) NULL,
  UNIQUE KEY uq_creneau (start_at, end_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  creneau_id INT NOT NULL,
  prestation_id INT NOT NULL,
  tarif_id INT NULL,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  telephone VARCHAR(40) NULL,
  commentaire TEXT NULL,
  statut ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_r_creneau FOREIGN KEY (creneau_id) REFERENCES creneaux(id) ON DELETE RESTRICT,
  CONSTRAINT fk_r_presta  FOREIGN KEY (prestation_id) REFERENCES prestations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_r_tarif   FOREIGN KEY (tarif_id) REFERENCES tarifs(id) ON DELETE SET NULL,
  UNIQUE KEY uq_booking (creneau_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin par défaut (email: admin@example.com / mdp: Admin123!)
INSERT INTO admins (email, password_hash)
VALUES ('admin@example.com', '$2y$12$LkdUeRiuzVGBbqcTrPVZuu.G7RxXLV7qXQGEJQrKyElAaK7zFfmrm')
ON DUPLICATE KEY UPDATE email=email;
-- (hash = password_hash('Admin123!', PASSWORD_DEFAULT) : change-le ensuite)

INSERT INTO sections (nom, slug, description, meta_description, ordre_affichage) VALUES
('Massages', 'massages', 
'Offrez à votre corps un moment de détente absolue grâce à des massages adaptés à vos besoins : relaxants, tonifiants ou énergétiques, pour libérer les tensions et retrouver harmonie et vitalité.', 'Découvrez nos massages bien-être et thérapeutiques : massage crânien, massage des pieds, prénatal et postnatal.', 1),
('Soins du Visage', 'soins-visage', 'Des soins personnalisés pour révéler la beauté naturelle de votre peau avec des techniques douces et des produits de qualité.', 'Soins du visage sur mesure : nettoyage, hydratation, anti-âge. Redonnez éclat et jeunesse à votre peau.', 2),
('Soins du Corps', 'soins-corps', 'Des soins personnalisés pour révéler la beauté naturelle de votre peau avec des techniques douces et des produits de qualité.', 'Soins du visage sur mesure : nettoyage, hydratation, anti-âge. Redonnez éclat et jeunesse à votre peau.', 3 
),
('Maquillage', 'maquillage', 'Sublimez votre beauté avec nos prestations maquillage pour tous vos événements spéciaux.', 'Services de maquillage professionnel : mariée, soirée, photo. Révélez votre beauté naturelle.', 4),
('Épilation', 'epilation', 'Épilation douce et efficace pour une peau lisse et soyeuse avec des techniques respectueuses de votre peau.', 'Épilation à la cire et au sucre. Techniques douces pour tous types de peau.', 5);

INSERT INTO prestations (section_id, nom, description, ordre_affichage) VALUES
-- Massages (section_id = 1)
(1, 'Massage Crânien', 'Combat la chute de cheveux et stimule la repousse. Soulage les tensions musculaires et maux de tête. Favorise la libération des toxines et réduit le stress.', 1),
(1, 'Massage Pieds', 'Massage relaxant des pieds qui stimule la circulation sanguine, soulage les tensions et procure une détente profonde.', 2),
(1, 'Prénatal ou Postnatal', 'Massage spécialement adapté aux femmes enceintes et jeunes mamans. Soulage les tensions du dos et améliore la circulation.', 3),
(1, 'Massage Californien', 'Massage global du corps aux mouvements fluides et enveloppants. Favorise la détente profonde et libère les tensions.', 4),

-- Soins du Visage (section_id = 2)
(2, 'Soin Hydratant', 'Soin en profondeur pour restaurer l\'hydratation naturelle de votre peau et lui redonner souplesse et éclat.', 1),
(2, 'Soin Anti-âge', 'Traitement ciblé pour atténuer les signes de l\'âge et stimuler le renouvellement cellulaire.', 2),
(2, 'Nettoyage de Peau', 'Purification en profondeur pour éliminer impuretés et points noirs, laissant la peau nette et fraîche.', 3),
-- Soins du Corps (section_id = 3)
(3, 'Gommage Corps Complet', 'Exfoliation au sucre et huiles végétales pour une peau douce et lisse.', 1),
(3, 'Enveloppement Minceur', 'Enveloppement chaud aux algues et caféine pour stimuler la microcirculation.', 2),
(3, 'Soin Dos Purifiant', 'Nettoyage et soin ciblé pour le dos (gommage + extraction + masque).', 3),

(4, 'Maquillage Jour', 'Teint léger, correction et rehaussement naturel du regard.', 1),
(4, 'Maquillage Soirée', 'Teint travaillé + yeux intenses (smoky/liner) ou lèvres glamour.', 2),
(4, 'Essai Maquillage Mariée', 'Séance d’essai complète (palette, tenue, planning jour J).', 3),
(4, 'Maquillage Mariée (Jour J)', 'Maquillage longue tenue + retouches planifiées.', 4),

(5, 'Sourcils', 'Restructuration et épilation des sourcils.', 1),
(5, 'Lèvre', 'Épilation douce de la lèvre supérieure.', 2),
(5, 'Aisselles', 'Épilation à la cire, peau nette et douce.', 3),
(5, 'Demi-Jambes', 'Épilation bas des jambes.', 4),
(5, 'Jambes Complètes', 'Épilation jambes complètes.', 5),
(5, 'Maillot Classique', 'Épilation maillot bordure.', 6),
(5, 'Maillot Intégral', 'Épilation maillot intégral.', 7);

INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage) VALUES
-- Massage Crânien
(1, '10 min', 10.00, 1),
(1, '20 min', 20.00, 2),
(1, '30 min', 35.00, 3),
-- Massage Pieds
(2, '20 min', 20.00, 1),
(2, '30 min', 30.00, 2),
(2, '45 min', 45.00, 3),
-- Prénatal/Postnatal
(3, '1h', 60.00, 1),
(3, '1h30', 80.00, 2),
-- Massage Californien
(4, '1h', 60.00, 1),
(4, '1h30', 80.00, 2),
-- Soins du Visage
(5, '45 min', 50.00, 1),
(5, '1h', 65.00, 2),
(6, '1h', 70.00, 1),
(6, '1h15', 85.00, 2),
(7, '30 min', 35.00, 1),
(7, '45 min', 45.00, 2);


INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage) VALUES
-- Gommage Corps Complet (id auto) : suppose IDs 8/9/10 si suite de tes inserts
(LAST_INSERT_ID(), '45 min', 45.00, 1);

-- Récupérer l'ID d'Enveloppement Minceur (ID = (SELECT id FROM prestations WHERE nom='Enveloppement Minceur' LIMIT 1))
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '1h', 60.00, 1 FROM prestations p WHERE p.nom='Enveloppement Minceur' LIMIT 1;

-- Soin Dos Purifiant
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '40 min', 42.00, 1 FROM prestations p WHERE p.nom='Soin Dos Purifiant' LIMIT 1;


INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '45 min', 35.00, 1 FROM prestations p WHERE p.nom='Maquillage Jour' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '1h', 50.00, 1 FROM prestations p WHERE p.nom='Maquillage Soirée' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '1h', 45.00, 1 FROM prestations p WHERE p.nom='Essai Maquillage Mariée' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '1h15', 70.00, 1 FROM prestations p WHERE p.nom='Maquillage Mariée (Jour J)' LIMIT 1;

INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '15 min', 10.00, 1 FROM prestations p WHERE p.nom='Sourcils' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '10 min', 8.00, 1 FROM prestations p WHERE p.nom='Lèvre' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '15 min', 12.00, 1 FROM prestations p WHERE p.nom='Aisselles' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '20 min', 18.00, 1 FROM prestations p WHERE p.nom='Demi-Jambes' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '30 min', 25.00, 1 FROM prestations p WHERE p.nom='Jambes Complètes' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '20 min', 16.00, 1 FROM prestations p WHERE p.nom='Maillot Classique' LIMIT 1;
INSERT INTO tarifs (prestation_id, duree, prix, ordre_affichage)
SELECT p.id, '30 min', 30.00, 1 FROM prestations p WHERE p.nom='Maillot Intégral' LIMIT 1;