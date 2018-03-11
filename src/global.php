<?php

function connection($db = 'test', $host = '127.0.0.1', $username = 'root', $password = '')
{
    return new PDO("mysql:dbname=$db;host=$host", $username, $password);
}

function query(PDO $conn, $query, $fetchStyle = PDO::FETCH_ASSOC)
{
    return $conn->query($query)->fetchAll($fetchStyle);
}

function array2vector($array, $col = 0)
{
    $vector = [];
    foreach ($array as $row) {
        $vector[] = $row[$col];
    }
    return $vector;
}

function printVector($vector, $glue = "\n")
{
    print(implode($glue, $vector));
}

function filterLines($filter, $vector)
{
    $res = [];
    foreach ($vector as $row) {
        if (strpos($row, $filter) !== false) $res[] = $row;
    }
    return $res;
}

function readTCV($filename)
{
    $lines = explode("\n", file_get_contents($filename));
    array_walk($lines,
               function (&$line) {
        $columns = explode("\t", $line);
        //var_dump($columns);die;
        array_walk($columns,
                   function (&$column) {
            $column = substr($column, 0, 1) === '"' && substr($column, -1, 1) === '"' ? str_replace('""', '"',
                                                                                                    substr($column, 1,
                                                                                                           -1)) : $column;
        });
        $line = $columns;
    });
    return $lines;
}

function array2assoc($array)
{
    $res = [];
    foreach ($array as $cols) {
        $res[$cols[0]] = $cols[1];
    }
    return $res;
}

function saveJson($path, $json)
{
    file_put_contents($path, json_encode($json));
}

function loadJson($path)
{
    if (!file_exists($path)) {
        return false;
    }
    return json_decode(file_get_contents($path), true);
}

function decodeDrawIO($base64Deflated)
{
    return urldecode(gzinflate(base64_decode($base64Deflated)));
}

function eachDrawIOHtmlDiagrams($html, callable $callback)
{
    $dom = new DOMDocument;
    $dom->loadHTML($html);
    $code = json_decode($dom->getElementsByTagName('div')->item(0)->getAttribute('data-mxgraph'));
    $design = new DOMDocument;
    $design->loadXML($code->xml);
    foreach ($design->getElementsByTagName('diagram') as $diagramTag) {
        $diagramEncoded = $diagramTag->nodeValue;
        $diagram = new DOMDocument;
        $diagram->loadXML(decodeDrawIO($diagramEncoded));
        $callback($diagram, $diagramTag->getAttribute('name'), $diagramTag->getAttribute('id'));
    }
}

function findDrawIOChain($shapes, $shape, callable $condition, &$res = [], $firstShape = null)
{
    $firstShape = isset($firstShape) ?: $shape;
    if ($firstShape === $shape || $condition($shape, $firstShape)) {
        $res[] = $shape;
        //forward
        foreach ($shapes as $flow) {
            if (in_array($flow, $res)) {
                continue;
            }
            if ($flow['source'] === $shape['id'] && $flow['target']) {
                $next = $shapes[$flow['target']];
                $res[] = $flow;
                findDrawIOChain($shapes, $next, $condition, $res, $firstShape);
            }
            if ($flow['target'] === $shape['id'] && $flow['source']) {
                $prev = $shapes[$flow['source']];
                $res[] = $flow;
                findDrawIOChain($shapes, $prev, $condition, $res, $firstShape);
            }
        }
    }
    return $res;
}

function findDrawIOChildren($shapes, $shape, callable $condition, &$res = [], $firstShape = null)
{
    $firstShape = isset($firstShape) ?: $shape;
    foreach ($shapes as $child) {
        if (in_array($child, $res)) {
            continue;
        }
        if ($child['parent'] === $shape['id'] && $condition($child, $firstShape)) {
            $res[] = $child;
            //findDrawIOChildren($shapes, $child, $condition, $res, $firstShape);
        }
    }
    return $res;
}

function nano2($file, $target)
{
    $file = realpath($file);
    $target = realpath($target);
    $path = getcwd();
    chdir('/Users/davidcallizaya/NetBeansProjects/nano2.1');
    echo "php artisan build $file $target", "\n";
    $exitCode = 0;
    passthru("/usr/local/bin/php artisan build $file $target 2>1", $exitCode);
    chdir($path);
    return $exitCode;
}

function nano2component($type, $data, $target)
{
    $filename = uniqid('output/') . '.xml';
    file_put_contents($filename,
                      '<?xml version="1.0" encoding="UTF-8"?>
    <root xmlns:v-bind="http://nano.com/vue">
    <script type="' . $type . '">
    ' . json_encode($data) . '
    </script>
    </root>');
    $exitCode = nano2($filename, $target);
    if ($exitCode == 0) {
        //exit without errors
        unlink($filename);
    }
}

function addComposerLoader($path)
{
    return require $path;
}

/**
 * Busca todas las clases en PSR4 una carpeta concreta y namespace especifico
 * Require que este se haya cargado con composer.
 *
 * @param type $path
 * @param type $namespace
 * @param ReflectionClass $res
 * @return \ReflectionClass
 */
function findClasses($path, $namespace, &$res = [])
{
    foreach (glob("$path/*.php") as $filename) {
        $name = basename($filename, '.php');
        $className = "$namespace\\$name";
        $res[] = new ReflectionClass($className);
    }
    foreach (glob("$path/*", GLOB_ONLYDIR) as $dir) {
        $name = basename($dir);
        findClasses("$path/$name", "$namespace\\$name", $res);
    }
    return $res;
}

function drawio_Interface(DOMDocument $dom, $name, $stereotype = 'interface', $x = null, $y = null)
{
    if (!isset($x)) $x = random_int(0, 400);
    if (!isset($y)) $y = random_int(0, 400);
    $xml = '<mxCell id="' . uniqid() . '" value="«' . $stereotype . '»&lt;br style=&quot;font-size: 12px;&quot;&gt;&lt;b style=&quot;font-size: 12px;&quot;&gt;' . $name . '&lt;/b&gt;" style="html=1;shadow=0;comic=0;strokeColor=#000000;strokeWidth=1;fillColor=#FFFFFF;gradientColor=none;fontSize=12;fontColor=#000000;align=center;" vertex="1" parent="1">
        <mxGeometry x="' . $x . '" y="' . $y . '" width="110" height="50" as="geometry"/>
    </mxCell>';
    $dom2 = new DOMDocument;
    $dom2->loadXML($xml);
    return $dom->importNode($dom2->firstChild, true);
}

function drawio_Relationship(DOMDocument $dom, $source, $target)
{
    $xml = '<mxCell id="' . uniqid() . '" style="edgeStyle=none;rounded=0;html=0;entryX=0.5;entryY=1;startArrow=none;startFill=0;endArrow=block;endFill=0;jettySize=auto;orthogonalLoop=1;fontSize=12;fontColor=#000000;" edge="1" parent="1" source="' . $source . '" target="' . $target . '">
            <mxGeometry relative="1" as="geometry"/>
        </mxCell>';
    $dom2 = new DOMDocument;
    $dom2->loadXML($xml);
    return $dom->importNode($dom2->firstChild, true);
}
