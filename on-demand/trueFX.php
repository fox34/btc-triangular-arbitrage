<?php

header("Content-Type: text/html; charset=UTF-8");
error_reporting(E_ALL);

?><!doctype html>
<html lang="de" style="font-size:14px">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" 
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" 
          integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" 
          crossorigin="anonymous">
    <title>TrueFX Crawler</title>
  </head>
  <body>
    <div class="container pt-1 pb-5">
    <h1>TrueFX Crawler</h1>
<?php

// !! Folgende Wechselkurse fehlen:
// EURAUD
// GBPAUD

// 2010/01
// 2010/02
// alles ab 2019/08 (einschließlich)

$yearFrom = 2019;
$yearTo = 2019;
$monthsFrom = 8;
$monthsTo = 12;

// Macbook
$outPath = 'TrueFX/';

// Googledrive Backup / drive.noecho.de
//$outPath = '/mnt/union/Backup/Dissertation/TrueFX/';


$forexFilter = 'AUDJPY|AUDUSD|EURGBP|EURJPY|EURUSD|GBPJPY|GBPUSD|USDJPY';
//$forexFilter = 'EURUSD';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "DNT: 1\r\n" . 
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*" . "/" . "*;q=0.8\r\n" . 
                    "Cache-Content: max-age=0\r\n" . 
                    "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_3) AppleWebKit/602.4.8 (KHTML, like Gecko) Version/10.0.3 Safari/602.4.8\r\n" . 
                    "Cookie: uid=fa6ce6732784712415b655f1e3ee4a900b0e0adf\r\n"
    ]
]);

$cmdOut = '';

echo '<h2>Einstellungen</h2>';
echo '<p>Zeitraum: ' . $yearFrom . '-' . $yearTo . ', Monate ' . $monthsFrom . '-' . $monthsTo . '</p>';
echo '<p>Ziel: <code>' . htmlspecialchars($outPath) . '</code></p>';
echo '<p>Filter: <code>' . htmlspecialchars($forexFilter) . '</code></p>';

for ($year = $yearFrom; $year <= $yearTo; $year++) {
    
    for ($month = $monthsFrom; $month <= $monthsTo; $month++) {
        
        echo "<h2>Verarbeite $year / $month ...</h2>";
        
        // get link list
        $url = 
            'http://www.truefx.com/?page=download&description=' . 
            strtolower(date('F', mktime(0, 0, 0, $month, 1, $year))) . $year .
            '&dir=' . $year . '/' . $year . '-' . sprintf('%02d', $month);
            
        echo "<p>Lade <code>$url</code> ...</p>";
        
        $downloadList = file_get_contents($url, false, $context);
        if ($downloadList === false) {
            echo "<p class='alert alert-danger'><b>Konnte $year / $month nicht laden!</b></p>";
            continue;
        }
        
        // find links
        if (preg_match_all('~<a href=([^\s]*(?:' . $forexFilter . ')[^\s]+)"?\s+class.+download=~', $downloadList, $links) === false) {
            echo "<p class='alert alert-danger'><b>Konnte $year / $month nicht verarbeiten!</b></p>";
            continue;
        }
        
        $links = $links[1];
        
        // find only relevant links
        if (empty($links)) {
            echo "<p class='alert alert-danger'>Keine Links für $year / $month gefunden!</p>";
            continue;
        }
        
        $thisURL = str_replace('http://www.truefx.com', '', $url);
        echo "<ul>";
        foreach($links as $link) {
            echo "<li>Verarbeite $link ...</li>";
            
            $cmdOut .=
                '[ ! -f ' . escapeshellarg($outPath . basename($link)) . ' ] && ' . 
                'curl ' . implode(' ', [
                    '-v',
                    '-o', escapeshellarg($outPath . basename($link)),
                    escapeshellarg('https://www.truefx.com' . $link)
            ]);
            
            $cmdOut .= " && sleep 2" . PHP_EOL;
        }
        echo "</ul>";
        
    }
    
}

echo '<h2>Shell-Skript:</h2>';
echo '<p class="mb-2"><button id="downloadShellScript" class="btn btn-primary">Download</button></p>';
echo '<textarea class="form-control" readonly rows="20" id="shellScript">#/bin/sh' . PHP_EOL;
echo 'mkdir -p ' . htmlspecialchars(escapeshellarg($outPath)) . PHP_EOL;
echo htmlspecialchars($cmdOut);
echo 'rm -i $0' . PHP_EOL;
echo "</textarea>";

?>
    </div>
    <script>
    document.getElementById('downloadShellScript').addEventListener('click', e => {
        e.preventDefault();
        
    	var textFileAsBlob = new Blob([document.getElementById('shellScript').value], {type:'text/plain'}); 
    	var downloadLink = document.createElement("a");
    	downloadLink.download = 'truefx.sh';
    	downloadLink.innerHTML = "Download File";
    	if (window.webkitURL != null) {
    		// Chrome allows the link to be clicked
    		// without actually adding it to the DOM.
    		downloadLink.href = window.webkitURL.createObjectURL(textFileAsBlob);
    	} else {
    		// Firefox requires the link to be added to the DOM
    		// before it can be clicked.
    		downloadLink.href = window.URL.createObjectURL(textFileAsBlob);
    		downloadLink.onclick = destroyClickedElement;
    		downloadLink.style.display = "none";
    		document.body.appendChild(downloadLink);
    	}
    
    	downloadLink.click();
    });
    </script>
</body>
</html>