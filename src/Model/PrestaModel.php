<?php

namespace App\Model;

use PDO;
use App\Model\AbstractModel;

class PrestaModel extends AbstractModel
{

  public function listSections(): array
  {
    $stmt = $this->pdo->query("SELECT * FROM sections ORDER BY ordre_affichage, nom");
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }


  public function getAllSections()
  {
    $query = "SELECT * FROM sections WHERE actif = 1 ORDER BY ordre_affichage ASC";
    $stmt = $this->pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getSectionBySlug($slug)
  {
    $query = "SELECT * FROM sections WHERE slug = :slug AND actif = 1";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':slug', $slug);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getPrestationsBySection($sectionId)
  {
    $query = "SELECT p.*, s.nom as section_nom
                  FROM prestations p 
                  JOIN sections s ON p.section_id = s.id
                  WHERE p.section_id = :section_id AND p.actif = 1 
                  ORDER BY p.ordre_affichage ASC";

    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':section_id', $sectionId);
    $stmt->execute();
    $prestations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($prestations as &$prestation) {
      $prestation['tarifs'] = $this->getTarifsByPrestation($prestation['id']);
    }

    return $prestations;
  }


  public function getTarifsByPrestation($prestationId)
  {
    $query = "SELECT * FROM tarifs WHERE prestation_id = :prestation_id ORDER BY ordre_affichage ASC";
    $stmt = $this->pdo->prepare($query);
    $stmt->bindParam(':prestation_id', $prestationId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Formater le prix pour view
  public function formatTarif($tarif)
  {
    $result = '';
    if (!empty($tarif['duree'])) {
      $result .= $tarif['duree'];
    }
    if (!empty($tarif['nb_seances'])) {
      $result .= (!empty($result) ? ' - ' : '') . $tarif['nb_seances'];
    }
    $result .= '/' . number_format($tarif['prix'], 0, ',', ' ') . '€';
    return $result;
  }

  public function sectionExists($slug)
  {
    $section = $this->getSectionBySlug($slug);
    return $section !== false;
  }

  /// FONCTIONS ADMINS ///

  public function createSection($data)
  {
    $query = "INSERT INTO sections (nom, slug, description, meta_description, ordre_affichage) 
                  VALUES (:nom, :slug, :description, :meta_description, :ordre_affichage)";
    $stmt = $this->pdo->prepare($query);
    return $stmt->execute([
      ':nom' => $data['nom'],
      ':slug' => $data['slug'],
      ':description' => $data['description'],
      ':meta_description' => $data['meta_description'],
      ':ordre_affichage' => $data['ordre_affichage'] ?? 0
    ]);
  }

  public function createPrestation($data)
  {
    $query = "INSERT INTO prestations (section_id, nom, description, ordre_affichage) 
                  VALUES (:section_id, :nom, :description, :ordre_affichage)";
    $stmt = $this->pdo->prepare($query);
    return $stmt->execute([
      ':section_id' => $data['section_id'],
      ':nom' => $data['nom'],
      ':description' => $data['description'],
      ':ordre_affichage' => $data['ordre_affichage'] ?? 0
    ]);
  }

  public function createTarif($data)
  {
    $query = "INSERT INTO tarifs (prestation_id, duree, nb_seances, prix, ordre_affichage) 
                  VALUES (:prestation_id, :duree, :nb_seances, :prix, :ordre_affichage)";
    $stmt = $this->pdo->prepare($query);
    return $stmt->execute([
      ':prestation_id' => $data['prestation_id'],
      ':duree' => $data['duree'],
      ':nb_seances' => $data['nb_seances'],
      ':prix' => $data['prix'],
      ':ordre_affichage' => $data['ordre_affichage'] ?? 0
    ]);
  }

  public function getCatalog(): array
  {
    $sql = "
      SELECT
        s.id   AS section_id,
        s.nom  AS section_nom,
        s.slug AS section_slug,
        s.description AS section_description,
        s.ordre_affichage AS section_ordre,

        p.id   AS prestation_id,
        p.nom  AS prestation_nom,
        p.description AS prestation_description,
        p.ordre_affichage AS prestation_ordre,

        t.id   AS tarif_id,
        t.duree,
        t.nb_seances,
        t.prix,
        t.ordre_affichage AS tarif_ordre
      FROM sections s
      LEFT JOIN prestations p ON p.section_id = s.id AND p.actif = 1
      LEFT JOIN tarifs t      ON t.prestation_id = p.id
      WHERE s.actif = 1
      ORDER BY
        s.ordre_affichage,
        p.ordre_affichage,
        COALESCE(t.ordre_affichage, 999), t.id
    ";

    $rows = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    // Transformer en structure hiérarchique : sections -> prestations -> tarifs
    $sections = [];   // tableau final ordonné
    $secIndex = [];   // index temporaire par section_id pour éviter les recherches O(n)

    foreach ($rows as $r) {
      $sid = $r['section_id'];
      if (!isset($secIndex[$sid])) {
        $secIndex[$sid] = count($sections);
        $sections[] = [
          'id'          => $sid,
          'nom'         => $r['section_nom'],
          'slug'        => $r['section_slug'],
          'description' => $r['section_description'],
          'prestations' => [],
        ];
      }
      $sIdx = $secIndex[$sid];

      // Si pas de prestation (LEFT JOIN), on passe
      if (!$r['prestation_id']) {
        continue;
      }

      // Indexer prestations par id pour y pousser les tarifs
      if (!isset($sections[$sIdx]['_pIndex'])) {
        $sections[$sIdx]['_pIndex'] = [];
      }
      $pid = $r['prestation_id'];
      if (!isset($sections[$sIdx]['_pIndex'][$pid])) {
        $sections[$sIdx]['_pIndex'][$pid] = count($sections[$sIdx]['prestations']);
        $sections[$sIdx]['prestations'][] = [
          'id'          => $pid,
          'nom'         => $r['prestation_nom'],
          'description' => $r['prestation_description'],
          'tarifs'      => [],
        ];
      }
      $pIdx = $sections[$sIdx]['_pIndex'][$pid];

      // Ajouter le tarif si présent
      if ($r['tarif_id']) {
        $sections[$sIdx]['prestations'][$pIdx]['tarifs'][] = [
          'id'          => $r['tarif_id'],
          'duree'       => $r['duree'],
          'nb_seances'  => $r['nb_seances'],
          'prix'        => $r['prix'],
        ];
      }
    }

    // On enleve les index internes
    foreach ($sections as &$sec) {
      unset($sec['_pIndex']);
    }

    return $sections; // tableau de sections ordonné, chacune avec prestations[] -> tarifs[]
  }

  public function findSection(int $id): ?array
  {
    $st = $this->pdo->prepare("SELECT * FROM sections WHERE id=?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }
  public function updateSection(int $id, array $data): bool
  {
    $sql = "UPDATE sections SET nom=:nom, slug=:slug, description=:description, meta_description=:meta, ordre_affichage=:ord, actif=:actif WHERE id=:id";
    $st = $this->pdo->prepare($sql);
    return $st->execute([
      ':nom' => $data['nom'],
      ':slug' => $data['slug'],
      ':description' => $data['description'],
      ':meta' => $data['meta_description'],
      ':ord' => $data['ordre_affichage'] ?? 0,
      ':actif' => $data['actif'] ?? 1,
      ':id' => $id
    ]);
  }
  public function deleteSection(int $id): bool
  {
    $st = $this->pdo->prepare("DELETE FROM sections WHERE id=?");
    return $st->execute([$id]);
  }

  /// PRESTATIONS ///
  public function listPrestations(?int $sectionId = null): array
  {
    if ($sectionId) {
      $st = $this->pdo->prepare("SELECT * FROM prestations WHERE section_id=? ORDER BY ordre_affichage, nom");
      $st->execute([$sectionId]);
      return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    return $this->pdo->query("SELECT * FROM prestations ORDER BY section_id, ordre_affichage, nom")->fetchAll(PDO::FETCH_ASSOC);
  }
  public function findPrestation(int $id): ?array
  {
    $st = $this->pdo->prepare("SELECT * FROM prestations WHERE id=?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }
  public function updatePrestation(int $id, array $data): bool
  {
    $sql = "UPDATE prestations SET section_id=:section_id, nom=:nom, description=:description, ordre_affichage=:ord, actif=:actif WHERE id=:id";
    $st = $this->pdo->prepare($sql);
    return $st->execute([
      ':section_id' => $data['section_id'],
      ':nom' => $data['nom'],
      ':description' => $data['description'],
      ':ord' => $data['ordre_affichage'] ?? 0,
      ':actif' => $data['actif'] ?? 1,
      ':id' => $id
    ]);
  }
  public function deletePrestation(int $id): bool
  {
    $st = $this->pdo->prepare("DELETE FROM prestations WHERE id=?");
    return $st->execute([$id]);
  }

  /// TARIFS ///
  public function listTarifs(int $prestationId): array
  {
    $st = $this->pdo->prepare("SELECT * FROM tarifs WHERE prestation_id=? ORDER BY ordre_affichage, id");
    $st->execute([$prestationId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
  public function findTarif(int $id): ?array
  {
    $st = $this->pdo->prepare("SELECT * FROM tarifs WHERE id=?");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
  }
  public function updateTarif(int $id, array $data): bool
  {
    $sql = "UPDATE tarifs SET duree=:duree, nb_seances=:nb, prix=:prix, ordre_affichage=:ord WHERE id=:id";
    $st = $this->pdo->prepare($sql);
    return $st->execute([
      ':duree' => $data['duree'],
      ':nb' => $data['nb_seances'],
      ':prix' => $data['prix'],
      ':ord' => $data['ordre_affichage'] ?? 0,
      ':id' => $id
    ]);
  }
  public function deleteTarif(int $id): bool
  {
    $st = $this->pdo->prepare("DELETE FROM tarifs WHERE id=?");
    return $st->execute([$id]);
  }

  public function getAdminTree(): array
{
    $sql = "
      SELECT
        s.id   AS section_id,
        s.nom  AS section_nom,
        s.slug AS section_slug,
        s.description        AS section_description,
        s.meta_description   AS section_meta,
        s.ordre_affichage    AS section_ordre,
        s.actif              AS section_actif,

        p.id   AS prestation_id,
        p.section_id         AS prestation_section_id,
        p.nom                AS prestation_nom,
        p.description        AS prestation_description,
        p.ordre_affichage    AS prestation_ordre,
        p.actif              AS prestation_actif,

        t.id   AS tarif_id,
        t.prestation_id      AS tarif_prestation_id,
        t.duree,
        t.nb_seances,
        t.prix,
        t.ordre_affichage    AS tarif_ordre

      FROM sections s
      LEFT JOIN prestations p ON p.section_id = s.id
      LEFT JOIN tarifs t      ON t.prestation_id = p.id
      ORDER BY
        s.ordre_affichage, s.id,
        p.ordre_affichage, p.id,
        COALESCE(t.ordre_affichage, 999), t.id
    ";

    $rows = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    $tree   = [];
    $sIndex = [];

    foreach ($rows as $r) {
        $sid = (int)$r['section_id'];
        if (!isset($sIndex[$sid])) {
            $sIndex[$sid] = count($tree);
            $tree[] = [
                'id'               => $sid,
                'nom'              => $r['section_nom'],
                'slug'             => $r['section_slug'],
                'description'      => $r['section_description'],
                'meta_description' => $r['section_meta'],
                'ordre_affichage'  => (int)$r['section_ordre'],  
                'actif'            => (int)$r['section_actif'],
                'prestations'      => [],
                '_pIndex'          => [],
            ];
        }
        $si = $sIndex[$sid];

        if (!empty($r['prestation_id'])) {
            $pid = (int)$r['prestation_id'];
            if (!isset($tree[$si]['_pIndex'][$pid])) {
                $tree[$si]['_pIndex'][$pid] = count($tree[$si]['prestations']);
                $tree[$si]['prestations'][] = [
                    'id'               => $pid,
                    'section_id'       => (int)$r['prestation_section_id'],
                    'nom'              => $r['prestation_nom'],
                    'description'      => $r['prestation_description'],
                    'ordre_affichage'  => (int)$r['prestation_ordre'],
                    'actif'            => (int)$r['prestation_actif'],
                    'tarifs'           => [],
                ];
            }
            $pi = $tree[$si]['_pIndex'][$pid];

            if (!empty($r['tarif_id'])) {
                $tree[$si]['prestations'][$pi]['tarifs'][] = [
                    'id'               => (int)$r['tarif_id'],
                    'prestation_id'    => (int)$r['tarif_prestation_id'],
                    'duree'            => $r['duree'],
                    'nb_seances'       => $r['nb_seances'],
                    'prix'             => $r['prix'],
                    'ordre_affichage'  => (int)$r['tarif_ordre'],
                ];
            }
        }
    }

    // nettoyage index interne
    foreach ($tree as &$sec) unset($sec['_pIndex']);

    return $tree;
}

}
