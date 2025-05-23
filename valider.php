<?php
function getCoordinatesAndPostalCode($address, $googleApiKey) {
    $addressEncoded = urlencode($address);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$addressEncoded&key=$googleApiKey";
    $resp = json_decode(file_get_contents($url), true);

    if ($resp['status'] === 'OK') {
        $location = $resp['results'][0]['geometry']['location'];
        $postalCode = null;

        foreach ($resp['results'][0]['address_components'] as $component) {
            if (in_array('postal_code', $component['types'])) {
                $postalCode = $component['short_name'];
                break;
            }
        }

        return [$location['lat'], $location['lng'], $postalCode];
    }

    return [null, null, null];
}

function getMatchingHouses($codePostal, $dpe, $ges, $surface) {
    $surfaceMin = round($surface * 0.9, 1);
    $surfaceMax = round($surface * 1.1, 1);

    $params = [
        "size" => 1000,
        "sort" => "_rand",
        "select" => implode(",", [
            "_geopoint",
            "numero_dpe",
            "date_etablissement_dpe",
            "date_fin_validite_dpe",
            "etiquette_dpe",
            "etiquette_ges",
            "type_batiment",
            "annee_construction",
            "type_installation_chauffage",
            "type_installation_ecs",
            "surface_habitable_immeuble",
            "surface_habitable_logement",
            "adresse_ban",
            "code_postal_ban",
            "nom_commune_ban"
        ]),
        "qs" => "code_postal_ban:$codePostal AND (etiquette_dpe:$dpe) AND (etiquette_ges:$ges) AND surface_habitable_logement:[$surfaceMin TO $surfaceMax]"
    ];

    $url = "https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?" . http_build_query($params);
    $json = file_get_contents($url);
    return json_decode($json, true)['results'] ?? [];
}


$googleApiKey = getenv('GOOGLE_API_KEY');


$adresse = $_POST['adresse'] ?? '';
$surface = floatval(str_replace(',', '.', $_POST['surface'] ?? '0'));
$dpe = $_POST['dpe'] ?? '';
$ges = $_POST['ges'] ?? '';


[$lat, $lon, $codePostal] = getCoordinatesAndPostalCode($adresse, $googleApiKey);

$results = ($codePostal && $lat && $lon) ? getMatchingHouses($codePostal, $dpe, $ges, $surface) : [];

foreach ($results as &$item) {
    $s = $item['surface_habitable_logement'] ?? null;
    if ($s) {
        $score = 1 - abs($s - $surface) / $surface;
        $item['_score'] = round(max(0, $score), 3);
    } else {
        $item['_score'] = 0;
    }
}
unset($item);

usort($results, fn($a, $b) => $b['_score'] <=> $a['_score']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats croisés</title>
    <link rel="stylesheet" href="style.css">
    <style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

body {
  font-family: 'Inter', sans-serif;
  background-color: #f9f9fb;
  color: #2d3436;
  padding: 40px 20px;
  max-width: 1000px;
  margin: auto;
  line-height: 1.6;
}

h1 {
  font-size: 2rem;
  color: #192a56;
  margin-bottom: 20px;
}

h2 {
  margin-top: 40px;
  font-size: 1.5rem;
  color: #2f3640;
}

h3 {
  font-size: 1.2rem;
  color: #40739e;
  margin-top: 30px;
  margin-bottom: 10px;
}

ul {
  list-style: none;
  padding-left: 0;
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

ul li {
  margin-bottom: 10px;
  font-weight: 500;
}

strong {
  font-weight: 600;
}

iframe {
  width: 100%;
  height: 300px;
  border: none;
  border-radius: 10px;
  margin-top: 10px;
}

.map-container {
  background: white;
  padding: 20px;
  margin: 25px 0;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s ease;
}

.map-container:hover {
  transform: translateY(-2px);
}

.map-container strong {
  font-size: 1.1rem;
  color: #2d3436;
}

p {
  margin-top: 10px;
  color: #c23616;
}

@media (max-width: 768px) {
  body {
    padding: 20px 15px;
  }

  iframe {
    height: 250px;
  }
}
    </style>
</head>
<body>
    <h1>Résultats</h1>

    <h3>Infos validées :</h3>
    <ul>
        <li><strong>Adresse :</strong> <?= htmlspecialchars($adresse) ?></li>
        <li><strong>Surface :</strong> <?= htmlspecialchars($surface) ?> m²</li>
        <li><strong>DPE :</strong> <?= htmlspecialchars($dpe) ?></li>
        <li><strong>GES :</strong> <?= htmlspecialchars($ges) ?></li>
        <li><strong>Code postal géolocalisé :</strong> <?= $codePostal ?: 'Non trouvé' ?></li>
    </ul>
    <h2>Logements similaires</h2>
    <?php if (!empty($results)): ?>
        <?php foreach ($results as $logement): ?>
            <?php if (!empty($logement['_geopoint'])):
                [$l, $g] = explode(',', $logement['_geopoint']);
            ?>
                <div class="map-container">
                    <strong><?= htmlspecialchars($logement['adresse_ban'] ?? 'Adresse inconnue') ?></strong><br>
                    Surface : <?= htmlspecialchars($logement['surface_habitable_logement']) ?> m²<br>
                    DPE : <?= htmlspecialchars($logement['etiquette_dpe']) ?> — GES : <?= htmlspecialchars($logement['etiquette_ges']) ?><br>
                    Score de confiance : <?= number_format($logement['_score'] * 100, 1) ?> %<br>
                    <iframe src="https://www.google.com/maps?q=<?= $l ?>,<?= $g ?>&t=k&output=embed"></iframe>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucun logement similaire trouvé dans les données ADEME.</p>
    <?php endif; ?>

    <footer style="
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background: #f1f2f6;
      color: #636e72;
      text-align: center;
      padding: 10px;
      font-size: 0.9em;
      border-top: 1px solid #dcdde1;
    ">
        &copy; <?= date("Y") ?> chrldb. All rights reserved.
    </footer>
</body>
</html>