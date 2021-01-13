<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$largura = filter_input(INPUT_GET, 'largura', FILTER_VALIDATE_INT, array('options' => array('default' => 1200, 'min_range' => 720, 'max_range' => 3840)));
$altura = filter_input(INPUT_GET, 'altura', FILTER_VALIDATE_INT, array('options' => array('default' => 566, 'min_range' => 480, 'max_range' => 2160)));
$projecao = filter_input(INPUT_GET, 'projecao', FILTER_VALIDATE_REGEXP, array('options' => array('default' => 'r', 'regexp' => '/[ekrs]/')));

$dsn = 'mysql:host=localhost;dbname=world;charset=utf8';
$user = 'usuario';
$pass = 'senha';
$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT city, lat, lng, country, id FROM worldcities
          WHERE lat IS NOT NULL AND lng IS NOT NULL AND capital = 'primary'
          ORDER BY population";
$result = $pdo->query($query);

echo montarPagina($result, $largura, $altura, $projecao);

function montarPagina(object $result, int $largura, int $altura, string $projecao): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $largura.'" height="' . $altura . '">' . PHP_EOL;
    $svg .= '<rect x="0" y="0" width="' . $largura . '" height="' . $altura . '" fill="none" stroke="rgb(150,150,150)" stroke-width="1" />' . PHP_EOL;
    $svg .= exibirMundo($largura, $altura, $projecao);
    $svg .= exibirCidades($result, $largura, $altura, $projecao);
    $svg .= exibirGrade($largura, $altura, $projecao);
    $svg .= '</svg>' . PHP_EOL;

    $html = '<!DOCTYPE html>' . PHP_EOL . '<html>' . PHP_EOL . '<body>' . PHP_EOL;
    $html .= $svg;
    $html .= '</body>' . PHP_EOL . '</html>';
    
    return $html;
}

function exibirCidades(object $result, int $largura, int $altura, string $projecao): string
{
    $svg = '<g fill="rgb(33,42,116)" stroke-width="0" onmouseover="evt.target.setAttribute(\'fill\', \'blue\');" onmouseout="evt.target.setAttribute(\'fill\',\'red\');">' . PHP_EOL;
    
    if ($result->rowCount() >= 1) {
        while ($cidade = $result->fetch(PDO::FETCH_ASSOC)) {
            $latitude = $cidade['lat'];
            $longitude = $cidade['lng'];
            $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
            $titulo = $cidade['city'] . ' (' . $cidade['country'] . ')';
            $svg .= '<circle id="'.$cidade['id'].'" cx="' . $ponto['x'] . '" cy="' . $ponto['y'] . '" r="3"><title>' . $titulo . '</title></circle>' . PHP_EOL;
        }
    }
    $svg .= '</g>' . PHP_EOL;
    return $svg;
}

function converterGeoPixel(float $latitude, float $longitude, int $largura, int $altura, string $projecao): array
{
    if ($projecao == 'e') { // plate carrée // equirectangular projection
        $radiano = false;
    } elseif ($projecao == 'k') { // Kavrayskiy VII projection
        $radiano = false;
    } elseif ($projecao == 'r') { // Robinson projection
        $radiano = true;
    } elseif ($projecao == 's') { // sinusoidal projection
        $radiano = false;
    }
    $centro = coordenarCentro($largura, $altura);
    $modulo = calcularModulo($largura, $altura, $radiano);
    
    if ($projecao == 'r') {
        $latitude = deg2rad($latitude);
        $longitude = deg2rad($longitude);
        if ($latitude >= 0) {
            $s = -1;
        } else {
            $s = 1;
        }
        $x = floor($centro['x'] + ((2.6666 - 0.367 * pow($latitude, 2) - 0.150 * pow($latitude, 4) + 0.0379 * pow($latitude, 6)) * $longitude / pi()) * $modulo);
        $y = floor($centro['y'] - (0.96047 * $latitude - 0.00857 * $s * pow(abs($latitude), 6.41)) * $modulo);
    } else {
        $fatorpolo = calcularFatorPolo($latitude, $projecao);
        $x = floor($centro['x'] + ($longitude * $modulo * $fatorpolo));
        $y = floor($centro['y'] - ($latitude * $modulo));
    }
    
    return array('x' => $x, 'y' => $y);
}

function coordenarCentro(int $largura, int $altura): array
{
    $centro = array('x' => $largura / 2,
                    'y' => $altura / 2);
    return $centro;
}

function calcularModulo(int $largura, int $altura, bool $radiano = false): float
{
    $paralelo = 360;
    $meridiano = 180;
    if ($radiano) {
        $paralelo = deg2rad($paralelo);
        $meridiano = deg2rad($meridiano);
    }
    if ($largura / $altura < 2) {
        $modulo = $largura / $paralelo;
    } else {
        $modulo = $altura / $meridiano;
    }
    return $modulo;
}

function calcularFatorPolo(float $latitude, string $projecao): float
{
    if ($projecao == 'e') {
        return 1; // plate carrée // equirectangular projection
    } elseif ($projecao == 'k') {
        return abs(3/2 * sqrt(1/3 - pow(deg2rad($latitude)/pi(), 2))); // Kavrayskiy VII projection
    } elseif ($projecao == 's') {
        return abs(cos(deg2rad($latitude))); // sinusoidal projection
    }
    ////return abs((abs($latitude) / 90) - 1); // 
    ////return abs(((abs($latitude) - abs($latitude) * 50 / 100) / 90) - 1); // redução de 50%
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

