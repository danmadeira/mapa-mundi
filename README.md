## Mapa-múndi com cidades capitais

Um mapa da superfície total da Terra, projetado a partir dos dados fornecidos por [Natural Earth](https://www.naturalearthdata.com/) e da base de dados, com 26.569 cidades do mundo, fornecida gratuitamente por [SimpleMaps.com](https://simplemaps.com/data/world-cities).

O Natural Earth fornece arquivos, no formato GeoJSON, com as coordenadas geográficas de todo o perímetro de cada país. Este script em PHP, converte estas coordenadas geográficas em pontos para a construção dos polígonos em SVG. A base do SimpleMaps foi importada para o banco de dados MySQL, para consulta pelo script, e assim, pontuar as cidades capitais.

Este script em PHP foi desenvolvido de uma forma simples para facilitar o entendimento dos algoritmos e aceita variáveis externas, pelo método GET, para definir a largura e a altura do mapa. Ex.: mapamundi.php?largura=1000&altura=500

### O mapa

![Mapa-múndi](img/mapamundi.svg?raw=true)

### Referências

- BUTLER, H. et al. *The GeoJSON Format*. IETF RFC 7946. August 2016. Disponível em: <https://tools.ietf.org/html/rfc7946>

- CHANG, K. *Introduction to Geographic Information Systems, Ninth Edition*. McGraw-Hill Education, 2018.

- COWBURN, P. e col. *Manual do PHP*. PHP Documentation Group. 25 de Dezembro de 2020. Disponível em: <https://www.php.net/manual/pt_BR/index.php>

- KELSO, N. V. *Natural Earth vector*. Disponível em: <https://github.com/nvkelso/natural-earth-vector>

- KELSO, N. V.; PATTERSON, T. *Natural Earth*. Disponível em: <https://www.naturalearthdata.com/>

- MOON, P.; SPENCER, D. E. *Field Theory Handbook: Including Coordinate Systems, Differential Equations and Their Solutions*. Corrected 3rd Printing, Springer-Verlag, 1988.

- NIMA *Technical Report 8350.2, Department of Defense World Geodetic System 1984: Its Definition and Relationships with Local Geodetic Systems, Third Edition, Amendment 1*. Geodesy and Geophysics Department, National Imagery and Mapping Agency. January 3, 2000.

- SimpleMaps.com *How to import a CSV file into MySQL*. Tutorials and Articles. Disponível em: <https://simplemaps.com/resources/import-csv-mysql>

- SimpleMaps.com *World Cities Database*. SimpleMaps Worldcities Basic v1.73. Disponível em: <https://simplemaps.com/data/world-cities>

- SNYDER, J. P. *Map Projections A Working Manual*, U.S. Geological Survey Professional Paper 1395, Supersedes USGS Bulletin 1532, United States Government Printing Office, Washington, 1987.

- W3C *Scalable Vector Graphics (SVG) 1.1 (Second Edition)*, W3C Recommendation 16 August 2011. Disponível em: <https://www.w3.org/TR/SVG11/>
