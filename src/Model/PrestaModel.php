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
  

}
