<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$largura = filter_input(INPUT_GET, 'largura', FILTER_VALIDATE_INT, array('options' => array('default' => 1200, 'min_range' => 720, 'max_range' => 3840)));
$altura = filter_input(INPUT_GET, 'altura', FILTER_VALIDATE_INT, array('options' => array('default' => 566, 'min_range' => 480, 'max_range' => 2160)));
$projecao = filter_input(INPUT_GET, 'projecao', FILTER_VALIDATE_REGEXP, array('options' => array('default' => 'k', 'regexp' => '/[ekmnNprs]/')));

echo montarPagina($largura, $altura, $projecao);

function montarPagina(int $largura, int $altura, string $projecao): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $largura.'" height="' . $altura . '">' . PHP_EOL;
    $svg .= '<rect x="0" y="0" width="' . $largura . '" height="' . $altura . '" fill="none" stroke="rgb(150,150,150)" stroke-width="1" />' . PHP_EOL;
    $svg .= exibirMundo($largura, $altura, $projecao);
    $svg .= exibirCidades($largura, $altura, $projecao);
    $svg .= exibirGrade($largura, $altura, $projecao);
    $svg .= '</svg>' . PHP_EOL;

    $html = '<!DOCTYPE html>' . PHP_EOL . '<html>' . PHP_EOL . '<body>' . PHP_EOL;
    $html .= $svg;
    $html .= '</body>' . PHP_EOL . '</html>';
    
    return $html;
}

function exibirCidades(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    
    $dsn = 'mysql:host=localhost;dbname=world;charset=utf8';
    $user = 'usuario';
    $pass = 'senha';
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "SELECT city, lat, lng, country, id FROM worldcities
              WHERE lat IS NOT NULL AND lng IS NOT NULL AND capital = 'primary'
              ORDER BY population";
    $result = $pdo->query($query);
    
    if ($result->rowCount() >= 1) {
        $svg .= '<g fill="rgb(33,42,116)" stroke-width="0" onmouseover="evt.target.setAttribute(\'fill\', \'blue\');" onmouseout="evt.target.setAttribute(\'fill\',\'red\');">' . PHP_EOL;
        while ($cidade = $result->fetch(PDO::FETCH_ASSOC)) {
            $latitude = $cidade['lat'];
            $longitude = $cidade['lng'];
            $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
            $titulo = $cidade['city'] . ' (' . $cidade['country'] . ')';
            $svg .= '<circle id="'.$cidade['id'].'" cx="' . $ponto['x'] . '" cy="' . $ponto['y'] . '" r="3"><title>' . $titulo . '</title></circle>' . PHP_EOL;
        }
        $svg .= '</g>' . PHP_EOL;
    }
    
    return $svg;
}

function converterGeoPixel(float $latitude, float $longitude, int $largura, int $altura, string $projecao): array
{
    $centro = coordenarCentro($largura, $altura);
    
    switch ($projecao) {
        case 'e': // equirectangular projection // plate carrée
            if ($largura / $altura < 2) {
                $modulo = $largura / 360;
            } else {
                $modulo = $altura / 180;
            }
            $x = floor($centro['x'] + ($longitude * $modulo));
            $y = floor($centro['y'] - ($latitude * $modulo));
            break;
            
        case 'k': // Kavrayskiy VII projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularKavrayskiyVIIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularKavrayskiyVIIY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularKavrayskiyVIIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularKavrayskiyVIIY($latitude) * $modulo));
            break;
            
        case 'm': // Mercator projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularMercatorX(180) * 2 * 1.4844222297453322);
            } else {
                $modulo = $altura / (calcularMercatorY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularMercatorX($longitude) * $modulo));
            $y = floor($centro['y'] - (calcularMercatorY($latitude) * $modulo));
            break;
            
        case 'n': // Natural Earth projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularNaturalEarthX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularNaturalEarthY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularNaturalEarthX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularNaturalEarthY($latitude) * $modulo));
            break;
            
        case 'N': // Natural Earth II projection (fix needed)
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularNaturalEarthIIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularNaturalEarthIIY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularNaturalEarthIIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularNaturalEarthIIY($latitude) * $modulo));
            break;
            
        case 'p': // Patterson projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularPattersonX(180) * 2);
            } else {
                $modulo = $altura / (calcularPattersonY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularPattersonX($longitude) * $modulo));
            $y = floor($centro['y'] - (calcularPattersonY($latitude) * $modulo));
            break;
            
        case 'r': // Robinson projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularRobinsonX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularRobinsonY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularRobinsonX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularRobinsonY($latitude) * $modulo));
            break;
            
        case 's': // sinusoidal projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularSinusoidalX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularSinusoidalY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularSinusoidalX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularSinusoidalY($latitude) * $modulo));
            break;
    }
    /*
    if ($largura / $altura < 2) { $modulo = $largura / 360; }
    else { $modulo = $altura / 180; }
    $x = $longitude * abs((abs($latitude) / 90) - 1); // losango
    $x = $longitude * abs(((abs($latitude) - abs($latitude) * 50 / 100) / 90) - 1); // trapézio
    $y = $latitude;
    $x = floor($centro['x'] + ($x * $modulo));
    $y = floor($centro['y'] - ($y * $modulo));
    */
    
    return array('x' => $x, 'y' => $y);
}

function calcularKavrayskiyVIIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return (3 * $longitude / 2) * sqrt(1 / 3 - pow($latitude / 3.14159265359, 2));
}

function calcularKavrayskiyVIIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return $latitude;
}

function calcularMercatorX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

function calcularMercatorY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    
    if ($latitude > 1.4844222297453322) {
        return 3.14159265359;
    } elseif ($latitude < -1.4844222297453322) {
        return -3.14159265359;
    } else {
        return (log(tan(3.14159265359/4 + $latitude/2)));
    }
    //return (log(1/cos($latitude) + tan($latitude)));
    //return (log(tan(3.14159265359/4 + $latitude/2)));
}

function calcularNaturalEarthX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ($longitude * (0.870700 - 0.131979 * pow($latitude, 2) - 0.013791 * pow($latitude, 4) + 0.003971 * pow($latitude, 10) - 0.001529 * pow($latitude, 12)));
}

function calcularNaturalEarthY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return (1.007226 * $latitude + 0.015085 * pow($latitude, 3) - 0.044475 * pow($latitude, 7) + 0.028874 * pow($latitude, 9) - 0.005916 * pow($latitude, 11));
}

function calcularNaturalEarthIIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    //return ($longitude * (0.84719 - 0.13063 * pow($latitude, 2) - 0.04515 * pow($latitude, 12) + 0.05494 * pow($latitude, 14) + 0.02326 * pow($latitude, 16) + 0.00331 * pow($latitude, 18)));
    $latitude2 = $latitude * $latitude;
    $latitude4 = $latitude2 * $latitude2;
    $latitude6 = $latitude4 * $latitude2;
    return ($longitude * (0.84719 - 0.13063 * $latitude2 + $latitude6 * $latitude6 * (-0.04515 + 0.05494 * $latitude2 + 0.02326 * $latitude4 + 0.00331 * $latitude6)));
}

function calcularNaturalEarthIIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    //return (1.01183 * $latitude - 0.02625 * pow($latitude, 9) + 0.01926 * pow($latitude, 11) - 0.00396 * pow($latitude, 13));
    $latitude2 = $latitude * $latitude;
    $latitude4 = $latitude2 * $latitude2;
    return ($latitude * (1.01183 + $latitude4 * $latitude4 * (-0.02625 + 0.01926 * $latitude2 - 0.00396 * $latitude4)));
}

function calcularPattersonX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

function calcularPattersonY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $quadrado = $latitude * $latitude;
    //$y = (1.0148 * $latitude + 0.23185 * pow($latitude, 5) - 0.14499 * pow($latitude, 7) + 0.02406 * pow($latitude, 9));
    return ($latitude * (1.0148 + $quadrado * $quadrado * (0.23185 + $quadrado * (-0.14499 + $quadrado * 0.02406))));
}

function calcularRobinsonX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ((2.6666 - 0.367 * pow($latitude, 2) - 0.150 * pow($latitude, 4) + 0.0379 * pow($latitude, 6)) * $longitude / 3.14159265359);
}

function calcularRobinsonY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    if ($latitude >= 0) { $s = 1; } else { $s = -1; }
    return (0.96047 * $latitude - 0.00857 * $s * pow(abs($latitude), 6.41));
}

function calcularSinusoidalX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return $longitude * cos($latitude);
}

function calcularSinusoidalY(float $latitude): float
{
    return $latitude;
}

function coordenarCentro(int $largura, int $altura): array
{
    $centro = array('x' => $largura / 2,
                    'y' => $altura / 2);
    return $centro;
}

function exibirMundo(int $largura, int $altura, string $projecao): string
{
    $svg = '<g fill="rgb(200,200,200)" fill-rule="nonzero" stroke="rgb(150,150,150)" stroke-width="1">' . PHP_EOL;
    
    $array = carregarMundo();
    $paises = $array['features'];
    
    foreach ($paises as $pais) {
        $coordenadas = $pais['geometry']['coordinates'];
        if ($pais['geometry']['type'] == 'MultiPolygon') {
            foreach ($coordenadas as $multi) {
                foreach ($multi as $poligono) {
                    $pontos = extrairPoligono($poligono, $largura, $altura, $projecao);
                    if (substr_count($pontos, ' ') > 1) {
                        $svg .= '<polygon points="' . $pontos . '" />' . PHP_EOL;
                    }
                }
            }
        } elseif ($pais['geometry']['type'] == 'Polygon') {
            foreach ($coordenadas as $poligono) {
                $pontos = extrairPoligono($poligono, $largura, $altura, $projecao);
                if (substr_count($pontos, ' ') > 1) {
                    $svg .= '<polygon points="' . $pontos . '" />' . PHP_EOL;
                }
            }
        }
    }
    $svg .= '</g>' . PHP_EOL;
    return $svg;
}

function extrairPoligono(array $poligono, int $largura, int $altura, string $projecao): string
{
    $pontos = '';
    $anterior = array('x' => 0, 'y' => 0);
    foreach ($poligono as $coordenada) {
        $latitude = $coordenada[1];
        $longitude = $coordenada[0];
        $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
        if ($ponto['x'] != $anterior['x'] or $ponto['y'] != $anterior['y']) {
            if (!empty($pontos)) {
                $pontos .= ' ';
            }
            $pontos .= $ponto['x'] . ',' . $ponto['y'];
            $anterior = $ponto;
        }
    }
    return $pontos;
}

function carregarMundo(): array
{
    $json = file_get_contents('json/ne_110m_admin_0_countries_lakes.geojson');
    $array = json_decode($json, true);
    return $array;
}

function exibirGrade(int $largura, int $altura, string $projecao): string
{
    $svg = '<g fill="none" stroke="lightgray" stroke-width="1">' . PHP_EOL;
    
    $svg .= exibirParalelos($largura, $altura, $projecao);
    $svg .= exibirMeridianos($largura, $altura, $projecao);
    $svg .= exibirCirculos($largura, $altura, $projecao);
    $svg .= exibirEquador($largura, $altura, $projecao);
    $svg .= exibirGreenwich($largura, $altura, $projecao);
    
    $svg .= '</g>' . PHP_EOL;
    return $svg;
}

function exibirParalelos(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $paralelos = array(15, 30, 45, 60, 75, 90);
    foreach ($paralelos as $latitude) {
        $nw = converterGeoPixel($latitude, 180, $largura, $altura, $projecao);
        $ne = converterGeoPixel($latitude, -180, $largura, $altura, $projecao);
        $svg .= '<line x1="' . $ne['x'] . '" y1="' . $ne['y'] . '" x2="' . $nw['x'] . '" y2="' . $nw['y'] . '" />' . PHP_EOL;
        $sw = converterGeoPixel(-$latitude, 180, $largura, $altura, $projecao);
        $se = converterGeoPixel(-$latitude, -180, $largura, $altura, $projecao);
        $svg .= '<line x1="' . $se['x'] . '" y1="' . $se['y'] . '" x2="' . $sw['x'] . '" y2="' . $sw['y'] . '" />' . PHP_EOL;
    }
    return $svg;
}

function exibirMeridianos(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $meridianos = array(15, 30, 45, 60, 75, 90, 105, 120, 135, 150, 165, 180);
    foreach ($meridianos as $longitude) {
        $ponto = converterGeoPixel(-90, $longitude, $largura, $altura, $projecao);
        $svg .= '<path d="M' . $ponto['x'] . ',' . $ponto['y'];
        for ($latitude = -89; $latitude <= 90; $latitude++) {
            $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
            $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
        }
        $svg .= '" />' . PHP_EOL;
        $ponto = converterGeoPixel(-90, -$longitude, $largura, $altura, $projecao);
        $svg .= '<path d="M' . $ponto['x'] . ',' . $ponto['y'];
        for ($latitude = -89; $latitude <= 90; $latitude++) {
            $ponto = converterGeoPixel($latitude, -$longitude, $largura, $altura, $projecao);
            $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
        }
        $svg .= '" />' . PHP_EOL;
    }
    return $svg;
}

function exibirCirculos(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    
    $tw = converterGeoPixel(23.43656, 180, $largura, $altura, $projecao);
    $te = converterGeoPixel(23.43656, -180, $largura, $altura, $projecao);
    $svg .= '<line x1="' . $te['x'] . '" y1="' . $te['y'] . '" x2="' . $tw['x'] . '" y2="' . $tw['y'] . '" stroke-dasharray="10,10" />' . PHP_EOL;
    $tw = converterGeoPixel(-23.43656, 180, $largura, $altura, $projecao);
    $te = converterGeoPixel(-23.43656, -180, $largura, $altura, $projecao);
    $svg .= '<line x1="' . $te['x'] . '" y1="' . $te['y'] . '" x2="' . $tw['x'] . '" y2="' . $tw['y'] . '" stroke-dasharray="10,10" />' . PHP_EOL;
    
    $cw = converterGeoPixel(66.5622, 180, $largura, $altura, $projecao);
    $ce = converterGeoPixel(66.5622, -180, $largura, $altura, $projecao);
    $svg .= '<line x1="' . $ce['x'] . '" y1="' . $ce['y'] . '" x2="' . $cw['x'] . '" y2="' . $cw['y'] . '" stroke-dasharray="10,10" />' . PHP_EOL;
    $cw = converterGeoPixel(-66.5622, 180, $largura, $altura, $projecao);
    $ce = converterGeoPixel(-66.5622, -180, $largura, $altura, $projecao);
    $svg .= '<line x1="' . $ce['x'] . '" y1="' . $ce['y'] . '" x2="' . $cw['x'] . '" y2="' . $cw['y'] . '" stroke-dasharray="10,10" />' . PHP_EOL;
    
    return $svg;
}

function exibirEquador(int $largura, int $altura, string $projecao): string
{
    $w = converterGeoPixel(0, 180, $largura, $altura, $projecao);
    $e = converterGeoPixel(0, -180, $largura, $altura, $projecao);
    return '<line x1="' . $e['x'] . '" y1="' . $e['y'] . '" x2="' . $w['x'] . '" y2="' . $w['y'] . '" />' . PHP_EOL;
}

function exibirGreenwich(int $largura, int $altura, string $projecao): string
{
    $n = converterGeoPixel(90, 0, $largura, $altura, $projecao);
    $s = converterGeoPixel(-90, 0, $largura, $altura, $projecao);
    return '<line x1="' . $n['x'] . '" y1="' . $n['y'] . '" x2="' . $s['x'] . '" y2="' . $s['y'] . '" />' . PHP_EOL;
}

