# GVNdatum
Normalizes dc:created dates from geheugenvannederland.nl to ISO 8601 dates.
It has been tested on 46.000 unique dates from the geheugenvannederland.nl-set. 

Some examples of the dates or dateranges that are understood are:

* "01-01-1937 t/m 31-12-1937" => start: "1937-01-01", end: "1937-12-31"
* "01-februari-1944" => start: "1944-02-01", end: ""
* "nov. (?) 1935" => start: "1935-11", end: ""
* "1916, 14 october" => start: "1916-10-14", end: ""
* "16de eeuw?" => start: "1500", end: "1599",
* "16e tot 18e eeuw" => start: "1500", end: "1799"
* "2e helft 19e eeuw" => start: "1850", end: "1899"
* "vierde kwart 17de eeuw" => start: "1675", end: "1699"
* "40er jaren" => start: "1940", end "1949"
* "60- er jaren" => start: "1960", end: "1969",
* "begin jaren '70" => start: "1970", end: "1973"
* "winter 1886-1887" => start: "1886-12-21", end: "1887-03-20"
*  "17XX" => start: "1700", end: "1799"
* "Begonnen 14 oktober 1883, voltooid 29 november 1883" => start: "1883-10-14", end: "1883-11-29" 
* "van -150000 tot -100000" => start: "-150000", end: "-100000"
* "van -27 tot 14" => start: "-27", end: "14"
* "van 98 tot 117", => start: "98", end: "117"
* "negentiende-eeuws handschrif" => start: "1800", end: "1899"
* "tweede wereldoorlog" => start: "1939-09-01", end: "1945-08-15"

GVNdatum comes with a PHPunit testset. Install using composer.
