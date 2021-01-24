<?php

$largura = filter_input(INPUT_GET, 'largura', FILTER_VALIDATE_INT, array('options' => array('default' => 1000, 'min_range' => 250, 'max_range' => 4000)));
$altura = filter_input(INPUT_GET, 'altura', FILTER_VALIDATE_INT, array('options' => array('default' => 500, 'min_range' => 250, 'max_range' => 4000)));
$projecao = filter_input(INPUT_GET, 'projecao', FILTER_VALIDATE_REGEXP, array('options' => array('default' => 'c', 'regexp' => '/^[a-zA-Z]$/')));

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

        case 'e': // Eckert IV projection
            if ($largura / $altura < 2.002) {
                $modulo = $largura / (calcularEckertIVX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularEckertIVY(1.5697757215205) * 2);
            }
            $theta = calcularEckertIVTheta($latitude);
            $x = floor($centro['x'] + (calcularEckertIVX($theta, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularEckertIVY($theta) * $modulo));
            break;
            
        case 'E': // Eckert VI projection
            if ($largura / $altura < 2) {
                $modulo = $largura / (calcularEckertVIX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularEckertVIY(1.570796326795) * 2);
            }
            $theta = calcularEckertVITheta($latitude);
            $x = floor($centro['x'] + (calcularEckertVIX($theta, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularEckertVIY($theta) * $modulo));
            break;
            
        case 'g': // Gott equal-area elliptical projection
            if ($largura / $altura < 1.621) {
                $modulo = $largura / (calcularGottEqualAreaEllipticalX(0, 180) * 2);
            } else {
                $modulo = $altura / (calcularGottEqualAreaEllipticalY(90, 0) * 2);
            }
            $x = floor($centro['x'] + (calcularGottEqualAreaEllipticalX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularGottEqualAreaEllipticalY($latitude, $longitude) * $modulo));
            break;
            
        case 'G': // Gott–Mugnolo azimuthal projection // (necessita ajuste)
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
            
        case 'l': // Lambert azimuthal equal-area projection // (necessita ajuste)
            if ($largura / $altura < 1) {
                $modulo = $largura / (calcularLambertAzimuthalEqualAreaX(0, 180) * 3.14159265359);
            } else {
                $modulo = $altura / (calcularLambertAzimuthalEqualAreaY(90, 0) * 3.14159265359);
            }
            $x = floor($centro['x'] + (calcularLambertAzimuthalEqualAreaX($latitude, $longitude) * $modulo));
            $y = floor($centro['y'] - (calcularLambertAzimuthalEqualAreaY($latitude, $longitude) * $modulo));
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
            
        default: // c: equirectangular projection -> plate carrée
            /*
             * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 219
             */
            if ($largura / $altura < 2) {
                $modulo = $largura / 360;
            } else {
                $modulo = $altura / 180;
            }
            $x = floor($centro['x'] + ($longitude * $modulo));
            $y = floor($centro['y'] - ($latitude * $modulo));
    }
    
    return array('x' => $x, 'y' => $y);
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 256
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 221
 */
function calcularEckertIVTheta(float $latitude): float
{
    $theta = $latitude * (3.14159265359 / 180);
    $k = (2 + (3.14159265359 / 2)) * sin($theta);
    $theta = $theta / 2;
    for ($i = 10; $i > 0; $i--) {
        $sint = sin($theta);
        $cost = cos($theta);
        $v = ($theta + $sint * $cost + 2 * $sint - $k) / (2 * $cost * (1 + $cost));
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    
    return $theta;
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 256
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 221
 */
function calcularEckertIVX(float $theta, float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return ((2 / (sqrt(3.14159265359 * (4 + 3.14159265359)))) * $longitude * (1 + cos($theta)));
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 256
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 221
 */
function calcularEckertIVY(float $theta): float
{
    return (2 * sqrt(3.14159265359 / (4 + 3.14159265359)) * sin($theta));
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 257
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularEckertVITheta(float $latitude): float
{
    $theta = $latitude * (3.14159265359 / 180);
    $k = (1 + (3.14159265359 / 2)) * sin($theta);
    for ($i = 10; $i > 0; $i--) {
        $v = ($theta + sin($theta) - $k) / (1 + cos($theta));
        $theta -= $v;
        if (abs($v) < 1e-7) {
            break;
        }
    }
    return $theta;
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 257
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularEckertVIX(float $theta, float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return ($longitude * (1 + cos($theta)) / sqrt(2 + 3.14159265359));
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 257
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularEckertVIY(float $theta): float
{
    return (2 * $theta / sqrt(2 + 3.14159265359));
}

/*
 * GOTT, J. R.; MUGNOLO, C.; COLLEY, W. N. Map Projections Minimizing Distance Errors. p 4
 */
function calcularGottEqualAreaEllipticalX(float $latitude, float $longitude): float
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
        $theta = ($theta < 0) ? (-3.14159265359 / 2) : (3.14159265359 / 2);
    } else {
        $theta *= 0.5;
    }
    return (sqrt(2) * sin($theta));
}

/*
 * GOTT, J. R.; MUGNOLO, C.; COLLEY, W. N. Map Projections Minimizing Distance Errors. p 4
 */
function calcularGottEqualAreaEllipticalY(float $latitude, float $longitude): float
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
        $theta = ($theta < 0) ? (-3.14159265359 / 2) : (3.14159265359 / 2);
    } else {
        $theta *= 0.5;
    }
    return ((3.14159265359 / (2 * sqrt(2))) * $lambda * cos($theta));
}

/*
 * GOTT, J. R.; MUGNOLO, C.; COLLEY, W. N. Map Projections Minimizing Distance Errors. p 8
 */
function calcularGottMugnoloAzimuthalX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return (cos($longitude) * sin(0.446 * (3.14159265359 / 2 - $latitude)));
}

/*
 * GOTT, J. R.; MUGNOLO, C.; COLLEY, W. N. Map Projections Minimizing Distance Errors. p 8
 */
function calcularGottMugnoloAzimuthalY(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return (sin($longitude) * sin(0.446 * (3.14159265359 / 2 - $latitude)));
}

/*
 * BUGAYEVSKIY, L. M.; SNYDER, J. P. *Map Projections A Reference Manual. p 176
 * JENNY, B. Adaptive Composite Map Projections. p 3
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 232
 */
function calcularHammerX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $eta = sqrt(1 + cos($latitude) * cos($longitude / 2));
    return ((2 * sqrt(2) * cos($latitude) * sin($longitude / 2)) / $eta);
}

/*
 * BUGAYEVSKIY, L. M.; SNYDER, J. P. *Map Projections A Reference Manual. p 176
 * JENNY, B. Adaptive Composite Map Projections. p 3
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 232
 */
function calcularHammerY(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $eta = sqrt(1 + cos($latitude) * cos($longitude / 2));
    return ((sqrt(2) * sin($latitude)) / $eta);
}

/*
 * SNYDER, J. P. Flattening the Earth: Two thousand years of map projections. p 202
 */
function calcularKavrayskiyVIIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return (3 * $longitude / 2) * sqrt(1 / 3 - pow($latitude / 3.14159265359, 2));
}

/*
 * SNYDER, J. P. Flattening the Earth: Two thousand years of map projections. p 202
 */
function calcularKavrayskiyVIIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return $latitude;
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 185
 */
function calcularLambertAzimuthalEqualAreaX(float $latitude, float $longitude): float
{
    if ($latitude == 0 and ($longitude == 180 or $longitude == -180)) { // estes dois pontos retornam NaN
        $latitude = 0.001;
    }
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $coslat0 = 1;
    $sinlat0 = 0;
    $sinlat = sin($latitude);
    $coslat = cos($latitude);
    $sinlon = sin($longitude);
    $coslon = cos($longitude);
    $k = sqrt(2 / (1 + $sinlat0 * $sinlat + $coslat0 * $coslat * $coslon));
    return ($k * $coslat * $sinlon);
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 185
 */
function calcularLambertAzimuthalEqualAreaY(float $latitude, float $longitude): float
{
    if ($latitude == 0 and ($longitude == 180 or $longitude == -180)) { // estes dois pontos retornam NaN
        $latitude = 0.001;
    }
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $coslat0 = 1;
    $sinlat0 = 0;
    $sinlat = sin($latitude);
    $coslat = cos($latitude);
    $coslon = cos($longitude);
    $k = sqrt(2 / (1 + $sinlat0 * $sinlat + $coslat0 * $coslat * $coslon));
    return ($k * ($coslat0 * $sinlat - $sinlat0 * $coslat * $coslon));
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 41
 * PEARSON, F. Map Projections: Theory and Applications. pp 190-191
 * GOLDBERG, D. M.; GOTT, J. R. Flexion and Skewness in Map Projections of the Earth. p 8
 * IOGP Coordinate Conversions and Transformations including Formulas. p 45
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 218
 */
function calcularMercatorX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 41
 * PEARSON, F. Map Projections: Theory and Applications. pp 190-191
 * GOLDBERG, D. M.; GOTT, J. R. Flexion and Skewness in Map Projections of the Earth. p 8
 * IOGP Coordinate Conversions and Transformations including Formulas. p 45
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 218
 */
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

/*
 * WEISSTEIN, E. W. Miller Cylindrical Projection
 */
function calcularMillerX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

/*
 * WEISSTEIN, E. W. Miller Cylindrical Projection
 */
function calcularMillerY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return ((5/4) * log(tan(3.14159265359/4 + ((2 * $latitude)/5))));
}

/*
 * WEISSTEIN, E. W. Mollweide Projection.
 * SNYDER, J. P. Map Projections - A Working Manual. p 251
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularMollweideTheta(float $latitude): float
{
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
        $theta = ($theta < 0) ? (-3.14159265359 / 2) : (3.14159265359 / 2);
    } else {
        $theta *= 0.5;
    }
    return $theta;
}

/*
 * WEISSTEIN, E. W. Mollweide Projection.
 * SNYDER, J. P. Map Projections - A Working Manual. p 251
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularMollweideX(float $theta, float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    $cx = 2 * sqrt(2) / 3.14159265359;
    return ($cx * $longitude * cos($theta));
}

/*
 * WEISSTEIN, E. W. Mollweide Projection.
 * SNYDER, J. P. Map Projections - A Working Manual. p 251
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularMollweideY(float $theta): float
{
    $cy = sqrt(2);
    return ($cy * sin($theta));
}

/*
 * ŠAVRIČ, B.; JENNY, B.; PATTERSON, T.; PETROVIČ, D.; HURNI, L. A Polynomial Equation for the Natural Earth Projection. p 366
 */
function calcularNaturalEarthX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ($longitude * (0.870700 - 0.131979 * pow($latitude, 2) - 0.013791 * pow($latitude, 4) + 0.003971 * pow($latitude, 10) - 0.001529 * pow($latitude, 12)));
}

/*
 * ŠAVRIČ, B.; JENNY, B.; PATTERSON, T.; PETROVIČ, D.; HURNI, L. A Polynomial Equation for the Natural Earth Projection. p 366
 */
function calcularNaturalEarthY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return (1.007226 * $latitude + 0.015085 * pow($latitude, 3) - 0.044475 * pow($latitude, 7) + 0.028874 * pow($latitude, 9) - 0.005916 * pow($latitude, 11));
}

/*
 * ŠAVRIČ, B.; PATTERSON, T.; JENNY, B. The Natural Earth II world map projection. p 125
 */
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

/*
 * ŠAVRIČ, B.; PATTERSON, T.; JENNY, B. The Natural Earth II world map projection. p 125
 */
function calcularNaturalEarthIIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    //return (1.01183 * $latitude - 0.02625 * pow($latitude, 9) + 0.01926 * pow($latitude, 11) - 0.00396 * pow($latitude, 13));
    $latitude2 = $latitude * $latitude;
    $latitude4 = $latitude2 * $latitude2;
    return ($latitude * (1.01183 + $latitude4 * $latitude4 * (0.01926 * $latitude2 - 0.00396 * $latitude4 - 0.02625)));
}

/*
 *  PATTERSON, T.; ŠAVRIČ, B.; JENNY, B. Introducing the Patterson Cylindrical Projection. p 80
 */
function calcularPattersonX(float $longitude): float
{
    $longitude = $longitude * (3.14159265359 / 180);
    return $longitude;
}

/*
 *  PATTERSON, T.; ŠAVRIČ, B.; JENNY, B. Introducing the Patterson Cylindrical Projection. p 80
 */
function calcularPattersonY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $latitude2 = $latitude * $latitude;
    //$y = (1.0148 * $latitude + 0.23185 * pow($latitude, 5) - 0.14499 * pow($latitude, 7) + 0.02406 * pow($latitude, 9));
    return ($latitude * (1.0148 + $latitude2 * $latitude2 * (0.23185 + $latitude2 * (-0.14499 + $latitude2 * 0.02406))));
}

/*
 *  IPBUKER, C. A computational approach to the Robinson projection. p 207
 */
function calcularRobinsonX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ((2.6666 - 0.367 * pow($latitude, 2) - 0.150 * pow($latitude, 4) + 0.0379 * pow($latitude, 6)) * $longitude / 3.14159265359);
}

/*
 *  IPBUKER, C. A computational approach to the Robinson projection. p 207
 */
function calcularRobinsonY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    if ($latitude >= 0) { $s = 1; } else { $s = -1; }
    return (0.96047 * $latitude - 0.00857 * $s * pow(abs($latitude), 6.41));
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 247
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularSinusoidalX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return $longitude * cos($latitude);
}

/*
 * SNYDER, J. P. Map Projections - A Working Manual. p 247
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 220
 */
function calcularSinusoidalY(float $latitude): float
{
    return $latitude;
}

/*
 * SNYDER, J. P. Flattening the Earth: Two thousand years of map projections. p 205
 */
function calcularWagnerVIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    return ($longitude * sqrt(1 - 3 * pow($latitude / 3.14159265359, 2)));
}

/*
 * SNYDER, J. P. Flattening the Earth: Two thousand years of map projections. p 205
 */
function calcularWagnerVIY(float $latitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    return $latitude;
}

/*
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 233
 */
function calcularWinkelIIIX(float $latitude, float $longitude): float
{
    $latitude = $latitude * (3.14159265359 / 180);
    $longitude = $longitude * (3.14159265359 / 180);
    $alpha = acos(cos($latitude) * cos($longitude / 2));
    $sinc = sin($alpha) / $alpha;
    return ((1 / 2) * ($longitude * cos(acos(2 / 3.14159265359)) + ((2 * cos($latitude) * sin($longitude / 2)) / ($sinc))));
}

/*
 * SNYDER, J. P.; VOXLAND, P. M. An Album of Map Projections. p 233
 */
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
    $svg = '<g fill="rgb(200,200,200)" fill-rule="nonzero" stroke="rgb(152,152,152)" stroke-width="1">' . PHP_EOL;
    
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
    
    if ($projecao == 'l') {
        $centro = coordenarCentro($largura, $altura);
        if ($largura / $altura < 1) {
            $raio = $largura / calcularLambertAzimuthalEqualAreaX(0, 180) / 3.14159265359 * 2;
        } else {
            $raio = $altura / calcularLambertAzimuthalEqualAreaY(90, 0) / 3.14159265359 * 2;
        }
        $svg .= '<circle cx="' . $centro['x'] . '" cy="' . $centro['y'] . '" r="' . $raio . '" fill="rgb(174,214,241)" />';
    } else {
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
    }
    return $svg;
}

function exibirGrade(int $largura, int $altura, string $projecao): string
{
    $svg = '<g fill="none" stroke="rgb(240,240,240)" stroke-width="1">' . PHP_EOL;
    
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
        if ($projecao == 'W' or $projecao == 'h' or $projecao == 'g' or $projecao == 'l') {
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
    
    if ($projecao == 'W' or $projecao == 'h' or $projecao == 'g' or $projecao == 'l') {
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
    $w = -180;
    $e = 180;
    if ($projecao == 'l') {
        $w++;
        $e--;
    }
    $ocidente = converterGeoPixel(0, $w, $largura, $altura, $projecao);
    $oriente = converterGeoPixel(0, $e, $largura, $altura, $projecao);
    return '<line x1="' . $ocidente['x'] . '" y1="' . $ocidente['y'] . '" x2="' . $oriente['x'] . '" y2="' . $oriente['y'] . '" />' . PHP_EOL;
}

function exibirGreenwich(int $largura, int $altura, string $projecao): string
{
    $n = converterGeoPixel(90, 0, $largura, $altura, $projecao);
    $s = converterGeoPixel(-90, 0, $largura, $altura, $projecao);
    return '<line x1="' . $n['x'] . '" y1="' . $n['y'] . '" x2="' . $s['x'] . '" y2="' . $s['y'] . '" />' . PHP_EOL;
}

