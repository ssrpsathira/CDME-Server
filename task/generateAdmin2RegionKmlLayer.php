<?php

require_once dirname(__FILE__) . '/../Engine/DatabaseHandler.php';

$dbHandler = new DatabaseHandler();

$getStatisticalDataQuery = "SELECT DISTINCT(`cdme_admin_2_region_statistics`.region_id), `cdme_admin_2_region`.name, `cdme_admin_2_region_statistics`.mod, `cdme_admin_2_region_statistics`.median, `cdme_admin_2_region_statistics`.mean, `cdme_admin_2_region_statistics`.sd FROM `cdme_admin_2_region_statistics` LEFT JOIN `cdme_admin_2_region` ON `cdme_admin_2_region`.id = `cdme_admin_2_region_statistics`.region_id ORDER BY `cdme_admin_2_region_statistics`.date_time DESC";
$results = $dbHandler->executeQuery($getStatisticalDataQuery);
$kmlStyleText = '';
$placeMarkText = '';
foreach ($results as $result) {
    $regionId = $result['region_id'];
    $polygonOpacity = '55';
    $portionSize = round(240 / 120, 0, PHP_ROUND_HALF_DOWN);
    $iH = 240 - ($result['mean']) * $portionSize;
    $iS = 100;
    $iV = 100;

    $dS = $iS / 100.0; // Saturation: 0.0-1.0
    $dV = $iV / 100.0; // Lightness:  0.0-1.0
    $dC = $dV * $dS;   // Chroma:     0.0-1.0
    $dH = $iH / 60.0;  // H-Prime:    0.0-6.0
    $dT = $dH;       // Temp variable

    while ($dT >= 2.0)
        $dT -= 2.0; // php modulus does not work with float
    $dX = $dC * (1 - abs($dT - 1));     // as used in the Wikipedia link

    switch ($dH) {
        case($dH >= 0.0 && $dH < 1.0):
            $dR = $dC;
            $dG = $dX;
            $dB = 0.0;
            break;
        case($dH >= 1.0 && $dH < 2.0):
            $dR = $dX;
            $dG = $dC;
            $dB = 0.0;
            break;
        case($dH >= 2.0 && $dH < 3.0):
            $dR = 0.0;
            $dG = $dC;
            $dB = $dX;
            break;
        case($dH >= 3.0 && $dH < 4.0):
            $dR = 0.0;
            $dG = $dX;
            $dB = $dC;
            break;
        case($dH >= 4.0 && $dH < 5.0):
            $dR = $dX;
            $dG = 0.0;
            $dB = $dC;
            break;
        case($dH >= 5.0 && $dH < 6.0):
            $dR = $dC;
            $dG = 0.0;
            $dB = $dX;
            break;
        default:
            $dR = 0.0;
            $dG = 0.0;
            $dB = 0.0;
            break;
    }

    $dM = $dV - $dC;
    $dR += $dM;
    $dG += $dM;
    $dB += $dM;
    $dR *= 255;
    $dG *= 255;
    $dB *= 255;

    $R = dechex(round($dR));
    If (strlen($R) < 2)
        $R = '0' . $R;

    $G = dechex(round($dG));
    If (strlen($G) < 2)
        $G = '0' . $G;

    $B = dechex(round($dB));
    If (strlen($B) < 2)
        $B = '0' . $B;

    $polygonColor = $B . $G . $R;
    $regionName = $result['name'];

    $kmlStyleText .= str_replace(array('%region_id%', '%poligon_opacity%', '%poligon_color%'), array($regionId, $polygonOpacity, $polygonColor), file_get_contents(dirname(__FILE__) . '/../kml/templates/kml_style.txt'));

    $mean = $result['mean'];
    $median = $result['median'];
    $mod = $result['mod'];
    $sd = $result['sd'];

    $description = "<table style=\"width:100%\">
                        <tr>
                            <td>Mean</td>
                            <td>$mean</td> 
                        </tr>
                        <tr>
                            <td>Median</td>
                            <td>$median</td> 
                        </tr>
                        <tr>
                            <td>Mod</td>
                            <td>$mod</td> 
                        </tr>
                        <tr>
                            <td>Standard Deviation</td>
                            <td>$sd</td> 
                        </tr>
                    </table>";

    $polygonsText = file_get_contents(dirname(__FILE__) . '/../kml/SL/polygons/level2/region_' . $regionId . '_polygons.txt');
    $placeMarkText .= str_replace(array('%region_name%', '%description%', '%region_id%', '%polygons%'), array($regionName, $description, $regionId, $polygonsText), file_get_contents(dirname(__FILE__) . '/../kml/templates/kml_placemark.txt'));
}

$kmlText = str_replace(array('{styles}', '{placemarks}'), array($kmlStyleText, $placeMarkText), file_get_contents(dirname(__FILE__) . '/../kml/templates/kml_template.txt'));

$path = dirname(__FILE__) . '/../kml/SL/admin_2_regions.kml';
$zipArchivePath = dirname(__FILE__) . '/../kml/SL/admin_2_regions.zip';
$kmzArchivePath = dirname(__FILE__) . '/../kml/SL/admin_2_regions.kmz';

file_put_contents($path, $kmlText);

$zipArchive = new ZipArchive();
if (file_exists($zipArchivePath)) {
    unlink($zipArchivePath);
}
if ($zipArchive->open($zipArchivePath, ZIPARCHIVE::CREATE) != TRUE) {
    die("Could not open archive");
}
$zipArchive->addFile($path, 'admin_2_regions.kml');
// close and save archive
$zipArchive->close();
rename($zipArchivePath, $kmzArchivePath);