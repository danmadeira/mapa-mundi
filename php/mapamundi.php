<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$largura = filter_input(INPUT_GET, 'largura', FILTER_VALIDATE_INT, array('options' => array('default' => 1200, 'min_range' => 720, 'max_range' => 3840)));
$altura = filter_input(INPUT_GET, 'altura', FILTER_VALIDATE_INT, array('options' => array('default' => 566, 'min_range' => 480, 'max_range' => 2160)));

$dsn = 'mysql:host=192.168.1.10;dbname=world;charset=utf8';
$user = 'usuario';
$pass = 'senha';
$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = "SELECT city, lat, lng, country, id FROM worldcities
          WHERE lat IS NOT NULL AND lng IS NOT NULL AND capital = 'primary'
          ORDER BY population";
$result = $pdo->query($query);

echo montarPagina($result, $largura, $altura);

function montarPagina(object $result, int $largura, int $altura): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $largura.'" height="' . $altura . '">' . PHP_EOL;
    $svg .= '<rect x="0" y="0" width="' . $largura . '" height="' . $altura . '" fill="none" stroke="rgb(150,150,150)" stroke-width="1" />' . PHP_EOL;
    $svg .= exibirMundo($largura, $altura);
    $svg .= exibirCidades($result, $largura, $altura);
    $svg .= '</svg>' . PHP_EOL;

    $html = '<!DOCTYPE html>' . PHP_EOL . '<html>' . PHP_EOL . '<body>' . PHP_EOL;
    $html .= $svg;
    $html .= '</body>' . PHP_EOL . '</html>';
    
    return $html;
}

function exibirCidades(object $result, int $largura, int $altura): string
{
    $svg = '<g fill="rgb(33,42,116)" stroke-width="0" onmouseover="evt.target.setAttribute(\'fill\', \'blue\');" onmouseout="evt.target.setAttribute(\'fill\',\'red\');">' . PHP_EOL;
    
    if ($result->rowCount() >= 1) {
        while ($cidade = $result->fetch(PDO::FETCH_ASSOC)) {
            $latitude = $cidade['lat'];
            $longitude = $cidade['lng'];
            $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura);
            $titulo = $cidade['city'] . ' (' . $cidade['country'] . ')';
            $svg .= '<circle id="'.$cidade['id'].'" cx="' . $ponto['x'] . '" cy="' . $ponto['y'] . '" r="3"><title>' . $titulo . '</title></circle>' . PHP_EOL;
        }
    }
    $svg .= '</g>' . PHP_EOL;
    return $svg;
}

function converterGeoPixel(float $latitude, float $longitude, int $largura, int $altura): array
{
    $centro = coordenarCentro($largura, $altura);
    $modulo = calcularModulo($largura, $altura);
    
    $x = floor($centro['x'] + ($longitude * $modulo));
    $y = floor($centro['y'] - ($latitude * $modulo));
    
    return array('x' => $x, 'y' => $y);
}

function coordenarCentro(int $largura, int $altura): array
{
    $centro = array();
    $centro['x'] = $largura / 2;
    $centro['y'] = $altura / 2;
    return $centro;
}

function calcularModulo(int $largura, int $altura): float
{
    if ($largura / $altura < 2) {
        $modulo = $largura / 360;
    } else {
        $modulo = $altura / 180;
    }
    return $modulo;
}

function exibirMundo(int $largura, int $altura): string
{
    $svg = '<g fill="rgb(200,200,200)" fill-rule="nonzero" stroke="rgb(150,150,150)" stroke-width="1">' . PHP_EOL;
    
    $array = carregarMundo();
    $paises = $array['features'];
    
    foreach ($paises as $pais) {
        $coordenadas = $pais['geometry']['coordinates'];
        if ($pais['geometry']['type'] == 'MultiPolygon') {
            foreach ($coordenadas as $multi) {
                foreach ($multi as $poligono) {
                    $pontos = extrairPoligono($poligono, $largura, $altura);
                    if (substr_count($pontos, ' ') > 1) {
                        $svg .= '<polygon points="' . $pontos . '" />' . PHP_EOL;
                    }
                }
            }
        } elseif ($pais['geometry']['type'] == 'Polygon') {
            foreach ($coordenadas as $poligono) {
                $pontos = extrairPoligono($poligono, $largura, $altura);
                if (substr_count($pontos, ' ') > 1) {
                    $svg .= '<polygon points="' . $pontos . '" />' . PHP_EOL;
                }
            }
        }
    }
    $svg .= '</g>' . PHP_EOL;
    return $svg;
}

function extrairPoligono(array $poligono, int $largura, int $altura): string
{
    $pontos = '';
    $anterior = array('x' => 0, 'y' => 0);
    foreach ($poligono as $coordenada) {
        $latitude = $coordenada[1];
        $longitude = $coordenada[0];
        $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura);
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

