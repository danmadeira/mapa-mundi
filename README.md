## Mapa-múndi com cidades capitais

Um mapa da superfície total da Terra, construído por um *script* em PHP, projetado a partir dos dados fornecidos por [Natural Earth](https://www.naturalearthdata.com/) e da base de dados, com 26.569 cidades do mundo, fornecida gratuitamente por [SimpleMaps.com](https://simplemaps.com/data/world-cities).

O Natural Earth fornece arquivos, no formato GeoJSON, com as coordenadas geográficas de todo o perímetro de cada país. Este *script* em PHP, converte estas coordenadas geográficas em pontos para a construção dos polígonos em SVG. A base do SimpleMaps foi importada para o banco de dados MySQL, para consulta pelo *script*, e assim, pontuar as cidades capitais.

Este *script* em PHP foi desenvolvido de uma forma simples para facilitar o entendimento dos algoritmos e aceita variáveis externas, pelo método GET, para definir a largura e a altura do mapa, como também o algoritmo de projeção.

Ex.: mapamundi.php?largura=1000&altura=500&projecao=N

### Sistemas de projeções

- Eckert IV;
- Eckert VI;
- Equidistante;
- Gott equal-area elliptical;
- Hammer;
- Kavrayskiy VII;
- Mercator;
- Miller cylindrical;
- Mollweide;
- Natural Earth;
- Natural Earth II;
- Patterson;
- Robinson;
- Sinusoidal;
- Wagner VI;
- Winkel Tripel.

### Cálculo da conversão

Para elaborar um mapa é necessário determinar um método no qual cada ponto na superfície terrestre corresponda a um ponto no plano e vice-versa. Existem diversos métodos e estes métodos são chamados sistemas de projeções. Um sistema de projeção adota um modelo matemático do planeta e relaciona os pontos estabelecendo uma escala e um sistema de coordenadas.

O sistema de coordenadas geográficas expressa a posição de um ponto na superfície esférica do globo terrestre. O globo é dividido em latitudes que vão de 0 a 90 graus, a partir da linha do Equador, separando os hemisférios Norte e Sul, e longitudes que vão de 0 a 180 graus, a partir do meridiano de Greenwich, separando os hemisférios Leste e Oeste. A latitude e a longitude, na notação decimal, é indicada com valor negativo quando é uma latitude Sul ou uma longitude Oeste.

O sistema de coordenadas cartesiano serve para especificar pontos num determinado espaço plano. Tem uma semelhança com o sistema de coordenadas geográficas pois a origem, a coordenada (0,0), está no centro. No entanto, em computação gráfica, as imagens possuem um sistema de coordenadas de pixel, onde a origem está no canto superior esquerdo. Em uma imagem, as coordenadas sempre possuem valores positivos, que totalizam na largura e na altura da imagem.

Para adaptar um sistema de coordenadas, onde é possível valores negativos, em um sistema que só adote valores positivos, usa-se um falso leste e um falso norte. São valores lineares que são acrescidos na equação para que, respectivamente, as coordenadas (x,y) não fiquem com os valores negativos. Trata-se de um deslocamento do ponto para compensar a diferença na coordenada. Neste *script*, o falso leste e o falso norte estão implementados pelas coordenadas do centro da imagem.

E ainda, para regular as proporções entre os sistemas de coordenadas, este *script* adota uma unidade de medida, na forma de um módulo, que é calculado dependendo da razão entre a largura e a altura da imagem. Esta razão da imagem é comparada com a razão nativa do respectivo sistema de projeção, razão dada entre a linha do Equador e o meridiano principal, para escolher se o módulo será baseado na largura/paralelo ou na altura/meridiano.

Tudo na imagem é construído de acordo com a projeção escolhida e proporcionalmente à dimensão da imagem. Este *script* constrói os polígonos dos países, que juntos formam os continentes, as linhas imaginárias dos paralelos e meridianos, o fundo azul dentro dos limites da projeção etc.

### O mapa

![Mapa-múndi](img/mapamundi.svg?raw=true)

<p align="center">projeção Natural Earth II</p>

### Referências

- BUTLER, H. et al. *The GeoJSON Format*. IETF RFC 7946. August 2016. Disponível em: <https://tools.ietf.org/html/rfc7946>

- CHANG, K. *Introduction to Geographic Information Systems, Ninth Edition*. McGraw-Hill Education, 2018.

- COWBURN, P. e col. *Manual do PHP*. PHP Documentation Group. 25 de Dezembro de 2020. Disponível em: <https://www.php.net/manual/pt_BR/index.php>

- DEAKIN, R. E. *A Guide to the Mathematics of Map Projections*. Victorian Tasmanian Survey Conference: Across the Strait, Launceston Tasmania. April 15-17, 2004.

- DMA WGS 84 Development Committee *Supplement to Department of Defense World Geodetic System 1984 Technical Report: Part II - Parameters, Formulas, and Graphics for the Practical Application of WGS 84*. Technical Report 8350.2-B, Second Printing. Defense Mapping Agency, December 1st, 1987. Disponível em: <https://earth-info.nga.mil/GandG/publications/tr8350.2/TR8350.2-b/DMA%20TR8350.pdf>

- ESRI *Understanding Map Projections*. GIS by ESRI. Environmental Systems Research Institute, 2000. Disponível em: <http://downloads2.esri.com/support/documentation/ao_/710Understanding_Map_Projections.pdf>

- FENNA, D. *Cartographic Science: A Compendium of Map Projections, with Derivations*. CRC Press, Taylor & Francis Group, 2007.

- GOLDBERG, D. M.; GOTT, J. R. *Flexion and Skewness in Map Projections of the Earth*. Cartographica: The International Journal for Geographic Information and Geovisualization. Volume 42 Issue 4, pp. 297-318, December 18, 2007. Disponível em: <https://www.physics.drexel.edu/~goldberg/projections/goldberg_gott.pdf>

- GOTT, J. R.; MUGNOLO, C.; COLLEY, W. N. *Map Projections Minimizing Distance Errors*. Cartographica: The International Journal for Geographic Information and Geovisualization, Volume 42 Issue 3, pp. 219-234, Fall 2007. Disponível em: <https://arxiv.org/pdf/astro-ph/0608500.pdf>

- HOOIJBERG, M. *Practical Geodesy: Using Computers*. Springer-Verlag, Berlin, Heidelberg, 1997.

- IOGP *Coordinate Conversions and Transformations including Formulas*. IOGP Publication 373-7-2, Geomatics Guidance Note number 7, part 2, October 2020. Disponível em: <https://epsg.org/guidance-notes.html>

- IPBUKER, C. *A computational approach to the Robinson projection*. Survey Review. Volume 38, Issue 297, pp. 204-217, July 2005.

- JENNY, B. *Java Map Projection Library*. Cartography and Geovisualization Group, Oregon State University. June 12, 2019. Disponível em: <https://github.com/OSUCartography/JMapProjLib>

- KELSO, N. V. *Natural Earth vector*. Disponível em: <https://github.com/nvkelso/natural-earth-vector>

- KELSO, N. V.; PATTERSON, T. *Natural Earth*. Disponível em: <https://www.naturalearthdata.com/>

- KENNEDY, M.; KOPP, S. *Understanding Map Projections*. GIS by ESRI. Esri Press, 2000. Disponível em: <https://giscourses.cfans.umn.edu/sites/giscourses.cfans.umn.edu/files/understanding_map_projections.pdf>

- MOON, P.; SPENCER, D. E. *Field Theory Handbook: Including Coordinate Systems, Differential Equations and Their Solutions*. Corrected 3rd Printing, Springer-Verlag, 1988.

- NGA *Map Projections for GEOINT Content, Products, and Applications*. Standardization Implementation Guidance 28, Version 1.0. National Geospatial-Intelligence Agency Standardization Document. U.S.A., December 13, 2017. Disponível em: <ftp://ftp.nga.mil/pub2/gandg/website/coordsys/resources/NGA.SIG.0028_1.0_MAPPROJ.PDF>

- NIMA *Technical Report 8350.2, Department of Defense World Geodetic System 1984: Its Definition and Relationships with Local Geodetic Systems, Third Edition, Amendment 1*. Geodesy and Geophysics Department, National Imagery and Mapping Agency. January 3, 2000. Disponível em: <https://earth-info.nga.mil/GandG/publications/tr8350.2/wgs84fin.pdf>

- OSGeo Project. *GeoTools*, Release 24.2. Open Source Geospatial Foundation, January 20, 2021. Disponível em: <https://geotools.org/>

- PATTERSON, T.; ŠAVRIČ, B.; JENNY, B. *Introducing the Patterson Cylindrical Projection*. Cartographic Perspectives, Number 78, pp. 77-81, December 2014. Disponível em: <https://cartographicperspectives.org/index.php/journal/article/view/cp78-patterson-et-al/1361>

- PEARSON, F. *Map Projections: Theory and Applications*. CRC Press, Taylor & Francis Group, 1990.

- PROJ contributors *PROJ coordinate transformation software library*, Release 7.2.1. Open Source Geospatial Foundation, January 1st 2021. Disponível em: <https://proj.org/>

- ŠAVRIČ, B.; JENNY, B.; PATTERSON, T.; PETROVIČ, D.; HURNI, L. *A Polynomial Equation for the Natural Earth Projection*. Cartography and Geographic Information Science, Vol. 38, No. 4, pp. 363-372, 2011.

- ŠAVRIČ, B.; PATTERSON, T.; JENNY, B. *The Natural Earth II world map projection*. International Journal of Cartography, 
Volume 1, Issue 2, pp. 123–133, 2015.

- SimpleMaps.com *How to import a CSV file into MySQL*. Tutorials and Articles. Disponível em: <https://simplemaps.com/resources/import-csv-mysql>

- SimpleMaps.com *World Cities Database*. SimpleMaps Worldcities Basic v1.73. Disponível em: <https://simplemaps.com/data/world-cities>

- SNYDER, J. P.; VOXLAND, P. M. *An Album of Map Projections*, 2nd Printing. U.S. Geological Survey Professional Paper 1453, United States Government Printing Office, Washington, 1989. Disponível em: <https://pubs.usgs.gov/pp/1453/report.pdf>

- SNYDER, J. P. *Flattening the Earth: Two thousand years of map projections*. University of Chicago Press, Chicago, 1993.

- SNYDER, J. P. *Map Projections - A Working Manual*, U.S. Geological Survey Professional Paper 1395, Supersedes USGS Bulletin 1532, United States Government Printing Office, Washington, 1987. Disponível em: <https://pubs.usgs.gov/pp/1395/report.pdf>

- W3C *Scalable Vector Graphics (SVG) 1.1 (Second Edition)*, W3C Recommendation 16 August 2011. Disponível em: <https://www.w3.org/TR/SVG11/>

- WEISSTEIN, E. W. *Miller Cylindrical Projection*. MathWorld: A Wolfram Web Resource. Disponível em: <https://mathworld.wolfram.com/MillerCylindricalProjection.html>

- WEISSTEIN, E. W. *Mollweide Projection*. MathWorld: A Wolfram Web Resource. Disponível em: <https://mathworld.wolfram.com/MollweideProjection.html>
