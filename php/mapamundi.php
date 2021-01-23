<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$largura = filter_input(INPUT_GET, 'largura', FILTER_VALIDATE_INT, array('options' => array('default' => 1000, 'min_range' => 250, 'max_range' => 4000)));
$altura = filter_input(INPUT_GET, 'altura', FILTER_VALIDATE_INT, array('options' => array('default' => 500, 'min_range' => 250, 'max_range' => 4000)));
$projecao = filter_input(INPUT_GET, 'projecao', FILTER_VALIDATE_REGEXP, array('options' => array('default' => 'k', 'regexp' => '/[ceEgGhkmMnNprstwW]/')));

echo montarPagina($largura, $altura, $projecao);

function montarPagina(int $largura, int $altura, string $projecao): string
{
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $largura.'" height="' . $altura . '">' . PHP_EOL;
    //$svg .= '<rect x="0" y="0" width="' . $largura . '" height="' . $altura . '" fill="none" stroke="rgb(150,150,150)" stroke-width="1" />' . PHP_EOL;
    //$inicio = microtime(true);
    $svg .= exibirFundoAzul($largura, $altura, $projecao);
    $svg .= exibirMundo($largura, $altura, $projecao);
    $svg .= exibirCidades($largura, $altura, $projecao);
    $svg .= exibirGrade($largura, $altura, $projecao);
    //$fim = microtime(true);
    $svg .= '</svg>' . PHP_EOL;

    $html = '<!DOCTYPE html>' . PHP_EOL . '<html>' . PHP_EOL . '<body>' . PHP_EOL;
    $html .= $svg;
    //$html .= '<p>Tempo de processamento: ' . round($fim - $inicio, 3) . ' segundos.</p>' . PHP_EOL;
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
        $r = round($largura * 0.002);
        while ($cidade = $result->fetch(PDO::FETCH_ASSOC)) {
            $latitude = $cidade['lat'];
            $longitude = $cidade['lng'];
            $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
            $titulo = $cidade['city'] . ' (' . $cidade['country'] . ')';
            $svg .= '<circle id="' . $cidade['id'] . '" cx="' . $ponto['x'] . '" cy="' . $ponto['y'] . '" r="' . $r . '"><title>' . $titulo . '</title></circle>' . PHP_EOL;
        }
        $svg .= '</g>' . PHP_EOL;
    }
    
    return $svg;
}

function converterGeoPixel(float $latitude, float $longitude, int $largura, int $altura, string $projecao): array
{
    $centro = coordenarCentro($largura, $altura);
    
    switch ($projecao) {
        case 'c': // equirectangular projection // plate carrée
            if ($largura / $altura < 2) {
                $modulo = $largura / 360;
            } else {
                $modulo = $altura / 180;
            }
            $x = floor($centro['x'] + ($longitude * $modulo));
            $y = floor($centro['y'] - ($latitude * $modulo));
            break;
            
        case 'e': // Eckert IV projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularEckertIVX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularEckertIVY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularEckertIVX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularEckertIVY($latitude) * $modulo));
            break;
            
        case 'E': // Eckert VI projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularEckertVIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularEckertVIY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularEckertVIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularEckertVIY($latitude) * $modulo));
            break;
            
        case 'g': // Gott equal-area elliptical projection
            if ($largura / $altura < 1.621) {
                $modulo = $largura / (calcularGottEqualareaEllipticalX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularGottEqualareaEllipticalY(90, 0) * 2);
            }
            $x = floor($centro['x'] + (calcularGottEqualareaEllipticalX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularGottEqualareaEllipticalY($latitude, $longitude) * $modulo));
            break;
            
        case 'G': // Gott–Mugnolo azimuthal projection (fix needed)
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularGottMugnoloAzimuthalX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularGottMugnoloAzimuthalY(90, 0) * 2);
            }
            $x = floor($centro['x'] + (calcularGottMugnoloAzimuthalX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularGottMugnoloAzimuthalY($latitude, $longitude) * $modulo));
            break;
            
        case 'h': // Hammer projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularHammerX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularHammerY(90, 0) * 2);
            }
            $x = floor($centro['x'] + (calcularHammerX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularHammerY($latitude, $longitude) * $modulo));
            break;
            
        case 'k': // Kavrayskiy VII projection
            if ($largura / $altura < 1.733) {
                $modulo = $largura / (calcularKavrayskiyVIIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularKavrayskiyVIIY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularKavrayskiyVIIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularKavrayskiyVIIY($latitude) * $modulo));
            break;
            
        case 'm': // Miller cylindrical projection
            if ($largura / $altura < 1.363) {
                $modulo = $largura / (calcularMillerX(180) * 2);
            } else {
                $modulo = $altura / (calcularMillerY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularMillerX($longitude) * $modulo));
            $y = floor($centro['y'] - (calcularMillerY($latitude) * $modulo));
            break;
            
        case 'M': // Mollweide projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularMollweideX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularMollweideY(1.570796326795) * 2);
            }
            $theta = calcularMollweideTheta($latitude);
            $x = floor($centro['x'] + (calcularMollweideX($theta, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularMollweideY($theta) * $modulo));
            break;
            
        case 'n': // Natural Earth projection
            if ($largura / $altura < 1.923) {
                $modulo = $largura / (calcularNaturalEarthX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularNaturalEarthY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularNaturalEarthX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularNaturalEarthY($latitude) * $modulo));
            break;
            
        case 'N': // Natural Earth II projection
            if ($largura / $altura < 1.869) {
                $modulo = $largura / (calcularNaturalEarthIIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularNaturalEarthIIY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularNaturalEarthIIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularNaturalEarthIIY($latitude) * $modulo));
            break;
            
        case 'p': // Patterson projection
            if ($largura / $altura < 1.755) {
                $modulo = $largura / (calcularPattersonX(180) * 2);
            } else {
                $modulo = $altura / (calcularPattersonY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularPattersonX($longitude) * $modulo));
            $y = floor($centro['y'] - (calcularPattersonY($latitude) * $modulo));
            break;
            
        case 'r': // Robinson projection
            if ($largura / $altura < 1.969) {
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
            
        case 't': // Mercator projection
            if ($largura / $altura < 1) {
                $modulo = $largura / (calcularMercatorX(180) * 2);
            } else {
                $modulo = $altura / (calcularMercatorY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularMercatorX($longitude) * $modulo));
            $y = floor($centro['y'] - (calcularMercatorY($latitude) * $modulo));
            break;
            
        case 'w': // Wagner VI projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularWagnerVIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularWagnerVIY(90) * 2);
            }
            $x = floor($centro['x'] + (calcularWagnerVIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularWagnerVIY($latitude) * $modulo));
            break;
            
        case 'W': // Winkel tripel projection
            if ($largura / $altura < 1.637) {
                $modulo = $largura / (calcularWinkelIIIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularWinkelIIIY(90, 0) * 2);
            }
            $x = floor($centro['x'] + (calcularWinkelIIIX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularWinkelIIIY($latitude, $longitude) * $modulo));
            break;
    }
    
    return array('x' => $x, 'y' => $y);
}

function calcularEckertIVX(float $latitude, float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    $theta = $latitude * (3.14159265359 / 180);
    $cx = 0.42223820031577120149;
    $cp = 3.57079632679489661922;
    $p = $cp * sin($theta);
    $v = $theta * $theta;
    $theta *= 0.895168 + $v * (0.0218849 + $v * 0.00826809);
    for ($i = 6; $i > 0; $i--) {
        $c = cos($theta);
        $s = sin($theta);
        $v = ($theta + $s * ($c + 2) - $p) / (1 + $c * ($c + 2) - $s * $s);
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    if ($i == 0) {
        return ($cx * $longitude);
    } else {
        return ($cx * $longitude * (1 + cos($theta)));
    }
}

function calcularEckertIVY(float $latitude): float
{
    $theta = $latitude * (3.14159265359 / 180);
    $cy = 1.32650042817700232218;
    $cp = 3.57079632679489661922;
    $p = $cp * sin($theta);
    $v = $theta * $theta;
    $theta *= 0.895168 + $v * (0.0218849 + $v * 0.00826809);
    for ($i = 6; $i > 0; $i--) {
        $c = cos($theta);
        $s = sin($theta);
        $v = ($theta + $s * ($c + 2) - $p) / (1 + $c * ($c + 2) - $s * $s);
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    if ($i == 0) {
        return ($theta < 0 ? -$cy : $cy);
    } else {
        return ($cy * sin($theta));
    }
}

function calcularEckertVIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ($longitude * (1 + cos($latitude)) / sqrt(2 + 3.14159265359));
}

function calcularEckertVIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return (2 * $latitude / sqrt(2 + 3.14159265359));
}

function calcularGottEqualareaEllipticalX(float $latitude, float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    $theta = $latitude * (3.14159265359 / 180);
    $phi = asin(cos($theta) * sin($longitude / 2));
    $k = 3.14159265359 * sin($phi);
    for ($i = 10; $i > 0; $i--) {
        $v = ($theta + sin($theta) - $k) / (1 + cos($theta));
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    if ($i == 0) {
        $theta = ($theta < 0) ? -3.14159265359 / 2 : 3.14159265359 / 2;
    } else {
        $theta *= 0.5;
    }
    return (sqrt(2) * sin($theta));
}

function calcularGottEqualareaEllipticalY(float $latitude, float $longitude): float
{
    if ($longitude == 180) { $longitude--; } elseif ($longitude == -180) { $longitude++; } // remendo para limitar em 179
    $longitude = $longitude * (3.14159265359 / 180);
    $theta = $latitude * (3.14159265359 / 180);
    $phi = asin(cos($theta) * sin($longitude / 2));
    $k = 3.14159265359 * sin($phi);
    $lambda = 0.5 * asin(sin($theta) / cos($phi));
    for ($i = 10; $i > 0; $i--) {
        $v = ($theta + sin($theta) - $k) / (1 + cos($theta));
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    if ($i == 0) {
        $theta = ($theta < 0) ? -3.14159265359 / 2 : 3.14159265359 / 2;
    } else {
        $theta *= 0.5;
    }
    return ((3.14159265359 / (2 * sqrt(2))) * $lambda * cos($theta));
}

function calcularGottMugnoloAzimuthalX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return (cos($longitude) * sin(0.446 * (3.14159265359 / 2 - $latitude)));
}

function calcularGottMugnoloAzimuthalY(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return (sin($longitude) * sin(0.446 * (3.14159265359 / 2 - $latitude)));
}

function calcularHammerX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ((2 * sqrt(2) * cos($latitude) * sin($longitude / 2)) / sqrt(1 + cos($latitude) * cos($longitude / 2)));
}

function calcularHammerY(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ((sqrt(2) * sin($latitude)) / sqrt(1 + cos($latitude) * cos($longitude / 2)));
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
        //return (log(1/cos($latitude) + tan($latitude)));
        return (log(tan(3.14159265359/4 + $latitude/2)));
    }
}

function calcularMillerX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

function calcularMillerY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return ((5/4) * log(tan(3.14159265359/4 + ((2 * $latitude)/5))));
}

function calcularMollweideTheta(float $latitude): float
{
    //$latitude = $latitude * (3.14159265359 / 180);
    //$theta = $latitude - (2 * asin(2 * $latitude / 3.14159265359));
    //$k = 3.14159265359 * sin($latitude);
    $theta = $latitude * (3.14159265359 / 180);
    $k = 3.14159265359 * sin($theta);
    for ($i = 10; $i > 0; $i--) {
        $v = ($theta + sin($theta) - $k) / (1 + cos($theta));
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    if ($i == 0) {
        $theta = ($theta < 0) ? -3.14159265359 / 2 : 3.14159265359 / 2;
    } else {
        $theta *= 0.5;
    }
    return $theta;
}

function calcularMollweideX(float $theta, float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    $sp = sin(3.14159265359 / 2);
    $r = sqrt(3.14159265359 * 2 * $sp / (3.14159265359 + sin(3.14159265359)));
    $cx = 2 * $r / 3.14159265359;
    return ($cx * $longitude * cos($theta));
}

function calcularMollweideY(float $theta): float
{
    $sp = sin(3.14159265359 / 2);
    $r = sqrt(3.14159265359 * 2 * $sp / (3.14159265359 + sin(3.14159265359)));
    $cy = $r / $sp;
    return ($cy * sin($theta));
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
    //return ($longitude * (0.84719 - 0.13063 * pow($latitude, 2) - 0.04515 * pow($latitude, 12) + 0.05494 * pow($latitude, 14) - 0.02326 * pow($latitude, 16) + 0.00331 * pow($latitude, 18)));
    $latitude2 = $latitude * $latitude;
    $latitude4 = $latitude2 * $latitude2;
    $latitude6 = $latitude2 * $latitude4;
    return ($longitude * (0.84719 - 0.13063 * $latitude2 + $latitude6 * $latitude6 * (-0.04515 + 0.05494 * $latitude2 - 0.02326 * $latitude4 + 0.00331 * $latitude6)));
}

function calcularNaturalEarthIIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    //return (1.01183 * $latitude - 0.02625 * pow($latitude, 9) + 0.01926 * pow($latitude, 11) - 0.00396 * pow($latitude, 13));
    $latitude2 = $latitude * $latitude;
    $latitude4 = $latitude2 * $latitude2;
    return ($latitude * (1.01183 + $latitude4 * $latitude4 * (0.01926 * $latitude2 - 0.00396 * $latitude4 - 0.02625)));
}

function calcularPattersonX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

function calcularPattersonY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $latitude2 = $latitude * $latitude;
    //$y = (1.0148 * $latitude + 0.23185 * pow($latitude, 5) - 0.14499 * pow($latitude, 7) + 0.02406 * pow($latitude, 9));
    return ($latitude * (1.0148 + $latitude2 * $latitude2 * (0.23185 + $latitude2 * (-0.14499 + $latitude2 * 0.02406))));
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

function calcularWagnerVIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ($longitude * sqrt(1 - 3 * pow($latitude / 3.14159265359, 2)));
}

function calcularWagnerVIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return $latitude;
}

function calcularWinkelIIIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $alpha = acos(cos($latitude) * cos($longitude / 2));
    $sinc = sin($alpha) / $alpha;
    return ((1 / 2) * ($longitude * cos(acos(2 / 3.14159265359)) + ((2 * cos($latitude) * sin($longitude / 2)) / ($sinc))));
}

function calcularWinkelIIIY(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $alpha = acos(cos($latitude) * cos($longitude / 2));
    $sinc = sin($alpha) / $alpha;
    return ((1 / 2) * ($latitude + (sin($latitude) / ($sinc))));
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

function exibirFundoAzul(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $ponto = converterGeoPixel(90, -180, $largura, $altura, $projecao);
    $svg .= '<path d="M' . $ponto['x'] . ',' . $ponto['y'];
    for ($longitude = -179; $longitude <= 180; $longitude++) {
        $ponto = converterGeoPixel(90, $longitude, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    for ($latitude = 89; $latitude >= -90; $latitude--) {
        $ponto = converterGeoPixel($latitude, 180, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    for ($longitude = 179; $longitude >= -180; $longitude--) {
        $ponto = converterGeoPixel(-90, $longitude, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    for ($latitude = -89; $latitude <= 90; $latitude++) {
        $ponto = converterGeoPixel($latitude, -180, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    $svg .= ' Z" fill="rgb(174,214,241)" />' . PHP_EOL;
    return $svg;
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
        if ($projecao == 'W' or $projecao == 'h' or $projecao == 'g') {
            $svg .= montarCaminhoParalelo($latitude, $largura, $altura, $projecao);
            $svg .= montarCaminhoParalelo(-$latitude, $largura, $altura, $projecao);
        } else {
            $svg .= montarLinhaParalelo($latitude, $largura, $altura, $projecao);
            $svg .= montarLinhaParalelo(-$latitude, $largura, $altura, $projecao);
        }
    }
    return $svg;
}

function montarCaminhoParalelo(float $latitude, int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $ponto = converterGeoPixel($latitude, -180, $largura, $altura, $projecao);
    $svg .= '<path d="M' . $ponto['x'] . ',' . $ponto['y'];
    for ($longitude = -179; $longitude <= 180; $longitude++) {
        $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    $svg .= '" />' . PHP_EOL;
    return $svg;
}

function montarLinhaParalelo(float $latitude, int $largura, int $altura, string $projecao): string
{
    $ocidente = converterGeoPixel($latitude, -180, $largura, $altura, $projecao);
    $oriente = converterGeoPixel($latitude, 180, $largura, $altura, $projecao);
    return '<line x1="' . $ocidente['x'] . '" y1="' . $ocidente['y'] . '" x2="' . $oriente['x'] . '" y2="' . $oriente['y'] . '" />' . PHP_EOL;
}

function exibirMeridianos(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $meridianos = array(15, 30, 45, 60, 75, 90, 105, 120, 135, 150, 165, 180);
    foreach ($meridianos as $longitude) {
        $svg .= montarCaminhoMeridiano(-$longitude, $largura, $altura, $projecao);
        $svg .= montarCaminhoMeridiano($longitude, $largura, $altura, $projecao);
    }
    return $svg;
}

function montarCaminhoMeridiano(float $longitude, int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $ponto = converterGeoPixel(-90, $longitude, $largura, $altura, $projecao);
    $svg .= '<path d="M' . $ponto['x'] . ',' . $ponto['y'];
    for ($latitude = -89; $latitude <= 90; $latitude++) {
        $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    $svg .= '" />' . PHP_EOL;
    return $svg;
}

function exibirCirculos(int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $cancer = 23.43656;
    $capricornio = -23.43656;
    $artico = 66.5622;
    $antartico = -66.5622;
    
    if ($projecao == 'W' or $projecao == 'h' or $projecao == 'g') {
        $svg .= montarCaminhoCirculo($cancer, $largura, $altura, $projecao);
        $svg .= montarCaminhoCirculo($capricornio, $largura, $altura, $projecao);
        $svg .= montarCaminhoCirculo($artico, $largura, $altura, $projecao);
        $svg .= montarCaminhoCirculo($antartico, $largura, $altura, $projecao);
    } else {
        $svg .= montarLinhaCirculo($cancer, $largura, $altura, $projecao);
        $svg .= montarLinhaCirculo($capricornio, $largura, $altura, $projecao);
        $svg .= montarLinhaCirculo($artico, $largura, $altura, $projecao);
        $svg .= montarLinhaCirculo($antartico, $largura, $altura, $projecao);
    }
    
    return $svg;
}

function montarCaminhoCirculo(float $latitude, int $largura, int $altura, string $projecao): string
{
    $svg = '';
    $ponto = converterGeoPixel($latitude, -180, $largura, $altura, $projecao);
    $svg .= '<path d="M' . $ponto['x'] . ',' . $ponto['y'];
    for ($longitude = -179; $longitude <= 180; $longitude++) {
        $ponto = converterGeoPixel($latitude, $longitude, $largura, $altura, $projecao);
        $svg .= ' L' . $ponto['x'] . ',' . $ponto['y'];
    }
    $svg .= '" stroke-dasharray="10,10" />' . PHP_EOL;
    return $svg;
}

function montarLinhaCirculo(float $latitude, int $largura, int $altura, string $projecao): string
{
    $ocidente = converterGeoPixel($latitude, -180, $largura, $altura, $projecao);
    $oriente = converterGeoPixel($latitude, 180, $largura, $altura, $projecao);
    return '<line x1="' . $ocidente['x'] . '" y1="' . $ocidente['y'] . '" x2="' . $oriente['x'] . '" y2="' . $oriente['y'] . '" stroke-dasharray="10,10" />' . PHP_EOL;
}

function exibirEquador(int $largura, int $altura, string $projecao): string
{
    $ocidente = converterGeoPixel(0, -180, $largura, $altura, $projecao);
    $oriente = converterGeoPixel(0, 180, $largura, $altura, $projecao);
    return '<line x1="' . $ocidente['x'] . '" y1="' . $ocidente['y'] . '" x2="' . $oriente['x'] . '" y2="' . $oriente['y'] . '" />' . PHP_EOL;
}

function exibirGreenwich(int $largura, int $altura, string $projecao): string
{
    $n = converterGeoPixel(90, 0, $largura, $altura, $projecao);
    $s = converterGeoPixel(-90, 0, $largura, $altura, $projecao);
    return '<line x1="' . $n['x'] . '" y1="' . $n['y'] . '" x2="' . $s['x'] . '" y2="' . $s['y'] . '" />' . PHP_EOL;
}

